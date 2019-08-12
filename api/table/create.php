<?php
/**
 * User: Nicu Neculache
 * Date: 16.04.2019
 * Time: 15:51
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
    !empty($post->tableName) &&
    !empty($post->game) &&
    !empty($post->playerLimit) &&
    !empty($post->rules)) {

    $player = new Player($post->playerName);
    $host = $player->create();
    if ($host < 1)
        die(json_encode(array("status" => 0, "message" => "Player creating failed!")));

    $password = null;
    if (!empty($post->password))
        $password = $post->password;
    $table = new Table();
    $table->setter($post->tableName, $password, $post->game, $post->playersLimit, $post->rules, $host);

    $idTable = $table->create();

    if ($idTable == 0) {
        http_response_code(200);        // set response code - 200 complete
        die(json_encode(array("status" => 0, "message" => "Name already used.")));
    } elseif ($idTable > 0) {
        $conn = DataBase::getConnection();
        if ($conn->commit()) {
            http_response_code(201);    // set response code - 201 created
            die(json_encode(array("status" => $idTable)));
        } else die(json_encode(array("status" => -1, "message" => "Unable to commit.")));
    } else {
        die(json_encode(array("status" => -1, "message" => "Unable to create table.")));
    }
} else {
    http_response_code(400);    // set response code - 400 bad request
    die(json_encode(array("status" => -2, "message" => "Unable to create table. Data is incomplete.")));
}