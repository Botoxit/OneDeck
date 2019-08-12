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
    private $id_table = null;
    private $name = "Player";
    private $cards;

    public function __construct(string $newName)
    {
        $this->name = $newName;
        $conn = DataBase::getConnection();
    }

    public static function getTableName(): string
    {
        return Player::$DBTable_name;
    }

    public function readOne($id)
    {
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id = '$id'";
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
        $conn = DataBase::getConnection();
        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name, cards) VALUES (NULL, ?, ?, '{}')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('is', $this->id_table, $this->name);

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return $this->id;
        } else return -1;
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
        if (!isset($this->cards['ready'])) {
            if (count($this->cards) > 0)
                return false;
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
     * @param int $idTable
     */
    public function setIdTable(int $idTable) : void
    {
        $this->id_table = $idTable;
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