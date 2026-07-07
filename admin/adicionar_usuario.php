<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $tipo = $_POST['tipo'];
    
    $erros = [];
    
    // Validações
    if (empty($nome)) {
        $erros[] = 'Nome é obrigatório';
    }
    
    if (empty($email)) {
        $erros[] = 'E-mail é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido';
    } else {
        // Verificar se e-mail já existe
        $email_check = $conn->real_escape_string($email);
        $result = $conn->query("SELECT id FROM usuarios WHERE email = '$email_check'");
        if ($result->num_rows > 0) {
            $erros[] = 'Este e-mail já está cadastrado';
        }
    }
    
    if (empty($senha)) {
        $erros[] = 'Senha é obrigatória';
    } elseif (strlen($senha) < 6) {
        $erros[] = 'Senha deve ter no mínimo 6 caracteres';
    }
    
    if ($senha !== $confirmar_senha) {
        $erros[] = 'As senhas não coincidem';
    }
    
    if (!in_array($tipo, ['admin', 'supervisor'])) {
        $erros[] = 'Tipo de usuário inválido';
    }
    
    // Se não houver erros, inserir no banco
    if (empty($erros)) {
        $nome_escaped = $conn->real_escape_string($nome);
        $email_escaped = $conn->real_escape_string($email);
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $tipo_escaped = $conn->real_escape_string($tipo);
        
        $query = "INSERT INTO usuarios (nome, email, senha, tipo, data_criacao) 
                  VALUES ('$nome_escaped', '$email_escaped', '$senha_hash', '$tipo_escaped', NOW())";
        
        if ($conn->query($query)) {
            $_SESSION['sucesso'] = 'Usuário adicionado com sucesso!';
            header('Location: usuarios.php');
            exit;
        } else {
            $erros[] = 'Erro ao adicionar usuário: ' . $conn->error;
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
    <title>Adicionar Usuário - ENGERADIOS</title>
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
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
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
            <h1>Adicionar Novo Usuário</h1>
            <p>Preencha os dados abaixo para criar um novo usuário</p>
        </div>
        
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <strong>❌ Erro ao adicionar usuário:</strong>
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo $erro; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="adicionar_usuario.php">
            <div class="form-group">
                <label for="nome">Nome Completo <span>*</span></label>
                <input type="text" id="nome" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail <span>*</span></label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                <small>O e-mail será usado para fazer login no sistema</small>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de Usuário <span>*</span></label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione...</option>
                    <option value="admin" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'admin') ? 'selected' : ''; ?>>🔑 Administrador</option>
                    <option value="supervisor" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'supervisor') ? 'selected' : ''; ?>>👤 Supervisor</option>
                </select>
                <small>
                    <strong>Administrador:</strong> Acesso total ao sistema<br>
                    <strong>Supervisor:</strong> Pode criar e consultar vistorias
                </small>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha <span>*</span></label>
                <input type="password" id="senha" name="senha" required minlength="6" onkeyup="checkPasswordStrength()">
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small id="strengthText">Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha <span>*</span></label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-primary">➕ Adicionar Usuário</button>
                <a href="usuarios.php" class="btn btn-secondary">← Cancelar</a>
            </div>
        </form>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const senha = document.getElementById('senha').value;
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
