<?php
/**
 * User: Nicu Neculache
 * Date: 22.04.2019
 * Time: 20:25
 */

class Game
{
    // database connection and table name
    private static $DBTable_name = "table";
    private $conn;

    // object properties
    private $id;
    private $cards;
    private $round;
    private $deck;

    /**
     * Player constructor.
     * @param $database
     */
    public function __construct(mysqli $database)
    {
        $this->conn = $database;
    }

    /**
     * @param $id
     * @return bool
     */
    public function readOne($id)
    {
        // select all query
        $query = "SELECT * FROM " . Game::$DBTable_name . " WHERE id = '$id'";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            // get retrieved row
            $row = $stmt->fetch();

            $this->id = $row['id'];
            $this->cards = $row['cards'];
            $this->round = $row['round'];
            $this->deck = $row['deck'];
            return true;
        } else return false;
    }
}