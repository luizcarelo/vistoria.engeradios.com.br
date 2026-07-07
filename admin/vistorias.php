<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();

// Filtros
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$cliente_id = $_GET['cliente_id'] ?? '';
$supervisor_id = $_GET['supervisor_id'] ?? '';
$busca_texto = trim($_GET['busca'] ?? ''); // Busca livre por texto

// Construir query
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

if (!empty($supervisor_id)) {
    $where .= " AND v.supervisor_id = " . intval($supervisor_id);
}

if (!empty($busca_texto)) {
    $busca_escaped = $conn->real_escape_string($busca_texto);
    $where .= " AND (v.laudo LIKE '%$busca_escaped%' OR v.orcamento_adequacao LIKE '%$busca_escaped%')";
}

// Buscar vistorias
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

// Buscar clientes e supervisores para os filtros
$clientes = $conn->query("SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome ASC");
$supervisores = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'supervisor' AND ativo = 1 ORDER BY nome ASC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Vistorias - ENGERADIOS</title>
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
        
        .btn-clear {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            align-self: flex-end;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-clear:hover {
            background: #5a6268;
            color: white;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .quick-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .quick-filter-btn {
            background: #f8f9fa;
            color: #495057;
            padding: 6px 12px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .quick-filter-btn:hover {
            background: #e9ecef;
            border-color: #c41e3a;
            color: #c41e3a;
        }
        
        .filter-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
            color: #004085;
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
            cursor: pointer;
        }
        
        .anexo-badge {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-pdf {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s;
            margin-right: 5px;
        }
        
        .btn-pdf:hover {
            background: #c82333;
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-excluir {
            background: #ff4444;
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s;
            margin-right: 5px;
        }
        
        .btn-excluir:hover {
            background: #cc0000;
            color: white;
            transform: translateY(-1px);
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
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
            max-width: 900px;
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
    <?php if (isset($_SESSION['sucesso'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
            ✅ <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['erro'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
            ❌ <?php echo $_SESSION['erro']; unset($_SESSION['erro']); ?>
        </div>
    <?php endif; ?>
    
    <div class="header">
        <div class="header-left">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>Visualizar Vistorias</h1>
        </div>
        <a href="dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Filtros de Busca</h2>
            
            <!-- Atalhos Rápidos -->
            <div class="quick-filters">
                <span style="font-weight: 600; color: #666; margin-right: 10px;">Atalhos:</span>
                <button type="button" class="quick-filter-btn" onclick="setHoje()">📅 Hoje</button>
                <button type="button" class="quick-filter-btn" onclick="setOntem()">📅 Ontem</button>
                <button type="button" class="quick-filter-btn" onclick="setUltimos7Dias()">📅 Últimos 7 dias</button>
                <button type="button" class="quick-filter-btn" onclick="setEstaSemana()">📅 Esta semana</button>
                <button type="button" class="quick-filter-btn" onclick="setEsteMes()">📅 Este mês</button>
                <button type="button" class="quick-filter-btn" onclick="setMesPassado()">📅 Mês passado</button>
            </div>
            
            <form method="GET" action="" id="filterForm">
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
                                <option value="<?php echo $supervisor['id']; ?>" <?php echo ($supervisor_id == $supervisor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supervisor['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">🔍 Buscar</button>
                        <a href="vistorias.php" class="btn-clear">🗑️ Limpar</a>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($data_inicio) || !empty($data_fim) || !empty($cliente_id) || !empty($supervisor_id) || !empty($busca_texto)): ?>
            <div class="filter-info">
                <strong>ℹ️ Filtros ativos:</strong>
                <?php if (!empty($data_inicio)): ?>
                    Data início: <?php echo date('d/m/Y', strtotime($data_inicio)); ?>
                <?php endif; ?>
                <?php if (!empty($data_fim)): ?>
                    | Data fim: <?php echo date('d/m/Y', strtotime($data_fim)); ?>
                <?php endif; ?>
                <?php if (!empty($cliente_id)): ?>
                    | Cliente selecionado
                <?php endif; ?>
                <?php if (!empty($supervisor_id)): ?>
                    | Supervisor selecionado
                <?php endif; ?>
                <?php if (!empty($busca_texto)): ?>
                    | Busca: "<?php echo htmlspecialchars($busca_texto); ?>"
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Vistorias Realizadas</h2>
            
            <?php if ($vistorias->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Cliente</th>
                            <th>Supervisor</th>
                            <th>Anexos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($vistoria = $vistorias->fetch_assoc()): ?>
                        <tr>
                            <td onclick="verDetalhes(<?php echo $vistoria['id']; ?>)" style="cursor: pointer;"><?php echo date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])); ?></td>
                            <td onclick="verDetalhes(<?php echo $vistoria['id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($vistoria['cliente_nome']); ?></td>
                            <td onclick="verDetalhes(<?php echo $vistoria['id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($vistoria['supervisor_nome']); ?></td>
                            <td onclick="verDetalhes(<?php echo $vistoria['id']; ?>)" style="cursor: pointer;">
                                <?php if ($vistoria['total_fotos'] > 0): ?>
                                    <span class="anexo-badge">📷 <?php echo $vistoria['total_fotos']; ?></span>
                                <?php endif; ?>
                                <?php if ($vistoria['total_audios'] > 0): ?>
                                    <span class="anexo-badge">🎤 <?php echo $vistoria['total_audios']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="gerar_pdf.php?id=<?php echo $vistoria['id']; ?>" class="btn-pdf" title="Baixar PDF" onclick="event.stopPropagation();">
                                    📄 PDF
                                </a>
                                <a href="excluir_vistoria.php?id=<?php echo $vistoria['id']; ?>" class="btn-excluir" title="Excluir Vistoria" onclick="event.stopPropagation(); return confirm('⚠️ TEM CERTEZA?\n\nEsta ação irá excluir PERMANENTEMENTE:\n- Todos os dados da vistoria\n- Laudo e orçamento\n- Todas as fotos\n- Todos os áudios\n\nEsta ação NÃO PODE SER DESFEITA!');">
                                    🗑️ Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
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
        // Funções de atalhos de data
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function setHoje() {
            const hoje = new Date();
            document.getElementById('data_inicio').value = formatDate(hoje);
            document.getElementById('data_fim').value = formatDate(hoje);
        }
        
        function setOntem() {
            const ontem = new Date();
            ontem.setDate(ontem.getDate() - 1);
            document.getElementById('data_inicio').value = formatDate(ontem);
            document.getElementById('data_fim').value = formatDate(ontem);
        }
        
        function setUltimos7Dias() {
            const hoje = new Date();
            const seteDiasAtras = new Date();
            seteDiasAtras.setDate(seteDiasAtras.getDate() - 7);
            document.getElementById('data_inicio').value = formatDate(seteDiasAtras);
            document.getElementById('data_fim').value = formatDate(hoje);
        }
        
        function setEstaSemana() {
            const hoje = new Date();
            const primeiroDia = new Date(hoje);
            primeiroDia.setDate(hoje.getDate() - hoje.getDay()); // Domingo
            document.getElementById('data_inicio').value = formatDate(primeiroDia);
            document.getElementById('data_fim').value = formatDate(hoje);
        }
        
        function setEsteMes() {
            const hoje = new Date();
            const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
            document.getElementById('data_inicio').value = formatDate(primeiroDia);
            document.getElementById('data_fim').value = formatDate(hoje);
        }
        
        function setMesPassado() {
            const hoje = new Date();
            const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
            const ultimoDia = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
            document.getElementById('data_inicio').value = formatDate(primeiroDia);
            document.getElementById('data_fim').value = formatDate(ultimoDia);
        }
        
        // Funções do modal
        function verDetalhes(vistoriaId) {
            const modal = document.getElementById('modalDetalhes');
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = '<p style="text-align: center; padding: 40px;">Carregando...</p>';
            modal.style.display = 'block';
            
            fetch('detalhes_vistoria_admin.php?id=' + vistoriaId)
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
