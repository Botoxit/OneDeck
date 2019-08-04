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
            $this->cards = json_decode($row['cards']);
            return true;
        } else return false;
    }

    public function readAll($id_table)
    {
        // select all query
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id_table = '$id_table'";

        // prepare query statement
        $result = $this->conn->query($query);

        // execute query
        if ($result)
            return $result;
        else return $this->conn->error;
    }

    public function readCurrent($id_player)
    {
        // select all query
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id = $id_player";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        if ($stmt->execute()) {
            // get retrieved row
            $row = $stmt->fetch();

            $this->id = $row['id'];
            $this->id_table = $row['id_table'];
            $this->name = $row['name'];
            $this->cards = json_decode($row['cards']);
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

        $cards = json_encode($this->cards);
        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name, cards) VALUES (NULL, '$this->id_table', '$this->name', '$cards')";

        $stmt = $this->conn->prepare($query);

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

        $cards = json_encode($this->cards);
        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name, cards) VALUES (NULL, '$this->id_table', '$this->name', '$cards')";

        $stmt = $this->conn->prepare($query);

        if ($stmt->execute())
            return 1;
        else return -1;
    }

    public function update()
    {
        $cards = json_encode($this->cards);
        $query = "UPDATE " . Player::$DBTable_name . " SET cards='$cards' WHERE id = '$this->id'";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute())
            return true;
        return false;
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
        foreach ($cards as $card) {
            if (!in_array($this->cards, $card))
                return false;
        }
        return true;
    }

    public function ready()
    {
        if (count($this->cards) > 0)
            return false;
        if (!isset($this->cards['ready'])) {
            $this->cards['ready'] = true;
            return true;
        }
        $this->cards['ready'] = !$this->cards['ready'];
        return $this->cards['ready'];
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

    public function getCardsCount()
    {
        return count($this->cards);
    }

    public function addCards(array $cards)
    {
        $this->cards = array_merge($this->cards, $cards);
    }

    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    public function removeCards(array $cards)
    {
        $this->cards = array_diff($this->cards, $cards);
        return count($this->cards);
    }
}