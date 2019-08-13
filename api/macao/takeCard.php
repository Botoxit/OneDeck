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
include_once '../config/DataBase.php';
include_once '../objects/Macao.php';
include_once '../objects/Player.php';

$conn = DataBase::getConnection();
$macao = new Macao();
$player = new Player();

$macao->readOne($_SESSION['id_table']);
$player->readCurrent($macao->getRound());
if ($player->getId() != $_SESSION['id_player'])
    die(json_encode(array('status' => 0, 'message' => "Is not your turn " . $_SESSION['id_player'] . ", is " . $player->getName() . " [" . $player->getId() . "] turn.")));

$details = $macao->getDetails();
if (!empty($details['wait']))
    die(json_encode(array('status' => 0, 'message' => "You can't take card in this situation.")));

if (!empty($details['new_game']) && $details['new_game'] > 0) {
    $cards = $macao->takeCards(5);
    $details['new_game'] = $details['new_game'] - 1;
    $macao->setDetails($details);
}
elseif (empty($details['takeCard']))
    $cards = $macao->takeCards(1);
else {
    $cards = $macao->takeCards($details['takeCard']);
    unset($details['takeCard']);
    $macao->setDetails($details);
}
$player->addCards($cards);

if (!$player->update())
    die(json_encode(array('status' => -1, 'message' => "Unable to update player.")));
if (!$macao->update())
    die(json_encode(array('status' => -1, 'message' => "Unable to update game table.")));
if ($conn->commit())
    die(json_encode(array('status' => 1, 'cards' => $cards)));
die(json_encode(array('status' => -1, 'message' => "Unable to commit.")));
