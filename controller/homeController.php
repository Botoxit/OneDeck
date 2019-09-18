<?php

require_once CORE . 'Controller.php';

class homeController extends Controller
{
    public function index()
    {
        $this->setView('index');
        $this->getView()->render();
    }

    public function privacy($params = "")
    {
        $this->setView('privacy_policy', array("game" => is_string($params) ? $params : ""));
        $this->getView()->render();
    }

    public function infoPage($title = "Error", $content = "An error has been encountered")
    {
        $params = [];
        $params['title'] = htmlspecialchars($title);
        $params['content'] = htmlspecialchars($content);
        $this->setView('home' . DIRECTORY_SEPARATOR . 'infoPage', $params);
        $this->getView()->render();
    }

    public function internal_error()
    {
        http_response_code(500);
        $params = array();
        $params['title'] = htmlspecialchars("Error 500");
        $params['content'] = htmlspecialchars("Internal Server Error");
        $this->setView('home' . DIRECTORY_SEPARATOR . 'infoPage', $params);
        $this->getView()->render();
    }
}
