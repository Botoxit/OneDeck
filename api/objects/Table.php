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
        } else return $stmt->error;
    }

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
     * @param $name
     * @param $password
     * @param $game
     * @param $players_limit
     * @param $rules
     * @return int|null => success = id | name already exist = -1 | other error = null
     */
    public function newTable($name, $password, $game, $players_limit, $rules)
    {
        if ($password == null || $password == "")
            $password = "NULL";
        else $password = "'$password'";

        // select all query
        $query = "INSERT INTO " . Table::$DBTable_name . " (id, name, password, game, players_limit, rules) VALUES (NULL, '$name', $password, '$game', '$players_limit', '$rules')";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute())
            return $stmt->insert_id;
        else if ($stmt->errno == 1062)
            return -1;
        else return null;
    }

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
}