<?php
require_once '../config.php';
verificarLogin();

if ($_SESSION['usuario_tipo'] != 'supervisor') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$conn = getDBConnection();
$supervisor_id_logado = $_SESSION['usuario_id'];

// Filtros
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$cliente_id = $_GET['cliente_id'] ?? '';
$supervisor_filtro = $_GET['supervisor_id'] ?? '';
$busca_texto = trim($_GET['busca'] ?? '');

// ============================================================================
// IMPORTANTE: Query mostra TODAS as vistorias de TODOS os supervisores
// ============================================================================
$where = "v.status = 'concluida'";
// NÃO filtra por supervisor_id aqui! Mostra todas!

if (!empty($data_inicio)) {
    $where .= " AND DATE(v.data_vistoria) >= '" . $conn->real_escape_string($data_inicio) . "'";
}

if (!empty($data_fim)) {
    $where .= " AND DATE(v.data_vistoria) <= '" . $conn->real_escape_string($data_fim) . "'";
}

if (!empty($cliente_id)) {
    $where .= " AND v.cliente_id = " . intval($cliente_id);
}

// Filtro opcional por supervisor (se o usuário selecionar)
if (!empty($supervisor_filtro)) {
    $where .= " AND v.supervisor_id = " . intval($supervisor_filtro);
}

// Busca por texto
if (!empty($busca_texto)) {
    $busca_escaped = $conn->real_escape_string($busca_texto);
    $where .= " AND (v.laudo LIKE '%$busca_escaped%' OR v.orcamento_adequacao LIKE '%$busca_escaped%')";
}

// Buscar vistorias - Inclui nome do supervisor
$query = "
    SELECT 
        v.id, v.data_vistoria, v.laudo, v.orcamento_adequacao,
        c.nome as cliente_nome,
        u.nome as supervisor_nome,
        v.supervisor_id,
        (SELECT COUNT(*) FROM vistoria_fotos WHERE vistoria_id = v.id) as total_fotos,
        (SELECT COUNT(*) FROM vistoria_audios WHERE vistoria_id = v.id) as total_audios
    FROM vistorias v
    INNER JOIN clientes c ON v.cliente_id = c.id
    INNER JOIN usuarios u ON v.supervisor_id = u.id
    WHERE $where
    ORDER BY v.data_vistoria DESC
";

$vistorias = $conn->query($query);

// Buscar todos os supervisores para o filtro
$supervisores = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'supervisor' ORDER BY nome");

// Buscar clientes para filtro
$clientes = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Vistorias - ENGERADIOS</title>
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
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #c41e3a;
            font-size: 24px;
        }
        
        .btn-voltar {
            background: #c41e3a;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .btn-voltar:hover {
            background: #a01830;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c41e3a;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
        }
        
        .btn-filter {
            background: #c41e3a;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-filter:hover {
            background: #a01830;
        }
        
        .btn-clear {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-clear:hover {
            background: #5a6268;
        }
        
        .vistoria-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .vistoria-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .vistoria-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .vistoria-info {
            flex: 1;
        }
        
        .vistoria-title {
            font-size: 18px;
            font-weight: bold;
            color: #c41e3a;
            margin-bottom: 5px;
        }
        
        .vistoria-meta {
            color: #666;
            font-size: 14px;
        }
        
        .vistoria-supervisor {
            background: #e7f3ff;
            color: #0066cc;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
        }
        
        .vistoria-body {
            margin-bottom: 15px;
        }
        
        .vistoria-section {
            margin-bottom: 10px;
        }
        
        .vistoria-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        
        .vistoria-text {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .vistoria-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .vistoria-stats {
            color: #666;
            font-size: 14px;
        }
        
        .btn-detalhes {
            background: #007bff;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-detalhes:hover {
            background: #0056b3;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .vistoria-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .vistoria-footer {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Consultar Vistorias</h1>
        <a href="dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
    </div>
    
    <div class="container">
        <h2>Filtros de Busca</h2>
        
        <form method="GET" action="" class="filter-form">
            <!-- Campo de busca livre -->
            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="busca">🔍 Buscar por palavra-chave</label>
                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca_texto); ?>" placeholder="Digite palavras-chave do laudo ou orçamento...">
                <small style="color: #666; font-size: 12px; margin-top: 5px;">
                    Busca no laudo e orçamento de adequação. Exemplo: "alarme", "câmera", "manutenção"
                </small>
            </div>
            
            <div class="form-group">
                <label for="data_inicio">Data Início</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
            </div>
            
            <div class="form-group">
                <label for="data_fim">Data Fim</label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
            </div>
            
            <div class="form-group">
                <label for="cliente_id">Cliente</label>
                <select id="cliente_id" name="cliente_id">
                    <option value="">Todos os clientes</option>
                    <?php while ($cliente = $clientes->fetch_assoc()): ?>
                        <option value="<?php echo $cliente['id']; ?>" <?php echo ($cliente_id == $cliente['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="supervisor_id">Supervisor</label>
                <select id="supervisor_id" name="supervisor_id">
                    <option value="">Todos os supervisores</option>
                    <?php while ($supervisor = $supervisores->fetch_assoc()): ?>
                        <option value="<?php echo $supervisor['id']; ?>" <?php echo ($supervisor_filtro == $supervisor['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supervisor['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn-filter">🔍 Buscar</button>
                <a href="consultar_vistorias.php" class="btn-clear">🗑️ Limpar Filtros</a>
            </div>
        </form>
    </div>
    
    <div class="container">
        <h2>Vistorias Realizadas (Todos os Supervisores)</h2>
        
        <?php if ($vistorias->num_rows > 0): ?>
            <?php while ($vistoria = $vistorias->fetch_assoc()): ?>
                <div class="vistoria-card">
                    <div class="vistoria-header">
                        <div class="vistoria-info">
                            <div class="vistoria-title">
                                🏢 <?php echo htmlspecialchars($vistoria['cliente_nome']); ?>
                            </div>
                            <div class="vistoria-meta">
                                📅 <?php echo date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])); ?>
                            </div>
                            <div class="vistoria-supervisor">
                                👤 <?php echo htmlspecialchars($vistoria['supervisor_nome']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vistoria-body">
                        <div class="vistoria-section">
                            <div class="vistoria-label">📝 Laudo:</div>
                            <div class="vistoria-text">
                                <?php 
                                $laudo = htmlspecialchars($vistoria['laudo']);
                                echo strlen($laudo) > 200 ? substr($laudo, 0, 200) . '...' : $laudo;
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vistoria-footer">
                        <div class="vistoria-stats">
                            📷 <?php echo $vistoria['total_fotos']; ?> foto(s) | 
                            🎤 <?php echo $vistoria['total_audios']; ?> áudio(s)
                        </div>
                        <a href="detalhes_vistoria.php?id=<?php echo $vistoria['id']; ?>" class="btn-detalhes">
                            Ver Detalhes →
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>Nenhuma vistoria encontrada</h3>
                <p>Não há vistorias que correspondam aos filtros selecionados.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>
