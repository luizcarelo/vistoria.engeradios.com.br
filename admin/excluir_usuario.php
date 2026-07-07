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

// Não permitir excluir o próprio usuário logado
if ($usuario_id == $_SESSION['usuario_id']) {
    $_SESSION['erro'] = 'Você não pode excluir seu próprio usuário!';
    header('Location: usuarios.php');
    exit;
}

// Buscar dados do usuário
$query = "SELECT 
            u.id, u.nome, u.email, u.tipo, u.data_criacao,
            (SELECT COUNT(*) FROM vistorias WHERE supervisor_id = u.id) as total_vistorias
          FROM usuarios u
          WHERE u.id = $usuario_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Usuário não encontrado';
    header('Location: usuarios.php');
    exit;
}

$usuario = $result->fetch_assoc();

// Se foi confirmada a exclusão
if (isset($_GET['confirmar']) && $_GET['confirmar'] == 'sim') {
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Se o usuário tem vistorias, atualizar para NULL (manter histórico)
        if ($usuario['total_vistorias'] > 0) {
            $conn->query("UPDATE vistorias SET supervisor_id = NULL WHERE supervisor_id = $usuario_id");
        }
        
        // Excluir o usuário
        $conn->query("DELETE FROM usuarios WHERE id = $usuario_id");
        
        // Confirmar transação
        $conn->commit();
        
        // Registrar log
        $admin_nome = $_SESSION['usuario_nome'];
        $log_msg = "Usuário #$usuario_id ({$usuario['nome']}) excluído por $admin_nome";
        error_log($log_msg);
        
        $_SESSION['sucesso'] = 'Usuário excluído com sucesso!';
        header('Location: usuarios.php');
        exit;
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $conn->rollback();
        $_SESSION['erro'] = 'Erro ao excluir usuário: ' . $e->getMessage();
        header('Location: usuarios.php');
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
    <title>Excluir Usuário - ENGERADIOS</title>
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
        
        .user-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .user-info h3 {
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
        
        <h1>Confirmar Exclusão de Usuário</h1>
        
        <div class="warning-text">
            <strong>⚠️ ATENÇÃO:</strong> Esta ação é <strong>IRREVERSÍVEL</strong> e não pode ser desfeita!
        </div>
        
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
            
            <div class="info-row">
                <div class="info-label">Vistorias Criadas:</div>
                <div class="info-value"><?php echo $usuario['total_vistorias']; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Cadastrado em:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?></div>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3>🗑️ O que será excluído:</h3>
            <ul>
                <li>Conta do usuário (login e senha)</li>
                <li>Dados pessoais (nome, e-mail)</li>
                <li>Acesso ao sistema</li>
            </ul>
            
            <?php if ($usuario['total_vistorias'] > 0): ?>
                <h3 style="margin-top: 15px;">ℹ️ Importante:</h3>
                <ul>
                    <li>As <strong><?php echo $usuario['total_vistorias']; ?> vistorias</strong> criadas por este usuário <strong>serão mantidas</strong></li>
                    <li>Elas ficarão sem supervisor associado</li>
                    <li>Você pode visualizá-las normalmente no sistema</li>
                </ul>
            <?php endif; ?>
        </div>
        
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Tem certeza que deseja excluir este usuário?
        </p>
        
        <div class="actions">
            <a href="excluir_usuario.php?id=<?php echo $usuario_id; ?>&confirmar=sim" class="btn btn-danger">
                🗑️ Sim, Excluir Definitivamente
            </a>
            <a href="usuarios.php" class="btn btn-secondary">
                ← Cancelar e Voltar
            </a>
        </div>
    </div>
</body>
</html>
