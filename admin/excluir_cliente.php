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
$query = "SELECT 
            c.id, c.nome, c.endereco, c.telefone, c.email, c.data_criacao,
            (SELECT COUNT(*) FROM vistorias WHERE cliente_id = c.id) as total_vistorias
          FROM clientes c
          WHERE c.id = $cliente_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Cliente não encontrado';
    header('Location: clientes.php');
    exit;
}

$cliente = $result->fetch_assoc();

// Se foi confirmada a exclusão
if (isset($_GET['confirmar']) && $_GET['confirmar'] == 'sim') {
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Se o cliente tem vistorias, atualizar para NULL (manter histórico)
        if ($cliente['total_vistorias'] > 0) {
            $conn->query("UPDATE vistorias SET cliente_id = NULL WHERE cliente_id = $cliente_id");
        }
        
        // Excluir o cliente
        $conn->query("DELETE FROM clientes WHERE id = $cliente_id");
        
        // Confirmar transação
        $conn->commit();
        
        // Registrar log
        $admin_nome = $_SESSION['usuario_nome'];
        $log_msg = "Cliente #$cliente_id ({$cliente['nome']}) excluído por $admin_nome";
        error_log($log_msg);
        
        $_SESSION['sucesso'] = 'Cliente excluído com sucesso!';
        header('Location: clientes.php');
        exit;
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $conn->rollback();
        $_SESSION['erro'] = 'Erro ao excluir cliente: ' . $e->getMessage();
        header('Location: clientes.php');
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Cliente - ENGERADIOS</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .warning-icon {
            text-align: center;
            font-size: 80px;
            margin-bottom: 20px;
            color: #ff4444;
        }
        
        h1 {
            color: #ff4444;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .warning-text {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #856404;
        }
        
        .cliente-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .cliente-info h3 {
            margin-bottom: 15px;
            color: #333;
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
            min-width: 120px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .danger-zone {
            background: #ffe6e6;
            border: 2px solid #ff4444;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .danger-zone h3 {
            color: #ff4444;
            margin-bottom: 10px;
        }
        
        .danger-zone ul {
            margin-left: 20px;
            color: #666;
        }
        
        .danger-zone li {
            margin-bottom: 5px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background: #ff4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #cc0000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 68, 68, 0.4);
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
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning-icon">⚠️</div>
        
        <h1>Confirmar Exclusão de Cliente</h1>
        
        <div class="warning-text">
            <strong>⚠️ ATENÇÃO:</strong> Esta ação é <strong>IRREVERSÍVEL</strong> e não pode ser desfeita!
        </div>
        
        <div class="cliente-info">
            <h3>Dados do Cliente:</h3>
            
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['nome']); ?></div>
            </div>
            
            <?php if (!empty($cliente['endereco'])): ?>
            <div class="info-row">
                <div class="info-label">Endereço:</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['endereco']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($cliente['telefone'])): ?>
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['telefone']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($cliente['email'])): ?>
            <div class="info-row">
                <div class="info-label">E-mail:</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['email']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <div class="info-label">Vistorias:</div>
                <div class="info-value"><?php echo $cliente['total_vistorias']; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Cadastrado em:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($cliente['data_criacao'])); ?></div>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3>🗑️ O que será excluído:</h3>
            <ul>
                <li>Cadastro do cliente</li>
                <li>Dados pessoais (nome, endereço, telefone, e-mail)</li>
            </ul>
            
            <?php if ($cliente['total_vistorias'] > 0): ?>
                <h3 style="margin-top: 15px;">ℹ️ Importante:</h3>
                <ul>
                    <li>As <strong><?php echo $cliente['total_vistorias']; ?> vistorias</strong> deste cliente <strong>serão mantidas</strong></li>
                    <li>Elas ficarão sem cliente associado</li>
                    <li>Você pode visualizá-las normalmente no sistema</li>
                </ul>
            <?php endif; ?>
        </div>
        
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Tem certeza que deseja excluir este cliente?
        </p>
        
        <div class="actions">
            <a href="excluir_cliente.php?id=<?php echo $cliente_id; ?>&confirmar=sim" class="btn btn-danger">
                🗑️ Sim, Excluir Definitivamente
            </a>
            <a href="clientes.php" class="btn btn-secondary">
                ← Cancelar e Voltar
            </a>
        </div>
    </div>
</body>
</html>
