<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 18:20
 */

class Table
{
    // database connection and table name
    private static $DBTable_name = "tabel_list";
    private $conn;

    // object properties
    private $id;
    private $name;
    private $password;
    private $game;
    private $players_limit;
    private $rules;

    /**
     * Table constructor.
     * @param $database
     */
    public function __construct(mysqli $database)
    {
        $this->conn = $database;
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
        $query = "SELECT * FROM " . Table::$DBTable_name . " WHERE id = $id";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            // get retrieved row
            $row = $stmt->fetch();

            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->game = $row['game'];
            $this->players_limit = $row['players_limit'];
            $this->rules = $row['rules'];
            return true;
        } else return false;
    }

    /**
     * @param $from_table_id
     * @param $count
     * @return mysqli_stmt|string
     */
    public function readPaging($from_table_id, $count)
    {
        // select all query
        $query = "SELECT * FROM " . Table::$DBTable_name . " LIMIT $from_table_id, $count";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute())
            return $stmt;
        else return $stmt->error;
    }

    /**
     * Create a new table
     * @return int => success = id | name already exist = 0 | other error = -1
     */
    public function create()
    {
        // select all query
        $query = "INSERT INTO " . Table::$DBTable_name . " (id, name, password, game, players_limit, rules) VALUES (NULL, '$this->name', $this->password, '$this->game', '$this->players_limit', '$this->rules')";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute())
            return $stmt->insert_id;
        else if ($stmt->errno == 1062)
            return 0;
        else return -1;
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteTable($id)
    {
        $query = "DELETE FROM " . Table::$DBTable_name . " WHERE id = $id";

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
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getGame(): string
    {
        return $this->game;
    }

    /**
     * @param string $game
     */
    public function setGame(string $game): void
    {
        $this->game = $game;
    }

    /**
     * @return int
     */
    public function getPlayersLimit(): int
    {
        return $this->players_limit;
    }

    /**
     * @param int $players_limit
     */
    public function setPlayersLimit(int $players_limit): void
    {
        $this->players_limit = $players_limit;
    }

    /**
     * @return array
     */
    public function getRules():array
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }
}