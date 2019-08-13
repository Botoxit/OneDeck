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
    protected $conn;
    // object properties
    private $id = 0;
    private $cards = [];
    private $round = [];
    private $deck = [];
    private $details = [];
    private $host = "";

    public function __construct()
    {
        $this->conn = DataBase::getConnection();
    }

    /**
     * Read game data for an id
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
            $this->cards = json_decode($row['cards']);
            $this->round = json_decode($row['round']);
            $this->deck = json_decode($row['deck']);
            $this->host = $row['host'];
            return true;
        } else return false;
    }

    /**
     * Update game data from class attributes
     * @param bool $macao (default false) true if a player remain without cards in hand
     * @return bool
     */
    public function update(bool $macao = false)
    {
        $cards = json_encode($this->cards);
        $current_player = array_splice($this->round, 0, 1);
        if (!$macao)
            array_push($this->round, $current_player);
        $round = json_encode($this->round);
        $deck = json_encode($this->deck);
        $details = json_encode($this->details);
        $query = "UPDATE " . Game::$DBTable_name . " SET cards='$cards', round='$round', 
            deck='$deck', details='$details'  WHERE id = '$this->id'";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute())
            return true;
        return false;
    }

    /**
     * @return array
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @return mixed
     */
    public function getFirstTableCard()
    {
        return $this->cards[0];
    }

    /**
     * @param array $cards
     * @return int
     */
    protected function addCards(array $cards): int
    {
        return array_unshift($this->cards, $cards);
    }

    protected function setDeck(array $deck)
    {
        $this->deck = $deck;
    }

    /**
     * @return int
     */
    public function getRound(): int
    {
        if (empty($this->round))
            return 0;
        return $this->round[0];
    }

    protected function setRound(array $round)
    {
        $this->round = $round;
    }

    public function getPlayerCount(): int
    {
        return count($this->round);
    }


    /**
     * @param int $count
     * @return array
     */
    public function takeCards(int $count): array
    {
        if ($count <= count($this->deck))
            return array_splice($this->deck, 0, $count);
        $this->deck = shuffle(array_splice($this->cards, 1));
        if ($count <= count($this->deck))
            return array_splice($this->deck, 0, $count);
        return null;
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

    public function getDeckCount()
    {
        return count($this->deck);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }
}