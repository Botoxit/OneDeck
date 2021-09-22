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
    private $details = [];

    /**
     * Player constructor.
     * @param string $newName (default 'Player')
     * Get in local attribute the db connection
     */
    public function __construct(string $newName = "Player")
    {
        $this->name = $newName;
        $this->conn = Database::getConnection();
    }

    /**
     * @return string
     * Get database table name for Players
     */
    public static function getTableName(): string
    {
        return Player::$DBTable_name;
    }

    /**
     * @param $id - player id
     * @throws GameException
     *
     * Read from db data about player with id = $id
     */
    public function readOne($id)
    {
        $query = "SELECT * FROM " . Player::$DBTable_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 0)
                throw new GameException("Player with id $id don't exist in database.", 19);
            $row = $result->fetch_assoc();

            $this->id = $row['id'];
            $this->id_table = $row['id_table'];
            $this->name = $row['name'];
            $this->cards = json_decode($row['cards'], true);
            $this->details = json_decode($row['details'], true);
        } else throw new GameException("Unable to read player with id $id, $stmt->errno: $stmt->error", 1);
    }


    /**
     * @param $id_table - table id
     * @return false|mysqli_result - MySQL rezult with all players or false
     * @throws GameException
     *
     * Read all players from a table
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
     * @return int - player id
     * @throws GameException
     *
     * Create a new player
     */
    public function create(): int
    {
        $query = "INSERT INTO " . Player::$DBTable_name . " (id, id_table, name) VALUES (NULL, ?, ?)";
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
     *
     * Update player data in db
     */
    public function update()
    {
        $cards = json_encode($this->cards);
        $details = json_encode($this->details);
        if ($cards == null) $cards = "{}";
        $query = "UPDATE " . Player::$DBTable_name . " SET id_table = ?, cards = ?, details = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('issi', $this->id_table, $cards, $details, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update player with id: $this->id, $stmt->errno: $stmt->error", 6);
    }

    /**
     * @throws GameException
     *
     * Delete player
     */
    public function delete()
    {
        $query = "DELETE FROM " . Player::$DBTable_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->id);
        if (is_bool($stmt) || !$stmt->execute())
            throw new GameException("Unable to delete player with id: $this->id, $stmt->errno: $stmt->error", 14);
    }

    /**
     * @param array $cards
     * @return bool
     *
     * Check if Player really have $cards in his hand
     */
    public function checkCards(array $cards): bool
    {
        foreach ($cards as $card) {
            if (!in_array($card, $this->cards))
                return false;
        }
        return true;
    }

    /**
     * @return bool
     *
     * Set if a Player is ready or not.
     */
    public function ready(): bool
    {
        if (!isset($this->cards['ready'])) {
            $this->cards['ready'] = true;
            return true;
        }
        $this->cards['ready'] = !$this->cards['ready'];
        return $this->cards['ready'];
    }

    /**
     * @return int
     * Id getter
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     * Id for table getter
     */
    public function getIdTable(): ?int
    {
        return $this->id_table;
    }

    /**
     * @param int $idTable
     * Id for table setter
     */
    public function setIdTable(int $idTable): void
    {
        $this->id_table = $idTable;
    }

    /**
     * @return string
     * Player name getter
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     * Player cards getter
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @param array $cards
     * Add cards in Player hand (when player get cards)
     */
    public function addCards(array $cards)
    {
        $this->cards = array_merge($this->cards, $cards);
    }

    /**
     * @param array $cards
     * Player cards setter
     */
    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    /**
     * @param array $cards
     * @return int - number of cards left in hand
     * Remove cards from Player hand (when player use cards)
     */
    public function removeCards(array $cards): int
    {
        $player_cards = array_diff($this->cards, $cards);
        $this->cards = array_values($player_cards);
        return count($this->cards);
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array $details
     */
    public function setDetails(array $details): void
    {
        $this->details = $details;
    }
}