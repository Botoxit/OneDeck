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
            $this->cards = $row['cards'];
            $this->round = $row['round'];
            $this->deck = $row['deck'];
            return true;
        } else return false;
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
        return array_splice($this->deck, 0, $count);
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
    protected function setDetails(array $details): void
    {
        $this->details = $details;
    }

    public function checkCardsOwner(Player $player, array $cards)
    {
        $playerCards = $player->getCards();
        foreach ($cards as $card) {
            if(!in_array($playerCards,$card))
                return false;
        }
        return true;
    }
}