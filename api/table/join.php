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
include_once '../config/DataBase.php';
include_once '../objects/Table.php';
include_once '../objects/Player.php';

// get posted data
$post = json_decode(file_get_contents("php://input"));
// make sure data is not empty
if (!empty($post->playerName) &&
    !empty($post->tableId)) {

    $table = new Table();
    if(!$table->readOne($post->tableId))
        die(json_encode(array("status" => -1, "message" => "Unable to read table.")));

    $playerLimit = $table->getPlayersLimit();
    if($playerLimit % 11 == 0)
        die(json_encode(array("status" => 0, "message" => "Table is full!")));

    $player = new Player($post->playerName);
    $player->setIdTable($post->tableId);
    if ($player->create() < 1)
        die(json_encode(array("status" => 0, "message" => "Player creating failed!")));

    $playerLimit += 10;
    $table->setPlayersLimit($playerLimit);
    if(!$table->update())
        die(json_encode(array("status" => 0, "message" => "Unable to update game table.")));

    $conn = DataBase::getConnection();
    if (!$conn->commit())
        die(json_encode(array('status' => -1, 'message' => "Unable to commit.")));
    die(json_encode(array('status' => 1)));
} else {
    http_response_code(400);    // set response code - 400 bad request
    die(json_encode(array("status" => -2, "message" => "Unable to join table. Data is incomplete.")));
}