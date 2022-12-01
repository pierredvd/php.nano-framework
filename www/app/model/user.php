<?php

    /**
     * Classe de modèle d'une table
     */
    namespace App\Model;

    class User extends \Nano\Model{
        
        // Nom de la connexion utilisé (defini en configuration)
        protected static $connexion         = 'default';

        /**
         * Recupère un utilisateur
         * @return arrya|null
         */
        public static function list($userid){
            $query = '
            SELECT  userid, 
                    login, 
                    adminlevel,
                    name, 
                    lastname, 
                    createdat, 
                    updatedat
            FROM    users;';
            $result = self::query($query);
            $users   = null;
            if(sizeof($result)>0){
                $users = [
                    'userid'    => $result[0]['userid']+0,
                    'login'     => $result[0]['login'],
                    'adminlevel'=> $result[0]['adminlevel']+0,
                    'name'      => $result[0]['name'],
                    'lastname'  => $result[0]['lastname'],
                    'createdat'  => strtotime($result[0]['createdat']),
                    'updatedat'  => strtotime($result[0]['updatedat'])
                ];
            }
            return $users;
        }
         
    }

    