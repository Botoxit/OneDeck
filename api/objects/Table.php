<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 18:20
 */

class Table
{
    // database connection and table name
    private static $DBTable_name = "tables_list";
    private $conn;
    // object properties
    private $id;
    private $name;
    private $password;
    private $game;
    private $players_limit;
    private $rules;
    private $host;

    /**
     * Table constructor.
     */
    public function __construct()
    {
        $this->conn = DataBase::getConnection();
    }

    /**
     * Get database table for Table list
     * @return string
     */
    public static function getTableName(): string
    {
        return Table::$DBTable_name;
    }

    /**
     * Table parameter setter
     * @param string $newName
     * @param string $newPassword
     * @param string $newGame
     * @param int $newPlayerLimit
     * @param array $newRules
     * @param int $newHost
     */
    public function setter(string $newName, string $newPassword, string $newGame, int $newPlayerLimit, array $newRules, int $newHost)
    {
        $this->name = $newName;
        $this->password = $newPassword;
        $this->game = $newGame;
        $this->players_limit = $newPlayerLimit;
        $this->rules = $newRules;
        $this->host = $newHost;
    }

    /**
     * @param $id
     * @throws GameException
     */
    public function readOne($id)
    {
        // select all query
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $row = $stmt->fetch();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->game = $row['game'];
            $this->players_limit = $row['players_limit'];
            $this->rules = json_decode($row['rules']);
        } else throw new GameException("Unable to read table with id: $id, $stmt->errno: $stmt->error", 15);
    }

    /**
     * @param int $fromTableId
     * @param int $count
     * @return false|mysqli_result
     * @throws GameException
     */
    public function readPaging(int $fromTableId, int $count)
    {
        $query = "SELECT * FROM " . Table::$DBTable_name . " LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $fromTableId, $count);
        if ($stmt->execute())
            return $stmt->get_result();
        else throw new GameException("Unable to read tables page with $count entry from $fromTableId, $stmt->errno: $stmt->error", 16);
    }

    public function search($from_table_id, $count, $name, $password, $game, $players_limit, $rules)
    {
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE";
        if (!empty($name))
            $query = $query . " `name` = '" . $name . "'";
        if (!empty($password))
            $query = $query . " AND `password` = ''";
        if (!empty($game))
            $query = $query . " AND `game` = '" . $game . "'";
        if (!empty($players_limit)) {
            if (intdiv($players_limit, 10) != 0)
                $query = $query . " AND `players_limit` % 11 != 0";
            if ($players_limit % 10 != 0)
                $query = $query . " AND `players_limit` % 10 = '" . ($players_limit % 10) . "'";
        }
        if (!empty($rules)) {
            $rules = json_encode($rules);
            $query = $query . " AND `rules` = '" . $rules . "'";
        }
        $query = $query . " LIMIT $from_table_id, $count";
        $query = str_replace("WHERE AND", "WHERE", $query);
        $result = $this->conn->query($query);
        if ($result)
            return $result;
        else throw new GameException("Unable to search tables", 18);
    }


    /**
     * @return int
     * @throws GameException
     */
    public function create()
    {
        $rules = json_encode($this->rules);

        $query = "INSERT INTO " . Table::$DBTable_name . " (id, name, password, game, players_limit, rules, host) VALUES (NULL, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssisi', $this->name, $this->password, $this->game, $this->players_limit, $rules, $this->host);

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return $this->id;
        } elseif ($stmt->errno == 1062)
            return 0;
        throw new GameException("Unable to create table, $stmt->errno: $stmt->error", 11);
    }

    /**
     * @throws GameException
     */
    public function update()
    {
        $query = "UPDATE " . Table::$DBTable_name . " SET players_limit = ? , host = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iii', $this->players_limit, $this->host, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update table with id: $this->id, $stmt->errno: $stmt->error", 12);
    }

    /**
     * @throws GameException
     */
    public function deleteTable()
    {
        $query = "DELETE FROM " . Table::$DBTable_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->id);

        // execute query
        if (!$stmt->execute())
            throw new GameException("Unable to delete table with id: $this->id, $stmt->errno: $stmt->error", 13);
    }

    /**
     * @return int
     */
    public function getPlayersLimit(): int
    {
        return $this->players_limit;
    }

    /**
     * @param mixed $players_limit
     */
    public function setPlayersLimit($players_limit): void
    {
        $this->players_limit = $players_limit;
    }

    /**
     * @throws GameException
     */
    public function newHost()
    {
        $query = "SELECT * FROM " . Player::getTableName() . " WHERE id != ? and id_table = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $this->host, $this->id);

        if ($stmt->execute()) {
            $row = $stmt->fetch();
            $this->host = $row['id'];
        } else throw new GameException("Unable change host for table with id $this->id, $stmt->errno: $stmt->error", 17);
    }
}