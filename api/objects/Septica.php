<?php

include_once 'Game.php';
include_once 'Player.php';

class Septica extends Game
{
    /**
     * @param Player $player
     * @throws GameException
     *
     * Initializing a new game
     */
    public function newGame(Player $player)
    {
        // player list initialization and distribute the cards to them
        $round = [];
        $players_list = $player->readAll($player->getIdTable());
        if ($players_list->num_rows == 0)
            die(json_encode(array("status" => -1, "message" => "Unable to read players.")));
        while ($row = $players_list->fetch_assoc()) {
            $player->readOne($row['id']);
            $player->setCards(array());
            $player->update();

            array_push($round, $row['id']);
        }


        // Generate deck
        $deck = array(11, 12, 13, 14, 71, 72, 73, 74, 81, 82);
        if (count($round) < 3) {
            array_push($deck, 83, 84);
            $opt_taie = false;
        } else $opt_taie = true;
        for ($i = 9; $i < 14; $i++) {
            $num = $i * 10;
            for ($j = 1; $j < 5; $j++)
                array_push($deck, $num + $j);
        }
        shuffle($deck);
        $this->setDeck($deck);

        // the winner of the last game will start the new one
        $round = array_values($round);
        $details = $this->getDetails();
        if (!empty($details['rank'])) {
            $key = array_search($details['rank'][0]['id'], $round);
            if ($key > 0) {
                $players_slice = array_splice($round, 0, $key);
                $round = array_merge($round, $players_slice);
            }
        } else {
            try {
                $key = random_int(0, count($round) - 1);
                if ($key > 0) {
                    $players_slice = array_splice($round, 0, $key);
                    $round = array_merge($round, $players_slice);
                }
            } catch (Exception $e) {
                Debug::Log($e->getMessage(), __FILE__, "EXCEPTION");
            }
        }

        // initialization game details
        $details = array("round_done" => true,
            "new_game" => count($round),
            "current_start" => $round[0],
            "next_start" => $round[0],
            "opt_taie" => $opt_taie,
            "total_cards" => count($deck));
        $this->setDetails($details);

        $this->setRound($round);
        $this->setCards(array());
    }

    /**
     * @param int $player_id - player id
     * @param int $card - the list of playing cards to be checked
     * @return bool
     *
     * Check if the cards and the current state of the game
     * follow the rules
     */
    public function verify(int $player_id, int $card): bool
    {
        $table_cards = $this->getCards();
        $details = $this->getDetails();

        // at the beginning of a game
        // player who started current round = player who will start next round
        if (count($table_cards) == 0) {
            $details['current_start'] = $player_id;
            $details['next_start'] = $player_id;
        } else {
            // check that the playing cards follow the rules of the game
            $first_card = $table_cards[count($table_cards) - 1];
            $switch_start_player = intdiv($card, 10) == intdiv($first_card, 10);
            $switch_start_player = $switch_start_player || intdiv($card, 10) == 7;
            if ($details['opt_taie'] == true)
                $switch_start_player = $switch_start_player || intdiv($card, 10) == 8;
            // if the current player has cut, he will start the next round
            if ($switch_start_player) {
                $details['next_start'] = $player_id;
            } elseif ($player_id == $details['current_start'])
                return false;
        }
        $this->addCards(array($card));
        if (isset($details['played_cards']))
            $details['played_cards'] += 1;
        else $details['played_cards'] = 1;
        $this->setDetails($details);
        return true;
    }

    /**
     * @param Player $player
     * @param array $cards - the list of playing cards to be checked
     * @return bool
     *
     * Check if playing cards have the same number
     * and if the player has these cards in his hand
     */
    public function checkCards(Player $player, array $cards): bool
    {
        for ($i = 1; $i < count($cards); $i++) {
            if (intdiv($cards[0], 10) != intdiv($cards[$i], 10))
                return false;
        }
        return $player->checkCards($cards);
    }

    /**
     * @param bool $win_condition
     * @throws GameException
     *
     * Change the current round
     * and it is decided whether a round has ended or not
     */
    protected function nextPlayer(bool $win_condition)
    {
        $current_player = array_splice($this->round, 0, 1);
        if (!$win_condition || count($this->deck) > count($this->round))
            $this->round = array_merge($this->round, $current_player);
        $details = $this->getDetails();

        // all players put a card on the table or drew one
        if ($this->getRound() == $details['current_start']) {
            // if the round is over and the players have taken cards, we start a new round
            if (isset($details['round_done']) && $details['round_done'] == true) {
                $details['round_done'] = false;
                $this->setCards(array());
                $details['current_start'] = $details['next_start'];

                $this->setStartPlayer($details['next_start']);
                $this->setDetails($details);
                // otherwise we end this round
            } elseif ($this->getRound() == $details['next_start']) {
                $this->end_round();
            }
        } else {
            if (isset($details['round_done']) && $details['round_done'] == true && count($this->deck) == 0) {
                $this->nextPlayer(false);
                return;
            }
        }

        $player = new Player();
        $player->readOne($this->getRound());
        $details = $this->getDetails();
        if (count($this->deck) == 0 && $details['round_done'] == false) {
            $end_game = $details['total_cards'] - $details['played_cards'] == 0;
            if ($details['total_cards'] - $details['played_cards'] == 1) {
                if (count($this->getCards()) == 0)
                    $end_game = true;
                else {
                    $special_situation = $details['current_start'] != $details['next_start'] && count($player->getCards()) == 0;
                    $special_situation2 = $this->getRound() == $details['current_start'];
                    $special_card = 0;
                    if(count($player->getCards()) != 0)
                        $special_card = intdiv($player->getCards()[0], 10);
                    $first_card = intdiv($this->cards[count($this->cards) - 1], 10);

                    $end_game = $special_situation;
                    $end_game = $end_game || ($special_situation2 && $special_card != 7 && $special_card != $first_card);
//                $end_game = $end_game || ($special_situation2 && $special_card == 8 && $details['opt_taie']);
                }
            }
            if ($end_game) {
                $details['round_done'] = true;
                if (!isset($details['rank']))
                    $details['rank'] = array(array('id' => $player->getId(), 'name' => $player->getName()));
                else array_push($details['rank'], array('id' => $player->getId(), 'name' => $player->getName()));
                $this->setDetails($details);
                if (count($this->getCards()) != 0)
                    $this->end_round();
                array_splice($this->round, 0, 1);
            }
        }
    }

    /**
     * We end this round and count the points won
     */
    public function end_round($from_take_cards = false)
    {
        $details = $this->getDetails();
        $details['round_done'] = true;
        $points = 0;
        foreach ($this->getCards() as $card) {
            if (intdiv($card, 10) == 10 || intdiv($card, 10) == 1)
                $points = $points + 1;
        }
        if (!isset($details['points']))
            $details['points'] = array();
        if (!isset($details['points'][$details['next_start']]))
            $details['points'][$details['next_start']] = $points;
        else $details['points'][$details['next_start']] += $points;
        // if there are no more cards in the pack,
        // we will skip the round in which the cards are drawn
        if ($this->getDeckCount() == 0) {
            $details['round_done'] = false;
            $this->setCards(array());
            $details['current_start'] = $details['next_start'];
            if (!$from_take_cards)
                $this->setStartPlayer($details['next_start']);
        }
        $this->setDetails($details);
    }

    /**
     * @param $id - player id
     * We set the player with id = $id as current player
     */
    private function setStartPlayer($id)
    {
        if ($this->getRound() != $id) {
            $key = array_search($id, $this->round);
            if ($key > 0) {
                $players_slice = array_splice($this->round, 0, $key);
                $this->round = array_merge($this->round, $players_slice);
            }
        }
    }

    /**
     * @param int $count - the amount of playing cards
     * @return array - list of drawn cards
     *
     * if there are not enough cards left in the deck,
     * we will distribute them equally
     */
    public function takeCards(int $count): array
    {
        if ($count == 1)
            return array_splice($this->deck, 0, $count);

        $details = $this->getDetails();
        $i = 0;
        if ($this->round[1] == $details['current_start'])
            $i = $this->getPlayerCount() - 1;
        elseif ($this->getPlayerCount() > 2 && $this->round[2] == $details['current_start'])
            $i = $this->getPlayerCount() - 2;
        $per_user = ceil(count($this->deck) / (count($this->round) - $i));
        if ($count <= $per_user)
            return array_splice($this->deck, 0, $count);
        else
            return array_splice($this->deck, 0, $per_user);
    }

}