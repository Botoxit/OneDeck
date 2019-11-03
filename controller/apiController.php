<?php

require_once CORE . 'Controller.php';

class apiController extends Controller
{
    public function index()
    {
        Application::redirectTo();
    }

    public function wakeUp()
    {
        if (isset($_SESSION['id_player']) && $_SESSION['id_player'] > 0) {
            try {
                $player = new Player();
                $player->readOne($_SESSION['id_player']);
                die(json_encode(array("status" => $player->getIdTable(), "message" => "You are already in a game")));
            }
            catch (GameException $e){
                unset($_SESSION['id_player']);
                GameException::exitMessage($e->getCode());
            }
        }
        if (isset($GET['ver']) && $_GET['ver'] < 0.4)
            die(json_encode(array("status" => 0, "message" => "Update application")));
        die(json_encode(array("status" => 1)));
    }

    public function table(string $action)
    {
        if (file_exists(API . 'table' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'table' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }

    public function macao(string $action)
    {
        if (file_exists(API . 'macao' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'macao' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }

    public function razboi(string $action)
    {
        if (file_exists(API . 'razboi' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'razboi' . DIRECTORY_SEPARATOR . $action . '.php';
        } else die(json_encode(array("status" => -1)));
    }
}