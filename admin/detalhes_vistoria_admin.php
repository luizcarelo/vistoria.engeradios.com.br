<?php
require_once '../config.php';
verificarAdmin();

$vistoria_id = intval($_GET['id'] ?? 0);

if ($vistoria_id <= 0) {
    echo '<p>Vistoria não encontrada</p>';
    exit;
}

$conn = getDBConnection();

// Buscar vistoria
$stmt = $conn->prepare("
    SELECT 
        v.id, v.laudo, v.orcamento_adequacao, v.data_vistoria,
        c.nome as cliente_nome, c.endereco as cliente_endereco, c.telefone as cliente_telefone, c.email as cliente_email,
        u.nome as supervisor_nome, u.email as supervisor_email
    FROM vistorias v
    JOIN clientes c ON v.cliente_id = c.id
    JOIN usuarios u ON v.supervisor_id = u.id
    WHERE v.id = ?
");
$stmt->bind_param("i", $vistoria_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<p>Vistoria não encontrada</p>';
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

<div class="detail-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h4 style="margin: 0;">Informações Gerais</h4>
        <a href="gerar_pdf.php?id=<?php echo $vistoria_id; ?>" class="btn-pdf" style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 13px;" target="_blank">
            📄 Baixar PDF
        </a>
    </div>
    <p><strong>Data da Vistoria:</strong> <?php echo date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])); ?></p>
    <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($vistoria['supervisor_nome']); ?> (<?php echo htmlspecialchars($vistoria['supervisor_email']); ?>)</p>
</div>

<div class="detail-section">
    <h4>Informações do Cliente</h4>
    <p><strong>Nome:</strong> <?php echo htmlspecialchars($vistoria['cliente_nome']); ?></p>
    <?php if (!empty($vistoria['cliente_endereco'])): ?>
        <p><strong>Endereço:</strong> <?php echo htmlspecialchars($vistoria['cliente_endereco']); ?></p>
    <?php endif; ?>
    <?php if (!empty($vistoria['cliente_telefone'])): ?>
        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($vistoria['cliente_telefone']); ?></p>
    <?php endif; ?>
    <?php if (!empty($vistoria['cliente_email'])): ?>
        <p><strong>E-mail:</strong> <?php echo htmlspecialchars($vistoria['cliente_email']); ?></p>
    <?php endif; ?>
</div>

<div class="detail-section">
    <h4>Laudo da Vistoria</h4>
    <p><?php echo nl2br(htmlspecialchars($vistoria['laudo'])); ?></p>
</div>

<div class="detail-section">
    <h4>Orçamento de Adequação</h4>
    <p><?php echo nl2br(htmlspecialchars($vistoria['orcamento_adequacao'])); ?></p>
</div>

<?php if (count($fotos) > 0): ?>
<div class="detail-section">
    <h4>Fotos da Vistoria (<?php echo count($fotos); ?>)</h4>
    <div class="fotos-grid">
        <?php foreach ($fotos as $index => $foto): ?>
            <div class="foto-item">
                <img src="../<?php echo htmlspecialchars($foto['caminho']); ?>" 
                     alt="<?php echo !empty($foto['legenda']) ? htmlspecialchars($foto['legenda']) : 'Foto da vistoria'; ?>" 
                     onclick="window.open('../<?php echo htmlspecialchars($foto['caminho']); ?>', '_blank')">
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

<?php if (count($audios) > 0): ?>
<div class="detail-section">
    <h4>Áudio da Vistoria</h4>
    <?php foreach ($audios as $audio): ?>
        <audio controls style="width: 100%; margin-top: 10px;">
            <source src="../<?php echo htmlspecialchars($audio); ?>" type="audio/mpeg">
            Seu navegador não suporta o elemento de áudio.
        </audio>
    <?php endforeach; ?>
</div>
<?php endif; ?>
