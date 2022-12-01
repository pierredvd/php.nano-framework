<?php
    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des view twig de controlleurs
     */
    namespace Nano\View;
    
    class Twig extends \Nano\View{

        private static $twig    = null;
        private $template       = null;
        
        /**
         * Constructeur d'une réponse de controller
         * @param string Chemin d'accès au fichier twig
         * @param array  Données envoyés a la vue
         */
        public function __construct($twigViewPath, array $data = array(), $httpCode=200, array $headers=array()){
            if(!isset(static::$httpCodeStr[$httpCode])){
                throw new \Exception('Twig view error, unknown http code "'.$httpCode.'"');
            }            
            self::loadTwig();
            $path = static::$viewDir.$twigViewPath;
            if(!is_file($path)){ throw new \Exception('Twig view error: Template "'.$twigViewPath.'" not found'); }
            $this->template = self::$twig->loadTemplate($twigViewPath);            
            $this->data     = $data;
            $this->headers  = array_merge(
                array('Content-type' => 'text/html; charset=utf-8'),
                $headers
            );
        }

        /**
         * Ajouter una variable au template
         * @access public
         * @param string    Nom de la variable
         * @param mixed     Valeur de la variable
         * @return static
         */
        public function set($name, $value){
            $this->data[$name] = $value;
            return $this;
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
            echo $this->compile();
        }
        
        /** 
         * Retourne le rendu sous forme de texte
         */
        public function compile($useCache=true){
            if($useCache){
                $className = get_class($this->template);
                $cachePath = substr($className, 15);
                $cachePath = static::$cacheDir.'twig'.DIRECTORY_SEPARATOR.substr($cachePath, 0, 2).DIRECTORY_SEPARATOR.substr($cachePath, 2, 2).DIRECTORY_SEPARATOR.substr($cachePath, 4).'.php';
                if(is_file($cachePath)){
                    @unlink($cachePath);
                }
            }
            return @$this->template->render($this->data);
        }

        /**
         * Vider le cache twig
         * @return boolean
         */
        public static function clearCache(){
            $cacheDir = static::$cacheDir.'twig'.DIRECTORY_SEPARATOR;
            self::deleteCacheRecursive($cacheDir, array_diff(scandir($cacheDir), array('.', '..')));
            return true;
        }
        
        /**
         * Supprimer le cache de manière recursives
         * @return boolean
         */
        private static function deleteCacheRecursive($path, $list){
            foreach($list as $item){
                if(is_dir($path.$item.DIRECTORY_SEPARATOR)){
                    self::deleteCacheRecursive($path.$item.DIRECTORY_SEPARATOR, array_diff(scandir($path.$item.DIRECTORY_SEPARATOR), array('.', '..')));
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
         * Charger twig
         * @access private
         */        
        private static function loadTwig(){
            
            if(is_null(self::$twig)){
                
                $requires = array(
                    //'Autoloader.php', //-> Deprecied
                    'CacheInterface.php',
                    'Cache/Filesystem.php',
                    'Cache/Null.php',
                    'CompilerInterface.php',
                    'Compiler.php',
                    'ExistsLoaderInterface.php',
                    'Environment.php',
                    'Error.php',
                    'Error/Loader.php',
                    'Error/Runtime.php',
                    'Error/Syntax.php',
                    'ExpressionParser.php',
                    'ExtensionInterface.php',
                    'Extension.php',
                    'Extension/Core.php',
                    'Extension/Debug.php',
                    'Extension/Escaper.php',
                    'Extension/GlobalsInterface.php',
                    'Extension/InitRuntimeInterface.php',
                    'Extension/Optimizer.php',
                    'Extension/Profiler.php',
                    'Extension/Sandbox.php',
                    'Extension/Staging.php',
                    'Extension/StringLoader.php',
                    'FileExtensionEscapingStrategy.php',
                    /*
                    'FilterCallableInterface.php',
                    'FilterInterface.php',
                    'Filter.php',
                    'Filter/Function.php',
                    'Filter/Method.php',
                    'Filter/Node.php',
                    'FunctionInterface.php',
                    'FunctionCallableInterface.php',
                    'Function.php',
                    'Function/Function.php',
                    'Function/Method.php',
                    'Function/Node.php',
                    */
                    'LexerInterface.php',
                    'Lexer.php',
                    'LoaderInterface.php',
                    'Loader/Array.php',
                    'Loader/Chain.php',
                    'Loader/Filesystem.php',
                    //'Loader/String.php',
                    'Markup.php',
                    'NodeInterface.php',
                    'Node.php',
                    'NodeOutputInterface.php',
                    'NodeTraverser.php',
                    'NodeVisitorInterface.php',
                    'Node/AutoEscape.php',
                    'Node/Block.php',
                    'Node/BlockReference.php',
                    'Node/Body.php',
                    'Node/CheckSecurity.php',
                    'Node/Do.php',
                    'Node/Include.php',
                    'Node/Embed.php',
                    'Node/Expression.php',
                    'Node/Expression/Array.php',
                    'Node/Expression/Binary.php',
                    'Node/Expression/Binary/Add.php',
                    'Node/Expression/Binary/And.php',
                    'Node/Expression/Binary/BitwiseAnd.php',
                    'Node/Expression/Binary/BitwiseOr.php',
                    'Node/Expression/Binary/BitwiseXor.php',
                    'Node/Expression/Binary/Concat.php',
                    'Node/Expression/Binary/Div.php',
                    'Node/Expression/Binary/EndsWith.php',
                    'Node/Expression/Binary/Equal.php',
                    'Node/Expression/Binary/FloorDiv.php',
                    'Node/Expression/Binary/Greater.php',
                    'Node/Expression/Binary/GreaterEqual.php',
                    'Node/Expression/Binary/In.php',
                    'Node/Expression/Binary/Less.php',
                    'Node/Expression/Binary/LessEqual.php',
                    'Node/Expression/Binary/Matches.php',
                    'Node/Expression/Binary/Mod.php',
                    'Node/Expression/Binary/Mul.php',
                    'Node/Expression/Binary/NotEqual.php',
                    'Node/Expression/Binary/NotIn.php',
                    'Node/Expression/Binary/Or.php',
                    'Node/Expression/Binary/Power.php',
                    'Node/Expression/Binary/Range.php',
                    'Node/Expression/Binary/StartsWith.php',
                    'Node/Expression/Binary/Sub.php',
                    'Node/Expression/BlockReference.php',
                    'Node/Expression/Call.php',
                    'Node/Expression/Conditional.php',
                    'Node/Expression/Constant.php',
                    //'Node/Expression/ExtensionReference.php',
                    'Node/Expression/Filter.php',
                    'Node/Expression/Filter/Default.php',
                    'Node/Expression/Function.php',
                    'Node/Expression/GetAttr.php',
                    'Node/Expression/MethodCall.php',
                    'Node/Expression/Name.php',
                    'Node/Expression/AssignName.php',
                    'Node/Expression/NullCoalesce.php',
                    'Node/Expression/Parent.php',
                    'Node/Expression/TempName.php',
                    'Node/Expression/Test.php',
                    'Node/Expression/Test/Constant.php',
                    'Node/Expression/Test/Defined.php',
                    'Node/Expression/Test/Divisibleby.php',
                    'Node/Expression/Test/Even.php',
                    'Node/Expression/Test/Null.php',
                    'Node/Expression/Test/Odd.php',
                    'Node/Expression/Test/Sameas.php',
                    'Node/Expression/Unary.php',
                    'Node/Expression/Unary/Neg.php',
                    'Node/Expression/Unary/Not.php',
                    'Node/Expression/Unary/Pos.php',
                    'Node/Flush.php',
                    'Node/For.php',
                    'Node/ForLoop.php',
                    'Node/If.php',
                    'Node/Import.php',
                    'Node/Macro.php',
                    'Node/Module.php',
                    'Node/Print.php',
                    'Node/Sandbox.php',
                    'Node/SandboxedPrint.php',
                    'Node/Set.php',
                    'Node/SetTemp.php',
                    'Node/Spaceless.php',
                    'Node/Text.php',
                    'BaseNodeVisitor.php',
                    'NodeVisitor/Escaper.php',
                    'NodeVisitor/Optimizer.php',
                    'NodeVisitor/SafeAnalysis.php',
                    'NodeVisitor/Sandbox.php',
                    'ParserInterface.php',
                    'Parser.php',
                    'Profiler/Profile.php',
                    'Profiler/Dumper/Text.php',
                    'Profiler/Dumper/Blackfire.php',
                    'Profiler/Dumper/Html.php',
                    'Profiler/Node/EnterProfile.php',
                    'Profiler/Node/LeaveProfile.php',
                    'Profiler/NodeVisitor/Profiler.php',
                    'Sandbox/SecurityError.php',
                    'Sandbox/SecurityNotAllowedFilterError.php',
                    'Sandbox/SecurityNotAllowedFunctionError.php',
                    'Sandbox/SecurityNotAllowedTagError.php',
                    'Sandbox/SecurityPolicyInterface.php',
                    'Sandbox/SecurityPolicy.php',
                    'SimpleFilter.php',
                    'SimpleFunction.php',
                    'SimpleTest.php',
                    'TemplateInterface.php',
                    'Template.php',
                    //'Test/Function.php',
                    /*
                    'Test/IntegrationTestCase.php',
                    'Test/Method.php',
                    'Test/Node.php',
                    'Test/NodeTestCase.php',
                    'TestInterface.php',
                    'TestCallableInterface.php',
                    'Test.php',
                    */
                    'Token.php',
                    'TokenParserInterface.php',
                    'TokenParser.php',
                    'TokenParserBrokerInterface.php',
                    'TokenParserBroker.php',
                    'TokenParser/AutoEscape.php',
                    'TokenParser/Block.php',
                    'TokenParser/Do.php',
                    'TokenParser/Include.php',
                    'TokenParser/Embed.php',
                    'TokenParser/Extends.php',
                    'TokenParser/Filter.php',
                    'TokenParser/Flush.php',
                    'TokenParser/For.php',
                    'TokenParser/From.php',
                    'TokenParser/If.php',
                    'TokenParser/Import.php',
                    'TokenParser/Macro.php',
                    'TokenParser/Sandbox.php',
                    'TokenParser/Set.php',
                    'TokenParser/Spaceless.php',
                    'TokenParser/Use.php',
                    'TokenStream.php',
                    'Util/DeprecationCollector.php',
                    'Util/TemplateDirIterator.php'
                );
                foreach($requires as $require){
                    if(is_file(static::$vendorDir.'twig'.DIRECTORY_SEPARATOR.$require)){
                        include_once(static::$vendorDir.'twig'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $require));
                    }
                }
                
                // Cache dir
                $cacheDir = static::$cacheDir.'twig'.DIRECTORY_SEPARATOR;
                if(!is_dir($cacheDir)){
                    @mkdir($cacheDir, 0775, true);
                    @chmod($path, 0775);
                }
                
                $loader         = new \Twig_Loader_Filesystem(static::$viewDir);
                $params         = static::$cacheEnable?array('cache' => $cacheDir):array();
                self::$twig     = new \Twig_Environment($loader, $params);
                
                self::loadTwigFunction(self::$twig);
                self::loadTwigFilter(self::$twig);
            }             
            
        } 
        
        /**
         * Ajouter des fonctions twig
         */
        private static function loadTwigFunction($twig){
            
            // Functions personalisées
            $twig->addFunction(
                new \Twig_SimpleFunction(
                    'basepath', 
                    function(){
                        return \Nano\Router::getBaseUri();
                    }
                )
            );
            $twig->addFunction(
                new \Twig_SimpleFunction(
                    'basename', 
                    function($path){
                        return basename($path);
                    }
                )
            );                    
            $twig->addFunction(
                new \Twig_SimpleFunction(
                    'uri', 
                    function(){
                        return \Nano\Router::getUri();
                    }
                )
            );
            $twig->addFunction(
                new \Twig_SimpleFunction(
                    'translate', 
                    function($key, $lang=null){
                        return \Nano\Lang::translate($key, $lang);
                    }
                )
            );
            $twig->addFunction(
                new \Twig_SimpleFunction(
                    'config', 
                    function($key){
                        return \Nano\Config::get($key);
                    }
                )
            );                
        }
        
        /**
         * Ajouter des filtres twig
         */
        private static function loadTwigFilter($twig){
            $twig->addFilter(
                new \Twig_SimpleFilter('time', function ($string, $timeFormat='H:i:s'){
                    $seconds    = $string+0;
                    $hours      = intval($seconds / 3600);
                    $seconds   -= $hours * 3600;
                    $minutes    = intval($seconds / 60);
                    $seconds   -= $minutes * 60;
                    $seconds    = intval($seconds);
                    return str_replace(
                        array(
                            'H', 
                            'i', 
                            's'
                        ),
                        array(
                           $hours,
                           ($minutes<10?'0':'').$minutes,
                           ($seconds<10?'0':'').$seconds
                        ),
                        $timeFormat
                    );
                })
            );
            $twig->addFilter(
                new \Twig_SimpleFilter('base64', function ($string){
                    if(preg_match("#^(http|https):\/\/#", $string)){
                        $basepath = \Nano\Router::getBaseUri();
                        if(substr($string, 0, strlen($basepath))==$basepath){
                            // Local file
                            $path = rtrim(DIR_PUBLIC, DS).str_replace('/', DS, substr($string, strlen($basepath)));
                            if(is_file($path)){
                                $data = file_get_contents($path);
                                $mime = mime_content_type($path);
                                return 'data:'.$mime.';base64,'.base64_encode($data);
                            }
                        }
                        $headers = get_headers($string, 1);
                        if(isset($headers['Content-Type']) && strpos($headers[0], '200')>0){
                            $mime = $headers['Content-Type'];
                            if($data = @file_get_contents($string)){
                                return 'data:'.$mime.';base64,'.base64_encode($data);
                            }
                        }
                    }
                    return base64_encode($string);
                })
            );  
            $twig->addFilter(
                new \Twig_SimpleFilter('rewrite', function ($string){
                    $sub = array(
                        '[\s\t\r\n]+'    => '_',
                        '[ÀÁÂÃÄÅàáâãäå]' => 'a',
                        '[Çç]'           => 'c',
                        '[ÈÉÊËèéêë]'     => 'e',
                        '[ÌÍÎÏìíîï]'     => 'i',
                        '[Ññ]'           => 'n',
                        '[ÒÓÔÕÖØòóôõöø]' => 'o',
                        '[ÙÚÛÜùúûü]'     => 'u',
                        '[ÿ]'            => 'y',
                        '[^a-z0-9]+'     => '_',
                        '^_+'            => '',
                        '_+$'            => ''
                    );
                    $rewrite = strtolower(trim($string));
                    foreach($sub as $pattern => $replace){
                        $rewrite = preg_replace('/'.$pattern.'/', $replace, $rewrite);
                    }
                    return $rewrite;
                })
            );
            $twig->addFilter(
                new \Twig_SimpleFilter('byteformat', function ($bytes, $decimal=2){
                    if(!is_numeric($decimal) || $decimal<0){ $decimal = 0; }
                    $precision  = pow(10, $decimal);
                    $levels = array(
                        'To' => 1099511627776,
                        'Go' => 1073741824,
                        'Mo' => 1048576,
                        'Ko' => 1024
                    );
                    $result = $bytes . ' o';
                    foreach($levels as $unit => $value){
                        if($bytes>=$value){
                            $result = round($bytes/($value==0?1:$value)*$precision)/$precision.' '.$unit;
                            break;
                        }
                    }
                    return $result;
                })
            );                
                
        }
        
    }