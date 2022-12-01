<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion du cache local
     */
    namespace Nano;
    
    abstract class Cache{

        const FILE_CACHE_EXTEND       = 'tmp';

        private static $cacheDir      = null;

        /**
         * Initialise la classe
         * @access public
         * @param  string Chemin d'accès aux fichiers de langues
         */
        public static function init($cacheDir){
            self::$cacheDir      = rtrim($cacheDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if(!is_dir(self::$cacheDir)){ 
                @mkdir(self::$cacheDir, 0775, true); 
                @chmod(self::$cacheDir, 0775);
            }
        }
        
        /**
         * Lit une entrée de cache
         * @access public
         * @param  string Clé du cache
         * @return string|null Contenu du cache
         */        
        public static function get($key){
            $hash       = sha1($key);
            $filePath   = array(substr($hash, 0, 2), substr($hash, 2, 2), $key.'.'.self::FILE_CACHE_EXTEND);
            if(!is_dir(self::$cacheDir.$filePath[0])){ return null; }
            if(!is_dir(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1])){ return null; }
            if(!is_file(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1].DIRECTORY_SEPARATOR.$filePath[2])){ return null; }
            $data = @file_get_contents(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1].DIRECTORY_SEPARATOR.$filePath[2]);
            return $data;
        }

        /**
         * Ecrit une entrée de cache
         * @access public
         * @param  string Clé du cache
         * @param  string Contenu du cache
         * @return boolean Retourne si le cache à été crée (true: crée, false: mis à jour)
         */        
        public static function set($key, $data){
            $hash       = sha1($key);
            $filePath   = array(substr($hash, 0, 2), substr($hash, 2, 2), $key.'.'.self::FILE_CACHE_EXTEND);
            if(!is_dir(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR)){
                @mkdir(self::$cacheDir.$filePath[0], 0775, true); 
                @chmod(self::$cacheDir.$filePath[0], 0775);
            }
            if(!is_dir(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1].DIRECTORY_SEPARATOR)){
                @mkdir(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1], 0775, true); 
                @chmod(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1], 0775);
            }
            $created = !is_file(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1].DIRECTORY_SEPARATOR.$filePath[2]);
            @file_put_contents(self::$cacheDir.$filePath[0].DIRECTORY_SEPARATOR.$filePath[1].DIRECTORY_SEPARATOR.$filePath[2], ''.$data);
            return $created;
        }

        /**
         * Detruit une entrée de cache
         * @access public
         * @param  string Clé du cache
         * @return boolean Retourne si le cache à été detruit (true: detruit, false: non detruit)
         */        
        public static function delete($key){

            $hash       = sha1($key);
            $deleted    = false;
            $filePath   = array(substr($hash, 0, 2), substr($hash, 2, 2), $key.'.'.self::FILE_CACHE_EXTEND);
            if(is_file(self::$cacheDir.$filePath[0].DS.$filePath[1].DS.$filePath[2])){
                @unlink(self::$cacheDir.$filePath[0].DS.$filePath[1].DS.$filePath[2]);
                $deleted = true;
            }
            if(is_dir(self::$cacheDir.$filePath[0].DS.$filePath[1].DS)){
                $files      = array_diff(scandir(self::$cacheDir.$filePath[0].DS.$filePath[1].DS), array('.', '..'));
                if(sizeof($files)==0){
                    @rmdir(self::$cacheDir.$filePath[0].DS.$filePath[1].DS);
                }
            }
            if(is_dir(self::$cacheDir.$filePath[0].DS)){
                $files      = array_diff(scandir(self::$cacheDir.$filePath[0].DS), array('.', '..'));
                if(sizeof($files)==0){
                    @rmdir(self::$cacheDir.$filePath[0].DS);
                }
            }
            return $deleted;
        }
     
        /**
         * Purge le cache ayant un durée de vie au dela de celle spécifiés
         * @access public
         * @param  integer Durée de vie limite en secondes, 0 pour cibler tous les fichiers
         * @return boolean
         */
        public static function purge($timelifeLimit=0, $currentDirectory=null){
            $now = time();
            $timelifeLimit = max(0, 0+$timelifeLimit);
            $dirPath = self::$cacheDir;
            if(!is_null($currentDirectory)){
                $currentDirectory = rtrim($currentDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $dirPath .= $currentDirectory;
            }
            $content    = array_diff(scandir($dirPath), array('.', '..'));
            $childs     = 0;
            foreach($content as $path){
                if(is_dir($dirPath.$path.DIRECTORY_SEPARATOR)){
                    self::purge($timelifeLimit, $currentDirectory.$path.DIRECTORY_SEPARATOR);
                    if(is_dir($dirPath.$path.DIRECTORY_SEPARATOR)){
                        $childs++;
                    }
                } else {
                    if(is_file($dirPath.$path)){
                        $childs++;
                        if(strlen($path)>4 && substr($path, -4, 4)=='.'.self::FILE_CACHE_EXTEND){
                            $timelife = $now-(0+@filemtime($dirPath.$path));
                            var_dump($path, $timelife);


                            if($timelife>=$timelifeLimit){
                                @unlink($dirPath.$path);
                                $childs--;
                            }
                        }
                    }
                }
            }
            if($childs==0 && $dirPath!=self::$cacheDir){
                @rmdir($dirPath);
            }
            return true;
        }


    }