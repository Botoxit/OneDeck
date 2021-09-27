<?php
/**
 * User: Nicu Neculache
 * Date: 22.04.2019
 * Time: 20:25
 */

include_once 'Table.php';
include_once 'Macao.php';

class Game
{
    // database connection and table name
    private static $DBTable_name = "game";
    protected $conn;
    // object properties
    private $id = 0;
    protected $cards = [];
    protected $round = [];
    protected $deck = [];
    protected $details = [];
    private $change_at = 0;
    private $rules = [];
    private $chat = [];
    private $host = 0;

    /**
     * Game constructor.
     * Get in local attribute the db connection
     */
    public function __construct()
    {
        $this->conn = Database::getConnection();
    }
    
    /**
     * @param $id - table id
     * @param $cards - the list of playing cards on the table
     * @param $round - the list of players from the current game
     * @param $deck - the remaining deck of playing cards
     * @param $details - list of details parameters
     * @param $rules - rules list
     */
    public function setter($id, $cards, $round, $deck, $details, $rules)
    {
        $this->setId($id);
        $this->setCards($cards);
        $this->setRound($round);
        $this->setDeck($deck);
        $this->setDetails($details);
        $this->setRules($rules);
    }

    /**
     * @param $id - id table
     * @param bool $read_rules - should the rules be read as well?
     * @throws GameException
     *
     * Read from db game data for table with id = $id
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
            if ($result->num_rows == 0)
                throw new GameException("Game data with id $id don't exist in database.", 19);
            $row = $result->fetch_assoc();

            $this->id = $row['id'];
            $this->cards = json_decode($row['cards'], true);
            $this->round = json_decode($row['round'], true);
            $this->deck = json_decode($row['deck'], true);
            $this->details = json_decode($row['details'], true);
            $this->chat = json_decode($row['chat'], true);
            $this->change_at = strtotime($row['change_at']);
            if ($read_rules) $this->rules = json_decode($row['rules'], true);
            $this->host = $row['host'];
        } else throw new GameException("Unable to read game data with id: $id, $stmt->errno: $stmt->error", 2);
    }

    public function readChat($id)
    {
        $query = "SELECT chat FROM " . Game::$DBTable_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 0)
                throw new GameException("Game data with id $id don't exist in database.", 19);
            $row = $result->fetch_assoc();

            $this->id = $id;
            $this->chat = json_decode($row['chat'], true);
        } else throw new GameException("Unable to read game data with id: $id, $stmt->errno: $stmt->error", 2);
    }

    /**
     * @param bool $update_time - update changed_at ?
     * @param bool $win_condition - is the winning condition fulfilled?
     * @param bool $end_turn - is the player's turn over?
     * @throws GameException
     *
     * Update in db the new state of game
     * if current player has finished his turn, call nextPlayer($win_condition) to change the turn
     * if there is only one player left in the game, we put him in the ranking
     */
    public function update(bool $update_time, bool $win_condition = false, bool $end_turn = true)
    {
        if ($end_turn) {
            $this->nextPlayer($win_condition);
        }
        if (count($this->round) == 1) {
            $lastPlayer = new Player();
            $lastPlayer->readOne($this->getRound());
            if (!isset($this->details['rank']))
                $this->details['rank'] = array(array('id' => $lastPlayer->getId(), 'name' => $lastPlayer->getName()));
            else array_push($this->details['rank'], array('id' => $lastPlayer->getId(), 'name' => $lastPlayer->getName()));
            $this->round = [];
        }
        $cards = json_encode($this->cards);
        $round = json_encode($this->round);
        $deck = json_encode($this->deck);
        $details = json_encode($this->details);
        $query = "UPDATE " . Game::$DBTable_name . " SET cards = ?, round = ?, deck = ?, details = ?";
        if ($update_time)
            $query = $query . ", change_at = CURRENT_TIMESTAMP WHERE id = ?";
        else $query = $query . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssssi', $cards, $round, $deck, $details, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update game chat for id: $this->id, $stmt->errno: $stmt->error", 3);
    }

    public function updateChat()
    {
        $chat = json_encode($this->chat);

        $query = "UPDATE " . Game::$DBTable_name . " SET chat = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('si', $chat, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update game chat for id: $this->id, $stmt->errno: $stmt->error", 3);
    }

    /**
     * @param bool $win_condition - is the winning condition fulfilled?
     *
     * We remove the current player from the list
     * if it does not meet the condition of winning we add it at the end
     */
    protected function nextPlayer(bool $win_condition)
    {
        $current_player = array_splice($this->round, 0, 1);
        if (!$win_condition) {
            $this->round = array_merge($this->round, $current_player);
        }
    }

    /**
     * @return int - table id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id - table id
     */
    protected function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return array - the list of playing cards on the table
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @return mixed - the latest playing card on the table
     */
    public function getFirstTableCard()
    {
        return $this->cards[0];
    }

    /**
     * @return int - timestamp for a player's last move
     */
    public function getChangeAt(): int
    {
        return $this->change_at;
    }

    /**
     * @param Player $player
     *
     * Delete a player, put all cards at the end of deck and delete from round array
     */
    public function deletePlayer(Player $player)
    {
        // if it was his turn, we delete kick votes
        if ($player->getId() == $this->getRound() && isset($this->details['kick'])) {
            unset($this->details['kick']);
        }
        $cards = $player->getCards();
        if (!empty($cards['ready']))
            unset($cards['ready']);
        if (count($cards) > 0)
            $this->deck = array_merge(array_values($this->deck), array_values($cards));
        else {
            // if the game has started, but player has not yet drawn first cards
            // we decrement the number of players who have to draw the starting cards
            if (isset($this->details['new_game']) && $this->details['new_game'] > 0)
                $this->details['new_game'] = $this->details['new_game'] - 1;
        }
        // delete id from round attribut
        $key = array_search($player->getId(), $this->round);
        //Debug::Log("deletePlayer script " . $key, __FILE__);
        if ($key !== false) {
            unset($this->round[$key]);
            $this->round = array_values($this->round);
        }
    }

    /**
     * @return array - game rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param $rules - game rules
     */
    protected function setRules($rules)
    {
        $this->rules = $rules;
    }

    /**
     * @return array
     */
    public function getChat(): array
    {
        return $this->chat;
    }

    /**
     * @param int $timestamp
     * @param string $playerName
     * @param string $text
     * @return bool
     */
    public function AddToChat($timestamp, $playerName, $text): bool
    {
        if (count($this->chat) > 0 && $this->chat[0]["playerName"] == $playerName && $this->chat[0]["text"] == $text)
            return false;
        $newMessage = array("timestamp" => $timestamp,
            "playerName" => $playerName,
            "text" => $text);
        array_unshift($this->chat, $newMessage);
        if (count($this->chat) > 15)
            unset($this->chat[15]);
        return true;
    }

    /**
     * @param array $cards - new playing cards that are placed on the table
     */
    protected function addCards(array $cards)
    {
        $this->cards = array_merge($cards, $this->cards);
    }

    /**
     * @param array $cards - list of playing cards on the table
     */
    protected function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    /**
     * @param array $deck - the remaining deck of playing cards
     */
    protected function setDeck(array $deck)
    {
        $this->deck = array_values($deck);
    }

    /**
     * @return array - the remaining deck of playing cards
     */
    protected function getDeck(): array
    {
        return $this->deck;
    }

    /**
     * @return int - if of current player
     */
    public function getRound(): int
    {
        if (empty($this->round))
            return 0;
        return $this->round[0];
    }

    /**
     * @return array - the list of players from the current game
     */
    protected function getRoundArray(): array
    {
        return $this->round;
    }

    /**
     * @param array $round - the list of players from the current game
     */
    protected function setRound(array $round)
    {
        $this->round = $round;
    }

    /**
     * @return int - count of players from the current game
     */
    public function getPlayerCount(): int
    {
        return count($this->round);
    }

    /**
     * @param int $count - the amount of playing cards
     * @return array - list of drawn cards
     */
    public function takeCards(int $count): array
    {
        if ($count <= count($this->deck))
            return array_splice($this->deck, 0, $count);
        else return array_splice($this->deck, 0);
    }

    /**
     * @return array - list of details parameters
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array $details - list of details parameters
     */
    public function setDetails(array $details): void
    {
        $this->details = $details;
    }

    /**
     * @return int - the number of playing cards left in deck
     */
    public function getDeckCount(): int
    {
        return count($this->deck);
    }

    /**
     * @return int - host player id
     */
    public function getHost(): int
    {
        return $this->host;
    }

    /**
     * @throws GameException
     * @return bool
     *
     * Counts in the database how many players have in the
     * details field true value at the key "ready", and how many are in total.
     *
     * returns the true value of the equality between the two results.
     */
    public function allPlayersReady(): bool
    {
        $query = "SELECT count(*) as \"ready\" , (SELECT count(*) FROM " . Player::getTableName() . " WHERE id_table = " . $this->id . ") as \"total\" 
                            FROM " . Player::getTableName() . " WHERE id_table = " . $this->id . " AND JSON_EXTRACT(cards,'$.ready') = true";
        $stmt = $this->conn->prepare($query);

        if (!$stmt->execute())
            throw new GameException("Unable to read ready players for table id: " . $this->id . ", $stmt->errno: $stmt->error", 5);
        $result = $stmt->get_result();
        if (!$result)
            throw new GameException("Players for table with id " . $this->id . " don't exist in database.", 19);
        $row = $result->fetch_assoc();
        $ready_players = $row['ready'] + 1;
        $total_players = $row['total'];

        if ($ready_players == $total_players && $total_players > 1)
            return true;
        return false;
    }

    /**
     * @param bool $addJokers - add Jokers?
     * @param bool $shuffle - shuffle generated deck?
     * @return array
     *
     * Generate deck
     */
    public function makeDeck(bool $addJokers, bool $shuffle): array
    {
        if($addJokers)
            $deck = array(5, 6);
        else $deck = array();

        for ($i = 1; $i < 14; $i++) {
            $num = $i * 10;
            for ($j = 1; $j < 5; $j++)
                array_push($deck, $num + $j);
        }
        if($shuffle)
            shuffle($deck);
        return $deck;
    }
}