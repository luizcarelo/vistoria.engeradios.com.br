<?php
require_once '../config.php';
verificarLogin();

if ($_SESSION['usuario_tipo'] != 'supervisor') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$conn = getDBConnection();

// Contar vistorias do supervisor
$supervisor_id = $_SESSION['usuario_id'];
$total_vistorias = $conn->query("SELECT COUNT(*) as total FROM vistorias WHERE supervisor_id = $supervisor_id AND status = 'concluida'")->fetch_assoc()['total'];
$vistorias_mes = $conn->query("SELECT COUNT(*) as total FROM vistorias WHERE supervisor_id = $supervisor_id AND status = 'concluida' AND MONTH(data_vistoria) = MONTH(CURRENT_DATE()) AND YEAR(data_vistoria) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Supervisor - ENGERADIOS</title>
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
        
        .header-left h1 {
            font-size: 18px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            font-size: 14px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            color: #c41e3a;
            font-size: 32px;
            font-weight: bold;
        }
        
        .menu-grid {
            display: grid;
            gap: 15px;
        }
        
        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .menu-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-card h2 {
            font-size: 20px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .menu-card p {
            color: #666;
            font-size: 14px;
        }
        
        .btn-new {
            background: #28a745;
        }
        
        .btn-view {
            background: #007bff;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-left h1 {
                font-size: 16px;
            }
            
            .user-info {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>Vistoria Remota</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
            </div>
            <a href="../logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
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
            <a href="nova_vistoria.php" class="menu-card btn-new">
                <div class="icon">➕</div>
                <h2>Nova Vistoria</h2>
                <p>Criar novo relatório de vistoria</p>
            </a>
            
            <a href="consultar_vistorias.php" class="menu-card btn-view">
                <div class="icon">📋</div>
                <h2>Consultar Vistorias</h2>
                <p>Visualizar vistorias realizadas</p>
            </a>
        </div>
    </div>
</body>
</html>
