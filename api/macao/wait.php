<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// include database and object files
include_once '../config/Database.php';
include_once '../objects/Macao.php';
include_once '../objects/Player.php';

$conn = Database::getConnection();
$macao = new Macao();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $macao->readOne($player->getIdTable());

    if ($macao->getRound() != $player->getId())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));

    $details = $macao->getDetails();
    if (empty($details['wait']))
        die(json_encode(array('status' => 0, 'message' => "You don't need to wait.")));

    if($details['wait'] > 1) {
        if (!isset($details['waiting']))
            $details['waiting'] = array();
        $details['waiting'][$player->getId()] = $details['wait'] - 1;
        unset($details['wait']);
        $macao->setDetails($details);
    }
    $macao->update();
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => 1, 'cards' => $cards)));
} catch (GameException $e) {
    switch ($e->getCode()) {
        case 1:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read player.")));
        case 2:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read macao game data.")));
        case 3:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update macao game data.")));
        case 4:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to commit.")));
        case 5:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read ready player.")));
        case 6:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update player.")));
    }
}
