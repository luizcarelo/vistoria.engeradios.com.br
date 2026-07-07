<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();
$mensagem = '';
$tipo_mensagem = '';

// Adicionar supervisor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'adicionar') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($nome) && !empty($email) && !empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'supervisor')");
        $stmt->bind_param("sss", $nome, $email, $senha_hash);
        
        if ($stmt->execute()) {
            $mensagem = 'Supervisor cadastrado com sucesso!';
            $tipo_mensagem = 'sucesso';
        } else {
            $mensagem = 'Erro ao cadastrar supervisor. E-mail já existe.';
            $tipo_mensagem = 'erro';
        }
        $stmt->close();
    }
}

// Desativar supervisor
if (isset($_GET['desativar'])) {
    $id = intval($_GET['desativar']);
    $conn->query("UPDATE usuarios SET ativo = 0 WHERE id = $id AND tipo = 'supervisor'");
    $mensagem = 'Supervisor desativado com sucesso!';
    $tipo_mensagem = 'sucesso';
}

// Ativar supervisor
if (isset($_GET['ativar'])) {
    $id = intval($_GET['ativar']);
    $conn->query("UPDATE usuarios SET ativo = 1 WHERE id = $id AND tipo = 'supervisor'");
    $mensagem = 'Supervisor ativado com sucesso!';
    $tipo_mensagem = 'sucesso';
}

// Listar supervisores
$supervisores = $conn->query("SELECT * FROM usuarios WHERE tipo = 'supervisor' ORDER BY nome ASC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Supervisores - ENGERADIOS</title>
    <link rel="icon" type="image/png" href="../Logooriginal.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: #c41e3a;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-left img {
            height: 40px;
        }
        
        .btn-voltar {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-voltar:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c41e3a;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c41e3a;
        }
        
        .btn-primary {
            background: #c41e3a;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #a01830;
            transform: translateY(-2px);
        }
        
        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .mensagem.sucesso {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .mensagem.erro {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge.ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .badge.inativo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-small {
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
            display: inline-block;
            margin-right: 5px;
        }
        
        .btn-desativar {
            background: #dc3545;
            color: white;
        }
        
        .btn-desativar:hover {
            background: #c82333;
        }
        
        .btn-ativar {
            background: #28a745;
            color: white;
        }
        
        .btn-ativar:hover {
            background: #218838;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 14px;
            }
            
            table th, table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>Gerenciar Supervisores</h1>
        </div>
        <a href="dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Cadastrar Novo Supervisor</h2>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="adicionar">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Cadastrar Supervisor</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Lista de Supervisores</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Data Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($supervisor = $supervisores->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($supervisor['nome']); ?></td>
                        <td><?php echo htmlspecialchars($supervisor['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $supervisor['ativo'] ? 'ativo' : 'inativo'; ?>">
                                <?php echo $supervisor['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($supervisor['data_criacao'])); ?></td>
                        <td>
                            <?php if ($supervisor['ativo']): ?>
                                <a href="?desativar=<?php echo $supervisor['id']; ?>" 
                                   class="btn-small btn-desativar"
                                   onclick="return confirm('Deseja realmente desativar este supervisor?')">
                                   Desativar
                                </a>
                            <?php else: ?>
                                <a href="?ativar=<?php echo $supervisor['id']; ?>" 
                                   class="btn-small btn-ativar">
                                   Ativar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
