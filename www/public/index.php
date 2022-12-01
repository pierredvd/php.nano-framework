<?php

    // Directories
    define('DS'                 , DIRECTORY_SEPARATOR);
    define('DIR_ROOT'           , substr(__DIR__, 0, strrpos(__DIR__, DS)) . DS);
    define('DIR_PUBLIC'         , DIR_ROOT . 'public'   . DS);
    define('DIR_NANO'           , DIR_ROOT . 'nano'     . DS);
    define('DIR_NANO_VENDOR'    , DIR_NANO . 'vendor'   . DS);
    define('DIR_APP'            , DIR_ROOT . 'app'      . DS);
    define('DIR_APP_CACHE'      , DIR_APP  . 'cache'    . DS);
    define('DIR_APP_CLASS'      , DIR_APP  . 'class'    . DS);
    define('DIR_APP_CONFIG'     , DIR_APP  . 'config'   . DS);
    define('DIR_APP_LANG'       , DIR_APP  . 'lang'     . DS);
    define('DIR_APP_LOG'        , DIR_APP  . 'log'      . DS);
    define('DIR_APP_VIEW'       , DIR_APP  . 'view'     . DS);
    define('NANO_VERSION'       , '1.0');

    session_start();
    
    // Initialise l'autoloader
    include(DIR_NANO.'autoload.php');
    \Nano\Autoload::init(array(DIR_ROOT, DIR_APP_CLASS));

    // Initialise l'autodiagnostic
    \Nano\Autotest::init(
        DIR_ROOT,
        array(
            'php_min_version' => 5.3,
            'php_max_version' => 7.5,
            'apache_modules'  => array(
                'mod_rewrite'
            ),
            'php_modules'       => array(
                'pdo',
                'pdo_mysql',
                'pdo_pgsql'
            ),
            'directories'     => array(
                array('path'  => ''                  , 'writable' => true ),
                array('path'  => 'app/cache'         , 'writable' => true ),
                array('path'  => 'app/cache/twig'    , 'writable' => true ), 
                array('path'  => 'app/class'         , 'writable' => false),
                array('path'  => 'app/config'        , 'writable' => false),
                array('path'  => 'app/controller'    , 'writable' => false),
                array('path'  => 'app/lang'          , 'writable' => false),
                array('path'  => 'app/log'           , 'writable' => true ),
                array('path'  => 'app/model'         , 'writable' => false),
                array('path'  => 'app/view'          , 'writable' => false),
                array('path'  => 'public/asset'      , 'writable' => true ),
                array('path'  => 'public/asset/css'  , 'writable' => true )
            )
        )
    );

    // Initialise des configurations
    \Nano\Config::init(DIR_APP_CONFIG, \Nano\Router::getHost());

    // Initialise les logs
    \Nano\Log::init(DIR_APP_LOG);

    // Initialise des erreurs
    function onError($type, $message, $file, $line) {
        if (\Nano\Config::get('global.error.log')) {
            \Nano\Log::trace($type . ' - ' . $message . ' (' . $file . ':' . $line . ')');
        }
    }
    \Nano\Error::init('onError', \Nano\Config::get('global.error.show'));

    // Initialise le cache local
    \Nano\Cache::init(DIR_APP_CACHE . 'local' . DS);

    // Initialise la gestion des langues
    \Nano\Lang::init(DIR_APP_LANG);

    // Intialise les modèles
    \Nano\Model::init(\Nano\Config::get('database'));

    // Initialise les vues
    \Nano\View::init(DIR_NANO_VENDOR, DIR_APP_VIEW, DIR_APP_CACHE, \Nano\Config::get('global.view.cache'));

    // Initialise la compilation des styles et lance le compilateur
    \Nano\Less::init(
        DIR_NANO_VENDOR, 
        DIR_PUBLIC . 'asset' . DS . 'less' . 
        DS, DIR_PUBLIC . 'asset' . DS . 'css' . DS, 
        \Nano\Config::get('global.view.cache')
    );

    // Initialise les mails
    \Nano\Mail::init(DIR_NANO_VENDOR, \Nano\Config::get('global.mail'));

    // Resolution d'une route
    \Nano\Router::init('\\App\\Controller\\', \Nano\Config::get('route'));
    $resolve = \Nano\Router::resolve(\Nano\Router::getUri());
    if (!is_null($resolve)) {
        $controller = new $resolve['controller']();                                                   // Apelle le controller
        $view = call_user_func_array(array($controller, $resolve['method']), $resolve['parameters']); // Apelle la methode du controller et recupère sa reponse
        if (!is_a($view, '\\Nano\\View')) {
            throw new \Exception('Controller response must be a View');                               // Verife la class de la réponse
        }         
        \Nano\Less::process(\Nano\Config::get('global.view.less'));                                   // Compilation LESS
        $view->render();                                                                              // Lance le rendu de la vue
    }  
