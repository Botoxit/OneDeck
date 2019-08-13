<?php
/**
 * User: Nicu Neculache
 * Date: 02.05.2019
 * Time: 16:21
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

// initialize object
$player = new Player($conn);
$macao = new Macao($conn);

if($player->readOne($_SESSION['id_player']))
    die(json_encode(array("status" => -1, "message" => "Unable to read player.")));
if($macao->readOne($_SESSION['id_table']))
    die(json_encode(array("status" => -1, "message" => "Unable to read macao host.")));

if($macao->getHost() == $_SESSION['id_player'])
{
    $query = "SELECT count(*) FROM " . Player::getTableName() . " WHERE id_table = ? AND JSON_EXTRACT(cards,'$.ready') = 'true'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['id_table']);

    if ($stmt->execute()) {
        $row = $stmt->fetch();
        $ready_player = $row['count(*)'];
        if($ready_player == $macao->getPlayerCount()) {
            $macao->new_game($player);
            if(!$macao->update())
                die(json_encode(array('status' => -1, 'message' => "Unable to update game table.")));
            if (!$conn->commit())
                die(json_encode(array('status' => -1, 'message' => "Unable to commit.")));
            die(json_encode(array('status' => 1)));
        }
        else die(json_encode(array('status' => 0)));
    } else die(json_encode(array("status" => -1, "message" => "Unable to read ready player.")));
}
else
{
    $ready = $player->ready();
    if (!$player->update())
        die(json_encode(array('status' => -1, 'message' => "Unable to update player.")));
    if (!$conn->commit())
        die(json_encode(array('status' => -1, 'message' => "Unable to commit.")));
    if($ready)
        die(json_encode(array('status' => 1)));
    die(json_encode(array('status' => 0)));
}

