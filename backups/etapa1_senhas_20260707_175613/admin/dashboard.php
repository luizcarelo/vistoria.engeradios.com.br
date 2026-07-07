<?php
require_once '../config.php';
verificarAdmin();

$conn = getDBConnection();

// Estatísticas
$total_supervisores = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'supervisor' AND ativo = 1")->fetch_assoc()['total'];
$total_clientes = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE ativo = 1")->fetch_assoc()['total'];
$total_vistorias = $conn->query("SELECT COUNT(*) as total FROM vistorias WHERE status = 'concluida'")->fetch_assoc()['total'];
$vistorias_mes = $conn->query("SELECT COUNT(*) as total FROM vistorias WHERE status = 'concluida' AND MONTH(data_vistoria) = MONTH(CURRENT_DATE()) AND YEAR(data_vistoria) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - ENGERADIOS</title>
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
        
        .header-left h1 {
            font-size: 20px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            font-size: 14px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            color: #c41e3a;
            font-size: 36px;
            font-weight: bold;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .menu-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #c41e3a;
        }
        
        .menu-card h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .menu-card p {
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-grid, .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>Painel Administrativo</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
            </div>
            <a href="../logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Supervisores Ativos</h3>
                <div class="number"><?php echo $total_supervisores; ?></div>
            </div>
            <div class="stat-card">
                <h3>Clientes Cadastrados</h3>
                <div class="number"><?php echo $total_clientes; ?></div>
            </div>
            <div class="stat-card">
                <h3>Vistorias Totais</h3>
                <div class="number"><?php echo $total_vistorias; ?></div>
            </div>
            <div class="stat-card">
                <h3>Vistorias Este Mês</h3>
                <div class="number"><?php echo $vistorias_mes; ?></div>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="usuarios.php" class="menu-card">
                <div class="icon">👤</div>
                <h2>Gerenciar Usuários</h2>
                <p>Adicionar, editar, excluir usuários e alterar senhas</p>
            </a>
            
            <a href="supervisores.php" class="menu-card">
                <div class="icon">👥</div>
                <h2>Gerenciar Supervisores</h2>
                <p>Cadastrar, editar e visualizar supervisores</p>
            </a>
            
            <a href="clientes.php" class="menu-card">
                <div class="icon">🏢</div>
                <h2>Gerenciar Clientes</h2>
                <p>Cadastrar, editar e visualizar clientes</p>
            </a>
            
            <a href="vistorias.php" class="menu-card">
                <div class="icon">📋</div>
                <h2>Visualizar Vistorias</h2>
                <p>Consultar todas as vistorias realizadas</p>
            </a>
        </div>
    </div>
</body>
</html>
