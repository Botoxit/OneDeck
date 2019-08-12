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

    public function __construct()
    {
        $this->conn = DataBase::getConnection();
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

    public static function getTableName(): string
    {
        return Table::$DBTable_name;
    }

    /**
     * @param $id
     * @return bool
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
            return true;
        } else return false;
    }

    /**
     * @param int $fromTableId
     * @param int $count
     * @return false|mysqli_result|string
     */
    public function readPaging(int $fromTableId, int $count)
    {
        $query = "SELECT * FROM " . Table::$DBTable_name . " LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $fromTableId, $count);

        if ($stmt->execute())
            return $stmt->get_result();
        else return $stmt->error;
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

        // prepare query statement
        $result = $this->conn->query($query);

        // execute query
        if ($result)
            return $result;
        else return $this->conn->error;
    }

    /**
     * Create a new table, all table variables need to be filed.
     * @return int => success = id | name already exist = 0 | other error = -1
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
        return -1;
    }

    public function update()
    {
        $query = "UPDATE " . Table::$DBTable_name . " SET players_limit = ? , host = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iii', $this->players_limit, $this->host, $this->id);

        if ($stmt->execute())
            return true;
        return false;
    }

    /**
     * @return bool
     */
    public function deleteTable()
    {
        $query = "DELETE FROM " . Table::$DBTable_name . " WHERE id = $this->id";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute())
            return true;
        else return false;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getGame(): string
    {
        return $this->game;
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
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }
}