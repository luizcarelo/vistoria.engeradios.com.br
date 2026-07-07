<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();

// Verificar se foi enviado ID do cliente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['erro'] = 'ID do cliente não informado';
    header('Location: clientes.php');
    exit;
}

$cliente_id = intval($_GET['id']);

// Buscar dados do cliente
$query = "SELECT * FROM clientes WHERE id = $cliente_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Cliente não encontrado';
    header('Location: clientes.php');
    exit;
}

$cliente = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $endereco = trim($_POST['endereco']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    
    $erros = [];
    
    // Validações
    if (empty($nome)) {
        $erros[] = 'Nome é obrigatório';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido';
    }
    
    // Se não houver erros, atualizar no banco
    if (empty($erros)) {
        $nome_escaped = $conn->real_escape_string($nome);
        $endereco_escaped = $conn->real_escape_string($endereco);
        $telefone_escaped = $conn->real_escape_string($telefone);
        $email_escaped = $conn->real_escape_string($email);
        
        $query = "UPDATE clientes 
                  SET nome = '$nome_escaped', 
                      endereco = '$endereco_escaped', 
                      telefone = '$telefone_escaped', 
                      email = '$email_escaped' 
                  WHERE id = $cliente_id";
        
        if ($conn->query($query)) {
            $_SESSION['sucesso'] = 'Cliente atualizado com sucesso!';
            header('Location: clientes.php');
            exit;
        } else {
            $erros[] = 'Erro ao atualizar cliente: ' . $conn->error;
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
    <title>Editar Cliente - ENGERADIOS</title>
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
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
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
            <h1>Editar Cliente</h1>
            <p>Atualize os dados do cliente abaixo</p>
        </div>
        
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <strong>❌ Erro ao atualizar cliente:</strong>
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo $erro; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p><strong>ℹ️ Informações:</strong></p>
            <p>• Apenas o nome é obrigatório</p>
            <p>• Os demais campos são opcionais</p>
            <p>• ID do Cliente: #<?php echo $cliente_id; ?></p>
        </div>
        
        <form method="POST" action="editar_cliente.php?id=<?php echo $cliente_id; ?>">
            <div class="form-group">
                <label for="nome">Nome do Cliente <span>*</span></label>
                <input type="text" id="nome" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : htmlspecialchars($cliente['nome']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço</label>
                <textarea id="endereco" name="endereco" rows="3"><?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : htmlspecialchars($cliente['endereco']); ?></textarea>
                <small>Endereço completo do cliente</small>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : htmlspecialchars($cliente['telefone']); ?>" placeholder="(11) 98765-4321">
                <small>Telefone de contato</small>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($cliente['email']); ?>" placeholder="cliente@empresa.com.br">
                <small>E-mail de contato</small>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                <a href="clientes.php" class="btn btn-secondary">← Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
