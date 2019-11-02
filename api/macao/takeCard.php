<?php
/**
 * User: Nicu Neculache
 * Date: 24.04.2019
 * Time: 00:19
 */

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
    $macao->readOne($player->getIdTable());

    if ($macao->getRound() != $player->getId())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));

    $details = $macao->getDetails();
    unset($details['kick']);
    if (!empty($details['wait']))
        die(json_encode(array('status' => 0, 'message' => "You can't take card in this situation.")));

    if (!empty($details['new_game']) && $details['new_game'] > 0) {
        $cards = $macao->takeCards(5);
        $details['new_game'] = $details['new_game'] - 1;
        $macao->setDetails($details);
    } elseif (empty($details['takeCards']))
        $cards = $macao->takeCards(1);
    else {
        $cards = $macao->takeCards($details['takeCards']);
        unset($details['takeCards']);
        $macao->setDetails($details);
    }
    $player->addCards($cards);

    $player->update();
    $macao->update(true);
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
