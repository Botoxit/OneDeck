<?php
/**
 * User: Nicu Neculache
 * Date: 22.04.2019
 * Time: 20:26
 */

class Macao extends Game
{
    public function verify(array $cards)
    {
        $tableCard = $this->getFirstTableCard();
        $details = $this->getDetails();

        // Same number
        if (intdiv($cards[0], 10) == intdiv($tableCard, 10))
            return true;

        if (!empty($details['symbol']))
            $tableSymbol = $details['symbol'];
        else $tableSymbol = $tableCard % 10;

        // If I need to take cards
        if (!empty($details['takeCard'])) {
            if (($cards[0] > 20 && $cards[0] < 40) || $cards[0] == 5 || $cards[0] == 6) {
                if (!$_SESSION['takeCard_color'])
                    return true;
                return $this->checkSymbol($cards, $tableSymbol);
            }
            return $this->checkStop($cards, $tableSymbol);
        }

        // If I need to wait
        if (!empty($details['wait'])) {
            if (intdiv($cards[0], 10) == $_SESSION['waitCard']) {
                if (!$_SESSION['waitCard_color'])
                    return true;
                return $this->checkSymbol($cards, $tableSymbol);
            }
            return $this->checkStop($cards, $tableSymbol);
        }

        if ((($cards[0] > 20) && ($cards[0] < 40)) || $cards[0] == 5 || $cards[0] == 6) {
            if (!$_SESSION['takeCard_color'])
                return true;
            return $this->checkSymbol($cards, $tableSymbol);
        } elseif (intdiv($cards[0], 10) == $_SESSION["changeCard"]) {
            if (!$_SESSION['changeCard_color'])
                return true;
            return $this->checkSymbol($cards, $tableSymbol);
        } elseif (intdiv($cards[0], 10) == $_SESSION["waitCard"]) {
            if (!$_SESSION['waitCard_color'])
                return true;
            return $this->checkSymbol($cards, $tableSymbol);
        } elseif (intdiv($cards[0],10) == $_SESSION["stopCard"]) {
            if (!$_SESSION['stopCard_color'])
                return true;
            return $this->checkSymbol($cards, $tableSymbol);
        } else return $this->checkSymbol($cards, $tableSymbol);
    }

    private function checkStop($cards, $tableSymbol)
    {
        if (intdiv($cards[0], 10) == $_SESSION["stopCard"]) {
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
        return $this->checkCardsOwner($player, $cards);
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
}