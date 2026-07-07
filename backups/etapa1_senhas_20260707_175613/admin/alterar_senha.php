<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();

// Verificar se foi enviado ID do usuário
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['erro'] = 'ID do usuário não informado';
    header('Location: usuarios.php');
    exit;
}

$usuario_id = intval($_GET['id']);

// Buscar dados do usuário
$query = "SELECT id, nome, email, tipo FROM usuarios WHERE id = $usuario_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Usuário não encontrado';
    header('Location: usuarios.php');
    exit;
}

$usuario = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    $erros = [];
    
    // Validações
    if (empty($nova_senha)) {
        $erros[] = 'Nova senha é obrigatória';
    } elseif (strlen($nova_senha) < 6) {
        $erros[] = 'Senha deve ter no mínimo 6 caracteres';
    }
    
    if ($nova_senha !== $confirmar_senha) {
        $erros[] = 'As senhas não coincidem';
    }
    
    // Se não houver erros, atualizar senha
    if (empty($erros)) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $query = "UPDATE usuarios SET senha = '$senha_hash' WHERE id = $usuario_id";
        
        if ($conn->query($query)) {
            $_SESSION['sucesso'] = 'Senha alterada com sucesso!';
            header('Location: usuarios.php');
            exit;
        } else {
            $erros[] = 'Erro ao alterar senha: ' . $conn->error;
        }
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
    <link rel="icon" type="image/png" href="../Logooriginal.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header img {
            height: 60px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            color: #c41e3a;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert ul {
            margin-left: 20px;
        }
        
        .user-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .user-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 80px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #dc3545;
            color: white;
        }
        
        .badge-supervisor {
            background: #007bff;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        
        .form-group label span {
            color: #dc3545;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c41e3a;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        .password-strength {
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #c41e3a;
            color: white;
        }
        
        .btn-primary:hover {
            background: #a01630;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>🔒 Alterar Senha</h1>
            <p>Defina uma nova senha para o usuário</p>
        </div>
        
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <strong>❌ Erro ao alterar senha:</strong>
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo $erro; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="user-info">
            <h3>Dados do Usuário:</h3>
            
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">E-mail:</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Tipo:</div>
                <div class="info-value">
                    <?php if ($usuario['tipo'] == 'admin'): ?>
                        <span class="badge badge-admin">🔑 Administrador</span>
                    <?php else: ?>
                        <span class="badge badge-supervisor">👤 Supervisor</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <form method="POST" action="alterar_senha.php?id=<?php echo $usuario_id; ?>">
            <div class="form-group">
                <label for="nova_senha">Nova Senha <span>*</span></label>
                <input type="password" id="nova_senha" name="nova_senha" required minlength="6" onkeyup="checkPasswordStrength()">
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small id="strengthText">Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Nova Senha <span>*</span></label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                <small>Digite a mesma senha novamente</small>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-primary">🔒 Alterar Senha</button>
                <a href="usuarios.php" class="btn btn-secondary">← Cancelar</a>
            </div>
        </form>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const senha = document.getElementById('nova_senha').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (senha.length >= 6) strength++;
            if (senha.length >= 10) strength++;
            if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) strength++;
            if (/[0-9]/.test(senha)) strength++;
            if (/[^a-zA-Z0-9]/.test(senha)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (senha.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Mínimo 6 caracteres';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Senha fraca';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Senha média';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Senha forte';
            }
        }
    </script>
</body>
</html>
