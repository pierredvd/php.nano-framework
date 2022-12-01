<?php

    /**
     * Nano framework.
     * @package    Nano
     * @version    1.0
     * @author     DAVID Pierre
     */

    /**
     * Classe de gestion des mails
     */
    namespace Nano;
    
    class Mail{
        
        /**
         * Chemin d'accès au dossiers des vendors
         */
        private static $vendorDir = '';
        
        /**
         * Configuration d'envoi de mail
         */
        private static $config = array();
        
        /**
         * Si php mailer est chargé en memoire
         */
        private static $phpMailerLoaded = false;
        
        /**
         * Instance courante php mailer
         */
        private $mail = null;
        
        /**
         * Configure l'envoi de mail
         * @param string Chemin d'accès aux vendors
         * @param array  Configuration mail
         */
        public static function init($vendorDir, $config){
            self::$vendorDir    = rtrim($vendorDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            self::$config       = $config;
        }
        
        /**
         * Charge les librairies requises
         */
        private static function loadPHPMailer(){
            if(!self::$phpMailerLoaded){
                $phpMailerDir = self::$vendorDir.'phpmailer'.DS;
                if(!is_dir($phpMailerDir)){
                    throw new \Exception('Mail error, vendor "phpmailer" not found at "'.$phpMailerDir.'"');
                }
                $files = array('Exception', 'OAuth', 'SMTP', 'PHPMailer');
                foreach($files as $file){ include_once($phpMailerDir . $file . '.php'); }
                self::$phpMailerLoaded = true;
            }
        }
        
        /**
         * Crée un nouveau mail
         */
        public function __construct(){
            self::loadPHPMailer();
            $this->mail = new \PHPMailer\PHPMailer\PHPMailer;
            $this->mail->SMTPDebug=0;
            $this->mail->CharSet    = "UTF-8";
            if(self::$config['isSMTP']){ $this->mail->isSMTP(); }
            $this->mail->Host       = self::$config['smtpHost']; 
            if(isset(self::$config['smtpAuth']) && self::$config['smtpAuth']===true){
                $this->mail->SMTPAuth   = true;                                                     // Enable SMTP authentication
                $this->mail->Username   = self::$config['smtpUser'];                                // SMTP username
                $this->mail->Password   = self::$config['smtpPassword'];                            // SMTP password
            } else {
                $this->mail->SMTPAuth   = false;                                                     // Enable SMTP authentication
            }
            if(isset(self::$config['smtpSecure']) && self::$config['smtpSecure']!==false){
                $this->mail->SMTPSecure = self::$config['smtpSecure'];                          // Enable encryption, 'ssl' also accepted
            }
            $this->mail->Port       = self::$config['smtpPort'];                                //Set the SMTP port number - 587 for authenticated TLS
            $this->mail->isHTML(self::$config['isHtml']);
            if(is_array(self::$config['smtpOptions'])){
                $this->mail->SMTPOptions = self::$config['smtpOptions'];
            }

            $this->mail->setFrom(self::$config['fromAddress'], self::$config['fromName']);      //Set who the message is to be sent from
            $this->mail->addReplyTo(self::$config['fromAddress'], self::$config['fromName']);   //Set who the message is to be sent from
            $this->mail->WordWrap = 50;  
        }
        
        /**
         * Ajoute un destinataire
         * @param string  Adresse mail du destinataire
         * @param string  Nom du destinataire
         * @return static
         */
        public function addAddress($address, $name=''){
            $this->mail->addAddress($address, $name);
            return $this;
        }
        
        /**
         * Ajoute un destinataire en copie cachée
         * @param string  Adresse mail du destinataire
         * @param string  Nom du destinataire
         * @return static
         */
        public function addCC($address, $name=''){
            $this->mail->addCC($address, $name);
        }
        
        /**
         * Ajoute une pièce jointe
         * @param string  Chemin d'accès à la pièce jointe
         * @return static
         */
        public function addAttachment($path){
            $name = basepath($path);
            if(is_file($path)){
                $this->mail->addAttachment($path, $name);
            } else {
                throw new \Exception('Cannot attach file "'.$path.'" to mail, file not found');
            }
            return $this;
        }        
        
        /**
         * Defini le nom de l'expediteur
         * @param string  Nom de l'expediteur
         * @return static
         */
        public function setFromName($name){
            $this->mail->setFrom(self::$config['fromAddress'], $name);
            return $this;
        }
        
        /**
         * Defini le message
         * @param string  Sujet du message
         * @param string  Message HTML
         * @param string  Message Alternatif en mode texte
         * @param boolean Si le message doit etre envoyé en html
         * @return static
         */
        public function setMessage($subject, $htmlBody, $textBody='', $html=false){
            $textBody = ($textBody==''?strip_tags($htmlBody):$textBody);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $htmlBody;
            $this->mail->AltBody = $textBody;
            return $this;
        }
        
        /**
         * Ajouter une image embarquée
         * @param string Nom associé à l'attachement
         * @param string Chemin d'accès de l'image
         */
        public function addEmbeddedImage($name, $path){
            if(is_file($path)){
                $this->mail->AddEmbeddedImage($path, $name, basename($path));
            } else {
                throw new \Exception('Cannot attach embedded image "'.$path.'" to mail, file not found');
            }
            return $this;
        }
        
        /**
         * Envoyer le message
         * @throw Exception
         * @return boolean
         */
        public function send(){
            try{
                if(!$this->mail->send()){
                    throw new \Exception('');
                }
            } catch(\Exception $e){
                throw new \Exception('Mail cannot be sent: '.$this->mail->ErrorInfo);
            }
            return true;
        }
        
    }
