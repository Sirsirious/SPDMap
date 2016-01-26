<?php

class ConnectionFactory {

    public $server = 'localhost';
    public $database = 'db_campo_estr';
    public $username = 'root';
    public $password = 'root';

    function connect() {
        $connection = new PDO("mysql:host=" . $this->server . ";dbname=" . $this->database, $this->username, $this->password);
        return $connection;
    }

}
