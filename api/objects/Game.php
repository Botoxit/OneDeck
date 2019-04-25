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
    private $id = 0;
    private $cards = [];
    private $round = 0;
    private $total_players = 0;
    private $deck = [];
    private $details = [];

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
            $this->cards = json_decode($row['cards']);
            $this->round = intdiv($row['round'], 10);
            $this->total_players = $row['round'] % 10;
            $this->deck = json_decode($row['deck']);
            return true;
        } else return false;
    }

    public function update()
    {
        $cards = json_encode($this->cards);
        if($this->round == $this->total_players)
            $this->round = 10 + $this->total_players;
        else $this->round = ($this->round + 1) * 10 + $this->total_players;
        $deck = json_encode($this->deck);
        $details = json_encode($this->details);
        $query = "UPDATE " . Game::$DBTable_name . " SET cards='$cards', round='$this->round', 
            deck='$deck', details='$details'  WHERE id = '$this->id'";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute())
            return true;
        return false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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

    /**
     * @return int
     */
    public function getRound(): int
    {
        return $this->round;
    }


    /**
     * @param int $count
     * @return array
     */
    public function takeCards(int $count): array
    {
        if($count <= count($this->deck))
            return array_splice($this->deck, 0, $count);
        $this->deck = shuffle(array_splice($this->deck, 1));
        if($count <= count($this->deck))
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
}