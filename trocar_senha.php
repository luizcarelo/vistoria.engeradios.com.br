<?php
require_once 'config.php';
verificarLogin();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$erros = [];
$sucesso = '';
$usuario_id = intval($_SESSION['usuario_id'] ?? 0);
if ($usuario_id <= 0) { header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    if ($senha_atual === '') { $erros[] = 'Informe a senha atual.'; }
    if ($nova_senha === '') { $erros[] = 'Informe a nova senha.'; }
    if (strlen($nova_senha) < 6) { $erros[] = 'A nova senha deve ter no minimo 6 caracteres.'; }
    if ($nova_senha !== $confirmar_senha) { $erros[] = 'As senhas nao coincidem.'; }
    if (empty($erros)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare('SELECT senha FROM usuarios WHERE id = ? AND ativo = 1');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $erros[] = 'Usuario nao encontrado ou inativo.';
        } else {
            $row = $result->fetch_assoc();
            if (!password_verify($senha_atual, $row['senha'])) {
                $erros[] = 'Senha atual incorreta.';
            } else {
                $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
                $up->bind_param('si', $hash, $usuario_id);
                if ($up->execute()) {
                    $sucesso = 'Senha alterada com sucesso.';
                    error_log('Senha alterada pelo proprio usuario ID ' . $usuario_id);
                } else {
                    $erros[] = 'Erro ao salvar nova senha.';
                }
                $up->close();
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trocar Senha - ENGERADIOS</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}
.box{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}
h1{margin-top:0;color:#b00020}
label{display:block;margin-top:14px;font-weight:bold}
input{width:100%;padding:12px;margin-top:6px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
.btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}
.erro{background:#ffe8e8;border-left:4px solid #b00020;padding:12px;margin:12px 0}
.sucesso{background:#e8fff0;border-left:4px solid #18864b;padding:12px;margin:12px 0}
.link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}
</style>
</head>
<body>
<div class="box">
<h1>Trocar Senha</h1>
<?php if (!empty($erros)): ?>
<div class="erro"><?php foreach ($erros as $e) { echo htmlspecialchars($e) . '<br>'; } ?></div>
<?php endif; ?>
<?php if ($sucesso !== ''): ?>
<div class="sucesso"><?php echo htmlspecialchars($sucesso); ?></div>
<?php endif; ?>
<form method="post" autocomplete="off">
<label>Senha atual</label>
<input type="password" name="senha_atual" required>
<label>Nova senha</label>
<input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label>
<input type="password" name="confirmar_senha" minlength="6" required>
<button class="btn" type="submit">Alterar senha</button>
</form>
<a href="javascript:history.back()" class="link">Voltar</a>
</div>
</body>
</html>
