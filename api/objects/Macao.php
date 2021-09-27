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

    // {"takeCards_color":0,"waitCard":4,"waitCard_color":1,"changeSymbol":1,"changeSymbol_color":0,"stopCard":7,"stopCard_color":0,"deck":1,"stop_wait":1}
    /**
     * @param array $cards - the list of playing cards to be checked
     * @param $symbol - in which symbol player want to change
     * @return bool
     *
     * Check if the cards and the current state of the game
     * follow the rules
     */
    public function verify(array $cards, $symbol): bool
    {
        $tableCard = $this->getFirstTableCard();
        $details = $this->getDetails();
        $rules = $this->getRules();

        // what symbol player need to use
        if (!empty($details['changeSymbol'])) {
            $tableSymbol = $details['changeSymbol'];
            unset($details['changeSymbol']);
        } else $tableSymbol = $tableCard % 10;

        // If player needs to take cards
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
            // player wants to use also a special card which forces players to draw cards
            if (($cards[0] > 20 && $cards[0] < 40) || $cards[0] == 5 || $cards[0] == 6) {
                // it matters what symbol the playing cards have? if so, check them out
                if ($rules['takeCards_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
                // calculate how many cards the next player has to draw
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
            // we check if the rules allow the cancellation of special carts
            // and check the player's cards if they follow the rules
            if (!$this->checkStop($rules, $cards, $tableSymbol))
                return false;
            unset($details['takeCards']);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }

        // If player needs to wait
        if (!empty($details['toWait'])) {
            // player wants to use also a special card which forces players to wait
            if (intdiv($cards[0], 10) == $rules['waitCard']) {
                // it matters what symbol the playing cards have? if so, check them out
//                if ($rules['waitCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
//                    return false;
                // calculate how many turns the next player has to wait
                $details['toWait'] = $details['toWait'] + count($cards);
                $this->setDetails($details);
                $this->addCards($cards);
                return true;
            }
            // we check if the rules allow the cancellation of special carts
            // and check the player's cards if they follow the rules
            if (!$rules['stop_wait'] || !$this->checkStop($rules, $cards, $tableSymbol))
                return false;
            unset($details['toWait']);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }

        // player wants to use a special card which forces players to draw cards
        if ((($cards[0] > 20) && ($cards[0] < 40)) || $cards[0] == 5 || $cards[0] == 6) {
            // it matters what symbol the playing cards have? if so, check them out
            if ((intdiv($cards[0], 10) != intdiv($tableCard, 10))) {
                if ($rules['takeCards_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            // calculate how many cards the next player has to draw
            if ($cards[0] == 5 || $cards[0] == 6) {
                if (count($cards) == 2)
                    $takeCards = 15;
                else $takeCards = 5 + (5 * ($cards[0] % 2));
            } else $takeCards = intdiv($cards[0], 10) * count($cards);
            $details['takeCards'] = $takeCards;
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        // player wants to use a special card which changes the next symbol to be used
        } elseif (intdiv($cards[0], 10) == $rules["changeSymbol"]) {
            // it matters what symbol the playing cards have? if so, check them out
            if ($rules["changeSymbol"] != intdiv($tableCard, 10)) {
                if ($rules['changeSymbol_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            if ($symbol > 0)
                $details['changeSymbol'] = $symbol;
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        // player wants to use a special card which forces players to wait
        } elseif (intdiv($cards[0], 10) == $rules["waitCard"]) {
            // it matters what symbol the playing cards have? if so, check them out
            if ($rules["waitCard"] != intdiv($tableCard, 10)) {
                if ($rules['waitCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            // set how many turns the next player has to wait
            $details['toWait'] = count($cards);
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        } elseif ($rules["stopCard"] > 0 && intdiv($cards[0], 10) == $rules["stopCard"]) {
            // it matters what symbol the playing cards have? if so, check them out
            if ($rules["stopCard"] != intdiv($tableCard, 10)) {
                if ($rules['stopCard_color'] && !$this->checkSymbol($cards, $tableSymbol))
                    return false;
            }
            $this->setDetails($details);
            $this->addCards($cards);
            return true;
        }
        // if the player uses a regular card then check if it has the right symbol
        if (intdiv($cards[0], 10) != intdiv($tableCard, 10)) {
            if (!$this->checkSymbol($cards, $tableSymbol))
                return false;
        }
        $this->setDetails($details);
        $this->addCards($cards);
        return true;
    }

    /**
     * @param $rules - rules list
     * @param $cards - the list of playing cards to be checked
     * @param $tableSymbol - what symbol should be used
     * @return bool
     *
     * Check if the rules allow the cancellation of special carts
     * Check if player's cards have the right value
     * and if it matters what symbol the playing cards have? if so, check them out
     */
    private function checkStop($rules, $cards, $tableSymbol): bool
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
     * @param array $cards - the list of playing cards to be checked
     * @param $tableSymbol - what symbol should be used
     * @return bool
     *
     * check if there is at least one card with the correct symbol
     */
    private function checkSymbol(array $cards, $tableSymbol): bool
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
     *
     * Initializing a new game
     */
    public function newGame(Player $player)
    {
        // Generate deck
        $deck = $this->makeDeck(true,true);
        $this->setDeck($deck);

        // player list initialization
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

        // the winner of the last game will start the new one
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


        $this->setRound($round);
        $this->setDetails(array('new_game' => $this->getPlayerCount()));

        if ($this->getId() < 5 && $this->getRound() == $this->getId()) {
            $this->nextPlayer($this->boot());
        }

        $this->setCards($this->chooseFirstCard());
    }

    /**
     * @param bool $win
     *
     * Change the current round
     * if the next player has to wait, we pass him
     */
    protected function nextPlayer(bool $win)
    {
        parent::nextPlayer($win);
        if (isset($this->details['waiting']) && isset($this->details['waiting'][$this->getRound()])) {
            if ($this->details['waiting'][$this->getRound()] > 1)
                $this->details['waiting'][$this->getRound()] -= 1;
            else unset($this->details['waiting'][$this->getRound()]);
            $this->nextPlayer(false);
        }
        if ($this->getPlayerCount() > 1 && $this->getId() < 5 && $this->getRound() == $this->getId())
            $this->nextPlayer($this->boot());
    }
    
    private function chooseFirstCard()
    {
        $invalidFirst = array(5, 6, 21, 22, 23, 24, 31, 32, 33, 34);
        if (array_search($this->deck[0], $invalidFirst) !== false) {
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
        else return parent::takeCards(1);
    }

    /**
     * @param int $count
     * @return array - list of drawn cards
     *
     * if there are not enough playing cards in the deck,
     * we shuffle the cards from table
     * and call the parent class function
     */
    public function takeCards(int $count): array
    {
        if ($count > count($this->deck)) {
            $this->deck = array_merge($this->deck, array_splice($this->cards, 1));
            if (!shuffle($this->deck))
                Debug::Log("Shuffle deck + old cards failed", __FILE__, "WARNING");
        }
        return parent::takeCards($count);
    }

    public function boot()
    {
        $details = $this->getDetails();

        if (!empty($details['changeSymbol'])) {
            $tableSymbol = $details['changeSymbol'];
            unset($details['changeSymbol']);
        } else $tableSymbol = $this->getFirstTableCard() % 10;
        $tableNumber = intdiv($this->getFirstTableCard(), 10);

        $boot = new Player();
        $boot->readOne($this->getId());

        $cards = $boot->getCards();

        $stop_cards = [];
        $take_cards = [];
        $switch_cards = [];
        $wait_cards = [];
        $other_cards = [];

        $valid_cards = [];

        foreach ($cards as $card) {
            switch (intdiv($card, 10)) {
                default:
                    array_push($other_cards, $card);
                    break;
                case 0:
                case 2:
                case 3:
                    array_push($take_cards, $card);
                    break;
                case 1:
                    array_push($switch_cards, $card);
                    break;
                case 4:
                    array_push($wait_cards, $card);
                    break;
                case 7:
                    array_push($stop_cards, $card);
            }
        }

        // If boot needs to take cards
        if (!empty($details['takeCards'])) {
            if (count($take_cards) > 0) {
                array_push($valid_cards, $take_cards[0]);
                for ($i = 1; $i < count($take_cards); $i++) {
                    if (intdiv($take_cards[$i], 10) == intdiv($take_cards[0], 10))
                        array_push($valid_cards, $take_cards[$i]);
                }
            } else $valid_cards = $this->bootStopCards($stop_cards, count($cards));
        } else {
            // If boot needs to wait
            if (!empty($details['toWait'])) {
                if (count($wait_cards) > 0) {
                    $valid_cards = $wait_cards;
                } else {
                    $valid_cards = $this->bootStopCards($stop_cards, count($cards));
                    if (empty($valid_cards)) { // wait
                        if ($details['toWait'] > 1) {
                            if (!isset($details['waiting']))
                                $details['waiting'] = array();
                            $details['waiting'][$boot->getId()] = $details['toWait'] - 1;
                            unset($details['toWait']);
                        } else unset($details['toWait']);
                        $this->setDetails($details);
                        return false;
                    }
                }
            } else {
                $other_cards = array_merge($other_cards, $wait_cards);
                $frequency = [];
                for ($i = 0; $i < count($other_cards); $i++) {
                    if (empty($frequency[intdiv($other_cards[$i], 10)]))
                        $frequency[intdiv($other_cards[$i], 10)] = array($other_cards[$i]);
                    else array_push($frequency[intdiv($other_cards[$i], 10)], $other_cards[$i]);
                }
                //Debug::Log("json frequency boot: " . json_encode($frequency), __FILE__);

                uasort($frequency, array($this, 'bootStopCards'));
                //Debug::Log("json sorted frequency boot: " . json_encode($frequency), __FILE__);

                foreach ($frequency as $card_val => $cards) {
                    if ($card_val == $tableNumber) {
                        $valid_cards = $cards;
                    } else {
                        foreach ($cards as $card) {
                            if ($card % 10 == $tableSymbol) {
                                $valid_cards = $cards;
                                break;
                            }
                        }
                        if (!empty($valid_cards)) {
                            if (count($valid_cards) > 1 && $valid_cards[0] % 10 == $tableSymbol) {
                                $aux = $valid_cards[0];
                                $valid_cards[0] = $valid_cards[1];
                                $valid_cards[1] = $aux;
                            }
                            break;
                        }
                    }
                }
                if (empty($valid_cards)) {
                    if (count($switch_cards) > 0) {
                        $valid_cards = $switch_cards;
                    } else {
                        if (count($take_cards) > 0) {
                            array_push($valid_cards, $take_cards[0]);
                            for ($i = 1; $i < count($take_cards); $i++) {
                                if (intdiv($take_cards[$i], 10) == intdiv($take_cards[0], 10))
                                    array_push($valid_cards, $take_cards[$i]);
                            }
                        } else $valid_cards = $this->bootStopCards($stop_cards, count($cards));
                    }
                }
            }
        }
        $win = false;
        if (empty($valid_cards)) {
            $this->bootTakeCards($boot); // Take cards
        } else {
            if (!$this->verify($valid_cards, 0)) {
                Debug::Log("Verify return false! " . json_encode($valid_cards), __FILE__, "ERROR");
                $this->bootTakeCards($boot); // Take cards
            } else {
                if ($boot->removeCards($valid_cards) == 0) {
                    $win = true;
                    $details = $this->getDetails();
                    if (!isset($details['rank']))
                        $details['rank'] = array(array('id' => $boot->getId(), 'name' => $boot->getName()));
                    else array_push($details['rank'], array('id' => $boot->getId(), 'name' => $boot->getName()));
                    $this->setDetails($details);
                }
            }
        }
        $boot->update();
        return $win;
    }

    private function bootStopCards($stop_cards, $total_cards)
    {
        $count = count($stop_cards);
        if ($count <= 0)
            return [];

        if ($count == $total_cards)
            return $stop_cards;

        return array($stop_cards[0]);
    }

    public function compareCountArrays($a, $b)
    {
        if (count($a) == count($b))
            return 0;
        if (count($a) > count($b))
            return -1;
        return 1;
    }

    private function bootTakeCards($boot)
    {
        $details = $this->getDetails();
        if (!empty($details['new_game']) && $details['new_game'] > 0) {
            $cards = $this->takeCards(5);
            $details['new_game'] = $details['new_game'] - 1;
            $this->setDetails($details);
        } elseif (empty($details['takeCards']))
            $cards = $this->takeCards(1);
        else {
            $cards = $this->takeCards($details['takeCards']);
            unset($details['takeCards']);
            $this->setDetails($details);
        }
        $boot->addCards($cards);
    }

    public function deletePlayer(Player $player)
    {
        parent::deletePlayer($player);

        if ($this->getPlayerCount() > 1 && $this->getId() < 5 && $this->getRound() == $this->getId()) {
            $macao = new Macao();
            $macao->setter($this->getId(), $this->getCards(), $this->round, $this->deck, $this->getDetails(), $this->getRules());
            $macao->nextPlayer($macao->boot());
            $this->setRound($macao->getRoundArray());
            $this->setCards($macao->getCards());
            $this->setDeck($macao->getDeck());
            $this->setDetails($macao->getDetails());
        }
    }
}