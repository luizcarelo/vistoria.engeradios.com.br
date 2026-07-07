<?php
require_once '../config.php';
verificarLogin();

if ($_SESSION['usuario_tipo'] != 'supervisor') {
    header('Location: ../index.php');
    exit;
}

$vistoria_id = intval($_GET['id'] ?? 0);

if ($vistoria_id <= 0) {
    header('Location: consultar_vistorias.php');
    exit;
}

$conn = getDBConnection();

// Buscar vistoria (permite ver vistorias de todos os supervisores)
$stmt = $conn->prepare("
    SELECT 
        v.id, v.laudo, v.orcamento_adequacao, v.data_vistoria,
        c.nome as cliente_nome, c.endereco as cliente_endereco, c.telefone as cliente_telefone, c.email as cliente_email,
        u.nome as supervisor_nome, u.email as supervisor_email
    FROM vistorias v
    JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN usuarios u ON v.supervisor_id = u.id
    WHERE v.id = ?
");
$stmt->bind_param("i", $vistoria_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['erro'] = 'Vistoria não encontrada';
    header('Location: consultar_vistorias.php');
    $stmt->close();
    $conn->close();
    exit;
}

$vistoria = $result->fetch_assoc();
$stmt->close();

// Buscar fotos com legendas
$fotos = [];
$result = $conn->query("SELECT caminho_foto, legenda FROM vistoria_fotos WHERE vistoria_id = $vistoria_id");
while ($row = $result->fetch_assoc()) {
    $fotos[] = [
        'caminho' => $row['caminho_foto'],
        'legenda' => $row['legenda']
    ];
}

// Buscar áudios
$audios = [];
$result = $conn->query("SELECT caminho_audio FROM vistoria_audios WHERE vistoria_id = $vistoria_id");
while ($row = $result->fetch_assoc()) {
    $audios[] = $row['caminho_audio'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Vistoria #<?php echo $vistoria_id; ?> - Vistoria Remota ENGERADIOS</title>
    <link rel="icon" type="image/png" href="../Logooriginal.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #c41e3a 0%, #8b1528 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-left img {
            height: 60px;
            background: white;
            padding: 10px;
            border-radius: 10px;
        }

        .header-title h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header-title p {
            font-size: 14px;
            opacity: 0.9;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-back:hover {
            background: white;
            color: #c41e3a;
        }

        .content {
            padding: 30px;
        }

        .detail-section {
            background: #f8f9fa;
            border-left: 4px solid #c41e3a;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .detail-section h4 {
            color: #c41e3a;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-section p {
            line-height: 1.8;
            color: #333;
            margin-bottom: 10px;
        }

        .detail-section p strong {
            color: #c41e3a;
            display: inline-block;
            min-width: 150px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .info-item strong {
            display: block;
            color: #c41e3a;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .info-item span {
            color: #333;
            font-size: 15px;
        }

        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .foto-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .foto-item:hover {
            transform: scale(1.05);
        }

        .foto-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        audio {
            width: 100%;
            margin-top: 10px;
            border-radius: 8px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #c41e3a;
            color: white;
        }

        .text-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 20px;
            }

            .header-left {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-left img {
                height: 50px;
            }

            .header-title h1 {
                font-size: 20px;
            }

            .content {
                padding: 15px;
            }

            .detail-section {
                padding: 15px;
            }

            .fotos-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="../Logooriginal.png" alt="ENGERADIOS">
                <div class="header-title">
                    <h1>📋 Vistoria #<?php echo str_pad($vistoria_id, 6, '0', STR_PAD_LEFT); ?></h1>
                    <p>Detalhes completos da vistoria realizada</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="consultar_vistorias.php" class="btn btn-back">
                    ← Voltar
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Informações Gerais -->
            <div class="detail-section">
                <h4>📊 Informações Gerais</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>📅 Data da Vistoria</strong>
                        <span><?php echo date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>👤 Supervisor Responsável</strong>
                        <span><?php echo htmlspecialchars($vistoria['supervisor_nome'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if (!empty($vistoria['supervisor_email'])): ?>
                    <div class="info-item">
                        <strong>📧 E-mail do Supervisor</strong>
                        <span><?php echo htmlspecialchars($vistoria['supervisor_email']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informações do Cliente -->
            <div class="detail-section">
                <h4>🏢 Informações do Cliente</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Nome</strong>
                        <span><?php echo htmlspecialchars($vistoria['cliente_nome']); ?></span>
                    </div>
                    <?php if (!empty($vistoria['cliente_telefone'])): ?>
                    <div class="info-item">
                        <strong>📞 Telefone</strong>
                        <span><?php echo htmlspecialchars($vistoria['cliente_telefone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($vistoria['cliente_email'])): ?>
                    <div class="info-item">
                        <strong>📧 E-mail</strong>
                        <span><?php echo htmlspecialchars($vistoria['cliente_email']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($vistoria['cliente_endereco'])): ?>
                <div class="info-item" style="margin-top: 15px;">
                    <strong>📍 Endereço</strong>
                    <span><?php echo htmlspecialchars($vistoria['cliente_endereco']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Laudo -->
            <div class="detail-section">
                <h4>📝 Laudo da Vistoria</h4>
                <div class="text-content">
                    <?php echo nl2br(htmlspecialchars($vistoria['laudo'])); ?>
                </div>
            </div>

            <!-- Orçamento -->
            <div class="detail-section">
                <h4>💰 Orçamento de Adequação</h4>
                <div class="text-content">
                    <?php echo nl2br(htmlspecialchars($vistoria['orcamento_adequacao'])); ?>
                </div>
            </div>

            <!-- Fotos -->
            <?php if (count($fotos) > 0): ?>
            <div class="detail-section">
                <h4>📷 Registro Fotográfico <span class="badge"><?php echo count($fotos); ?> foto(s)</span></h4>
                <div class="fotos-grid">
                    <?php foreach ($fotos as $index => $foto): ?>
                        <div class="foto-item">
                            <img src="../<?php echo htmlspecialchars($foto['caminho']); ?>" 
                                 alt="<?php echo !empty($foto['legenda']) ? htmlspecialchars($foto['legenda']) : 'Foto ' . ($index + 1) . ' da vistoria'; ?>" 
                                 onclick="window.open('../<?php echo htmlspecialchars($foto['caminho']); ?>', '_blank')"
                                 title="Clique para ampliar">
                            <?php if (!empty($foto['legenda'])): ?>
                                <div style="padding: 8px; background: white; font-size: 13px; color: #333; border-top: 1px solid #e0e0e0;">
                                    <?php echo htmlspecialchars($foto['legenda']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Áudios -->
            <?php if (count($audios) > 0): ?>
            <div class="detail-section">
                <h4>🎤 Áudio da Vistoria <span class="badge"><?php echo count($audios); ?> áudio(s)</span></h4>
                <?php foreach ($audios as $index => $audio): ?>
                    <div style="margin-bottom: 15px;">
                        <p style="margin-bottom: 5px;"><strong>Áudio <?php echo ($index + 1); ?>:</strong></p>
                        <audio controls>
                            <source src="../<?php echo htmlspecialchars($audio); ?>" type="audio/mpeg">
                            <source src="../<?php echo htmlspecialchars($audio); ?>" type="audio/wav">
                            <source src="../<?php echo htmlspecialchars($audio); ?>" type="audio/ogg">
                            Seu navegador não suporta o elemento de áudio.
                        </audio>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
