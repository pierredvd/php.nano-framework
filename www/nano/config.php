<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des configurations
     */
    namespace Nano;
    
    abstract class Config{

        private static $loaded = false;
        private static $config = array();

        /**
         * Initialise le chargement des configurations
         * @access public
         * @param  string  Dossier racine des configurations
         * @param  string  Dossier de la configuration specifique
         */
        public static function init($rootDir, $currentDir){
            if(!self::$loaded){
                $rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $currentDir = rtrim($currentDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                self::$config = self::smartArrayMerge(
                    self::loadConfigDirectory($rootDir),
                    self::loadConfigDirectory($rootDir.$currentDir)
                );
                self::$loaded = true;
            }
        }
        
        /**
         * Charge les fichiers de configirations d'un dossier
         * @access private
         * @param  string   Chemin d'accès d'un dossier de configuration
         * @return array
         */
        private static function loadConfigDirectory($directory){
            $data = array();
            if(is_dir($directory)){
                $files = array_diff(scandir($directory), array('.', '..'));
                foreach($files as $file){
                    if(is_file($directory.$file)){
                        $name = substr($file, 0, strrpos($file, '.'));
                        $buffer = include($directory.$file);
                        $data[$name] = is_array($buffer)?$buffer:array();
                    }
                }
            }
            return $data;
        }
        
        /**
         * Merge recursif de 2 tableaux
         * @access private
         * @param  array Tableau de reference
         * @param  array Tableau de surcharge
         * @return array
         */
        private static function smartArrayMerge($array1, $array2){

            // Surcharge
            foreach($array1 as $name => $value){
                if(isset($array2[$name])){
                    if(is_array($array1[$name]) && is_array($array2[$name])){
                        $array1[$name] = self::smartArrayMerge($array1[$name], $array2[$name]);
                    } else {
                        $array1[$name] = $array2[$name];
                    }
                }
            }
            
            // Colmatage
            foreach($array2 as $name => $value){
                if(!isset($array1[$name])){
                    $array1[$name] = $value;
                }
            }
            
            return $array1;
        }
        
        /**
         * Retourne une configuration
         * @access public
         * @param  string Nom de la configuration
         * @return mixed
         */
        public static function get($name=null){
            
            if(is_null($name)){ return self::$config; }
            
            // Recherche le nom tel quel
            if(isset(self::$config[$name])){ return self::$config[$name]; }
            
            // Recherche par segments
            $data = self::$config;
            if(strpos($name, '.')!==false){
                $segments   = explode('.', $name);
                $buffer     = $data;
                while(sizeof($segments)>0){
                    $segment    = array_shift($segments);
                    if(isset($buffer[$segment])){
                        $buffer = $buffer[$segment];
                    } else {
                        return null;
                    }
                }
                return $buffer;
            }
            
            return null;
        }
        
    }