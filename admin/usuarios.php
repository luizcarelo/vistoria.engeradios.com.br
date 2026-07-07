<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();

// Filtros
$tipo_filtro = $_GET['tipo'] ?? '';
$busca = trim($_GET['busca'] ?? '');

// Construir query
$where = "1=1";

if (!empty($tipo_filtro)) {
    $where .= " AND tipo = '" . $conn->real_escape_string($tipo_filtro) . "'";
}

if (!empty($busca)) {
    $busca_escaped = $conn->real_escape_string($busca);
    $where .= " AND (nome LIKE '%$busca_escaped%' OR email LIKE '%$busca_escaped%')";
}

// Buscar usuários
$query = "
    SELECT 
        id, nome, email, tipo, data_criacao,
        (SELECT COUNT(*) FROM vistorias WHERE supervisor_id = usuarios.id) as total_vistorias
    FROM usuarios
    WHERE $where
    ORDER BY nome ASC
";

$usuarios = $conn->query($query);

// Contar totais
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
$total_admins = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'admin'")->fetch_assoc()['total'];
$total_supervisores = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'supervisor'")->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - ENGERADIOS</title>
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
            padding-bottom: 50px;
        }
        
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header img {
            height: 50px;
        }
        
        .header h1 {
            color: #c41e3a;
            font-size: 28px;
        }
        
        .btn-voltar {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-voltar:hover {
            background: #5a6268;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            font-size: 50px;
        }
        
        .stat-info h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #333;
            font-size: 32px;
            font-weight: 700;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card h2 {
            color: #c41e3a;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        
        .form-group select,
        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .actions-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-admin {
            background: #dc3545;
            color: white;
        }
        
        .badge-supervisor {
            background: #007bff;
            color: white;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-password {
            background: #17a2b8;
            color: white;
        }
        
        .btn-password:hover {
            background: #138496;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px 5px;
            }
            
            .btn-small {
                display: block;
                margin-bottom: 5px;
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
            <h1>Gerenciar Usuários</h1>
        </div>
        <a href="dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
    </div>
    
    <div class="container">
        <!-- Estatísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3>Total de Usuários</h3>
                    <p><?php echo $total_usuarios; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔑</div>
                <div class="stat-info">
                    <h3>Administradores</h3>
                    <p><?php echo $total_admins; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <h3>Supervisores</h3>
                    <p><?php echo $total_supervisores; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Filtros e Ações -->
        <div class="card">
            <h2>Filtros e Ações</h2>
            
            <form method="GET" action="usuarios.php">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="tipo">Tipo de Usuário</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <option value="admin" <?php echo $tipo_filtro == 'admin' ? 'selected' : ''; ?>>Administradores</option>
                            <option value="supervisor" <?php echo $tipo_filtro == 'supervisor' ? 'selected' : ''; ?>>Supervisores</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="busca">🔍 Buscar por Nome ou E-mail</label>
                        <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Digite nome ou e-mail...">
                    </div>
                </div>
                
                <div class="actions-row">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    <a href="usuarios.php" class="btn btn-secondary">🗑️ Limpar Filtros</a>
                    <a href="adicionar_usuario.php" class="btn btn-success">➕ Adicionar Novo Usuário</a>
                </div>
            </form>
        </div>
        
        <!-- Tabela de Usuários -->
        <div class="card">
            <h2>Lista de Usuários</h2>
            
            <?php if ($usuarios->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Vistorias</th>
                            <th>Data Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <?php if ($usuario['tipo'] == 'admin'): ?>
                                    <span class="badge badge-admin">🔑 Administrador</span>
                                <?php else: ?>
                                    <span class="badge badge-supervisor">👤 Supervisor</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $usuario['total_vistorias']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                            <td>
                                <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn-small btn-edit" title="Editar Usuário">
                                    ✏️ Editar
                                </a>
                                <a href="alterar_senha.php?id=<?php echo $usuario['id']; ?>" class="btn-small btn-password" title="Alterar Senha">
                                    🔒 Senha
                                </a>
                                <a href="excluir_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn-small btn-delete" title="Excluir Usuário" onclick="return confirm('⚠️ TEM CERTEZA?\n\nEsta ação irá excluir o usuário:\n<?php echo htmlspecialchars($usuario['nome']); ?>\n\nSe for um supervisor, todas as suas vistorias serão mantidas mas ficarão sem supervisor associado.\n\nEsta ação NÃO PODE SER DESFEITA!');">
                                    🗑️ Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <p>😕 Nenhum usuário encontrado com os filtros aplicados.</p>
                    <p><a href="usuarios.php" class="btn btn-secondary" style="margin-top: 15px;">Limpar Filtros</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
