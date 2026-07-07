<?php
// Aumentar limite de memória globalmente e remover limites de upload
ini_set('memory_limit', '512M');
ini_set('upload_max_filesize', '5000M');
ini_set('post_max_size', '5000M');
ini_set('max_file_uploads', '200');
ini_set('max_execution_time', '600');
ini_set('max_input_time', '600');

// Configuração de Sessão
// Modificado: Só altera as configurações se a sessão NÃO estiver ativa
//if (session_status() === PHP_SESSION_NONE) {
//    ini_set('session.gc_maxlifetime', 28800); // 8 horas
//    ini_set('session.cookie_lifetime', 28800);
//    session_set_cookie_params(28800);
//}

// Configuração do Banco de Dados
define('DB_HOST', 'mysql.engeradios.com.br');
define('DB_USER', 'engeradios_add1');
// CORREÇÃO AQUI: Ajustado para a senha exata que você informou na primeira mensagem
define('DB_PASS', '2026Engeradios'); 
define('DB_NAME', 'engeradios');

// Configuração de E-mail e Sistema
define('EMAIL_DESTINO', 'operacional@engeradios.com.br');
define('EMAIL_REMETENTE', 'noreply@engeradios.com.br');
define('NOME_SISTEMA', 'Vistoria Remota ENGERADIOS');

// Configuração de Upload (LIMITES REMOVIDOS)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880000); // 5000MB (5GB)
define('MAX_AUDIO_DURATION', 600); // 10 minutos em segundos

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco de dados (Mantendo o seu padrão original MySQLi)
function getDBConnection() {
    // Força o PHP a lançar Exceptions (Erros detalhados) quando o MySQLi falhar
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (mysqli_sql_exception $e) {
        // Exibe o erro de forma mais detalhada para ajudar no diagnóstico
        die("<h3>Falha Crítica na conexão com o Banco de Dados</h3>
             <p><strong>Detalhe Técnico:</strong> " . $e->getMessage() . "</p>
             <p><strong>Dica:</strong> Se o erro for 'Connection Refused', não esqueça de liberar o IP deste site no ícone 'MySQL Remoto' do painel cPanel do servidor de banco de dados.</p>");
    }
}

// Função para verificar se usuário está logado
function verificarLogin() {
    // Adicionado verificador para evitar erro de "Session already started"
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Função para verificar se é administrador
function verificarAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'admin') {
        header('Location: index.php');
        exit;
    }
}

// Criar diretório de uploads se não existir
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    mkdir(UPLOAD_DIR . 'fotos/', 0755, true);
    mkdir(UPLOAD_DIR . 'audios/', 0755, true);
}
?>