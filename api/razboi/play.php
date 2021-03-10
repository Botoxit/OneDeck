<?php
/**
 * User: Nicu Neculache
 * Date: 04.03.2021
 * Time: 16:21
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

if (!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();

// initialize object
$player = new Player();
$razboi = new Razboi();

try {
    $player->readOne($_SESSION['id_player']);
    $razboi->readOne($player->getIdTable(), true);
    if ($razboi->getHost() == $player->getId()) {
        if ($razboi->allPlayersReady()) {
            $razboi->new_game($player);
            $razboi->update(true, false, true);
            if (!$conn->commit())
                throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
            die(json_encode(array('status' => 2)));
        } else die(json_encode(array('status' => -1)));
    } else {
        if ($razboi->getPlayerCount() < 2) {
            $ready = $player->ready();
            $player->update();
//          For table with BOOT as a host
//            if ($player->getIdTable() < 5 && $razboi->allPlayersReady()) {
//                $razboi->new_game($player);
//                $razboi->update(true, false, true);
//            }

            if (!$conn->commit())
                throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
            if ($ready)
                die(json_encode(array('status' => 1)));
        }
        die(json_encode(array('status' => 0)));
    }
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}



