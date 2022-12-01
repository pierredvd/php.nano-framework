<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des reponses de controlleurs
     */
    namespace Nano;
    
    class View{

        protected static $httpCodeStr = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            210 => 'Content Different',
            226 => 'IM Used',
            300 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            310 => 'Too many Redirects',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Typ',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            444 => 'No response',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            456 => 'Unrecoverable Error',
            499 => 'Client has closed connection',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway ou Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            506 => 'Variant also negociate',
            507 => 'Insufficient storage',
            508 => 'Loop detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not extended',
            520 => 'Web server is returning an unknown error'
        );
        
        
        protected static $vendorDir         = '';
        protected static $viewDir           = '';
        protected static $cacheDir          = '';
        protected static $cacheEnable       = false;
        protected $data                     = '';
        protected $httpCode                 = 200;
        protected $headers                  = array();

        /**
         * Initialise la classe
         * @param string Chemin d'accès au dossier des vendors
         */
        public static function init($vendorDir, $viewDir, $cacheDir, $enableCache=false){
            self::$vendorDir   = rtrim($vendorDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$viewDir     = rtrim($viewDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$cacheDir    = rtrim($cacheDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$cacheEnable = $enableCache===true;
        }
        
        /**
         * Constructeur d'une réponse de controller
         * @param string Corps de la réponse
         * @param array  Headers
         */
        public function __construct($data='', $httpCode=200, array $headers=array()){
            if(!isset(static::$httpCodeStr[$httpCode])){
                throw new \Exception('View error, unknown http code "'.$httpCode.'"');
            }
            $this->httpCode = $httpCode;
            $this->data     = $data;
            $this->headers  = $headers;
        }
        
        /**
         * Constructeur d'une réponse de controller
         * @access public
         * @return string Retourne le corps de la réponse
         */
        public function getData(){
            return $this->data;
        }

        /**
         * Constructeur d'une réponse de controller
         * @access public
         * @return array  Retourne les entetes
         */
        public function getHeaders(){
            return $this->headers;
        }
        
        /**
         * Rendu de la réponse
         * @access public
         */
        public function render(){
            $protocol = isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:'HTTP/1.1';
            header($protocol . ' ' . $this->httpCode . ' ' . static::$httpCodeStr[$this->httpCode], true, $this->httpCode);
            foreach($this->headers as $name => $value){
                header($name.':'.$value, true);
            }
            echo $this->data;
            
            
        }
        
    }