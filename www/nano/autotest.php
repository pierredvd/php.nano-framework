<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des prérequis systeme
     */
    namespace Nano;
    
    abstract class Autotest{

        const AUTOTEST_MANIFEST_FILENAME        = '.autotest'; // Nom du fichier manisfeste de l'autotest
        const WRITE_TEST_FILENAME               = 'test.tmp';  // Nom du fichier utilisé pour les tests d'ecritures

        /**
         * Initialise le module
         */
        public static function init($dirRoot, $options){

            $dirRoot = rtrim($dirRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if(self::requireAutotest($dirRoot)){
                $test = self::test($dirRoot, $options);

                if($test['error']){
                    // Rendu de la checklist de l'autotest
                    $html = '<table>';
                    foreach($test['trace'] as $name=>$value){
                        $html .= '<tr><td>'.$name.'</td><td>'.($value?'<span class="success">Yes</span>':'<span class="error">No</span>').'</td></tr>';
                    }
                    $html .= '<table>';
                    $template = self::getNativeTemplate();
                    $template = str_replace('[TRACE]', $html, $template);
                    ob_clean();
                    ob_start();
                        header('Content-Type', 'text/html');
                        header('Content-Length', strlen($template));
                        echo $template;
                    ob_flush();
                    exit();
                } else {
                    // Ecrit un manifest temoin pour ne plus avoir à rééxécuter l'autotest
                    @file_put_contents($dirRoot.self::AUTOTEST_MANIFEST_FILENAME, '');
                }
            }
        }

        /** Verifie qu'il y ait besion d'un autotest
         * @access private
         * @return boolean
         */
        private static function requireAutotest($dirRoot){

            // Pas de fichier manifest
            if(!is_file($dirRoot.self::AUTOTEST_MANIFEST_FILENAME)){ return true; }

            // Verifie si le fichier manifeste à été touché, déplacé, modifié depuis sa création
            $createdAt = filectime($dirRoot.self::AUTOTEST_MANIFEST_FILENAME);
            $updatedAt = filemtime($dirRoot.self::AUTOTEST_MANIFEST_FILENAME);
            $touchAt   = fileatime($dirRoot.self::AUTOTEST_MANIFEST_FILENAME);
            if($createdAt!=$updatedAt || $createdAt!=$touchAt){ 
                @unlink($dirRoot.self::AUTOTEST_MANIFEST_FILENAME);
                return true; 
            }

            return false;
        }

        /**
         * Monte le systeme de capture d'erreur
         * @access private
         * @return array  Resultat du test
         */
        public static function test($dirRoot, $options){
            
            $test = array('error' => false, 'trace' => array());
            try{

                // --- Teste les options de l'autotest ---
                if(!isset($options['php_min_version']) || !is_float($options['php_min_version'])){
                    $test['error'] = true;
                    $test['trace']['Autotest configuration require option "php_min_version"'] = false;
                } else {
                    $test['trace']['Autotest configuration require option "php_min_version"'] = true;
                }
                if(!isset($options['php_max_version']) || !is_float($options['php_max_version'])){
                    $test['error'] = true;
                    $test['trace']['Autotest configuration require option "php_max_version"'] = false;
                } else {
                    $test['trace']['Autotest configuration require option "php_max_version"'] = true;
                }
                if(!isset($options['apache_modules']) || !is_array($options['apache_modules'])){
                    $test['error'] = true;
                    $test['trace']['Autotest configuration require option "apache_modules"'] = false;
                } else {
                    $test['trace']['Autotest configuration require option "apache_modules"'] = true;
                }
                if(!isset($options['php_modules']) || !is_array($options['php_modules'])){
                    $test['error'] = true;
                    $test['trace']['Autotest configuration require option "php_modules"'] = false;
                } else {
                    $test['trace']['Autotest configuration require option "php_modules"'] = true;
                }
                if(!isset($options['directories']) || !is_array($options['directories'])){
                    $test['error'] = true;
                    $test['trace']['Autotest configuration require option "directories"'] = false;
                } else {
                    $test['trace']['Autotest configuration require option "directories"'] = true;
                }
                if($test['error']){ return $test; }

                $test = array('error' => false, 'trace' => array('Autotest configuration' => true));

                // --- Lance l'autotest ---

                // Test php version
                if(function_exists('phpversion')){
                    $phpVersion     = explode('.', phpversion());
                    $major          = array_shift($phpVersion);
                    $phpVersion     = floatval($major . '.' . implode('', $phpVersion));
                    if($phpVersion<$options['php_min_version']){
                        $test['trace']['PHP version '.$phpVersion.' >= '.$options['php_min_version'].' require'] = false;
                        $test['error'] = true;
                    } else {
                        $test['trace']['PHP version '.$phpVersion.'>= '.$options['php_min_version'].' require'] = true;
                    }
                    if($phpVersion>$options['php_max_version']){
                        $test['trace']['PHP version '.$phpVersion.'<='.$options['php_max_version'].' require'] = false;
                        $test['error'] = true;
                    } else {
                        $test['trace']['PHP version '.$phpVersion.'<= '.$options['php_max_version'].' require'] = true;
                    }
                } else {
                    $test['trace']['Ignore phpversion test, function "phpversion" not found'];
                }

                // Test apache module
                if(function_exists('apache_get_modules')){
                    $modules = @apache_get_modules();
                    foreach($modules as $i => $module){ $modules[$i] = strtolower($module); }
                    foreach($options['apache_modules'] as $requiredModule){
                        if(!in_array(strtolower($requiredModule), $modules)){
                            $test['trace']['Apache module "'.$requiredModule.'" required'] = false;
                            $test['error'] = true;
                        } else {
                            $test['trace']['Apache module "'.$requiredModule.'" required'] = true;
                        }
                    }
                } else {
                    $test['trace']['Ignore apache module test, function "apache_get_modules" not found'] = true;
                }

                // Test PHP Modules
                if(function_exists('get_loaded_extensions')){
                    $modules = @get_loaded_extensions();
                    foreach($modules as $i => $module){ $modules[$i] = strtolower($module); }
                    foreach($options['php_modules'] as $requiredModule){
                        if(!in_array(strtolower($requiredModule), $modules)){
                            $test['trace']['PHP module "'.$requiredModule.'" required'] = false;
                            $test['error'] = true;
                        } else {
                            $test['trace']['PHP module "'.$requiredModule.'" required'] = true;
                        }
                    }
                } else {
                    $test['trace']['Ignore PHP module test, function "get_loaded_extensions" not found'] = true;
                }

                // Tests les dossiers requis avec leurs droits
                foreach($options['directories'] as $dir){
                    if($dir['path']!=''){
                        $path = str_replace('/', DIRECTORY_SEPARATOR, $dirRoot.str_replace('/', DIRECTORY_SEPARATOR, $dir['path']).DIRECTORY_SEPARATOR);

                        // Essai de créer le dossier (autofix)
                        if(!is_dir($path)){ 
                            $right = $dir['writable']?0775:0755;
                            @mkdir($path, $right, true); 
                            @chmod($path, $right);
                        }
                        if(!is_dir($path)){
                            $test['trace']['Dir "'.$dir['path'].'" readable'] = false;
                            if($dir['writable']){
                                $test['trace']['Dir "'.$dir['path'].'" writable'] = false;
                            }
                            $test['error'] = true;
                        } else {
                            $test['trace']['Dir "'.$dir['path'].'" readable'] = true;
                            if($dir['writable']){
                                try{
                                    $fp = fopen($path.self::WRITE_TEST_FILENAME, 'w+', 0);
                                    if(is_resource($fp)){
                                        @fwrite($fp, '-');
                                        @fclose($fp);
                                    }
                                } catch(\Exception $e){}
                                if(!is_file($path.self::WRITE_TEST_FILENAME)){
                                    $test['trace']['Dir "'.$dir['path'].'" writable'] = false;
                                    $test['error'] = true;
                                } else {
                                    $test['trace']['Dir "'.$dir['path'].'" writable'] = true;
                                    @unlink($path.self::WRITE_TEST_FILENAME);
                                }
                            }
                        }
                    } else {
                        if($dir['writable']){
                            try{
                                $fp = fopen($dirRoot.self::WRITE_TEST_FILENAME, 'w+', 0);
                                if(is_resource($fp)){
                                    @fwrite($fp, '-');
                                    @fclose($fp);
                                }
                            } catch(\Exception $e){}
                            if(!is_file($dirRoot.self::WRITE_TEST_FILENAME)){
                                $test['trace']['Dir [Root] writable'] = false;
                                $test['error'] = true;
                            } else {
                                $test['trace']['Dir [Root] writable'] = true;
                                @unlink($dirRoot.self::WRITE_TEST_FILENAME);
                            }
                        }
                    }
                }

            } catch(\Exception $e){
                $test['trace']['Test fail: '.$e->getMessage()] = false;
                $test['error'] = true;
            }
            return $test;
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
                background-color        : #f0f0f0;
                margin                  : 0px;
                padding                 : 0px;
            }
            .title{
                display                 : block;
                height                  : 70px;
                line-height             : 70px;
                font-family             : Tahoma;
                font-size               : 34px;
                border-bottom           : solid 1px #666666;
                -webkit-box-sizing      : border-box;
                   -moz-box-sizing      : border-box;
                    -ms-box-sizing      : border-box;
                     -o-box-sizing      : border-box;
                        box-sizing      : border-box;
                background-color        : #444444;
                color                   : #f0f0f0;
                padding                 : 0px 10px;
            }
            .message{
                font-family             : Tahoma;
                font-size               : 13px;
                -webkit-box-sizing      : border-box;
                   -moz-box-sizing      : border-box;
                    -ms-box-sizing      : border-box;
                     -o-box-sizing      : border-box;
                        box-sizing      : border-box;
                background-color        : #f0f0f0;
                color                   : #444444;
                padding                 : 0px 0px;
            }
            table{
                width: 100%;
                padding: 0px;
                margin: 0px;
                border-spacing: 0px;
                border-collapse: separate;                
                border-left: solid 1px #666666;
                border-top: solid 1px #666666;
            }
            table tr:nth-child(2n) td{
                background-color        : #e5e5e5;
            }
            table tr td:first-child{
                width: 350px;
                text-align: left;
            }
            table tr td{
                padding: 5px 8px;
                margin: 0px;
                border-bottom: solid 1px #666666;
                border-right: solid 1px #666666;
            }
            .success{
                color: #008000;
            }
            .error{
                color: #800000;
                font-weight: bold;
            }
            
        </style>
    </head>
    <body>
        <span class="title">Nano requirement</span>
        <span class="message">
            [TRACE]
        </span>
    </body>
</html>