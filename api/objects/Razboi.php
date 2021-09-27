<?php

include_once 'Game.php';
include_once 'Player.php';

class Razboi extends Game
{
    /**
     * @param Player $player
     * @throws GameException
     *
     * Initializing a new game
     */
    public function newGame(Player $player)
    {
        // Generate deck
        $deck = $this->makeDeck(false, true);
        $this->setDeck($deck);

        // player list initialization and distribute the cards to them
        $round = [];
        $players_list = $player->readAll($player->getIdTable());
        if ($players_list->num_rows == 0)
            die(json_encode(array("status" => -1, "message" => "Unable to read players.")));
        while ($row = $players_list->fetch_assoc()) {
            $player->readOne($row['id']);
            $player->setCards($this->split_deck(intdiv(count($this->deck), $players_list->num_rows - count($round))));
            $player->update();

            array_push($round, $row['id']);
        }

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
        unset($details['rank']);
        $this->setDetails($details);

        $this->setRound($round);
        $this->setCards(array());

        if ($this->getId() < 5 && $this->getRound() == $this->getId()) {
            parent::nextPlayer(false);
        }
    }

    /**
     * @param $count
     * @return array
     *
     * Take cards out of the deck
     */
    public function split_deck($count): array
    {
        return array_splice($this->deck, 0, $count);
    }

    /**
     * @param Player $player
     * @param array $cards_array - cards drawn by the player
     * @throws GameException
     *
     * Placing the cards on the table and checking the condition of the war
     */
    public function nextCard(Player $player, array $cards_array)
    {
        $table_cards = $this->getCards();

        // a new round begins?
        $details = $this->getDetails();
        if (isset($details['round_done']) && $details['round_done'] == true) {
            unset($details['inWar']);
            $table_cards = array();
            $details['round_done'] = false;
        }

        // placing the cards on the table
        if (!isset($table_cards[$player->getId()]))
            $table_cards[$player->getId()] = array();
        foreach ($cards_array as $card)
            array_unshift($table_cards[$player->getId()], $card);

        // check if the round ends
        $round_done = true;
        $biggest_card = -1;
        $winner_id = -1;
        // if all players have placed cards on the table and it is not war
        if (count($table_cards) == $this->getPlayerCount() && (!isset($details['inWar']) || count($details['inWar']) == 0)) {
            // check who takes the playing cards from the table
            foreach ($table_cards as $id_player => $cards) {
                if (count($cards) == 0) {
                    $round_done = false;
                    break;
                }
                // 'A' is the most valuable playing card
                if (intdiv($cards[0], 10) == 1) {
                    if ($biggest_card == 1) {
                        if (!isset($details['inWar']))
                            $details['inWar'] = array($winner_id, $id_player);
                        else array_push($details['inWar'], $id_player);
                        $round_done = false;
                    } else {
                        unset($details['inWar']);
                        $round_done = true;
                    }
                    $biggest_card = 1;
                    $winner_id = $id_player;
                } else {
                    // who has the biggest card
                    if ($biggest_card == 1)
                        continue;
                    if (intdiv($cards[0], 10) > $biggest_card) {
                        $biggest_card = intdiv($cards[0], 10);
                        $winner_id = $id_player;
                        unset($details['inWar']);
                        $round_done = true;
                    } elseif (intdiv($cards[0], 10) == $biggest_card) {
                        if (!isset($details['inWar']))
                            $details['inWar'] = array($winner_id, $id_player);
                        else array_push($details['inWar'], $id_player);
                        $round_done = false;
                    }
                }
            }
        } else $round_done = false;

        // if the round is over, we put the cards in the winning player's deck
        if ($round_done == true) {
            $all_cards = array();
            foreach ($table_cards as $cards) {
                $all_cards = array_merge($all_cards, $cards);
            }
            if ($winner_id == $player->getId()) {
                $player->addCards($all_cards);
            } else {
                $winner = new Player();
                $winner->readOne($winner_id);
                $winner->addCards($all_cards);
                $winner->update();
            }
            $details['round_done'] = true;
        }
        $this->setDetails($details);
        $this->setCards($table_cards);
    }

    /**
     * @return bool
     */
    public function isWar(): bool
    {
        $details = $this->getDetails();
        if (!isset($details['inWar']))
            return false;
        return true;
    }

    /**
     * @param $id_player
     * @return int
     *
     * Which is the most recent card used by a player
     */
    public function getPlayerCard($id_player): int
    {
        $table_cards = $this->getCards();
        if (isset($table_cards[$id_player]))
            return $table_cards[$id_player][0];
        return 0;
    }

    /**
     * @param bool $win_condition
     * @throws GameException
     *
     * Change the current round
     * if there is a war and the next player
     * does not participate, we pass him
     */
    protected function nextPlayer(bool $win_condition)
    {
        $current_player = array_splice($this->round, 0, 1);
        $details = $this->getDetails();
        $player = new Player();
        $player->readOne($current_player[0]);
        // if the player still has cards or the turn has not ended, we add it at the end
        if (count($player->getCards()) > 0 || !isset($details['round_done']) || $details['round_done'] == false) {
            $this->round = array_merge($this->round, $current_player);
        } else {
            // if he finished the cards we add him to the ranking
            if (!isset($details['rank']))
                $details['rank'] = array(array('id' => $player->getId(), 'name' => $player->getName()));
            else array_push($details['rank'], array('id' => $player->getId(), 'name' => $player->getName()));
            $this->setDetails($details);
        }
        // if the round is over and the next player has no more cards, we pass him
        if (isset($details['round_done']) && $details['round_done'] == true) {
            $player->readOne($this->getRound());
            if ($this->getRound() > 0 && count($player->getCards()) == 0)
                $this->nextPlayer(false);
        }
        // if there is a war and the next player does not participate, we pass him
        if (isset($details['isWar']) && count($details['isWar']) > 0 && !in_array($this->getRound(), $details['isWar'])) {
            $this->nextPlayer(false);
        }
        if ($this->getPlayerCount() > 1 && $this->getId() < 5 && $this->getRound() == $this->getId()) {
            $this->boot();
            $this->nextPlayer(false);
        }
    }

    public function boot()
    {
        $details = $this->getDetails();

        $boot = new Player();
        $boot->readOne($this->getId());

        $boot_cards = $boot->getCards();
        $win = false;

        if ($this->isWar()) {
            if (!in_array($boot->getId(), $details['inWar']))
                return;
            unset($details['inWar'][array_search($boot->getId(), $details['inWar'])]);
            $this->setDetails($details);
            $count = intdiv($this->getPlayerCard($boot->getId()), 10);
            if ($count <= 0)
                return;
            $cards = array_splice($boot_cards, 0, $count);
        } else {
            $cards = array($boot_cards[0]);
        }
        $boot->removeCards($cards);
        $this->nextCard($boot, $cards);

        $boot->update();
        return;
    }

    public function deletePlayer(Player $player)
    {
        parent::deletePlayer($player);

        if ($this->getPlayerCount() > 1 && $this->getId() < 5 && $this->getRound() == $this->getId()) {
            $razboi = new Razboi();
            $razboi->setter($this->getId(), $this->getCards(), $this->round, $this->deck, $this->getDetails(), $this->getRules());
            $razboi->nextPlayer($razboi->boot());
            $this->setRound($razboi->getRoundArray());
            $this->setCards($razboi->getCards());
            $this->setDeck($razboi->getDeck());
            $this->setDetails($razboi->getDetails());
        }
    }
}