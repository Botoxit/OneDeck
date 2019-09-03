<?php
/**
 * User: Nicu Neculache
 * Date: 22.04.2019
 * Time: 20:25
 */

include_once 'Table.php';

class Game
{
    // database connection and table name
    private static $DBTable_name = "game";
    protected $conn;
    // object properties
    private $id = 0;
    private $cards = [];
    private $round = [];
    private $deck = [];
    private $details = [];
    private $change_at = 0;
    private $rules = [];
    private $host = 0;

    public function __construct()
    {
        $this->conn = DataBase::getConnection();
    }

    /**
     * @param $id
     * @param bool $read_rules
     * @throws GameException
     */
    public function readOne($id, bool $read_rules = false)
    {
        // select all query
        $query = "SELECT g.*, ";
        if ($read_rules) $query .= "t.rules, ";
        $query .= "t.host FROM " . Game::$DBTable_name . " g JOIN " . Table::getTableName() . " t ON t.id = g.id WHERE g.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if (!$result)
                throw new GameException("Game data with id $id don't exist in database.", 19);
            $row = $result->fetch_assoc();

            $this->id = $row['id'];
            $this->cards = json_decode($row['cards'], true);
            $this->round = json_decode($row['round'], true);
            $this->deck = json_decode($row['deck'], true);
            $this->details = json_decode($row['details'], true);
            $this->change_at = $row['change_at'];
            if ($read_rules) $this->rules = json_decode($row['rules'], true);
            $this->host = $row['host'];
        } else throw new GameException("Unable to read game data with id: $id, $stmt->errno: $stmt->error", 2);
    }

    /**
     * @param bool $macao
     * @param bool $new_game
     * @throws GameException
     */
    public function update(bool $macao = false, bool $new_game = false)
    {
        $cards = json_encode($this->cards);

        if (!$new_game) {
            $this->nextPlayer($macao);
        }
        $round = json_encode($this->round);
        $deck = json_encode($this->deck);
        $details = json_encode($this->details);
        $query = "UPDATE " . Game::$DBTable_name . " SET cards = ?, round = ?, deck = ?, details = ?  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssssi', $cards, $round, $deck, $details, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update game data with id: $this->id, $stmt->errno: $stmt->error", 3);
    }

    private function nextPlayer(bool $macao)
    {
        $current_player = array_splice($this->round, 0, 1);
        if (!$macao)
            $this->round = array_merge($this->round, $current_player);
        if (isset($this->details['waiting']) && isset($this->details['waiting'][$this->getRound()])) {
            if ($this->details['waiting'][$this->getRound()] > 1)
                $this->details['waiting'][$this->getRound()] -= 1;
            else unset($this->details['waiting'][$this->getRound()]);
            $this->nextPlayer(false);
        }
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
     * Delete a player, put all cards at the end of deck and delete from round array
     * @param Player $player
     */
    public function deletePlayer(Player $player)
    {
        array_push($this->deck, $player->getCards());
        $key = array_search($player->getId(), $this->round);
        if ($key >= 0) {
            array_splice($this->round, $key, 1);
        }
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param array $cards
     */
    protected function addCards(array $cards)
    {
        $this->cards = array_merge($cards, $this->cards);
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