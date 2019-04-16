<?php

/*
En klass för att ansluta till databasen
*/

class Database {

    private static $pdo = NULL;

    // connect är en funktion som ansluter till datbasen
    private static function connect() {
        $host = 'localhost';
        $db   = 'classicmodels';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        try {
            static::$pdo = new PDO($dsn, $user, $pass);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // pdo är den funktion som ska användas för att ansluta till databasen
    public static function pdo() {
        // ifall det inte redan finns en anslutning till databasen skapas en anslutning
        if(static::$pdo == NULL) {
            static::connect();
        }
        // anslutningen retuneras tillbaka
        return static::$pdo;

    }

}


?>