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

    if (!isset($details['kick']))
        $details['kick'] = array();
    elseif (array_search($player->getId(), $details['kick']) >= 0)
        die(json_encode(array("status" => 0)));

    if ($game->getPlayerCount() - 2 > count($details['kick'])) {
        array_push($details['kick'], $player->getId());
    } else {
        $kick_player = new Player();
        $kick_player->readOne($game->getRound());

        $table = new Table();
        $table->readOne($player->getIdTable());

        $game->deletePlayer($kick_player);

        $table->setPlayersLimit($table->getPlayersLimit() - 10);
        if ($game->getHost() == $kick_player->getId())
            $table->newHost();
        $table->update();
        $kick_player->delete();

        unset($details['kick']);
    }

    $game->setDetails($details);
    $game->update(true);

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