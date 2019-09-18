<?php

define('ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('CORE', ROOT . 'core' . DIRECTORY_SEPARATOR);
define('MODEL', ROOT . 'model' . DIRECTORY_SEPARATOR);
define('VIEW', ROOT . 'view' . DIRECTORY_SEPARATOR);
define('CONTROLLER', ROOT . 'controller' . DIRECTORY_SEPARATOR);
define('API', ROOT . 'api' . DIRECTORY_SEPARATOR);

$modules = [ROOT, CORE, MODEL, VIEW, CONTROLLER, API];
set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $modules));
spl_autoload_register('spl_autoload',false);

require_once CORE . 'Application.php';
new Application;
