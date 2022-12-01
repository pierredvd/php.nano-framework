<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des compilations de styles less
     */
    namespace Nano;
    
    class Less{

        private static $vendorDir       = '';
        private static $lessDir         = '';
        private static $cssDir          = '';
        private static $cacheEnable     = false;
        private static $loaded          = false;

        /**
         * Initialise la classe
         * @param string Chemin d'accès au dossier des vendors
         * @param string Chemin d'accès au dossier des fichiers less
         * @param string Chemin d'accès au dossier des fichiers css
         * @param boolean Activation du cache
         */
        public static function init($vendorDir, $lessDir, $cssDir, $enableCache=false){
            self::$vendorDir   = rtrim($vendorDir   , DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$lessDir     = rtrim($lessDir     , DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$cssDir      = rtrim($cssDir      , DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$cacheEnable = $enableCache===true;
            // A ne pas appeler dans le process, car les classes lessc serait declarées dans le mauvaise namespace
            self::loadLess(); 
        }
       
        /**
         * Charge les librairies requises
         */
        private static function loadLess(){
            if(!self::$loaded){
                
                $lessFile = self::$vendorDir.'less'.DS.'lessc.inc.php';
                if(!is_file($lessFile)){
                    throw new \Exception('Less error, vendor "less" not found at "'.$lessFile.'"');
                }
                include_once($lessFile);
                $cssminFile = self::$vendorDir.'cssmin'.DS.'CssMin.php';
                if(!is_file($cssminFile)){
                    throw new \Exception('Less error, vendor "cssmin" not found at "'.$cssminFile.'"');
                }
                include_once($cssminFile);
                self::$loaded = true;
            }
        }        
        
        /**
         * Demander la compilation d'un fichier less vers un fichier css
         * @param string  Chemin du fichier LESS à compiler
         * @param string  Chemin du fichier CSS recevant les styles compilés
         * @return boolean
         */
        public static function compile($lessFile, $cssFile){
            if(!is_file(self::$lessDir.$lessFile)){ throw new \Exception('Less compilation error, file "'.$lessFile.'" not found at "'.self::$lessDir.'"'); }
            
            // On ne recompile jamais si le cache est activé est qu'un fichier css existe deja
            if(!(self::$cacheEnable && is_file(self::$cssDir.$cssFile))){
                
                // La plus grande date de dernière modification du fichier less ou de ses dependances
                $lessFiles  = self::getLessDependencies($lessFile);
                $lessDate   = 0;
                foreach($lessFiles as $file){ $lessDate = max($lessDate, filemtime(self::$lessDir.$file)); }
                
                // Date de dernière modification du css compilé
                $cssDate    = is_file(self::$cssDir.$cssFile)?filemtime(self::$cssDir.$cssFile):0;
                
                // On de recompile les fichiers less que s'il sont plus recent que le fichier css compilé
                if($lessDate>$cssDate){
                    try{
                        $less = new \lessc;
                        $less->setImportDir(array(str_replace(DIRECTORY_SEPARATOR, '/', self::$lessDir)));
                        $data = $less->compileFile(self::$lessDir.$lessFile);
                        if($data!=''){ $data = \CssMin::minify($data, array(), array()); }
                        $path = self::$cssDir.$cssFile;
                        if(@file_put_contents($path, $data)===false){
                            throw new \Exception('Less compilation error, cannot write in file "'.self::$cssDir.$cssFile.'"');
                        }
                    } catch (\Exception $e){
                        throw new \Exception('Less compilation error, '.$e->getMessage());
                    }
                    unset($less);
                }
            }
            return true;
        }
        
        /**
         * Retourne la liste des dependances d'un fichier less
         * @param string Chemin d'accès du fichier less
         * @return array Liste des fichiers dependants
         */
        private static function getLessDependencies($lessFile){
            $lessPath   = self::$lessDir.$lessFile;
            $files      = array();
            if(is_file($lessPath)){
                $files[]    = $lessFile;
                $data       = file_get_contents($lessPath);
                $matches    = array();
                preg_match_all('#@import\s(.*);#', $data, $matches);
                if(sizeof($matches)>0){
                    $matches = $matches[1];
                    foreach($matches as $file){
                        $file = trim($file, " '\"");
                        $files = array_merge(
                            $files,
                            self::getLessDependencies($file)
                        );
                    }
                }
            }
            return $files;
        }
        
        /**
         * Vider le cache less
         * @return boolean
         */
        public static function clearCache($config=array()){
            $lessFiles = $config;
            if(!is_null($lessFiles) && !empty($lessFiles)){
                foreach($lessFiles as $lessFile => $cssFile){
                    try{
                        if(is_file(self::$cssDir.$cssFile)){
                            // On ne supprime pas les fichiers commencant par un point "."
                            if(substr($cssFile, 0, 1)!='.'){
                                @unlink(self::$cssDir.$cssFile);
                            }                            
                        }
                    } catch (\Exception $e) {}
                }
            }            
            return true;
        }
        
        /**
         * Executer les ordres de configurations
         * @param array  Configuration
         * return boolean
         */
        public static function process($config=array()){
            $lessFiles = $config;
            if(!is_null($lessFiles) && !empty($lessFiles)){
                foreach($lessFiles as $lessFile => $cssFile){
                    try{
                        \Nano\Less::compile($lessFile, $cssFile);
                    } catch (\Exception $e) {
                        throw new \Exception('Less process error, '.$e->getMessage());
                    }
                }
            }   
            return true;
        }
        
    }