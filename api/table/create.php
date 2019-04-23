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
    if ($id > 0) {

        // set response code - 201 created
        http_response_code(201);
        echo json_encode(array("status" => $id));
    } // if unable to create the product, tell the user
    elseif ($id == 0) {
        // set response code - 200
        http_response_code(200);
        echo json_encode(array("id" => 0, "message" => "Name already used."));
    } else {

        // set response code - 503 service unavailable
        http_response_code(503);
        echo json_encode(array("id" => -1, "message" => "Unable to create table."));
    }
} // tell the user data is incomplete
else {

    // set response code - 400 bad request
    http_response_code(400);

    // tell the user
    echo json_encode(array("id" => -2, "message" => "Unable to create table. Data is incomplete."));
}