<?php

include_once 'Game.php';
include_once 'Player.php';

class Septica extends Game
{
    /**
     * @param Player $player
     * @throws GameException
     */
    public function new_game(Player $player)
    {
        $round = [];
        $players_list = $player->readAll($player->getIdTable());
        if ($players_list->num_rows == 0)
            die(json_encode(array("status" => -1, "message" => "Unable to read players.")));
        while ($row = $players_list->fetch_assoc()) {
            $player->readOne($row['id']);
//            $player->setCards($this->takeCards(4));
            $player->setCards(array());
            $player->update();

            array_push($round, $row['id']);
        }

        $details = $this->getDetails();

        $deck = array(11, 12, 13, 14, 71, 72, 73, 74, 81, 82);
        if (count($round) < 3) {
            array_push($deck, 83, 84);
            $details['opt_taie'] = false;
        } else $details['opt_taie'] = true;
        for ($i = 9; $i < 14; $i++) {
            $num = $i * 10;
            for ($j = 1; $j < 5; $j++)
                array_push($deck, $num + $j);
        }
        shuffle($deck);
        $this->setDeck($deck);

        $round = array_values($round);
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
        unset($details['kick']);
        $details['round_done'] = true;
        $details['new_game'] = count($round);
        $details['current_start'] = $round[0];
        $details['next_start'] = $round[0];
        $this->setDetails($details);

        $this->setRound($round);
        $this->setCards(array());
    }

    public function verify(int $player_id, int $card): bool
    {
        $table_cards = $this->getCards();
        $details = $this->getDetails();

        if (count($table_cards) == 0) {
            $details['current_start'] = $player_id;
            $details['next_start'] = $player_id;
        } else {
            $first_card = $table_cards[count($table_cards) - 1];
            $switch_start_player = intdiv($card, 10) == intdiv($first_card, 10);
            $switch_start_player = $switch_start_player || intdiv($card, 10) == 7;
            if ($details['opt_taie'] == true)
                $switch_start_player = $switch_start_player || intdiv($card, 10) == 8;
            if ($switch_start_player) {
                $details['next_start'] = $player_id;
            } elseif ($player_id == $details['current_start'])
                return false;
        }
        $this->addCards(array($card));
        $this->setDetails($details);
        return true;
    }

    /**
     * @param Player $player
     * @param array $cards
     * @return bool
     */
    public function checkCards(Player $player, array $cards): bool
    {
        for ($i = 1; $i < count($cards); $i++) {
            if (intdiv($cards[0], 10) != intdiv($cards[$i], 10))
                return false;
        }
        return $player->checkCards($cards);
    }

    protected function nextPlayer(bool $done_cards)
    {
        $current_player = array_splice($this->round, 0, 1);
        if(!$done_cards || count($this->deck) > count($this->round))
            $this->round = array_merge($this->round, $current_player);
        $details = $this->getDetails();

        if ($this->getRound() == $details['current_start']) {
            if ($this->getRound() == $details['next_start']) {
                $this->end_round();
            } elseif (isset($details['round_done']) && $details['round_done'] == true) {
                $details['round_done'] = false;
                $this->setCards(array());
                $details['current_start'] = $details['next_start'];
                $key = array_search($details['next_start'], $this->round);
                if ($key > 0) {
                    $players_slice = array_splice($this->round, 0, $key);
                    $this->round = array_merge($this->round, $players_slice);
                }
                $this->setDetails($details);
            }
        }
    }

    public function end_round()
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
        if (!isset($details['points'][$this->getRound()]))
            $details['points'][$this->getRound()] = $points;
        else $details['points'][$this->getRound()] += $points;
        $this->setDetails($details);
    }

    public function takeCards(int $count, bool $firstCard = false): array
    {
        if ($count == 1)
            return array_splice($this->deck, 0, $count);
        $per_user = intdiv(count($this->deck), count($this->round));
        if ($count <= $per_user)
            return array_splice($this->deck, 0, $count);
        else
            return array_splice($this->deck, 0, $per_user);
    }


}