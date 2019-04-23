<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 18:20
 */

class Player
{
    // database connection and table name
    private static $DBTable_name = "player";
    private $conn;

    // object properties
    private $id = 0;
    private $id_table = 0;
    private $name = "Player";
    private $cards;

    /**
     * Player constructor.
     * @param $database
     */
    public function __construct(mysqli $database)
    {
        $this->conn = $database;
    }

    public static function getTableName(): string
    {
        return Player::$DBTable_name;
    }

    /**
     * @param $id
     * @return bool
     */
    public function readOne($id)
    {
        // select all query
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id = '$id'";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            // get retrieved row
            $row = $stmt->fetch();

            $this->id = $row['id'];
            $this->id_table = $row['id_table'];
            $this->name = $row['name'];
            $this->cards = $row['cards'];
            return true;
        } else return false;
    }

    public function readCurrent($id_table, $round)
    {
        // select all query
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id_table = '$id_table' LIMIT $round-1,$round";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            // get retrieved row
            $row = $stmt->fetch();

            $this->id = $row['id'];
            $this->id_table = $row['id_table'];
            $this->name = $row['name'];
            $this->cards = $row['cards'];
            return true;
        } else return false;
    }

    /**
     * @return int
     */
    public function create()
    {
        $query = "SELECT players_limit FROM " . Table::getTableName() . " WHERE id='$this->id_table'";
        $stmt = $this->conn->prepare($query);
        if (!$stmt->execute())
            return -1;
        $row = $stmt->fetch();
        $players_limit = $row['players_limit'];

        $query = "SELECT count(*) FROM " . Player::$DBTable_name . " WHERE id_table='$this->id_table'";
        $stmt = $this->conn->prepare($query);
        if (!$stmt->execute())
            return -1;
        $row = $stmt->fetch();
        $players_count = $row['players_limit'];

        if ($players_count >= $players_limit)
            return 0;

        $stmt = $this->createQuery();

        // execute query
        if ($stmt->execute())
            return $stmt->insert_id;
        else if ($stmt->errno == 1062)
            return $this->sameUsername();
        else return -1;
    }

    private function sameUsername()
    {
        $length = strlen($this->name);
        $query = "SELECT count(*) FROM " . Player::$DBTable_name . " WHERE id_table='$this->id_table' AND SUBSTR(name,1,$length) = '$this->name' AND LENGTH(name) < $length+1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt->execute())
            return -1;
        $row = $stmt->fetch();
        $this->name = $this->name . $row[0];

        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name, cards) VALUES (NULL, '$this->id_table', '$this->name', '$this->cards')";

        $stmt = $this->conn->prepare($query);

        if ($stmt->execute())
            return 1;
        else return -1;
    }

    public function delete()
    {
        $query = "DELETE FROM " . Player::$DBTable_name . " WHERE id = $this->id";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute())
            return true;
        else return false;
    }

    public function checkCards(array $cards)
    {
        return true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIdTable()
    {
        return $this->id_table;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @param mixed $cards
     */
    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }
}