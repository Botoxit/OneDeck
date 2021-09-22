<?php
/**
 * User: Nicu Neculache
 * Date: 12.09.2019
 * Time: 14:02
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// include database and object files
include_once CORE . 'Database.php';
include_once API . 'objects/Table.php';
include_once API . 'objects/Player.php';

// get posted data
$post = json_decode(file_get_contents("php://input"));

try {
// make sure data is not empty
    if (empty($post->playerName) || empty($post->id))
        throw new GameException("Bad request, post data is missing", 8);

    $table = new Table();
    $table->readOne($post->id);

    $playerLimit = $table->getPlayersLimit();
    if ($playerLimit % 11 == 0)
        die(json_encode(array("status" => -20, "message" => "Table is full!")));

    if(!$table->checkPassword($post->password))
        die(json_encode(array("status" => 0, "message" => "Wrong password!")));
    // Create player
    $player = new Player($post->playerName);
    $player->setIdTable($post->id);
    $id_player = $player->create();

    $playerLimit += 10;
    $table->setPlayersLimit($playerLimit);
    $table->update();

    $conn = Database::getConnection();
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => $id_player)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}