<?php
/**
 * User: Nicu Neculache
 * Date: 10.04.2020
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

try {
    $player->readOne($_SESSION['id_player']);

    $details = $player->getDetails();

    if (!isset($details['pause']))
        $details['pause'] = true;
    else $details['pause'] = !$details['pause'];

    $player->setDetails($details);

    $player->update();
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);

    if ($details['pause'])
        die(json_encode(array('status' => 1)));
    else die(json_encode(array('status' => 0)));

} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}


