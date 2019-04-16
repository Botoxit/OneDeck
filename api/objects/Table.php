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
    private $players;
    private $rules;

    /**
     * Table constructor.
     * @param $database
     */
    public function __construct(mysqli $database)
    {
        $this->conn = $database;
    }

    /**
     * Read $count games_table starting with $from_table_id from DataBase
     * @param $from_table_id
     * @param $count
     * @return mysqli_stmt
     */
    public function readPaging($from_table_id, $count)
    {

        // select all query
        $query = "SELECT * FROM" . Table::$DBTable_name . " LIMIT $from_table_id, $count";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();

        return $stmt;
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
        if($stmt->execute())
            return $stmt->insert_id;
        else if($stmt->errno == 1062)
            return -1;
        else return null;
    }
}