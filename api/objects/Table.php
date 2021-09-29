<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 18:20
 */
include_once 'GameException.php';

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
     * Get in local attribute the db connection
     */
    public function __construct()
    {
        $this->conn = Database::getConnection();
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
     * @param string $newName - table name
     * @param string|null $newPassword - password
     * @param string $newGame - the game played at the table
     * @param int $newPlayerLimit - maximum limit of players
     * @param array $newRules - rules list
     * @param int $newHost - host player id
     *
     * playerLimit = <players already at the table> * 10 + <maximum limit of players>
     */
    public function setter(string $newName, $newPassword, string $newGame, int $newPlayerLimit, array $newRules, int $newHost)
    {
        $this->name = $newName;
        $this->password = $newPassword;
        $this->game = $newGame;
        if($newPlayerLimit < 10)
            $newPlayerLimit += 10;
        $this->players_limit = $newPlayerLimit;
        $this->rules = $newRules;
        $this->host = $newHost;
    }

    /**
     * @param $id - table id
     * @throws GameException
     *
     * Read from db table data
     */
    public function readOne($id)
    {
        // select all query
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 0)
                throw new GameException("Table with id $id don't exist in database.", 19);
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->game = $row['game'];
            $this->players_limit = $row['players_limit'];
            $this->rules = json_decode($row['rules']);
            $this->host = $row['host'];
        } else throw new GameException("Unable to read table with id: $id, $stmt->errno: $stmt->error", 15);
    }

    /**
     * @param $name - table name
     * @param $password - locked tables?
     * @param $game - the game played at the table
     * @param $players_limit - maximum limit of players
     * @param $rules - rules list
     * @return bool|mysqli_result - list of tables or false in case of error
     * @throws GameException
     *
     * Search tables which matches the input criteria
     */
    public function readPaging(int $fromTableId, int $count)
    {
        $query = "SELECT * FROM " . Table::$DBTable_name . " ORDER BY `created_at` DESC LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $fromTableId, $count);
        if ($stmt->execute())
            return $stmt->get_result();
        else throw new GameException("Unable to read tables page with $count entry from $fromTableId, $stmt->errno: $stmt->error", 16);
    }

    /**
     * @param $from_table_id
     * @param $count
     * @param $name
     * @param $password
     * @param $game
     * @param $players_limit
     * @param $rules
     * @return bool|mysqli_result
     * @throws GameException
     */
    public function search($from_table_id, $count, $name, $password, $game, $players_limit, $rules)
    {
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE";
        if (!empty($name))
            $query = $query . " `name` = '" . $name . "'";
        if (!empty($password)) {
            $query = $query . " AND ( `password` = '' OR `password` IS NULL )";
        }
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
        $query = $query . " ORDER BY `created_at` DESC LIMIT $from_table_id, $count";
        $query = str_replace("WHERE AND", "WHERE", $query);
        $result = $this->conn->query($query);
        if ($result)
            return $result;
        else throw new GameException("Unable to search tables", 18);
    }


    /**
     * @return int - table id
     * @throws GameException
     *
     * Create a new table
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
     *
     * Update in db the new state of table
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
     *
     * Delete table
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
     * @return int - maximum limit of players
     */
    public function getPlayersLimit(): int
    {
        return $this->players_limit;
    }

    /**
     * @param mixed $players_limit - maximum limit of players
     */
    public function setPlayersLimit($players_limit): void
    {
        $this->players_limit = $players_limit;
    }

    /**
     * @param string $password
     * @return bool
     *
     * Check if input password matches the table password
     */
    public function checkPassword(string $password) : bool
    {
        if(empty($this->password))
            return true;
        if(!empty($password) && $password == $this->password)
            return true;
        return false;
    }

    /**
     * @throws GameException
     * Search for a new host player
     */
    public function newHost()
    {
        $query = "SELECT * FROM " . Player::getTableName() . " WHERE id != ? and id_table = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $this->host, $this->id);

        if ($stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            $this->host = $row['id'];
        } else throw new GameException("Unable change host for table with id $this->id, $stmt->errno: $stmt->error", 17);
    }

    /**
     * @return array - rules list
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}