<?php
/**
 * User: Nicu Neculache
 * Date: 25.04.2019
 * Time: 20:05
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// include database and object files
include_once '../config/DataBase.php';
include_once '../objects/Table.php';
include_once '../objects/Macao.php';
include_once '../objects/Player.php';

$conn = DataBase::getConnection();
$macao = new Macao();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $macao->readOne($player->getIdTable());
    $players_list = $player->readAll($player->getIdTable());
    if (!$players_list)
        throw new GameException("Players list for table with id " . $player->getIdTable() . " don't exist in database.", 19);

    $result = array();
    $result['cards'] = array_slice($macao->getCards(), 0, 10);
    $result['deck'] = $macao->getDeckCount();
    $result['details'] = $macao->getDetails();
    $result['status'] = 0; // it's not your turn or you are not ready
    $result['players'] = array();
    $i = 0;
    $me = 0;

    while ($row = $players_list->fetch_assoc()) {
        $i = $i + 1;
        $player_cards = json_decode($row['cards'], true);
        if ($row['id'] != $_SESSION['id_player']) {
            $table_item = array(
                "status" => 0,
                "name" => $row['name'],
                "cards" => 0
            );
            if ($macao->getPlayerCount() < 2) {
                if ($macao->getHost() == $row['id'])
                    $table_item['status'] = 2;
                elseif (isset($player_cards['ready']) && $player_cards['ready'] == true)
                    $table_item['status'] = 1;
            } else {
                if ($row['id'] == $macao->getRound())
                    $table_item['status'] = 1;
                $table_item['cards'] = count($player_cards);
            }
            array_push($result['players'], $table_item);
        } else {
            $me = $i - 1;
            if ($macao->getPlayerCount() < 2) {
                if ($macao->getHost() == $_SESSION['id_player']) {
                    $result['status'] = 2;
                } elseif (isset($player_cards['ready']) && $player_cards['ready'] == true)
                    $result['status'] = 1;
            } elseif ($_SESSION['id_player'] == $macao->getRound())
                $result['status'] = 1;
        }
    }

// 1 2 [me]3 4     1 2  4    =  2       4 1 2
    if ($me > 0 && $me < $i - 1) {
        $players_slice = array_splice($result['players'], 0, $me);
        $result['players'] = array_merge($result['players'], $players_slice);
    }
// carti de pe masa, carti jucatori, runda, detalii

    die(json_encode($result));
} catch (GameException $e) {
    switch ($e->getCode()) {
        case 1:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read player.")));
        case 2:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read macao game data.")));
        case 3:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update macao game data.")));
        case 4:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to commit.")));
        case 5:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read ready player.")));
        case 6:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update player.")));
        case 7:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read all players from table.")));
    }
}
