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
    private $cards = [];
    protected $round = [];
    protected $deck = [];
    private $details = [];
    private $change_at = 0;
    private $rules = [];
    private $chat = [];
    private $host = 0;

    public function __construct()
    {
        $this->conn = Database::getConnection();
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
     * @param bool $update_time
     * @param bool $done_cards
     * @param bool $new_game
     * @throws GameException
     */
    public function update(bool $update_time, bool $done_cards = false, bool $new_game = false)
    {
        if (!$new_game) {
            $this->nextPlayer($done_cards);
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

    /**
     * @throws GameException
     */
    public function updateChat()
    {
        $chat = json_encode($this->chat);

        $query = "UPDATE " . Game::$DBTable_name . " SET chat = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('si', $chat, $this->id);

        if (!$stmt->execute())
            throw new GameException("Unable to update game chat for id: $this->id, $stmt->errno: $stmt->error", 3);
    }

    protected function nextPlayer(bool $done_cards)
    {
        $current_player = array_splice($this->round, 0, 1);
        if (!$done_cards) {
            $this->round = array_merge($this->round, $current_player);
        }
        if (isset($this->details['waiting']) && isset($this->details['waiting'][$this->getRound()])) {
            if ($this->details['waiting'][$this->getRound()] > 1)
                $this->details['waiting'][$this->getRound()] -= 1;
            else unset($this->details['waiting'][$this->getRound()]);
            $this->nextPlayer(false);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    protected function setId($id)
    {
        $this->id = $id;
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
     * @return int
     */
    public function getChangeAt()
    {
        return $this->change_at;
    }

    /**
     * Delete a player, put all cards at the end of deck and delete from round array
     * @param Player $player
     */
    public function deletePlayer(Player $player)
    {
        if ($player->getId() == $this->getRound() && isset($this->details['kick'])) {
            unset($this->details['kick']);
        }
        $cards = $player->getCards();
        if (!empty($cards['ready']))
            unset($cards['ready']);
        if (count($cards) > 0)
            $this->deck = array_merge(array_values($this->deck), array_values($cards));
        else {
            if (isset($this->details['new_game']) && $this->details['new_game'] > 0)
                $this->details['new_game'] = $this->details['new_game'] - 1;
        }
        $key = array_search($player->getId(), $this->round);
        //Debug::Log("deletePlayer script " . $key, __FILE__);
        if ($key !== false) {
            unset($this->round[$key]);
            $this->round = array_values($this->round);
            if ($this->getPlayerCount() > 1 && $this->getId() < 5 && $this->getRound() == $this->getId()) {
                $macao = new Macao();
                $macao->copy_class($this->id, $this->cards, $this->round, $this->deck, $this->details, $this->rules);
                $macao->nextPlayer($macao->boot());
                $this->setRound($macao->getRoundArray());
                $this->setCards($macao->getCards());
                $this->setDeck($macao->getDeck());
                $this->setDetails($macao->getDetails());
            }
        }
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

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
     * @param array $cards
     */
    protected function addCards(array $cards)
    {
        $this->cards = array_merge($cards, $this->cards);
    }

    protected function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    protected function setDeck(array $deck)
    {
        $this->deck = array_values($deck);
    }

    protected function getDeck()
    {
        return $this->deck;
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
    protected function getRoundArray(): array
    {
        return $this->round;
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
     * @param bool $firstCard
     * @return array
     */
    public function takeCards(int $count, bool $firstCard = false): array
    {
        if ($count > count($this->deck)) {
            $this->deck = array_merge($this->deck, array_splice($this->cards, 1));
            if (!shuffle($this->deck))
                Debug::Log("Shuffle deck + old cards failed", __FILE__, "WARNING");
        }

        if ($count <= count($this->deck)) {
            $invalidFirst = array(5, 6, 21, 22, 23, 24, 31, 32, 33, 34);
            if ($firstCard && (array_search($this->deck[0], $invalidFirst) !== false)) {
                $cards = [];
                //Debug::Log("firstCard is " . $this->deck[0], __FILE__);
                for ($i = 1; $i < count($this->deck); $i++) {
                    if (array_search($this->deck[$i], $invalidFirst) == false) {
                        array_push($cards, $this->deck[$i]);
                        unset($this->deck[$i]);
                        return $cards;
                    }
                }
            }
            return array_splice($this->deck, 0, $count);
        } else {
            return array_splice($this->deck, 0);
        }
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

    /**
     * @throws GameException
     */
    public function allPlayersReady()
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
}