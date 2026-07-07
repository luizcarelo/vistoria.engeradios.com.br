<?php
/**
 * Classe de Conexão com o Banco de Dados.
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

    /**
     * Obtém a conexão com o banco de dados.
     * Se a conexão ainda não existir, ela será criada.
     * @return PDO A instância da conexão PDO.
     */
    public function getConnection()
    {
        // Se a conexão ainda não foi estabelecida, cria uma nova.
        if ($this->conn === null) {
            try {
                $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Em caso de falha, exibe o erro. Em produção, o ideal é logar o erro.
                // Para a API, é melhor retornar um JSON.
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Erro de Conexão com o Banco de Dados: ' . $e->getMessage()
                ]);
                exit; // Interrompe a execução para não expor outros erros.
            }
        }

        return $this->conn;
    }
}
?>