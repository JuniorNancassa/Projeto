<?php

class Database {
    private $servername;
    private $username;
    private $password;
    private $database;
    private $conn;

    // Construtor que recebe os parâmetros de conexão
    public function __construct($servername, $username, $password, $database) {
        $this->servername = $servername;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    // Método para conectar ao banco de dados com PDO
    public function conectar() {
        try {
            $dsn = "mysql:host=$this->servername;dbname=$this->database;charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            // Define o modo de erro do PDO para exceções
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            die("Falha na conexão: " . $e->getMessage());
        }
    }

    // Método para fechar a conexão
    public function fechar() {
        $this->conn = null;
    }
}

// Exemplo de uso:
$database = new Database("seu_servidor", "seu_usuario", "sua_senha", "seu_banco_de_dados");

// Conectar ao banco
$conn = $database->conectar();

// Fechar a conexão quando não for mais necessária
$database->fechar();

?>
