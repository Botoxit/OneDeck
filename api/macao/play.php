<?php
/**
 * User: Nicu Neculache
 * Date: 02.05.2019
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
include_once API . 'objects/Macao.php';
include_once API . 'objects/Player.php';

if(!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();

// initialize object
$player = new Player();
$macao = new Macao();

try {
    $player->readOne($_SESSION['id_player']);
    $macao->readOne($player->getIdTable());
    if ($macao->getHost() == $player->getId()) {
        if ($macao->allPlayersReady()) {
            $macao->new_game($player);
            $macao->update(true,false, true);
            if (!$conn->commit())
                throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
            die(json_encode(array('status' => 2)));
        } else die(json_encode(array('status' => -1)));
    } else {
        if ($macao->getPlayerCount() < 2) {
            $ready = $player->ready();
            $player->update();

            if($player->getIdTable() == 1 && $macao->allPlayersReady()) {
                $macao->new_game($player);
                $macao->update(true,false, true);
            }

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



