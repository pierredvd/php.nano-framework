<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion de chargement des classes
     */
    namespace Nano;
    
    abstract class Autoload{

        private static $dirs       = array();
        private static $binded     = false;
        
        /** 
         * Initialise la classe
         * @access public
         * @param  string Chemin d'accès racine des classes à charger
         */        
        public static function init($dirs){
            self::$dirs = $dirs;
            if(!self::$binded){
                self::$binded = true;
                if (PHP_VERSION_ID < 50300) {
                    spl_autoload_register(array(__CLASS__, 'autoload'));
                } else {
                    spl_autoload_register(array(__CLASS__, 'autoload'), true, false);
                }                
            }
        }
        
        /** 
         * Autoload
         * @access public
         * @param  string  Namespace de la classe à charger
         * @return boolean
         */        
        private static function autoload($namespace){
            
            // Si la classe n'existe pas deja dans la liste des classes declarées (namespace compris)
            if (!in_array($namespace, get_declared_classes())) {

                // Standard namespace
                $path = str_replace('\\', DS, rtrim(strtolower($namespace), DS)) . '.php';
                foreach(self::$dirs as $dir){
                    if (is_file($dir . $path)){
                        require_once($dir . $path);
                        return true;
                    }                    
                }
                
                throw new \Exception('Class "' . $namespace . '" not found');
                return false;
            }
            return true;
        }
        
    }

    
