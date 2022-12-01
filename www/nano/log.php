<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des logs
     */
    namespace Nano;
    
    abstract class Log{

        /**
         * Chemin d'accès au dossier des logs
         * @var string
         */
        private static $logDir = null;
        
        /**
         * Initialisationd e la classe
         * @param string Dossier des logs
         */
        public static function init($logDir=null){
            if(!is_null($logDir)){
                $logDir = rtrim($logDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                self::$logDir = $logDir;
            }
        }
        
        /**
         * Retourne le dernier fichier de log
         * @return string
         */
        public static function getLogs($limit=50){
            $block      = null;
            $log        = array();
            $list       = self::listLogFiles();
            $data       = '';
            if(sizeof($list)>0){
                sort($list);
                foreach($list as $path){
                    if($limit<0){ break; }
                    if(filesize($path)>1048567){
                        $log[date('Y-m-d H:i:s')] = 'Log file "'.$path.'" is too large (> 1Mb)';
                        $limit = -1;
                    } else {
                        $data = @file_get_contents($path);
                    }
                    if($data!=''){
                        $lines = explode("\r\n",  $data);
                        $pos = 0;
                        foreach($lines as $line){
                            if($limit<0){ break; }
                            if($line!=''){
                                if(preg_match("#^\[[0-9]{4,4}\-#", $line)){
                                    if($block!=null){
                                        $log[$block['date']] = $block['message'];
                                    }
                                    $pos   = strpos($line, ']');
                                    $block = array(
                                        'date'      => trim(substr($line, 0, $pos+1), "\s\t\n\r\0\x0B[]"),
                                        'message'   => trim(substr($line, $pos+1))
                                    );
                                } else {
                                    $block['message'] .= "\r\n" . $line;
                                }
                            }
                            $limit--;
                        }
                    }
                }
                ksort($log);
            }
            if(!is_null($block)){
                $log[$block['date']] = $block['message'];
            }
            return $log;
        }
        
        /**
         * Lister les fichiers de logs
         * @param string Chemin relatif du dissier de log à parcourir
         * @return array Liste de chemins de fichiers
         */
        public static function listLogFiles($path=null){
            $files = array();
            if(is_null($path)){ $path = self::$logDir; }
            $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if(is_dir($path)){
                $list = array_diff(scandir($path), array('.', '..'));
                foreach($list as $item){
                    if(substr($item, 0, 1)!='.'){
                        if(is_dir($path.$item.DIRECTORY_SEPARATOR)){
                            $files = array_merge(
                                 $files,
                                 self::listLogFiles($path.$item.DIRECTORY_SEPARATOR)
                            );
                        }
                        if(is_file($path.$item)){
                            $files[] = $path.$item;
                        }                    
                    }
                }
            }
            return $files;
        }
        
        /** 
         * Effacer tous les fichiers de logs
         * @return boolean
         */
        public static function clear(){
            self::deleteLogRecursive(self::$logDir, array_diff(scandir(self::$logDir), array('.', '..')));
            return true;            
        }

        /**
         * Supprimer les logs de manière recursives
         * @return boolean
         */
        private static function deleteLogRecursive($path, $list){
            foreach($list as $item){
                if(is_dir($path.$item.DIRECTORY_SEPARATOR)){
                    self::deleteLogRecursive($path.$item.DIRECTORY_SEPARATOR, array_diff(scandir($path.$item.DIRECTORY_SEPARATOR), array('.', '..')));
                    @rmdir($path.$item.DIRECTORY_SEPARATOR);
                }
                if(is_file($path.$item)){
                    // On ne supprime pas les fichiers commencant par un point "."
                    if(substr($item, 0, 1)!='.'){
                        @unlink($path.$item);
                    }
                }
            }
            return true;
        }        
        
        /**
         * Ecrit un log
         * @access  public
         * @param   string  Message
         */         
        public static function trace($message){
            if(!is_null(self::$logDir) && is_dir(self::$logDir)){
                foreach(array(
                    self::$logDir.date('Y'),
                    self::$logDir.date('Y').DIRECTORY_SEPARATOR.date('m'),
                ) as $path){
                    if(!is_dir($path)){ 
                        @mkdir($path, 0775, true); 
                        @chmod($path, 0775);
                    }
                }
                $path = self::$logDir.date('Y').DIRECTORY_SEPARATOR.date('m').DIRECTORY_SEPARATOR.date('d').'.php';
                $fp = @fopen($path, 'a+');
                if(!!$fp){
                    @fwrite($fp, '['.date('Y-m-d H:i:s').'] '.$message."\r\n");
                    @fclose($fp);
                }
                @chmod($path, 0775);
            }
        }
        
    }