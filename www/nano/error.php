<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des errors
     */
    namespace Nano;
    
    abstract class Error{

        /**
         * Constante d'erreur pour les exceptions
         * @const integer
         */
        const E_EXCEPTION = -1;
	
        /**
         * Determine si un erreur à deja été levé
         * @var boolean
         */
        private static $hasError = false;

        /**
         * Determine les erreurs muettes si elles ne sont pas affichés
         * @var array
         */
        private static $muteErrors = array(
            E_WARNING, 
            E_NOTICE, 
            E_USER_WARNING, 
            E_USER_NOTICE, 
            E_DEPRECATED, 
            E_USER_DEPRECATED
        );
        
        /**
         * Determine si on doit efficher une erreur
         * @var boolean
         */
        private static $showError = false;
        
        /**
         * Closure de callback en cas d'erreur levée
         */
        private static $onError = null;
        
        /**
         * Monte le systeme de capture d'erreur
         */
        public static function init($onError=null, $showError=false){
            self::$onError              = $onError;
            self::$showError            = $showError;
            if(function_exists('error_reporting')           ){ error_reporting(             -1                                      ); }
            if(function_exists('ini_set')                   ){ ini_set(                     'display_errors', 0                     ); }
            if(function_exists('set_exception_handler')     ){ set_exception_handler(       '\Nano\Error::exception'                ); }
            if(function_exists('set_exception_handler')     ){ set_error_handler(           '\Nano\Error::error', E_ALL | E_STRICT  ); }
            if(function_exists('register_shutdown_function')){ register_shutdown_function(  '\Nano\Error::shutdown'                 ); }
        }
        
        /**
         * Capture une exception
         * @access public
         * @param Exception exception
         */        
        public static function exception($exception){
            
            $traces = array();
            $line = null;
            foreach($exception->getTrace() as $line){
                $traces[] = array(
                    'file' => isset($line['file'])?str_replace(DIRECTORY_SEPARATOR, '/', $line['file']):'',
                    'line' => isset($line['line'])?$line['line']:'',
                    'function' => (isset($line['class'])?$line['class']:'').(isset($line['type'])?$line['type']:'').(isset($line['function'])?$line['function']:'?')
                );
            }
            if(isset($_SERVER['SCRIPT_FILENAME'])){
                $traces[] = array(
                    'file' => str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['SCRIPT_FILENAME']),
                    'line' => 0,
                    'function' => '[entry point]'
                );
            }
            
            self::catchError(
                -1, 
                $exception->getMessage(), 
                $exception->getFile(), 
                $exception->getLine(),
                array_reverse($traces)
            );
        }
        
        /**
         * Capture une erreur
         * @access public
         * @param int       Numero de l'erreur
         * @param string    Message d'erreur
         * @param string    Fichier source de l'erreur
         * @param int       Ligne de l'erreur
         */        
        public static function error($errno, $errstr, $errfile, $errline){
            $traces = array();
            foreach(debug_backtrace() as $line){
                $traces[] = array(
                    'file' => isset($line['file'])?str_replace(DIRECTORY_SEPARATOR, '/', $line['file']):'',
                    'line' => isset($line['line'])?$line['line']:'',
                    'function' => (isset($line['class'])?$line['class']:'').(isset($line['type'])?$line['type']:'').(isset($line['function'])?$line['function']:'?')
                );
            }            
            if(isset($_SERVER['SCRIPT_FILENAME'])){
                $traces[] = array(
                    'file' => str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['SCRIPT_FILENAME']),
                    'line' => 0,
                    'function' => '[entry point]'
                );
            }
            self::catchError(
                $errno, 
                $errstr, 
                $errfile, 
                $errline,
                array_reverse($traces)
            );
        }

        /**
         * Capture une erreur fatale
         * @access public
         */        
        public static function shutdown(){
            try{
                $error = error_get_last();
            } catch(\Exception $e){
                $error = null;
            }
            if(!is_null($error)){
                self::catchError(
                    $error['type'], 
                    $error['message'], 
                    $error['file'], 
                    $error['line'],
                    array()
                );
            }
        }
        
        /**
         * Retourne le label d'un code d'erreur
         * @access private
         * @return string
         */        
        private static function getTypeError($error){
            $typeErrorStr = 'UNKNOWN ERROR';
            switch($error){
                case self::E_EXCEPTION  : $typeErrorStr = 'E_EXCEPTION'         ; break;
                case E_ERROR            : $typeErrorStr = 'E_ERROR'             ; break;
                case E_WARNING          : $typeErrorStr = 'E_WARNING'           ; break;
                case E_PARSE            : $typeErrorStr = 'E_PARSE'             ; break;
                case E_NOTICE           : $typeErrorStr = 'E_NOTICE'            ; break;
                case E_CORE_ERROR       : $typeErrorStr = 'E_CORE_ERROR'        ; break;
                case E_CORE_WARNING     : $typeErrorStr = 'E_CORE_WARNING'      ; break;
                case E_COMPILE_ERROR    : $typeErrorStr = 'E_COMPILE_ERROR'     ; break;
                case E_COMPILE_WARNING  : $typeErrorStr = 'E_COMPILE_WARNING'   ; break;
                case E_USER_ERROR       : $typeErrorStr = 'E_USER_ERROR'        ; break;
                case E_USER_WARNING     : $typeErrorStr = 'E_USER_WARNING'      ; break;
                case E_USER_NOTICE      : $typeErrorStr = 'E_USER_NOTICE'       ; break;
                case E_STRICT           : $typeErrorStr = 'E_STRICT'            ; break;
                case E_RECOVERABLE_ERROR: $typeErrorStr = 'E_RECOVERABLE_ERROR' ; break;
                case E_DEPRECATED       : $typeErrorStr = 'E_DEPRECATED'        ; break;
                case E_USER_DEPRECATED  : $typeErrorStr = 'E_USER_DEPRECATED'   ; break;
            }
            return $typeErrorStr;
        }
        
        /**
         * Gére une erreur capturée
         * @access private
         * @param int       Code de l'erreur
         * @param string    Message de l'erreur
         * @param string    Fichier de l'erreur
         * @param int       Ligne de l'erreur
         */          
        private static function catchError($type, $message, $file, $line, $traces=array()){

            // Error callback
            if(is_callable(self::$onError)){
                $func = self::$onError;
                $func(self::getTypeError($type), $message, $file, $line);
            }
 
            // Sortie d'erreur sur le template par defaut
            if(self::$showError){
                $source = '';
                if(is_file($file)){
                    $fileData = file_get_contents($file);
                    $fileData = explode("\n", $fileData);
                    $stepLine = 0;
                    for($stepLine=$line-4; $stepLine<$line+4; $stepLine++){
                        if(isset($fileData[$stepLine])){
                            if($stepLine==$line-1){
                                $source .= '<span class="line">'.$stepLine . '</span> <span class="message focus">' . trim($fileData[$stepLine], "\r\n") . "</span>\r\n";
                            } else {
                                $source .= '<span class="line">'.$stepLine . '</span> <span class="message">' . trim($fileData[$stepLine], "\r\n") . "</span>\r\n";
                            }
                        }
                    }
                    unset($fileData);
                }    
                $htmltrace = '';
                if(sizeof($traces)>0){
                    $htmltrace = '<table cellspacing="0" cellpadding="0">';
                    $index = 1;
                    foreach($traces as $trace){
                        $htmltrace .= 
                        '<tr>'.
                            '<td width="30">'.$index.'</td>'.
                            '<td width="400">'.$trace['function'].'</td>'.
                            '<td>'.($trace['file']!=''?$trace['file'].':'.$trace['line']:'').'</td>'.
                        '</tr>';
                        $index++;
                    }
                    $htmltrace .= '</table>';
                }
                $data = str_replace(
                    array('[Type]', '[Message]', '[File]', '[Line]', '[Source]', '[Trace]'),
                    array(self::getTypeError($type), str_replace("\r", '<br />', $message), $file, $line, $source, $htmltrace),
                    self::getNativeTemplate()
                );
                ob_clean();
                ob_start();
                    header('Content-Type: text/html', true, 503);
                    header('Content-Length: '.strlen($data));
                    echo $data;
                ob_flush();
                exit();
            } else {
                if(!in_array($type, self::$muteErrors)){
                    ob_clean();
                    ob_start();
                        $protocol = isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:'HTTP/1.1';
                        header($protocol.' 503 Service unavailable', true, 503);
                    ob_flush();
                    exit();                    
                }
            }
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
                background-color        : #e5e5e5;
                margin                  : 0px;
                padding                 : 20px;
            }
            table.error{
                width                   : 100%;
                background-color        : #444444;
                color                   : #444444;
                font-family             : Tahoma;
                font-size               : 12px;
                overflow                : hidden;
            }
            table.error td:nth-child(1){
                background-color        : #ffc56a;
                font-weight             : bold;
            }
            table.error td:nth-child(2){
                background-color        : white;
            }
            table.error td{
                padding                 : 3px 5px;
            }
            table.error span{
                display                 : block;
                width                   : 100%;
                min-height              : 20px;
                float                   : left;
                -webkit-box-sizing      : border-box;
                   -moz-box-sizing      : border-box;
                    -ms-box-sizing      : border-box;
                     -o-box-sizing      : border-box;
                        box-sizing      : border-box;
            }     
            table.error code{
                display                 : inline-block;
                width                   : 100%;
                height                  : 100%;
            }
            table.error code span.line{
                color                   : #676fb5;
                width                   : 30px;
                text-align              : right;
                padding-right           : 5px;
                -webkit-box-sizing      : border-box;
                   -moz-box-sizing      : border-box;
                    -ms-box-sizing      : border-box;
                     -o-box-sizing      : border-box;
                        box-sizing      : border-box;                       
            }
            table.error code span.message{
                margin-top              : -20px;
                padding-left            : 35px;
                -webkit-box-sizing      : border-box;
                   -moz-box-sizing      : border-box;
                    -ms-box-sizing      : border-box;
                     -o-box-sizing      : border-box;
                        box-sizing      : border-box; 
            }
            table.error code span.focus{
                font-weight             : bold;
            }
            table.error .trace table{
                width: 100%;
            }
            table.error .trace table td{
                padding                 : 2px 5px 2px 0px;
            }
            table.error .trace table td:nth-child(1){
                font-weight             : normal;
                background-color        : white;
                color                   : #676fb5;
                text-align              : center;
            }            
            
        </style>
    </head>
    <body>
        <table cellpadding="0" cellspacing="1" class="error">
            <tr>
                <td valign="top">Error</td>
                <td valign="top">[Type] [Message]</td>
            </tr>
            <tr>
                <td valign="top">File</td>
                <td valign="top">[File]:[Line]</td>
            </tr>
            <tr>
                <td valign="top">Source</td>
                <td valign="top"><code>[Source]</code></td>
            </tr>
            <tr>
                <td valign="top">Trace</td>
                <td valign="top" class="trace">[Trace]</td>
            </tr>
        </table>
    </body>
</html>