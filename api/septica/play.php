<?php
/**
 * User: Nicu Neculache
 * Date: 28.04.2021
 * Time: 11:12
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

if (!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();

// initialize object
$player = new Player();
$septica = new Septica();

try {
    $player->readOne($_SESSION['id_player']);
    $septica->readOne($player->getIdTable());
    // if the player is the host, a new game is initialized
    if ($septica->getHost() == $player->getId()) {
        if ($septica->allPlayersReady()) {
            $septica->newGame($player);
            $septica->update(true, false, true);
            if (!$conn->commit())
                throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
            die(json_encode(array('status' => 2)));
        } else die(json_encode(array('status' => -1)));
    } else {
        // otherwise, player change ready status
        if ($septica->getPlayerCount() < 2) {
            $ready = $player->ready();
            $player->update();

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



