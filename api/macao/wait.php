<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// include database and object files
include_once CORE . 'Database.php';
include_once API . 'objects/Macao.php';
include_once API . 'objects/Player.php';

if(!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$macao = new Macao();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $macao->readOne($player->getIdTable(), $player->getIdTable() == 1);

    if ($macao->getRound() != $player->getId())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));

    $details = $macao->getDetails();
    unset($details['kick']);
    if (empty($details['toWait']))
        die(json_encode(array('status' => 0, 'message' => "You don't need to wait.")));

    if ($details['toWait'] > 1) {
        if (!isset($details['waiting']))
            $details['waiting'] = array();
        $details['waiting'][$player->getId()] = $details['toWait'] - 1;
        unset($details['toWait']);
    } else unset($details['toWait']);
    $macao->setDetails($details);
    $macao->update(true);
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => 1)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}