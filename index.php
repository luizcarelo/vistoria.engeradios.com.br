<?php
session_start();
require_once 'config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($email) && !empty($senha)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, nome, senha, tipo FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];
                
                if ($usuario['tipo'] == 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: supervisor/dashboard.php');
                }
                exit;
            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $erro = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vistoria Remota ENGERADIOS</title>
    <link rel="icon" type="image/png" href="Logooriginal.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: #c41e3a;
            padding: 30px;
            text-align: center;
        }
        
        .login-header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            color: white;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c41e3a;
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #c41e3a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: #a01830;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(196, 30, 58, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .erro {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
            font-size: 14px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="Logooriginal.png" alt="ENGERADIOS Logo">
            <h1>Vistoria Remota</h1>
            <p>Sistema de Gestão de Vistorias</p>
        </div>
        <div class="login-body">
            <?php if ($erro): ?>
                <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <button type="submit" class="btn-login">Entrar</button>
            <!-- INICIO ETAPA1 SENHAS -->
<div style="text-align:center;margin-top:12px">
    <a href="esqueci_senha.php">Esqueci minha senha</a>
</div>
<!-- FIM ETAPA1 SENHAS -->

</form>
        </div>
    </div>
</body>
</html>
