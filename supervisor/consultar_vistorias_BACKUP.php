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
$busca_texto = trim($_GET['busca'] ?? ''); // Busca livre por texto

// Construir query - MODIFICADO: Agora mostra vistorias de TODOS os supervisores
$where = "v.status = 'concluida'";

if (!empty($data_inicio)) {
    $where .= " AND DATE(v.data_vistoria) >= '" . $conn->real_escape_string($data_inicio) . "'";
}

if (!empty($data_fim)) {
    $where .= " AND DATE(v.data_vistoria) <= '" . $conn->real_escape_string($data_fim) . "'";
}

if (!empty($cliente_id)) {
    $where .= " AND v.cliente_id = " . intval($cliente_id);
}

if (!empty($supervisor_filtro)) {
    $where .= " AND v.supervisor_id = " . intval($supervisor_filtro);
}

if (!empty($busca_texto)) {
    $busca_escaped = $conn->real_escape_string($busca_texto);
    $where .= " AND (v.laudo LIKE '%$busca_escaped%' OR v.orcamento_adequacao LIKE '%$busca_escaped%')";
}

// Buscar vistorias - MODIFICADO: Agora inclui nome do supervisor
$query = "
    SELECT 
        v.id, v.data_vistoria, v.laudo, v.orcamento_adequacao,
        c.nome as cliente_nome,
        u.nome as supervisor_nome,
        (SELECT COUNT(*) FROM vistoria_fotos WHERE vistoria_id = v.id) as total_fotos,
        (SELECT COUNT(*) FROM vistoria_audios WHERE vistoria_id = v.id) as total_audios
    FROM vistorias v
    JOIN clientes c ON v.cliente_id = c.id
    JOIN usuarios u ON v.supervisor_id = u.id
    WHERE $where
    ORDER BY v.data_vistoria DESC
";

$vistorias = $conn->query($query);

// Buscar clientes para o filtro
$clientes = $conn->query("SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome ASC");

// Buscar supervisores para o filtro
$supervisores = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'supervisor' AND ativo = 1 ORDER BY nome ASC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Vistorias - ENGERADIOS</title>
    <link rel="icon" type="image/png" href="../Logooriginal.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#c41e3a">
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
            padding: 15px 20px;
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
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-voltar:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c41e3a;
            font-size: 20px;
        }
        
        .filter-grid {
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
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c41e3a;
        }
        
        .btn-filter {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            align-self: flex-end;
        }
        
        .btn-filter:hover {
            background: #0056b3;
        }
        
        .vistoria-item {
            background: #f8f9fa;
            border-left: 4px solid #c41e3a;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .vistoria-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .vistoria-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .vistoria-cliente {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .vistoria-data {
            color: #666;
            font-size: 14px;
        }
        
        .vistoria-info {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .vistoria-anexos {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .anexo-badge {
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-results .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .modal-header {
            background: #c41e3a;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .close:hover {
            opacity: 0.8;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .detail-section {
            margin-bottom: 25px;
        }
        
        .detail-section h4 {
            color: #c41e3a;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #c41e3a;
        }
        
        .detail-section p {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .foto-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .foto-item img:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .vistoria-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .fotos-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            
            .foto-item img {
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>Consultar Vistorias</h1>
        </div>
        <a href="dashboard.php" class="btn-voltar">← Voltar</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Filtros de Busca</h2>
            <form method="GET" action="">
                <!-- Campo de busca livre -->
                <div class="form-group" style="grid-column: 1 / -1; margin-bottom: 15px;">
                    <label for="busca">🔍 Buscar por palavra-chave</label>
                    <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca_texto); ?>" placeholder="Digite palavras-chave do laudo ou orçamento..." style="width: 100%;">
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        Busca no laudo e orçamento de adequação. Exemplo: "alarme", "câmera", "manutenção"
                    </small>
                </div>
                
                <div class="filter-grid">
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
                    <button type="submit" class="btn-filter">🔍 Buscar</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>Vistorias Realizadas</h2>
            
            <?php if ($vistorias->num_rows > 0): ?>
                <?php while ($vistoria = $vistorias->fetch_assoc()): ?>
                    <div class="vistoria-item" onclick="verDetalhes(<?php echo $vistoria['id']; ?>)">
                        <div class="vistoria-header">
                            <div class="vistoria-cliente"><?php echo htmlspecialchars($vistoria['cliente_nome']); ?></div>
                            <div class="vistoria-data"><?php echo date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])); ?></div>
                        </div>
                        <div class="vistoria-info">
                            <strong style="color: #c41e3a;">👤 Supervisor:</strong> <?php echo htmlspecialchars($vistoria['supervisor_nome']); ?><br>
                            <?php echo substr(htmlspecialchars($vistoria['laudo']), 0, 150); ?>...
                        </div>
                        <div class="vistoria-anexos">
                            <?php if ($vistoria['total_fotos'] > 0): ?>
                                <span class="anexo-badge">📷 <?php echo $vistoria['total_fotos']; ?> foto(s)</span>
                            <?php endif; ?>
                            <?php if ($vistoria['total_audios'] > 0): ?>
                                <span class="anexo-badge">🎤 <?php echo $vistoria['total_audios']; ?> áudio(s)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <div class="icon">📋</div>
                    <p>Nenhuma vistoria encontrada com os filtros selecionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Detalhes -->
    <div id="modalDetalhes" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Vistoria</h3>
                <button class="close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Conteúdo carregado via JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        function verDetalhes(vistoriaId) {
            const modal = document.getElementById('modalDetalhes');
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = '<p style="text-align: center; padding: 40px;">Carregando...</p>';
            modal.style.display = 'block';
            
            fetch('detalhes_vistoria.php?id=' + vistoriaId)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = '<p style="text-align: center; color: red;">Erro ao carregar detalhes.</p>';
                });
        }
        
        function fecharModal() {
            document.getElementById('modalDetalhes').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalDetalhes');
            if (event.target == modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>
