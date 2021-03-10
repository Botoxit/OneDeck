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
include_once CORE . 'Database.php';
include_once API . 'objects/Table.php';
include_once API . 'objects/Razboi.php';
include_once API . 'objects/Player.php';

if (!isset($_SESSION['id_player'])) {
    if (isset($id_player) && $id_player > 0)
        $_SESSION['id_player'] = $id_player;
    else die(json_encode(array("status" => -21, "message" => "id_player is not set!")));
}

$conn = Database::getConnection();
$razboi = new Razboi();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $razboi->readOne($player->getIdTable());
    $players_list = $player->readAll($player->getIdTable());
    if ($players_list->num_rows == 0)
        throw new GameException("Players list for table with id " . $player->getIdTable() . " don't exist in database.", 19);

    $result = array();
//    $result['table'] = $razboi->getCards();
    $result['table'] = array();
    $table_cards = $razboi->getCards();
    foreach ($table_cards as $key => $value) {
        array_push($result['table'], array("id_player" => $key, "cards" => $value));
    }
    if(isset($table_cards[$player->getId()]))
        $result['cards'] = $table_cards[$player->getId()];

    $result['chat'] = $razboi->getChat();
    if ($razboi->getPlayerCount() < 2)
        $result['details']['new_game'] = -1;
    $result['status'] = 0; // it's not your turn or you are not ready
    $result['elapsedTime'] = strtotime("now") - $razboi->getChangeAt();
    $result['players'] = array();
    $result['host'] = $razboi->getHost() == $_SESSION['id_player'];
    $i = 0;
    $me = 0;

    while ($row = $players_list->fetch_assoc()) {
        $i = $i + 1;
        $player_cards = json_decode($row['cards'], true);
        if ($row['id'] != $_SESSION['id_player']) {
            $table_item = array(
                "id" => $row['id'],
                "status" => 0,
                "name" => $row['name'],
                "cards" => 0,
                "details" => json_decode($row['details'], true)
            );
            if (isset($table_item['details']['pause']))
                $table_item['pause'] = $table_item['details']['pause'];
            else $table_item['pause'] = false;

            if ($razboi->getPlayerCount() < 2) {
                if ($razboi->getHost() == $row['id'])
                    $table_item['status'] = 2;
                elseif (isset($player_cards['ready']) && $player_cards['ready'] == true)
                    $table_item['status'] = 1;
            } else {
                if ($row['id'] == $razboi->getRound())
                    $table_item['status'] = 1;
                $table_item['cards'] = count($player_cards);
            }
            array_push($result['players'], $table_item);
        } else {
            $me = $i - 1;
            if ($razboi->getPlayerCount() < 2) {
                if ($razboi->getHost() == $_SESSION['id_player']) {
                    $result['status'] = 2;
                } elseif (isset($player_cards['ready']) && $player_cards['ready'] == true)
                    $result['status'] = 1;
            } else {
                if ($_SESSION['id_player'] == $razboi->getRound())
                    $result['status'] = 1;
                $result['deck'] = count($player_cards);
            }
        }
    }

// 1 2 [me]3 4     1 2  4    =  2       4 1 2
    if ($me > 0 && $me < $i - 1) {
        $players_slice = array_splice($result['players'], 0, $me);
        $result['players'] = array_merge($result['players'], $players_slice);
    }
// carti de pe masa, carti jucatori, runda, detalii

    if (isset($result['details']['kick']))
        $result['details']['kick'] = count($result['details']['kick']) * 10 + $razboi->getPlayerCount() - 1;
    else $result['details']['kick'] = $razboi->getPlayerCount() - 1;

    die(json_encode($result));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}
