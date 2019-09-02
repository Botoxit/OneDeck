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
include_once '../objects/Table.php';
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
        $query = "SELECT count(*) as \"ready\" , (SELECT count(*) FROM " . Player::getTableName() . " WHERE id_table = " . $player->getIdTable() . ") as \"total\" 
                            FROM " . Player::getTableName() . " WHERE id_table = " . $player->getIdTable() . " AND JSON_EXTRACT(cards,'$.ready') = true";
        $stmt = $conn->prepare($query);

        if (!$stmt->execute())
            throw new GameException("Unable to read ready players for table id: " . $player->getIdTable() . ", $stmt->errno: $stmt->error", 5);
        $result = $stmt->get_result();
        if (!$result)
            throw new GameException("Players for table with id " . $player->getIdTable() . " don't exist in database.", 19);
        $row = $result->fetch_assoc();
        $ready_players = $row['ready'] + 1;
        $total_players = $row['total'];
        if ($ready_players == $total_players && $total_players > 1) {
            $macao->new_game($player);
            $macao->update(false, false);
            if (!$conn->commit())
                throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
            die(json_encode(array('status' => 2)));
        } else die(json_encode(array('status' => -1)));
    } else {
        $ready = $player->ready();
        $player->update();
        if (!$conn->commit())
            throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
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



