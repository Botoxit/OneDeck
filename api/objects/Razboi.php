<?php

include_once 'Game.php';
include_once 'Player.php';

class Razboi extends Game
{
    /**
     * @param Player $player
     * @throws GameException
     */
    public function new_game(Player $player)
    {

        $deck = array();
        for ($i = 1; $i < 14; $i++) {
            $num = $i * 10;
            for ($j = 1; $j < 5; $j++)
                array_push($deck, $num + $j);
        }
        shuffle($deck);
        $this->setDeck($deck);

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

        $details = $this->getDetails();
        if (!empty($details['rank'])) {
            $key = array_search($details['rank'][0]['id'], $round);
            if ($key > 0) {
                $players_slice = array_splice($round, 0, $key);
                $round = array_merge($round, $players_slice);
            }
        }
        unset($details['rank']);
        $this->setDetails($details);

        $this->setRound($round);
        $this->setCards(array());
    }

    public function split_deck($count)
    {
        return array_splice($this->deck, 0, $count);
    }

    /**
     * @param Player $player
     * @param array $cards_array
     * @throws GameException
     */
    public function nextCard(Player $player, array $cards_array)
    {
        $table_cards = $this->getCards();

        $details = $this->getDetails();
        if (isset($details['round_done']) && $details['round_done'] == true) {
            unset($details['inWar']);
            $table_cards = array();
            $details['round_done'] = false;
        }

        if (!isset($table_cards[$player->getId()]))
            $table_cards[$player->getId()] = array();
        foreach ($cards_array as $card)
            array_unshift($table_cards[$player->getId()], $card);
        $round_done = true;
        $biggest_card = -1;
        $winner_id = -1;
        if (count($table_cards) == $this->getPlayerCount() && (!isset($details['inWar']) || count($details['inWar']) == 0)) {
            foreach ($table_cards as $id_player => $cards) {
                if (count($cards) == 0) {
                    $round_done = false;
                    break;
                }
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

    public function getPlayerCard($id_player): int
    {
        $table_cards = $this->getCards();
        if (isset($table_cards[$id_player]))
            return $table_cards[$id_player][0];
        return 0;
    }

    protected function nextPlayer(bool $done_cards)
    {
        $current_player = array_splice($this->round, 0, 1);
        $details = $this->getDetails();
        $player = new Player();
        $player->readOne($current_player[0]);
        if (count($player->getCards()) > 0 || !isset($details['round_done']) || $details['round_done'] == false) {
            $this->round = array_merge($this->round, $current_player);
        } else {
            if (!isset($details['rank']))
                $details['rank'] = array(array('id' => $player->getId(), 'name' => $player->getName()));
            else array_push($details['rank'], array('id' => $player->getId(), 'name' => $player->getName()));
            $this->setDetails($details);
        }
        if (isset($details['round_done']) && $details['round_done'] == true) {
            $player->readOne($this->getRound());
            if ($this->getRound() > 0 && count($player->getCards()) == 0)
                $this->nextPlayer(false);
        }

        if (isset($details['isWar']) && count($details['isWar']) > 0 && !in_array($this->getRound(), $details['isWar'])) {
            $this->nextPlayer(false);
        }
    }


}