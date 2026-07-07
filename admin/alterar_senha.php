<?php
require_once '../config.php';
verificarAdmin();
$usuario_id = intval($_GET['id'] ?? 0);
if ($usuario_id <= 0) {
    $_SESSION['erro'] = 'ID de usuario nao informado.';
    header('Location: usuarios.php');
    exit;
}
$conn = getDBConnection();
$stmt = $conn->prepare('SELECT id, nome, email, tipo FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['erro'] = 'Usuario nao encontrado.';
    header('Location: usuarios.php');
    exit;
}
$usuario = $result->fetch_assoc();
$stmt->close();
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    if ($nova_senha === '') { $erros[] = 'Nova senha e obrigatoria.'; }
    if (strlen($nova_senha) < 6) { $erros[] = 'Senha deve ter no minimo 6 caracteres.'; }
    if ($nova_senha !== $confirmar_senha) { $erros[] = 'As senhas nao coincidem.'; }
    if (empty($erros)) {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
        $up->bind_param('si', $hash, $usuario_id);
        if ($up->execute()) {
            $admin = $_SESSION['usuario_nome'] ?? 'admin';
            error_log('Senha do usuario ID ' . $usuario_id . ' alterada por ' . $admin);
            $_SESSION['sucesso'] = 'Senha alterada com sucesso.';
            header('Location: usuarios.php');
            exit;
        } else {
            $erros[] = 'Erro ao alterar senha.';
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
<title>Alterar Senha - ENGERADIOS</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}
.box{max-width:560px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}
h1{margin-top:0;color:#b00020}
.dados{background:#f7f7f7;padding:12px;border-radius:6px;margin-bottom:18px}
label{display:block;margin-top:14px;font-weight:bold}
input{width:100%;padding:12px;margin-top:6px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
.btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}
.erro{background:#ffe8e8;border-left:4px solid #b00020;padding:12px;margin:12px 0}
.link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}
</style>
</head>
<body>
<div class="box">
<h1>Alterar Senha</h1>
<div class="dados">
<strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['nome']); ?><br>
<strong>E-mail:</strong> <?php echo htmlspecialchars($usuario['email']); ?><br>
<strong>Tipo:</strong> <?php echo htmlspecialchars($usuario['tipo']); ?>
</div>
<?php if (!empty($erros)): ?>
<div class="erro"><?php foreach ($erros as $e) { echo htmlspecialchars($e) . '<br>'; } ?></div>
<?php endif; ?>
<form method="post" autocomplete="off">
<label>Nova senha</label>
<input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label>
<input type="password" name="confirmar_senha" minlength="6" required>
<button class="btn" type="submit">Alterar senha</button>
</form>
<a href="usuarios.php" class="link">Voltar para usuarios</a>
</div>
</body>
</html>
