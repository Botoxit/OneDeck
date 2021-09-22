<?php

require_once 'View.php';
require_once 'Debug.php';

abstract class Controller
{
    private $view;
    private $model;

    /**
     * @return View
     * Return $view attribute
     */
    public function getView() : View
    {
        return $this->view;
    }

    /**
     * @return stdClass
     * Return $model attribute
     */
    public function getModel() : stdClass
    {
        return $this->model;
    }

    /**
     * @param string $viewName - phtml filename
     * @param array $data - list of parameters
     * @return View - instance of a View
     *
     * Instantiate a new View with input parameters
     */
    public function setView(string $viewName, array $data = []): View
    {
        $this->view = new View($viewName, $data);
        return $this->view;
    }

    /**
     * @param string $modelName - filename of a model class
     *
     * Instantiate a new $modelName
     * if input model isn't exist, user will be redirected to an error page
     */
    public function setModel(string $modelName)
    {
        if (file_exists(MODEL . $modelName . '.php')) {
            require_once MODEL . $modelName . '.php';
            $this->model = new $modelName;
        } else {
            Application::logger("Model $modelName is not exist in model(modelName)", __CLASS__, "ERROR");
            Application::redirectTo("/home/internal_error");
        }
    }

    protected function bad_request()
    {
        http_response_code(400);
        $params = array();
        $params['title'] = "Error 400";
        $params['content'] = "Bad request";
        $this->setView('home' . DIRECTORY_SEPARATOR . 'infoPage', $params);
        $this->getView()->render();
        exit();
    }
}
