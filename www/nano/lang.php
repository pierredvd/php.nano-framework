<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des langues
     */
    namespace Nano;
    
    abstract class Lang{

        private static $langDir      = null;
        private static $defaultLang  = null;
        private static $currentLang  = null;
        private static $translates   = array();
        private static $sessionName  = 'nano.lang';

        /**
         * Initialise la classe
         * @access public
         * @param  string Chemin d'accès aux fichiers de langues
         */
        public static function init($langDir, $defaultLang='fr'){
            if(session_id()==''){ @session_start(); }
            self::$langDir      = rtrim($langDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$defaultLang  = $defaultLang;
        }
        
        /**
         * Retourne la langue en cours
         * @access public
         * @return string Langue
         */        
        public static function getLang(){
            $lang = self::$currentLang;
            
            if(isset($_SESSION[self::$sessionName])){
                // Langue de la dernière session
                $lang = $_SESSION[self::$sessionName];
            } else {
                // Detection de la langue en cours
                if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                }
            }
            
            // Langue de fallback
            if(is_null($lang) || !is_file(self::$langDir.$lang.'.php')){
                $lang = self::$defaultLang;
            }
            self::$currentLang = $lang;
            return $lang;
        }

        /**
         * Change la langue en cours
         * @access public
         * @param string Langue
         */        
        public static function setLang($lang){
            $lang = strtolower(strlen($lang)>2?substr($lang, 0, 2):$lang);
            if( is_file(self::$langDir.$lang.'.php') && 
                !isset(self::$translates[$lang])
            ){
                self::$translates[$lang]        = include(self::$langDir.$lang.'.php');
                self::$currentLang              = $lang;
                $_SESSION[self::$sessionName]   = $lang;
            }
            return $lang;
        }
        
        /**
         * Traduction
         * @param string Clé de langue
         * @param string langue
         * @return string
         */
        public static function translate($key, $lang=null){
            if(is_null($lang)){ $lang = self::getLang(); }
            self::setLang($lang);
            
            if(!isset(self::$translates[self::$currentLang])){ return $key; }
            $translations = self::$translates[self::$currentLang];
            
            // Recherche le nom tel quel
            if(isset($translations[$key])){ return $translations[$key]; }
            
            // Recherche par segments
            if(strpos($key, '.')!==false){
                $segments   = explode('.', $key);
                $buffer     = $translations;
                while(sizeof($segments)>0){
                    $segment    = array_shift($segments);
                    if(isset($buffer[$segment])){
                        $buffer = $buffer[$segment];
                    } else {
                        return $key;
                    }
                }
                return $buffer;
            }            
            return $key;
        }
        
    }