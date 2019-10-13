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
include_once CORE . 'Database.php';
include_once API . 'objects/Table.php';
include_once API . 'objects/Player.php';
include_once API . 'objects/Game.php';

$conn = Database::getConnection();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $game = new Game();
    $game->readOne($player->getIdTable());
    $details = $game->getDetails();

    if($game->getPlayerCount() - 1 > $details['kick'])
    {

    }

    $table = new Table();
    $table->readOne($player->getIdTable());



        $game->deletePlayer($player);
        $game->update();

        $table->setPlayersLimit($playerLimit);
        if ($game->getHost() == $player->getId())
            $table->newHost();
        $table->update();
        $player->delete();


    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    session_unset();
    session_destroy();
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