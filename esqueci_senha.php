<?php
require_once 'config.php';
$mensagem = '';
function garantirTabelaRecuperacao($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS recuperacao_senha (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, token_hash VARCHAR(255) NOT NULL, expira_em DATETIME NOT NULL, usado_em DATETIME NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ip_solicitante VARCHAR(45) NULL, INDEX idx_token_hash (token_hash), INDEX idx_usuario_id (usuario_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);
}
function baseUrlSistema() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $scheme . '://' . $host . $dir;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mensagem = 'Se o e-mail estiver cadastrado, enviaremos as instrucoes de redefinicao.';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn = getDBConnection();
        garantirTabelaRecuperacao($conn);
        $stmt = $conn->prepare('SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ins = $conn->prepare('INSERT INTO recuperacao_senha (usuario_id, token_hash, expira_em, ip_solicitante) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)');
            $ins->bind_param('iss', $usuario['id'], $token_hash, $ip);
            if ($ins->execute()) {
                $link = baseUrlSistema() . '/redefinir_senha.php?token=' . urlencode($token);
                $assunto = 'Redefinicao de senha - Vistoria Engeradios';
                $corpo = 'Ola, ' . $usuario['nome'] . "\n\n";
                $corpo .= 'Para redefinir sua senha, acesse o link abaixo:' . "\n";
                $corpo .= $link . "\n\n";
                $corpo .= 'Este link expira em 1 hora. Se voce nao solicitou, ignore esta mensagem.';
                $headers = 'From: noreply@engeradios.com.br' . "\r\n";
                if (!@mail($usuario['email'], $assunto, $corpo, $headers)) {
                    error_log('Falha ao enviar e-mail de recuperacao para usuario ID ' . $usuario['id']);
                }
            }
            $ins->close();
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
<title>Esqueci Minha Senha - ENGERADIOS</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}
.box{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}
h1{margin-top:0;color:#b00020}
input{width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
.btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}
.msg{background:#eef6ff;border-left:4px solid #2b70b8;padding:12px;margin:12px 0}
.link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}
</style>
</head>
<body>
<div class="box">
<h1>Esqueci Minha Senha</h1>
<?php if ($mensagem !== ''): ?><div class="msg"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<label>E-mail cadastrado</label>
<input type="email" name="email" required>
<button class="btn" type="submit">Enviar instrucoes</button>
</form>
<a href="index.php" class="link">Voltar ao login</a>
</div>
</body>
</html>
