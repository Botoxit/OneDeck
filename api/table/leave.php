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
    $id_table = $player->getIdTable();

    if ($id_table == null) {
        $player->delete();
        if (!$conn->commit())
            throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
        die(json_encode(array('status' => 1)));
    }
    $table = new Table();
    $table->readOne($id_table);
    $playerLimit = $table->getPlayersLimit() - 10;
    if ($playerLimit < 10) {
        $table->deleteTable();
    } else {
        $game = new Game();
        $game->readOne($player->getIdTable());
        $resetTime = $game->getRound() == $player->getId();
        $game->deletePlayer($player);
        $game->update($resetTime);

        $table->setPlayersLimit($playerLimit);
        if ($game->getHost() == $player->getId())
            $table->newHost();
        $table->update();
        $player->delete();
    }

    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    unset($_SESSION['id_player']);
    session_unset();
    die(json_encode(array("status" => 1)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}