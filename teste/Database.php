<?php
/**
 * Classe de Conexão com o Banco de Dados (Singleton Pattern)
 * Garante que haverá apenas uma única instância da conexão.
 */
class Database
{
    // --- ATENÇÃO: Insira aqui as suas credenciais do banco de dados ---
    private $host = '10.132.36.2';
    private $db_name = 'engeradid20423c4_chamado';
    private $username = 'engeradid20423c4_luizcarelo';
    private $password = '2?.rPiRZnoF-M4A[';
    // --------------------------------------------------------------------

    private $conn;
    private static $instance = null;

    private function __construct()
    {
        $this->conn = null;
        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Em caso de falha, exibe o erro. Em produção, o ideal é logar o erro.
            die('Erro de Conexão com o Banco de Dados: ' . $e->getMessage());
        }
    }

    /**
     * Método estático que controla o acesso à instância da conexão.
     * @return PDO A instância da conexão PDO.
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
