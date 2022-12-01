<?php
    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des reponses JSON de controlleurs
     */
    namespace Nano\View;
    
    class Json extends \Nano\View{

        /**
         * Constructeur d'une réponse de controller
         * @param string Données
         * @param array  Headers
         */
        public function __construct(array $data=array(), $httpCode=200, array $headers=array()){
            if(!isset(static::$httpCodeStr[$httpCode])){
                throw new \Exception('Json error, unknown http code "'.$httpCode.'"');
            }
            $this->data     = $data;
            $this->headers  = array_merge(
                array('Content-type' => 'application/json; charset=utf-8'),
                $headers
            );
        }

        /**
         * Rendu de la réponse
         * @access public
         */
        public function render(){
            $protocol = isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:'HTTP/1.1';
            header($protocol . ' ' . $this->httpCode . ' ' . static::$httpCodeStr[$this->httpCode]);
            foreach($this->headers as $name => $value){
                header($name.': '.$value);
            }
            echo json_encode($this->data);
        }
        
    }