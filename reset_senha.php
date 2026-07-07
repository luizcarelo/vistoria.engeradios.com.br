<?php
/**
 * SCRIPT DE REDEFINIÇÃO DE SENHA DE ADMINISTRADOR
 * * 1. Altere a variável $nova_senha abaixo para a senha que desejar.
 * 2. Coloque este ficheiro na raiz do seu site (vistoria.engeradios.com.br).
 * 3. Aceda no navegador: https://vistoria.engeradios.com.br/reset_senha.php
 * 4. APAGUE ESTE FICHEIRO IMEDIATAMENTE APÓS O USO.
 */

require_once 'config.php';

// Defina o email do administrador e a nova senha desejada aqui:
$email_admin = 'admin@engeradios.com.br';
$nova_senha = 'MudarParaUmaSenhaForte123!'; 

// Cria o hash seguro da nova senha (padrão moderno do PHP)
$senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

echo "<h2>Recuperação de Senha do Sistema</h2>";

try {
    // Conecta usando a função do seu config.php
    $conn = getDBConnection();
    
    // Verifica se o utilizador existe na base de dados
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email_admin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // O utilizador existe, vamos atualizar a senha
        $update = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $update->bind_param("ss", $senha_hash, $email_admin);
        
        if ($update->execute()) {
            echo "<p style='color:green;'>✅ <b>Sucesso!</b> A senha do utilizador <b>{$email_admin}</b> foi alterada.</p>";
            echo "<p>A sua nova senha para efetuar o login é: <code>{$nova_senha}</code></p>";
            echo "<hr><p style='color:red;'><b>⚠️ ATENÇÃO:</b> Por motivos de segurança, elimine o ficheiro <code>reset_senha.php</code> do servidor agora mesmo!</p>";
        } else {
            echo "<p style='color:red;'>❌ Erro ao atualizar a senha na base de dados.</p>";
        }
        $update->close();
    } else {
        echo "<p style='color:red;'>❌ O utilizador <b>{$email_admin}</b> não foi encontrado na tabela de utilizadores.</p>";
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Erro crítico: " . $e->getMessage() . "</p>";
}
?>