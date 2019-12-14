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
    private $cards = [];

    /**
     * Player constructor.
     * @param string $newName (default 'Player')
     */
    public function __construct(string $newName = "Player")
    {
        $this->name = $newName;
        $this->conn = Database::getConnection();
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
     * @param $id
     * @throws GameException
     */
    public function readOne($id)
    {
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id = '$id'";
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 0)
                throw new GameException("Player with id $id don't exist in database.", 19);
            $row = $result->fetch_assoc();

            $this->id = $row['id'];
            $this->id_table = $row['id_table'];
            $this->name = $row['name'];
            $this->cards = json_decode($row['cards'], true);
        } else throw new GameException("Unable to read player with id $id, $stmt->errno: $stmt->error", 1);
    }


    /**
     * @param $id_table
     * @return false|mysqli_result
     * @throws GameException
     */
    public function readAll($id_table)
    {
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id_table = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id_table);

        if ($stmt->execute())
            return $stmt->get_result();
        else throw new GameException("Unable to read players for table with id $id_table, $stmt->errno: $stmt->error", 7);
    }

    /**
     * @return int
     * @throws GameException
     */
    public function create()
    {
        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name, cards) VALUES (NULL, ?, ?, '{}')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('is', $this->id_table, $this->name);

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            $_SESSION['id_player'] = $this->id;
            return $this->id;
        } else throw new GameException("Unable to create player, $stmt->errno: $stmt->error", 10);
    }

    /**
     * @throws GameException
     */
    public function update()
    {
        $cards = json_encode($this->cards);
        if ($cards == null) $cards = "{}";
        $query = "UPDATE " . Player::$DBTable_name . " SET id_table = ?, cards = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('isi', $this->id_table, $cards, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update player with id: $this->id, $stmt->errno: $stmt->error", 6);
    }

    /**
     * @throws GameException
     */
    public function delete()
    {
        $query = "DELETE FROM " . Player::$DBTable_name . " WHERE id = ?";
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->id);
        // execute query
        if (is_bool($stmt) || !$stmt->execute())
            throw new GameException("Unable to delete player with id: $this->id, $stmt->errno: $stmt->error", 14);
    }

    /**
     * Check if Player really have $cards in his hand
     * @param array $cards
     * @return bool
     */
    public function checkCards(array $cards)
    {
        foreach ($cards as $card) {
            if (!in_array($card, $this->cards))
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
     * Id for table getter
     * @return int|null
     */
    public function getIdTable()
    {
        return $this->id_table;
    }

    /**
     * Id for table setter
     * @param int $idTable
     */
    public function setIdTable(int $idTable): void
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
        $this->cards = array_values(array_diff($this->cards, $cards));
        return count($this->cards);
    }
}