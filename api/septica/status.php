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
include_once API . 'objects/Septica.php';
include_once API . 'objects/Player.php';

if (!isset($_SESSION['id_player'])) {
    if (isset($id_player) && $id_player > 0)
        $_SESSION['id_player'] = $id_player;
    else die(json_encode(array("status" => -21, "message" => "id_player is not set!")));
}

$conn = Database::getConnection();
$septica = new Septica();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $septica->readOne($player->getIdTable());
    $players_list = $player->readAll($player->getIdTable());
    if ($players_list->num_rows == 0)
        throw new GameException("Players list for table with id " . $player->getIdTable() . " don't exist in database.", 19);

    // prepare game data
    $result = array();
    $result['cards'] = array();
    $result['cards'] = $septica->getCards();
    $result['deck'] = $septica->getDeckCount();
    $result['chat'] = $septica->getChat();
    $result['details'] = $septica->getDetails();
    $new_game = $septica->getPlayerCount() < 2;
    if ($new_game)
        $result['details']['new_game'] = -1;
    $result['status'] = 0; // it's not your turn or you are not ready
    $result['elapsedTime'] = strtotime("now") - $septica->getChangeAt();
    $result['players'] = array();
    $result['host'] = $septica->getHost() == $_SESSION['id_player'];
    $i = 0;
    $me = 0;

    // Read data about players
    while ($row = $players_list->fetch_assoc()) {
        $i = $i + 1;
        $player_cards = json_decode($row['cards'], true);
        // opponents data
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

            // the game hasn't started yet
            if ($new_game) {
                if ($septica->getHost() == $row['id'])
                    $table_item['status'] = 2;
                elseif (isset($player_cards['ready']) && $player_cards['ready'] == true)
                    $table_item['status'] = 1;
            } else {
                // the status of opponents
                if ($row['id'] == $septica->getRound())
                    $table_item['status'] = 1;
                $table_item['cards'] = count($player_cards);
            }
            if (isset($result['details']['points']) && !empty($result['details']['points'][$row['id']]))
                $table_item['wait'] = $result['details']['points'][$row['id']];
            array_push($result['players'], $table_item);
        } else {
            $me = $i - 1;
            // the game hasn't started yet
            if ($new_game) {
                if ($septica->getHost() == $_SESSION['id_player']) {
                    $result['status'] = 2;
                } elseif (isset($player_cards['ready']) && $player_cards['ready'] == true)
                    $result['status'] = 1;
            } else {
                // it's this player's round
                if ($_SESSION['id_player'] == $septica->getRound())
                    $result['status'] = 1;
            }
            if (isset($result['details']['points']) && !empty($result['details']['points'][$row['id']]))
                $result['details']['iWait'] = $result['details']['points'][$row['id']];
        }
    }

    // we order the players
    if ($me > 0 && $me < $i - 1) {
        $players_slice = array_splice($result['players'], 0, $me);
        $result['players'] = array_merge($result['players'], $players_slice);
    }

    // ordering and updating rankings
    if ($new_game && isset($result['details']['rank'])) {
        for ($i = 0; $i < count($result['details']['rank']); $i = $i + 1) {
            if (isset($result['details']['points'][$result['details']['rank'][$i]['id']]))
                $result['details']['rank'][$i]['cards'] = $result['details']['points'][$result['details']['rank'][$i]['id']];
        }
        usort($result['details']['rank'], "cmp");
    }

    // the number of votes required to eliminate the current player
    if (isset($result['details']['kick']))
        $result['details']['kick'] = count($result['details']['kick']) * 10 + $septica->getPlayerCount() - 1;
    else $result['details']['kick'] = $septica->getPlayerCount() - 1;

    die(json_encode($result));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}

function cmp(array $a, array $b)
{
    if (!isset($b['cards']))
        return -1;
    if (!isset($a['cards']))
        return 1;
    if ($a['cards'] == $b['cards']) {
        return 0;
    }
    return ($a['cards'] < $b['cards']) ? 1 : -1;
}
