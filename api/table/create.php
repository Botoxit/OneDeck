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

// instantiate database and table object
$database = new Database();
$conn = $database->getConnection();

$table = new Table($conn);

// get posted data
$data = json_decode(file_get_contents("php://input"));

// make sure data is not empty
if (!empty($data->name) &&
    !empty($data->game) &&
    !empty($data->players_limit) &&
    !empty($data->rules)) {

    // set product property values
    $table->setName($data->name);
    if (empty($data->password))
        $table->setPassword("NULL");
    else $table->setPassword("'" . $data->password . "'");
    $table->setGame($data->game);
    $table->setPlayersLimit($data->players_limit);
    $table->setRules($data->rules);

    // create the product
    $id = $table->create();

    if ($id == 0) {
        http_response_code(200);
        die(json_encode(array("status" => 0, "message" => "Name already used.")));
    } elseif ($id > 0) {
        if ($conn->commit()) {
            // set response code - 201 created
            http_response_code(201);
            die(json_encode(array("status" => $id)));
        } else die(json_encode(array("status" => -1, "message" => "Unable to commit.")));
    } else {
        // set response code - 503 service unavailable
        http_response_code(503);
        die(json_encode(array("status" => -1, "message" => "Unable to create table.")));
    }
} // tell the user data is incomplete
else {

    // set response code - 400 bad request
    http_response_code(400);

    // tell the user
    die(json_encode(array("status" => -2, "message" => "Unable to create table. Data is incomplete.")));
}