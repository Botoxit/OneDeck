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

    /**
     * Player constructor.
     * @param string $newName (default 'Player')
     */
    public function __construct(string $newName = "Player")
    {
        $this->name = $newName;
        $this->conn = DataBase::getConnection();
    }

    /**
     * Get database table name for Players
     * @return string
     */
    public static function getTableName(): string
    {
        return Player::$DBTable_name;
    }

    /**
     * Read from database player data for an id
     * @param $id
     * @return bool
     */
    public function readOne($id)
    {
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id = '$id'";
        $stmt = $this->conn->prepare($query);

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
     * Read all player from a table
     * @param $id_table
     * @return int|mysqli_result
     */
    public function readAll($id_table)
    {
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id_table = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i',$id_table);

        if ($stmt->execute())
            return $stmt->get_result();
        else return $stmt->errno;
    }

    /**
     * Create new Player with class attributes
     * @return int (player id or -1 for fail)
     */
    public function create()
    {
        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name, cards) VALUES (NULL, ?, ?, '{}')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('is', $this->id_table, $this->name);

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return $this->id;
        } else return -1;
    }

    /**
     * Update Player with class attributes
     * @return bool
     */
    public function update()
    {
        $cards = json_encode($this->cards);
        $query = "UPDATE " . Player::$DBTable_name . " SET cards='$cards' WHERE id = '$this->id'";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute())
            return true;
        return false;
    }

    /**
     * Delete Player with id from class attributes
     * @return bool
     */
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

    /**
     * Check if Player really have $cards in his hand
     * @param array $cards
     * @return bool
     */
    public function checkCards(array $cards)
    {
        foreach ($cards as $card) {
            if (!in_array($this->cards, $card))
                return false;
        }
        return true;
    }

    /**
     * Set if a Player is ready or not.
     * @return bool
     */
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
     * Id getter
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * id for table getter
     * @param int $idTable
     */
    public function setIdTable(int $idTable) : void
    {
        $this->id_table = $idTable;
    }

    /**
     * Player name getter
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Player cards getter
     * @return mixed
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * Add cards in Player hand (when player get cards)
     * @param array $cards
     */
    public function addCards(array $cards)
    {
        $this->cards = array_merge($this->cards, $cards);
    }

    /**
     * Player cards setter
     * @param array $cards
     */
    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    /**
     * Remove cards from Player hand (when player use cards)
     * @param array $cards
     * @return int
     */
    public function removeCards(array $cards)
    {
        $this->cards = array_diff($this->cards, $cards);
        return count($this->cards);
    }
}