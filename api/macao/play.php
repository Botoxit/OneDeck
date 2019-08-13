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
include_once '../config/DataBase.php';
include_once '../objects/Macao.php';
include_once '../objects/Player.php';

$conn = DataBase::getConnection();

// initialize object
$player = new Player();
$macao = new Macao();

try {
    $player->readOne($_SESSION['id_player']);
    $macao->readOne($player->getIdTable());
    if ($macao->getHost() == $player->getId()) {
        $query = "SELECT count(*) FROM " . Player::getTableName() . " WHERE id_table = ? AND JSON_EXTRACT(cards,'$.ready') = 'true'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $player->getIdTable());

        if ($stmt->execute()) {
            $row = $stmt->fetch();
            $ready_player = $row['count(*)'];
            if ($ready_player == $macao->getPlayerCount()) {
                $macao->new_game($player);
                $macao->update();
                if (!$conn->commit())
                    throw new GameException("Commit work failed, $conn->errno: $conn->error",4);
                die(json_encode(array('status' => 1)));
            } else die(json_encode(array('status' => 0)));
        } else throw new GameException("Unable to read ready players for table id: " . $player->getIdTable() . ", $stmt->errno: $stmt->error",5);
    } else {
        $ready = $player->ready();
        $player->update();
        if (!$conn->commit())
            throw new GameException("Commit work failed, $conn->errno: $conn->error",4);
        if ($ready)
            die(json_encode(array('status' => 1)));
        die(json_encode(array('status' => 0)));
    }
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
    }
}



