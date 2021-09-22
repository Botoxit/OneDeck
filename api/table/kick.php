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

if (!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$player = new Player();
$game = new Game();

try {
    $player->readOne($_SESSION['id_player']);
    $game->readOne($player->getIdTable());
    // the host can eliminate a player based on the id
    if ($id_player > 0) {
        if ($game->getHost() != $player->getId())
            throw new GameException("Player [" . $player->getId() . "]" . $player->getName() . " try to kick player but is not the host " . $player->getIdTable(), 23);

        $kick_player = new Player();
        $kick_player->readOne($id_player);
        if ($kick_player->getIdTable() != $player->getIdTable())
            throw new GameException("Player [" . $player->getId() . "]" . $player->getName() . " try to kick player from other table " . $player->getIdTable() . " != " . $kick_player->getIdTable(), 23);
    } else {
        // players can vote to eliminate the current player
        if($game->getPlayerCount() < 2)
            die(json_encode(array("status" => 0, "message" => "You can't vote kick now.")));

        if (!isset($details['kick']))
            $details['kick'] = array();
        elseif (array_search($player->getId(), $details['kick']) !== false)
            die(json_encode(array("status" => 0)));

        $update_time = false;
        if ($game->getPlayerCount() - 2 > count($details['kick'])) {
            array_push($details['kick'], $player->getId());
        } else {
            $kick_player = new Player();
            $kick_player->readOne($game->getRound());
        }
        $game->setDetails($details);
    }

    // if a player must be eliminated
    if(isset($kick_player)) {
        $table = new Table();
        $table->readOne($player->getIdTable());

        // the table details are updated
        $table->setPlayersLimit($table->getPlayersLimit() - 10);
        if ($game->getHost() == $kick_player->getId())
            $table->newHost();
        $table->update();

        // the player is eliminated from the current game
        if($game->getPlayerCount() > 1)
            $game->deletePlayer($kick_player);

        try {
            // Delete player
            $kick_player->delete();
        } catch (GameException $ex) {
        }

        $update_time = true;
    }

    $game->update($update_time, false, true);

    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);

    die(json_encode(array("status" => 1)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}