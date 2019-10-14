<?php


class Razboi extends Game
{
    public function nextCard(int $card)
    {

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
        if ($players_list->num_rows == 0)
            die(json_encode(array("status" => -1, "message" => "Unable to read players.")));
        while ($row = $players_list->fetch_assoc()) {
            $player->readOne($row['id']);
            $player->setCards($this->takeCards(intdiv(52, $players_list->num_rows - count($round))));
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
    }
}