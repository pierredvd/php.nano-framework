<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des abstractions de bases de données
     */
    namespace Nano;
    
    abstract class Model{
        
        const LIMIT_QUERY_TRACE     = 1024;

        protected static $access    = array();
        protected static $connexions= array();
        protected static $connexion = null;
        private static   $binded    = false;
        
        /**
         * Initialise la classe
         */
        public static function init(array $connexions=array()){
            self::$access = $connexions;
            if(!self::$binded){
                self::$binded = true;
                if(function_exists('register_shutdown_function')){ 
                    register_shutdown_function('\Nano\Model::shutdown'); 
                }
                
            }
        }
        
        /**
         * Initialise la connexion et charge les informations de la table (Heritable, mais non redefinissable)
         * @access  protected
         * @return  PDO
         */         
        final protected static function getConnexion(){
            
            // Si la connexion PDO à deja été engagée 
            // (self = commun à toutes les classes heritant de Model)
            if(!isset(self::$connexions[static::$connexion])){

                // Charge la connexion
                if(!isset(self::$access[static::$connexion])){
                    throw new \Exception('Model "'.static::getClassName().'" error: database connexion "'.static::$connexion.'" is undefined');
                } else {
                    $config = self::$access[static::$connexion];
                }

                // Tente une connexion pdo
                try{
                    $pdo = new \PDO(
                        $config['dsn'], 
                        $config['user'], 
                        $config['password'],
                        array(
                            \PDO::ATTR_TIMEOUT              => $config['timeout']+0,
                            \PDO::ATTR_ERRMODE              => \PDO::ERRMODE_EXCEPTION,
                            \PDO::MYSQL_ATTR_INIT_COMMAND   => "SET NAMES utf8"
                        )
                    );
                    self::$connexions[static::$connexion] = $pdo;
                } catch(\Exception $e){
                    self::$connexions[static::$connexion] = null;
                    throw new \Exception('Model "'.static::getClassName().'" error: '.$e->getMessage());
                }
            }
            return self::$connexions[static::$connexion];
        }
        
        /** 
         * Ferme les connexions ouvertes
         * @access private
         * @return boolean
         */
        final public static function shutdown(){
            foreach(self::$connexions as $connexionName => $pdo){
                unset(self::$connexions[$connexionName]);
            }            
        }
        
        /**
         * Retourne le nom de la classe courante (Heritable, mais non redefinissable)
         * Accessible de la classe mère pour connaitre le nom de la classe fille
         * @access  protected
         * @return string
         */         
        final protected static function getClassName(){
            return get_called_class();
        }
        
        /**
         * Formatte une valeur en protegeant les caractères SQL
         * @access  private
         * @return  array
         */         
        final public static function escape($value){
            if(!is_numeric($value)){
                // Emule la fonction depreciée mysql_real_escape_string
                //ajoute un anti-slash aux caractères suivants : NULL, \x00, \n, \r, \, ', " et \x1a. 
                $protected = str_replace(
                    array("\\"  , "\x00"    , "\n"  , "\r"  , "'"   , "\""  ),
                    array("\\\\", "\\\x00"  , "\\\n", "\\\r", "\\'" , "\\\""),
                    $value
                );
                return $protected;
            }
            return $value;
        }
        
        /**
         * Execute une requete SQL (Heritable, mais non redefinissable)
         * @access  protected
         * @throw   Exception
         * @return  array
         */         
        final protected static function query($query){
            $pdo = static::getConnexion();
            if(is_null($pdo)){ throw new \Exception('Model "'.static::getClassName().'::query" error: require connexion'); }
            $buffer = false;
            try{
                $buffer = $pdo->query($query);
            } catch(\Exception $e){
                $buffer = false;
            }
            if($buffer===false){ 
                $info       = $pdo->errorInfo();
                $queryText  = trim($query);
                $trunk      = (strlen($queryText)>self::LIMIT_QUERY_TRACE);
                if($trunk){ $queryText = substr($queryText, 0, self::LIMIT_QUERY_TRACE); }
                $lines      = explode("\r\n", $queryText);
                $buffer     = array();
                foreach($lines as $i => $line){
                    $line = trim($line);
                    if($line!=''){
                        $buffer[] = $line;
                    }
                }
                $queryText = implode("\r\n", $buffer).($trunk?"\r\n(Extract ".(self::LIMIT_QUERY_TRACE)." of ".strlen($query)." characters)":'');
                throw new \Exception("Model \"".static::getClassName()."::query\" error on query:\r\n\r\n".$queryText."\r\n\r\n".$info[2]);
            }
            $results = array();
            
            try{
                while ($record = $buffer->fetch(\PDO::FETCH_ASSOC)){
                    $result = array();
                    foreach($record as $field => $value){
                        if(!is_numeric($field)){
                            $result[$field] = $value;
                        }                    
                    }
                    $results[] = $result;
                }            
            } catch(\Exception $e){
                // INSERT, UPDATE, DELETE
                $results = $buffer->rowCount();
            }
            
            return $results;
        }  
        
        /**
         * Retourne le dernier ID inséré
         * @access  protected
         * @throw   Exception
         * @return  integer|boolean
         */
        final protected static function getLastInsertedId(){
            $pdo = static::getConnexion();
            $lastId = false;
            if(is_null($pdo)){ throw new \Exception('Model "'.static::getClassName().'::query" error: require connexion'); }
            try{
                $lastId = $pdo->lastInsertId();
            } catch(\Exception $e){
                $info = $pdo->errorInfo();
                throw new \Exception("Model \"".static::getClassName()."::getLastInsertedId\" error :\r\n".$info[2]);
            }            
            return $lastId;
        }
        
    }