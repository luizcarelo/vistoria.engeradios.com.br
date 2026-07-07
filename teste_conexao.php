<?php
/**
 * SCRIPT DE DIAGNÓSTICO DE CONEXÃO (MySQLi)
 * Suba este arquivo para a raiz do seu site (vistoria.engeradios.com.br)
 * e acesse no navegador: https://vistoria.engeradios.com.br/teste_conexao.php
 * * Após confirmar que está funcionando, APAGUE ESTE ARQUIVO por segurança.
 */

echo "<h2>Diagnóstico de Conexão com o Banco de Dados (MySQLi)</h2>";

// Verifica as extensões necessárias do PHP
if (!extension_loaded('mysqli')) {
    die("<p style='color:red;'>Erro: A extensão <b>mysqli</b> não está habilitada no seu PHP. Ative-a no cPanel/Painel de Hospedagem.</p>");
}

require_once 'config.php'; // Chama a conexão do sistema

echo "<p>Tentando conectar via função <code>getDBConnection()</code> do seu sistema...</p>";

try {
    $conn = getDBConnection();
    echo "<p style='color:green;'>✅ Conexão MySQLi estabelecida com sucesso!</p>";

    // Teste extra: tentar realizar uma consulta básica
    $result = $conn->query("SELECT VERSION() as version");
    $row = $result->fetch_assoc();
    echo "<p style='color:blue;'>✅ Banco de dados respondendo. Versão do MySQL/MariaDB: " . htmlspecialchars($row['version']) . "</p>";
    
    // Verifica se as tabelas existem
    $result = $conn->query("SHOW TABLES");
    echo "<h3>Tabelas encontradas no banco '" . DB_NAME . "':</h3><ul>";
    
    if ($result->num_rows > 0) {
        while($table = $result->fetch_array()) {
            echo "<li>" . htmlspecialchars($table[0]) . "</li>";
        }
    } else {
        echo "<li style='color:orange;'>O banco está conectado, mas não possui nenhuma tabela ainda. Você precisa importar o arquivo database.sql.</li>";
    }
    echo "</ul>";

    $conn->close();

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro ao tentar consultar o banco: " . $e->getMessage() . "</p>";
}

echo "<hr><p><b>Atenção:</b> Apague este arquivo após realizar os testes.</p>";
?>