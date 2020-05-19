<?php
/**
 * User: Nicu Neculache
 * Date: 11.04.2020
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// include database and object files
include_once CORE . 'Database.php';
include_once API . 'objects/Game.php';
include_once API . 'objects/Player.php';

if (!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$game = new Game();
$player = new Player();
// get posted data
$post = json_decode(file_get_contents("php://input"));

try {
    $player->readOne($_SESSION['id_player']);
    $game->readChat($player->getIdTable());

    $timestamp = strtotime("now");

    if(!$game->AddToChat($timestamp, $player->getName(), $post->text))
        die(json_encode(array("status" => 0)));

    $game->updateChat();

    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);

    die(json_encode(array("status" => $timestamp)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}