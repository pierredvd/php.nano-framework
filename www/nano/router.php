<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des routes
     */
    namespace Nano;
    
    abstract class Router{
        
        private static $routes              = array();
        private static $parsed              = false;
        private static $baseUri             = null;
        private static $uri                 = null;
        private static $method              = null;
        private static $scheme              = null;
        private static $host                = null;
        private static $rewriteBase         = null;
        private static $controllerNameSpace = '';
        
        /**
         * Initialise le router
         * @param string Namespace contenant les controllers
         */
        public static function init($controllerNameSpace, $routes){
            self::$controllerNameSpace  = $controllerNameSpace;
            self::$routes               = $routes;
        }
        
        /**
         * Retourne la base de l'uri en cours
         * @access  public
         * @return  string  Base de l'uri courante
         */
        public static function getBaseUri(){
            self::parseServerVars();
            return self::$baseUri;
        }

        /**
         * Retourne l'uri en cours
         * @access  public
         * @return  string  Uri courante
         */
        public static function getUri(){
            self::parseServerVars();
            return self::$uri;
        }

        /**
         * Retourne la method HTTP en cours
         * @return string  Method http
         */
        public static function getMethod(){
            self::parseServerVars();
            return self::$method;
        }
        
        /**
         * Retourne le shema de protocole HTTP en cours
         * @return string|boolean  scheme
         */
        public static function getScheme(){
            self::parseServerVars();
            return self::$scheme;
        }
        
        /**
         * Retourne le shema de protocole HTTP en cours
         * @return string|boolean  scheme
         */
        public static function getHost(){
            self::parseServerVars();
            return self::$host;
        }        
        
        /**
         * Retourne la base de routage de l'uri en cours
         * @access public
         * @return string Base de routage
         */
        public static function getRewriteBase(){
            self::parseServerVars();
            return self::$rewriteBase;
        }

        /**
         * Parse la requete serveur pour en extraire les informations de la requete
         * @acces private
         */
        private static function parseServerVars(){
            if(!self::$parsed){
                global $_SERVER;
                $protocol   = isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:'HTTP/1.1';
                $protocol   = explode('/', $protocol);
                if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on'){
                    self::$scheme       = 'https';
                } else {
                    if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])){
                        self::$scheme   = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
                    } else {
                        self::$scheme   = strtolower(array_shift($protocol));
                    }
                }
                self::$method       = isset($_SERVER['REQUEST_METHOD'])?strtolower($_SERVER['REQUEST_METHOD']):null;            
                self::$host         = isset($_SERVER['HTTP_HOST'])?strtolower($_SERVER['HTTP_HOST']):null;
                $rewriteBase        = isset($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:basename(__FILE__);
                $pos                = strrpos($rewriteBase, '/');
                self::$rewriteBase  = $pos>0?substr($rewriteBase, 0, $pos):''; 
                
                $path = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:null;
                if(!is_null($path)){
                    $pos = strpos($path, '#');
                    if($pos>0){ $path = substr($path, 0, $pos); }
                    $pos = strpos($path, '?');
                    if($pos>0){ $path = substr($path, 0, $pos); }
                    if(substr($path, 0, strlen(self::$rewriteBase))==self::$rewriteBase){
                        $path = substr($path, strlen(self::$rewriteBase));
                    }            
                }
                self::$uri = $path;
                self::$baseUri = self::$scheme . '://' . self::$host . self::$rewriteBase;
                self::$parsed = true;
            }
        }
        
        /**
         * Retourne un controller pour une route definie
         * @access  public
         * @return  string  Uri courante
         */        
        public static function resolve($route){
            
            $resolve = null;
            
            // Cherche une correspondance de route
            foreach(self::$routes as $pattern => $data){
                
                // Ignore les routes speciales encadrées par des __
                if($pattern!='__'.trim($pattern, '_').'__'){
                    if(preg_match('#^\\'.$pattern.'$#', $route, $matches)){
                        
                        // Cherche le controller correpondant
                        $data                   = explode('/', $data);
                        $method                 = array_pop($data);
                        $path                   = rtrim(str_replace(' ', '\\', ucwords(implode(' ', $data))), '\\');
                        $controllerClassName    = self::$controllerNameSpace.$path;
                        $methodHTTP             = self::$method;
                        
                        if(!class_exists($controllerClassName)){ throw new \Exception('Controller class "'.$controllerClassName.'" not found'); }
                        
                        // Cherche la methode avec le prefix de methodeHTTP
                        if(method_exists($controllerClassName, $methodHTTP.'_'.$method)){
                            $method = $methodHTTP.'_'.$method;
                        }
                        
                        // Capture les parametres de la methode du controlleur
                        if(method_exists($controllerClassName, $method)){
                            $reflector  = new \ReflectionClass($controllerClassName);
                            $buffer     = $reflector->getMethod($method)->getParameters();   
                            
                            $parameters = array();
                            $index      = 1;
                            foreach($buffer as $param){
                                $parameters[$param->getName()] = null;
                                if(isset($matches[$index])){
                                     $parameters[$param->getName()] = $matches[$index];
                                }
                                $index++;
                            }

                            // Retour
                            $resolve = array(
                                'controller' => $controllerClassName,
                                'method'     => $method,
                                'parameters' => $parameters
                            );
                            break;
                        }
                    }
                }
            }
            
            // Cherche la route de la 404
            if(is_null($resolve)){
                if(isset(self::$routes['__404__'])){
                    // Cherche le controller correpondant
                    $data                   = explode('/', self::$routes['__404__']);
                    $method                 = array_pop($data);
                    $path                   = str_replace(' ', '\\', ucwords(implode(' ', $data)));
                    $controllerClassName    = self::$controllerNameSpace.$path;
                    $methodHTTP             = self::$method;
                    if(!class_exists($controllerClassName)){ throw new \Exception('Controller class "'.$controllerClassName.'" not found'); }                
                    $resolve = array(
                        'controller' => $controllerClassName,
                        'method'     => $method,
                        'parameters' => array()
                    );
                } else {
                    // Aucune route de 404 n'existe
                    ob_clean();
                    ob_start();
                        header('HTTP/1.1 404 Not Found', 404, true);
                        echo self::getNativeTemplate();
                    ob_flush();
                    exit();
                }
            }
            return $resolve;
        }
        
        /**
         * Charge le template natif
         * @access private
         * @return string
         */
        private static function getNativeTemplate(){
            $data = file_get_contents(__FILE__);
            $template   = '';
            $start      = strrpos($data, "__halt_compiler();");
            if($start>=0){
                return substr($data, $start+18);
            }
            return false;
        }        
        
    }
    
    __halt_compiler();
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <style>
            body{
                background-color        : #e5e5e5;
                margin                  : 0px;
                position                : absolute;
                height                  : 100%;
                width                   : 100%;
                overflow                : hidden;
            }
            body > span{
                position                : absolute;
                top                     : 50%;
                left                    : 50%;
                margin-top              : -50px;
                margin-left             : -150px;
                display                 : inline-block;
                height                  : 100px;
                width                   : 300px;
                background-color        : white;
                border                  : solid 1px #666666;
                text-align              : center;
                line-height             : 100px;
                font-size               : 30px;
                font-family             : Arial;
                -webkit-border-radius   : 5px;
                -moz-border-radius      : 5px;
                -ms-border-radius       : 5px;
                -o-border-radius        : 5px;
                border-radius           : 5px;
            }
        </style>
    </head>
    <body>
        <span>404 Not found</span>
    </body>
</html>