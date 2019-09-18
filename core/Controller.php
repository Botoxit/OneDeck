<?php

class Controller
{
    private $view;
    private $model;

    /**
     * @return View
     */
    public function getView() : View
    {
        return $this->view;
    }

    /**
     * @return stdClass
     */
    public function getModel() : stdClass
    {
        return $this->model;
    }

    public function setView(string $viewName, $data = [])
    {
        $this->view = new View($viewName, $data);
        return $this->view;
    }

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
