<?php
/**
 * User: Nicu Neculache
 * Date: 22.04.2019
 * Time: 20:26
 */
include_once 'Game.php';
include_once 'Player.php';

class Macao extends Game
{
    // {"takeCards_color":0,"waitCard":4,"waitCard_color":1,"changeSymbol":1,"changeSymbol_color":0,"stopCard":7,"stopCard_color":0,"deck":1,"stop_wait":1}
    public function verify(array $cards, $symbol)
    {
        $tableCard = $this->getFirstTableCard();
        $details = $this->getDetails();
        $rules = $this->getRules();

        if (!empty($details['changeSymbol'])) {
            $tableSymbol = $details['changeSymbol'];
            unset($details['changeSymbol']);
        } else $tableSymbol = $tableCard % 10;

        // If I need to take cards
        if (!empty($details['takeCards'])) {
//            if ((intdiv($cards[0], 10) == intdiv($tableCard, 10)) || ($tableCard > 4 && $cards[0] > 4)) {
//                if ($cards[0] == 5 || $cards[0] == 6) {
//                    $takeCards = 5 + (5 * ($cards[0] % 2));
//                } else $takeCards = intdiv($cards[0], 10) * count($cards);
//
//                $details['takeCard'] = $details['takeCard'] + $takeCards;
//                $this->setDetails($details);
//                $this->addCards($cards);
//                return true;
//            }
            if (($cards[0] > 20 && $cards[0] < 40) || $cards[0] == 5 || $cards[0] == 6) {
                if ($rules['takeCards_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
                if ($cards[0] == 5 || $cards[0] == 6) {
                    if (count($cards) == 2)
                        $takeCards = 15;
                    else $takeCards = 5 + (5 * ($cards[0] % 2));
                } else $takeCards = intdiv($cards[0], 10) * count($cards);
                $details['takeCards'] = $details['takeCards'] + $takeCards;
                $this->setDetails($details);
                $this->addCards($cards);
                return true;
            }
            if (!$this->checkStop($rules, $cards, $tableSymbol))
                return false;
            unset($details['takeCards']);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }

        // If I need to wait
        if (!empty($details['wait'])) {
            if (intdiv($cards[0], 10) == $rules['waitCard']) {
//                if ($rules['waitCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
//                    return false;
                $details['wait'] = $details['wait'] + count($cards);
                $this->setDetails($details);
                $this->addCards($cards);
                return true;
            }
            if (!$rules['stop_wait'] || !$this->checkStop($rules, $cards, $tableSymbol))
                return false;
            unset($details['wait']);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }


        if ((($cards[0] > 20) && ($cards[0] < 40)) || $cards[0] == 5 || $cards[0] == 6) {
            if ((intdiv($cards[0], 10) != intdiv($tableCard, 10))){// || ($tableCard > 4 && $cards[0] > 4)) {
                if ($rules['takeCards_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            if ($cards[0] == 5 || $cards[0] == 6) {
                if (count($cards) == 2)
                    $takeCards = 15;
                else $takeCards = 5 + (5 * ($cards[0] % 2));
            } else $takeCards = intdiv($cards[0], 10) * count($cards);
            $details['takeCards'] = $takeCards;
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif (intdiv($cards[0], 10) == $rules["changeSymbol"]) {
            if ($rules["changeSymbol"] != intdiv($tableCard, 10)) {
                if ($rules['changeSymbol_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            if ($symbol > 0)
                $details['changeSymbol'] = $symbol;
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif (intdiv($cards[0], 10) == $rules["waitCard"]) {
            if ($rules["waitCard"] != intdiv($tableCard, 10)) {
                if ($rules['waitCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            $details['wait'] = count($cards);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif ($rules["stopCard"] > 0 && intdiv($cards[0], 10) == $rules["stopCard"]) {
            if ($rules["stopCard"] != intdiv($tableCard, 10)) {
                if ($rules['stopCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }
        if (intdiv($cards[0], 10) != intdiv($tableCard, 10)) {
            if (!$this->checkSymbol($cards, $tableSymbol))
                return false;
        }
        $this->setDetails($details);
        $this->addCards($cards);
        return true;
    }

    private function checkStop($rules, $cards, $tableSymbol)
    {
        if ($rules["stopCard"] > 0 && intdiv($cards[0], 10) == $rules["stopCard"]) {
            if (!$rules['stopCard_color'])
                return true;
            return $this->checkSymbol($cards, $tableSymbol);
        }
        return false;
    }

    /**
     * @param Player $player
     * @param array $cards
     * @return bool
     */
    public function checkCards(Player $player, array $cards)
    {
        for ($i = 1; $i < count($cards); $i++) {
            if (intdiv($cards[0], 10) != intdiv($cards[$i], 10))
                return false;
        }
        return $player->checkCards($cards);
    }

    private function checkSymbol(array $cards, $tableSymbol)
    {
        for ($i = count($cards) - 1; $i >= 0; $i--) {
            $match = false;
            if (($cards[$i] == 5 || $cards[$i] == 6) && ($tableSymbol % 2 == $cards[$i] % 2))
                $match = true;
            else {
                $card_symbol = $cards[$i] % 10;
                if ($card_symbol == $tableSymbol)
                    $match = true;
                elseif (($tableSymbol == 5 || $tableSymbol == 6) && ($tableSymbol % 2 == $card_symbol % 2))
                    $match = true;
            }
            if ($match) {
                if ($i == 0 && count($cards) > 1) {
                    return false;
//                    $aux = $cards[1];
//                    $cards[1] = $cards[0];
//                    $cards[0] = $aux;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param Player $player
     * @throws GameException
     */
    public function new_game(Player $player) // castigatorul incepe!!!!!!!!!!!!!!!!!!
    {
        $deck = array(5, 6);
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
//            $player->setCards($this->takeCards(5));
            $player->setCards(array());
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
        $this->setDetails(array('new_game' => $this->getPlayerCount()));
        $this->addCards($this->takeCards(1));
    }
}