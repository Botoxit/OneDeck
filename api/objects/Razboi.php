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

        $this->setRound($round);
        $this->setCards(array());
    }

    public function split_deck($count)
    {
        return array_splice($this->deck, 0, $count);
    }

    /**
     * @param Player $player
     * @param int $card
     * @throws GameException
     */
    public function nextCard(Player $player, int $card)
    {
        $table_cards = $this->getCards();

        $details = $this->getDetails();
        if(isset($details['round_done']) && $details['round_done'] == true) {
            $table_cards = array();
            $details['round_done'] = false;
        }

        if (!isset($table_cards[$player->getId()]))
            $table_cards[$player->getId()] = array();
        array_unshift($table_cards[$player->getId()], $card);
        $round_done = true;
        $biggest_card = -1;
        $winner_id = -1;
        if (count($table_cards) == $this->getPlayerCount()) {
            foreach ($table_cards as $id_player => $cards) {
                if (count($cards) == 0) {
                    $round_done = false;
                    break;
                }
                if (intdiv($cards[0], 10) == 1) {
                    $biggest_card = 1;
                    $winner_id = $id_player;
                } else {
                    if ($biggest_card != 1 && intdiv($cards[0], 10) > $biggest_card) {
                        $biggest_card = intdiv($cards[0], 10);
                        $winner_id = $id_player;
                    }
                }
            }
        } else $round_done = false;

        if ($round_done == true) {
            $all_cards = array();
            foreach ($table_cards as $cards) {
                $all_cards = array_merge($all_cards, $cards);
            }
            if($winner_id == $player->getId())
            {
                $player->addCards($all_cards);
            }
            else {
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
    public function isWar()
    {
        $cards = $this->getCards();
        $total_cards_down = count($cards);
        for ($i = 0; $i < $total_cards_down - 1; $i++) {
            for ($j = $i + 1; $j < $total_cards_down; $j++) {
                if (intdiv($cards[$i], 10) == intdiv($cards[$j], 10)) {
                    return true;
                }
            }
        }
        return false;
    }
}