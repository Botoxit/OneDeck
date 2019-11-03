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

if(!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

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
    GameException::exitMessage($e->getCode());
}