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
    public function verify(array $cards, $symbol = 0)
    {
        $tableCard = $this->getFirstTableCard();
        $details = $this->getDetails();

        if (!empty($details['changeSymbol'])) {
            $tableSymbol = $details['changeSymbol'];
            unset($details['changeSymbol']);
        } else $tableSymbol = $tableCard % 10;

        // If I need to take cards
        if (!empty($details['takeCard'])) {
            if ((intdiv($cards[0], 10) == intdiv($tableCard, 10)) || ($tableCard > 4 && $cards[0] > 4)) {
                if ($cards[0] == 5 || $cards[0] == 6) {
                    $takeCards = 5 + (5 * ($cards[0] % 2));
                } else $takeCards = intdiv($cards[0], 10) * count($cards);

                $details['takeCard'] = $details['takeCard'] + $takeCards;
                $this->setDetails($details);
                $this->addCards($cards);
                return true;
            }
            if (($cards[0] > 20 && $cards[0] < 40) || $cards[0] == 5 || $cards[0] == 6) {
                if ($_SESSION['takeCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
                if ($cards[0] == 5 || $cards[0] == 6) {
                    if (count($cards) == 2)
                        $takeCards = 15;
                    else $takeCards = 5 + (5 * ($cards[0] % 2));
                } else $takeCards = intdiv($cards[0], 10) * count($cards);
                $details['takeCard'] = $details['takeCard'] + $takeCards;
                $this->setDetails($details);
                $this->addCards($cards);
                return true;
            }
            if (!$this->checkStop($cards, $tableSymbol))
                return false;
            unset($details['takeCard']);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }

        // If I need to wait
        if (!empty($details['wait'])) {
            if (intdiv($cards[0], 10) == $_SESSION['waitCard']) {
                if ($_SESSION['waitCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
                $details['wait'] = $details['wait'] + count($cards);
                $this->setDetails($details);
                $this->addCards($cards);
                return true;
            }
            if (!$this->checkStop($cards, $tableSymbol))
                return false;
            unset($details['wait']);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }


        if ((($cards[0] > 20) && ($cards[0] < 40)) || $cards[0] == 5 || $cards[0] == 6) {
            if ((intdiv($cards[0], 10) != intdiv($tableCard, 10)) || ($tableCard > 4 && $cards[0] > 4)) {
                if ($_SESSION['takeCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            if ($cards[0] == 5 || $cards[0] == 6) {
                if (count($cards) == 2)
                    $takeCards = 15;
                else $takeCards = 5 + (5 * ($cards[0] % 2));
            } else $takeCards = intdiv($cards[0], 10) * count($cards);
            $details['takeCard'] = $takeCards;
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif (intdiv($cards[0], 10) == $_SESSION["changeSymbol"]) {
            if ($_SESSION["changeSymbol"] != intdiv($tableCard, 10)) {
                if ($_SESSION['changeSymbol_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            if ($symbol > 0)
                $details['changeSymbol'] = $symbol;
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif (intdiv($cards[0], 10) == $_SESSION["waitCard"]) {
            if ($_SESSION["waitCard"] != intdiv($tableCard, 10)) {
                if ($_SESSION['waitCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            $details['wait'] = count($cards);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif ($_SESSION["stopCard"] > 0 && intdiv($cards[0], 10) == $_SESSION["stopCard"]) {
            if ($_SESSION["stopCard"] != intdiv($tableCard, 10)) {
                if ($_SESSION['stopCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            $this->addCards($cards);
            return true;
        }
        if (intdiv($cards[0], 10) != intdiv($tableCard, 10)) {
            if (!$this->checkSymbol($cards, $tableSymbol))
                return false;
        }
        $this->addCards($cards);
        return true;
    }

    private function checkStop($cards, $tableSymbol)
    {
        if ($_SESSION["stopCard"] > 0 && intdiv($cards[0], 10) == $_SESSION["stopCard"]) {
            if (!$_SESSION['stopCard_color'])
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
                    $aux = $cards[1];
                    $cards[1] = $cards[0];
                    $cards[0] = $aux;
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
    public function new_game(Player $player)
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
        if (!$players_list)
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
            $key = array_search($details['rank'][0], $round);
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