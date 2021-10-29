<?php
/**
 * User: Nicu Neculache
 * Date: 16.04.2019
 * Time: 14:26
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

// get database connection
$conn = Database::getConnection();
// get posted data
$post = json_decode(file_get_contents("php://input"));

$search = false;

if (empty($post->id)) $start_id = 0; else $start_id = $post->id;    // paging
if (!empty($post->tableName)) {
    $name = $post->tableName;
    $search = true;
} else $name = null;

if (!empty($post->password)) {
    $password = $post->password;
    $search = true;
} else $password = null;

if (!empty($post->game)) {
    $game = $post->game;
    $search = true;
} else $game = null;

if (!empty($post->playersLimit) && $post->playersLimit > 0) {
    $players_limit = $post->playersLimit;
    $search = true;
} else $players_limit = null;

if (!empty($post->rules)) {
    $rules = json_decode($post->rules);
    $search = true;
} else $rules = null;

$stmt = $conn->prepare("CALL `delete_inactive_tables`();");
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0)
        Debug::Log("I deleted " . $stmt->affected_rows . " inactive tables.", __FILE__, 'INFO');
}
else Debug::Log("I can't delete inactive tables $stmt->errno: $stmt->error", __FILE__, 'WARNING');
$conn->commit();

$table = new Table();

try {
    if (!$search)
        $result = $table->readPaging($start_id, 100);
    else $result = $table->search($start_id, 100, $name, $password, $game, $players_limit, $rules);

    $rowCount = $result->num_rows;
    $table_list = array();
    while ($row = $result->fetch_assoc()) {
        $table_item = array(
            "id" => $row['id'],
            "tableName" => $row['name'],
            "password" => $row['password'] == '' ? '' : 'X',
            "game" => $row['game'],
            "playersLimit" => $row['players_limit'],
            "rules" => $row['rules']
        );
        array_push($table_list, $table_item);
    }
    if (count($table_list) > 0)
        die(json_encode(array('status' => 1, 'table' => $table_list), JSON_PRETTY_PRINT));
    die(json_encode(array('status' => 0)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}