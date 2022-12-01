<?php
    return array(
        'error'                     => array(
            'show'                  => true, // Afficher les erreurs
            'log'                   => true  // Faire un trace log automatique lors d'une erreur
        ),
        'view'                      => array(
            'cache'                 => false, // Activer le cache twig et less
            'less'                  => array(
                // Liste les fichiers less à compiler
                'index.less'              => 'login.css'
            ),
        ),
        'lang'                      => array(
            'default'               => 'fr'   // Langue selectionée par defaut
        ),
        'mail'                      => array( // Parametres de l'envoi de mail
            'isSMTP'                => true,
            'smtpAuth'              => true,
            'smtpHost'              => 'smtp.live.com', //'smtp.gmail.com',
            'smtpUser'              => 'login@emailer.com',
            'smtpPassword'          => '************',
            'smtpSecure'            => 'tls',
            'smtpPort'              => 587,
            'smtpOptions'           => array(
                /*
                'ssl'               => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                )
                */
            ),
            'fromAddress'           => 'login@emailer.com',
            'fromName'              => 'Nano',
            'isHtml'                => false,
            'wordWrap'              => 50,
        )
    );