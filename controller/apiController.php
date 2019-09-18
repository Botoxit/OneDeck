<?php

require_once CORE . 'Controller.php';

class apiController extends Controller
{
    public function index()
    {
        Application::redirectTo();
    }

    public function table(string $action)
    {
        if (file_exists(API . 'table' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'table' . DIRECTORY_SEPARATOR . $action . '.php';
        }
        else die(json_encode(array("status" => -1)));
    }

    public function macao(string $action)
    {
        if (file_exists(API . 'macao' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'macao' . DIRECTORY_SEPARATOR . $action . '.php';
        }
        else die(json_encode(array("status" => -1)));
    }

    public function razboi(string $action)
    {
        if (file_exists(API . 'razboi' . DIRECTORY_SEPARATOR . $action . '.php')) {
            require_once API . 'razboi' . DIRECTORY_SEPARATOR . $action . '.php';
        }
        else die(json_encode(array("status" => -1)));
    }
}