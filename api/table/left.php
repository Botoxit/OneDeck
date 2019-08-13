<?php
/**
 * User: Nicu Neculache
 * Date: 12.09.2019
 * Time: 14:02
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
include_once '../objects/Player.php';
include_once '../objects/Game.php';

$conn = DataBase::getConnection();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $id_table = $player->getIdTable();

    if ($id_table == null) {
        $player->delete();
        if (!$conn->commit())
            throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
        die(json_encode(array('status' => 1)));
    }

    $playerLimit -= 10;
    if ($playerLimit < 10) {
        $table->deleteTable();
    } else {
        $game = new Game();
        $game->readOne($player->getIdTable());
        $game->deletePlayer($player);
        $game->update();

        $table = new Table();
        $table->readOne($player->getIdTable());
        $table->setPlayersLimit($playerLimit);
        $table->newHost();
        $table->update();
    }

    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array("status" => 1)));
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
        case 8:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Bad request, data is missing.")));
        case 9:
            die(json_encode(array("status" => -$e->getCode(), "message" => "It's not your cards! YOU ARE A CHEATER!")));
        case 10:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to create player")));
        case 11:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to create table")));
        case 12:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update table")));
        case 13:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to delete table")));
        case 14:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to delete player")));
        case 15:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read table.")));
    }
}