<?php
Class Conexion{

   

    private $host;
    private $user;
    private $pass;
    private $db;

    public function __construct($host, $user, $pass, $db) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->db = $db;
    }

    public function conectar() {
        date_default_timezone_set("America/Mexico_City");
        require_once('../getEnv.php');
        try {
            return new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db . ";charset=utf8mb4", 
                           $this->user, 
                           $this->pass);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}