<?php

include_once CORE . 'Database.php';

class Application
{
    private $controller = 'homeController';
    private $action = 'index';
    private $params = [];

    public function __construct()
    {
        $this->prepareURL();
        if (file_exists(CONTROLLER . $this->controller . '.php')) {
            require_once CONTROLLER . $this->controller . '.php';
            $this->controller = new $this->controller;
            if (!method_exists($this->controller, $this->action))
                self::redirectTo("/" . substr(get_class($this->controller),0,strlen(get_class($this->controller)) - 10));
            call_user_func_array([$this->controller, $this->action], $this->params);
        } else {
            http_response_code(404);
            include_once(VIEW . '404.phtml');
        }
    }

    protected function prepareURL()
    {
        $request = trim($_SERVER['REQUEST_URI'], '/');
        if (!empty($request)) {
            $url = explode('/', $request);
//            unset($url[0]);
            $this->controller = isset($url[0]) ? $url[0] . 'Controller' : 'homeController';
            $this->action = isset($url[1]) ? $url[1] : 'index';
            unset($url[0], $url[1]);
            $this->params = !empty($url) ? array_values($url) : [];
        }
    }

    public static function redirectTo($path = "")
    {
        if (empty($path)) {
            header("Location: /home");
        } else header("Location: $path");
        exit();
    }

    public static function logger($message = "Unknown error message", $file = "", $level = "WARNING")
    {
        $date = date("Y-m-d h:m:s");
        $message = "[$date][$file][$level] " . trim($message) . PHP_EOL;

        $log_file = ROOT . 'log/' . date("Y-m-d") . '.log';
        error_log($message, 3, $log_file);
    }
}
