<?php

    /**
     * Classe de gestion des configurations
     */
    namespace App\Controller;
    
    use \App\Model\User as ModelUser;

    class Index{
        
        public function index(){
            return new \Nano\View\Twig('index.twig', array(), 200, array());
        }

        public function e404(){
            return new \Nano\View\Twig('e404.twig', array(), 404, array());
        }

        public function e403(){
            return new \Nano\View\Twig('e404.twig', array(), 403, array());
        }

    };                                                                                                                                                 