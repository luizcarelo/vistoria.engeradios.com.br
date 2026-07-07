<?php
require_once '../config.php';
verificarLogin();

// Verificar se é administrador
if ($_SESSION['usuario_tipo'] != 'admin') {
    header('Location: ../supervisor/dashboard.php');
    exit;
}

$conn = getDBConnection();

// Verificar se foi enviado ID da vistoria
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['erro'] = 'ID da vistoria não informado';
    header('Location: vistorias.php');
    exit;
}

$vistoria_id = intval($_GET['id']);

// Verificar se a vistoria existe
$query = "SELECT v.id, c.nome as cliente_nome, u.nome as supervisor_nome, v.data_vistoria 
          FROM vistorias v
          INNER JOIN clientes c ON v.cliente_id = c.id
          INNER JOIN usuarios u ON v.supervisor_id = u.id
          WHERE v.id = $vistoria_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Vistoria não encontrada';
    header('Location: vistorias.php');
    exit;
}

$vistoria = $result->fetch_assoc();

// Se foi confirmada a exclusão
if (isset($_GET['confirmar']) && $_GET['confirmar'] == 'sim') {
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // 1. Buscar e excluir arquivos de fotos
        $fotos = $conn->query("SELECT caminho_foto FROM vistoria_fotos WHERE vistoria_id = $vistoria_id");
        while ($foto = $fotos->fetch_assoc()) {
            $caminho_foto = __DIR__ . '/../' . $foto['caminho_foto'];
            if (file_exists($caminho_foto)) {
                unlink($caminho_foto);
            }
        }
        
        // 2. Buscar e excluir arquivos de áudio
        $audios = $conn->query("SELECT caminho_audio FROM vistoria_audios WHERE vistoria_id = $vistoria_id");
        while ($audio = $audios->fetch_assoc()) {
            $caminho_audio = __DIR__ . '/../' . $audio['caminho_audio'];
            if (file_exists($caminho_audio)) {
                unlink($caminho_audio);
            }
        }
        
        // 3. Excluir registros de fotos do banco
        $conn->query("DELETE FROM vistoria_fotos WHERE vistoria_id = $vistoria_id");
        
        // 4. Excluir registros de áudios do banco
        $conn->query("DELETE FROM vistoria_audios WHERE vistoria_id = $vistoria_id");
        
        // 5. Excluir a vistoria
        $conn->query("DELETE FROM vistorias WHERE id = $vistoria_id");
        
        // Confirmar transação
        $conn->commit();
        
        // Registrar log (opcional)
        $admin_nome = $_SESSION['usuario_nome'];
        $log_msg = "Vistoria #$vistoria_id excluída por $admin_nome - Cliente: {$vistoria['cliente_nome']}, Supervisor: {$vistoria['supervisor_nome']}";
        error_log($log_msg);
        
        $_SESSION['sucesso'] = 'Vistoria excluída com sucesso!';
        header('Location: vistorias.php');
        exit;
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $conn->rollback();
        $_SESSION['erro'] = 'Erro ao excluir vistoria: ' . $e->getMessage();
        header('Location: vistorias.php');
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
    <title>Excluir Vistoria - ENGERADIOS</title>
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
        
        .vistoria-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
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
        
        <h1>Confirmar Exclusão de Vistoria</h1>
        
        <div class="warning-text">
            <strong>⚠️ ATENÇÃO:</strong> Esta ação é <strong>IRREVERSÍVEL</strong> e não pode ser desfeita!
        </div>
        
        <div class="vistoria-info">
            <h3 style="margin-bottom: 15px; color: #333;">Dados da Vistoria:</h3>
            
            <div class="info-row">
                <div class="info-label">🏢 Cliente:</div>
                <div class="info-value"><?php echo htmlspecialchars($vistoria['cliente_nome']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">👤 Supervisor:</div>
                <div class="info-value"><?php echo htmlspecialchars($vistoria['supervisor_nome']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">📅 Data/Hora:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">🔢 ID:</div>
                <div class="info-value">#<?php echo $vistoria['id']; ?></div>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3>🗑️ O que será excluído:</h3>
            <ul>
                <li>Todos os dados da vistoria</li>
                <li>Laudo completo</li>
                <li>Orçamento de adequação</li>
                <li>Todas as fotos anexadas</li>
                <li>Todos os áudios anexados</li>
                <li>Registros do banco de dados</li>
            </ul>
        </div>
        
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Tem certeza que deseja excluir esta vistoria?
        </p>
        
        <div class="actions">
            <a href="excluir_vistoria.php?id=<?php echo $vistoria_id; ?>&confirmar=sim" class="btn btn-danger">
                🗑️ Sim, Excluir Definitivamente
            </a>
            <a href="vistorias.php" class="btn btn-secondary">
                ← Cancelar e Voltar
            </a>
        </div>
    </div>
</body>
</html>
