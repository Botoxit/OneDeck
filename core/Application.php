<?php

include_once CORE . 'Database.php';
include_once CORE . 'View.php';
include_once CORE . 'Controller.php';

class Application
{
    private $controller = 'homeController';
    private $action = 'index';
    private $params = [];

    /**
     * Application constructor.
     *
     * Populate local attributes by calling function prepareURL()
     * Make a new instance of controller and call the action,
     * if controller or action is missing, 404 page is displayed
     */
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

    /**
     * Extract from url: controller, action and parameters
     * {domain}/{controller}/{action}/{parameter_1}/..../{parameter_n}
     *
     * Default values for controller is 'homeController'
     * and for action is 'index' (website home page)
     */
    protected function prepareURL()
    {
        $request = trim($_SERVER['REQUEST_URI'], '/');
        if (!empty($request)) {
            $url = explode('/', $request);
            $this->controller = isset($url[0]) ? $url[0] . 'Controller' : 'homeController';
            $this->action = isset($url[1]) ? $url[1] : 'index';
            unset($url[0], $url[1]); // delete from array controller and action
            // Now, array $url is list of parameters
            $this->params = !empty($url) ? array_values($url) : [];
        }
    }

    /**
     * @param string $path - url
     * Redirect user to $path address
     */
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
