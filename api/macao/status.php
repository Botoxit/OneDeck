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
include_once '../objects/Macao.php';
include_once '../objects/Player.php';

$conn = DataBase::getConnection();
$macao = new Macao();

if (!$macao->readOne($_SESSION['id_table']))
    die(json_encode(array("status" => -1, "message" => "Unable to read macao.")));

$player = new Player();
$players_list = $player->readAll($_SESSION['id_table']);
if (!$players_list)
    die(json_encode(array("status" => -1, "message" => "Unable to read players.")));

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
    $player_cards = json_decode($row['cards']);
    if ($row['id'] != $_SESSION['id_player']) {
        $table_item = array(
            "id" => 0,
            "name" => $row['name'],
            "cards" => 0
        );
        if ($macao->getPlayerCount() < 2) {
            if ($macao->getHost() == $row['id'])
                $table_item['id'] = 2;
            elseif (isset($player_cards['ready']) && $player_cards['ready'] = true)
                $table_item['id'] = 1;
        } elseif ($row['id'] == $macao->getRound()) {
            $table_item['id'] = 1;
            $table_item['cards'] = count($player_cards);
        }
        array_push($result['players'], $table_item);
    } else {
        $me = $i - 1;
        if ($macao->getPlayerCount() < 2) {
            if ($macao->getHost() == $_SESSION['id_player']) {
                $result['status'] = 2;
            } elseif (isset($player_cards['ready']) && $player_cards['ready'] = true)
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