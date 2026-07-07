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
$query = "SELECT * FROM usuarios WHERE id = $usuario_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Usuário não encontrado';
    header('Location: usuarios.php');
    exit;
}

$usuario = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
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
        // Verificar se e-mail já existe (exceto para o próprio usuário)
        $email_check = $conn->real_escape_string($email);
        $result = $conn->query("SELECT id FROM usuarios WHERE email = '$email_check' AND id != $usuario_id");
        if ($result->num_rows > 0) {
            $erros[] = 'Este e-mail já está cadastrado para outro usuário';
        }
    }
    
    if (!in_array($tipo, ['admin', 'supervisor'])) {
        $erros[] = 'Tipo de usuário inválido';
    }
    
    // Se não houver erros, atualizar no banco
    if (empty($erros)) {
        $nome_escaped = $conn->real_escape_string($nome);
        $email_escaped = $conn->real_escape_string($email);
        $tipo_escaped = $conn->real_escape_string($tipo);
        
        $query = "UPDATE usuarios 
                  SET nome = '$nome_escaped', email = '$email_escaped', tipo = '$tipo_escaped' 
                  WHERE id = $usuario_id";
        
        if ($conn->query($query)) {
            $_SESSION['sucesso'] = 'Usuário atualizado com sucesso!';
            header('Location: usuarios.php');
            exit;
        } else {
            $erros[] = 'Erro ao atualizar usuário: ' . $conn->error;
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
    <title>Editar Usuário - ENGERADIOS</title>
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
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box p {
            margin: 5px 0;
            color: #555;
        }
        
        .info-box strong {
            color: #333;
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
            <h1>Editar Usuário</h1>
            <p>Atualize os dados do usuário abaixo</p>
        </div>
        
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <strong>❌ Erro ao atualizar usuário:</strong>
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo $erro; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p><strong>ℹ️ Informações:</strong></p>
            <p>• Para alterar a senha, use o botão "🔒 Senha" na lista de usuários</p>
            <p>• O e-mail é usado para fazer login no sistema</p>
            <p>• Cadastrado em: <?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?></p>
        </div>
        
        <form method="POST" action="editar_usuario.php?id=<?php echo $usuario_id; ?>">
            <div class="form-group">
                <label for="nome">Nome Completo <span>*</span></label>
                <input type="text" id="nome" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : htmlspecialchars($usuario['nome']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail <span>*</span></label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($usuario['email']); ?>" required>
                <small>O e-mail será usado para fazer login no sistema</small>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de Usuário <span>*</span></label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione...</option>
                    <option value="admin" <?php echo ((isset($_POST['tipo']) ? $_POST['tipo'] : $usuario['tipo']) == 'admin') ? 'selected' : ''; ?>>🔑 Administrador</option>
                    <option value="supervisor" <?php echo ((isset($_POST['tipo']) ? $_POST['tipo'] : $usuario['tipo']) == 'supervisor') ? 'selected' : ''; ?>>👤 Supervisor</option>
                </select>
                <small>
                    <strong>Administrador:</strong> Acesso total ao sistema<br>
                    <strong>Supervisor:</strong> Pode criar e consultar vistorias
                </small>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                <a href="usuarios.php" class="btn btn-secondary">← Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
