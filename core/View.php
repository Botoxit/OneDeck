<?php

class View
{
    protected $file;
    protected $data;
    protected $title = "";

    /**
     * View constructor.
     * @param $view_file - phtml filename
     * @param $view_data - list of parameters
     *
     * Save filename and list of parameters in local attributes
     * and set title of page according to the filename
     */
    public function __construct($view_file, $view_data)
    {
        $this->file = $view_file;
        $this->data = $view_data;
        $this->title = ucfirst(dirname($view_file));
    }

    /**
     * We include the phtml file if it exist
     * otherwise we include 404 webpage
     */
    public function render()
    {
        if (file_exists(VIEW . $this->file . '.phtml')) {
            include_once VIEW . $this->file . '.phtml';
        }
        else
        {
            http_response_code(404);
            include_once( VIEW . '404.phtml');
        }
    }
}
