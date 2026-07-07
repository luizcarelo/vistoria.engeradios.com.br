<?php
require_once 'config.php';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$erros = [];
$sucesso = '';
function buscarTokenValido($conn, $token) {
    if ($token === '') { return null; }
    $hash = hash('sha256', $token);
    $stmt = $conn->prepare('SELECT r.id, r.usuario_id FROM recuperacao_senha r JOIN usuarios u ON u.id = r.usuario_id WHERE r.token_hash = ? AND r.usado_em IS NULL AND r.expira_em >= NOW() AND u.ativo = 1 LIMIT 1');
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}
$conn = getDBConnection();
$registro = buscarTokenValido($conn, $token);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    if (!$registro) { $erros[] = 'Link invalido ou expirado.'; }
    if ($nova_senha === '') { $erros[] = 'Informe a nova senha.'; }
    if (strlen($nova_senha) < 6) { $erros[] = 'Senha deve ter no minimo 6 caracteres.'; }
    if ($nova_senha !== $confirmar_senha) { $erros[] = 'As senhas nao coincidem.'; }
    if (empty($erros)) {
        $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
        $up->bind_param('si', $hash_senha, $registro['usuario_id']);
        if ($up->execute()) {
            $mk = $conn->prepare('UPDATE recuperacao_senha SET usado_em = NOW() WHERE id = ?');
            $mk->bind_param('i', $registro['id']);
            $mk->execute();
            $mk->close();
            $sucesso = 'Senha redefinida com sucesso.';
            $registro = null;
        } else {
            $erros[] = 'Erro ao salvar nova senha.';
        }
        $up->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redefinir Senha - ENGERADIOS</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}
.box{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}
h1{margin-top:0;color:#b00020}
input{width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
.btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}
.erro{background:#ffe8e8;border-left:4px solid #b00020;padding:12px;margin:12px 0}
.sucesso{background:#e8fff0;border-left:4px solid #18864b;padding:12px;margin:12px 0}
.link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}
</style>
</head>
<body>
<div class="box">
<h1>Redefinir Senha</h1>
<?php if (!empty($erros)): ?><div class="erro"><?php foreach ($erros as $e) { echo htmlspecialchars($e) . '<br>'; } ?></div><?php endif; ?>
<?php if ($sucesso !== ''): ?><div class="sucesso"><?php echo htmlspecialchars($sucesso); ?></div><a href="index.php" class="link">Ir para o login</a><?php endif; ?>
<?php if ($sucesso === '' && $registro): ?>
<form method="post" autocomplete="off">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
<label>Nova senha</label>
<input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label>
<input type="password" name="confirmar_senha" minlength="6" required>
<button class="btn" type="submit">Redefinir senha</button>
</form>
<?php elseif ($sucesso === ''): ?>
<div class="erro">Link invalido ou expirado.</div>
<a href="esqueci_senha.php" class="link">Solicitar novo link</a>
<?php endif; ?>
</div>
</body>
</html>
