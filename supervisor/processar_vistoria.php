<?php
// Aumentar limite de memória para processamento de fotos e áudios
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '60M');

require_once '../config.php';
verificarLogin();

header('Content-Type: application/json');

if ($_SESSION['usuario_tipo'] != 'supervisor') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$cliente_id = intval($_POST['cliente_id'] ?? 0);
$laudo = trim($_POST['laudo'] ?? '');
$orcamento_adequacao = trim($_POST['orcamento_adequacao'] ?? '');
$email_adicional = trim($_POST['email_adicional'] ?? '');
$supervisor_id = $_SESSION['usuario_id'];

// Validações
if ($cliente_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Cliente não selecionado']);
    exit;
}

if (empty($laudo)) {
    echo json_encode(['success' => false, 'message' => 'Laudo é obrigatório']);
    exit;
}

if (empty($orcamento_adequacao)) {
    echo json_encode(['success' => false, 'message' => 'Orçamento de adequação é obrigatório']);
    exit;
}

$conn = getDBConnection();

// Inserir vistoria
$stmt = $conn->prepare("INSERT INTO vistorias (cliente_id, supervisor_id, laudo, orcamento_adequacao, status) VALUES (?, ?, ?, ?, 'concluida')");
$stmt->bind_param("iiss", $cliente_id, $supervisor_id, $laudo, $orcamento_adequacao);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar vistoria']);
    $stmt->close();
    $conn->close();
    exit;
}

$vistoria_id = $stmt->insert_id;
$stmt->close();

// Upload de fotos com legendas
if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
    $upload_dir = '../uploads/fotos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $legendas = $_POST['legendas'] ?? [];
    
    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
        if (empty($tmp_name)) continue;
        
        $filename = uniqid() . '_' . basename($_FILES['fotos']['name'][$key]);
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($tmp_name, $filepath)) {
            $caminho_foto = 'uploads/fotos/' . $filename;
            $legenda = isset($legendas[$key]) ? trim($legendas[$key]) : null;
            
            $stmt = $conn->prepare("INSERT INTO vistoria_fotos (vistoria_id, caminho_foto, legenda) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $vistoria_id, $caminho_foto, $legenda);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Upload de áudio
if (isset($_FILES['audio']) && $_FILES['audio']['error'] == 0) {
    $upload_dir = __DIR__ . '/../uploads/audios/';
    $filename = uniqid() . '_' . basename($_FILES['audio']['name']);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['audio']['tmp_name'], $filepath)) {
        $caminho_audio = 'uploads/audios/' . $filename;
        $stmt = $conn->prepare("INSERT INTO vistoria_audios (vistoria_id, caminho_audio) VALUES (?, ?)");
        $stmt->bind_param("is", $vistoria_id, $caminho_audio);
        $stmt->execute();
        $stmt->close();
        
        // Verificar duração do áudio (opcional)
        $getid3_path = __DIR__ . '/../lib/getid3/getid3.php';
        if (file_exists($getid3_path)) {
            require_once $getid3_path;
        }

        if (class_exists('getID3')) {
            $getID3 = new getID3;
            $file_info = $getID3->analyze($filepath);
            if (isset($file_info['playtime_seconds']) && defined('MAX_AUDIO_DURATION') && $file_info['playtime_seconds'] > MAX_AUDIO_DURATION) {
                // Áudio muito longo, mas já foi salvo
            }
        }
    }
}

// Buscar informações para o e-mail
$stmt = $conn->prepare("
    SELECT 
        v.id, v.laudo, v.orcamento_adequacao, v.data_vistoria,
        c.nome as cliente_nome, c.endereco as cliente_endereco,
        u.nome as supervisor_nome, u.email as supervisor_email
    FROM vistorias v
    JOIN clientes c ON v.cliente_id = c.id
    JOIN usuarios u ON v.supervisor_id = u.id
    WHERE v.id = ?
");
$stmt->bind_param("i", $vistoria_id);
$stmt->execute();
$result = $stmt->get_result();
$vistoria_info = $result->fetch_assoc();
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

// Buscar áudio
$audio = null;
$result = $conn->query("SELECT caminho_audio FROM vistoria_audios WHERE vistoria_id = $vistoria_id LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $audio = $row['caminho_audio'];
}

$conn->close();

// Enviar e-mail
$sucesso_email = enviarEmailVistoria($vistoria_info, $fotos, $audio, $email_adicional);

// Enviar para WhatsApp
$sucesso_whatsapp = enviarWhatsAppVistoria($vistoria_id, $vistoria_info);

// Mensagem de retorno
if ($sucesso_email && $sucesso_whatsapp) {
    echo json_encode(['success' => true, 'message' => 'Vistoria concluída! E-mail e WhatsApp enviados com sucesso']);
} elseif ($sucesso_email) {
    echo json_encode(['success' => true, 'message' => 'Vistoria concluída e e-mail enviado. WhatsApp não enviado']);
} elseif ($sucesso_whatsapp) {
    echo json_encode(['success' => true, 'message' => 'Vistoria concluída e WhatsApp enviado. E-mail não enviado']);
} else {
    echo json_encode(['success' => true, 'message' => 'Vistoria concluída, mas houve erro ao enviar notificações']);
}

// Função para enviar e-mail
function enviarEmailVistoria($vistoria, $fotos, $audio, $email_adicional = '') {
    // Incluir configuração SMTP
    require_once __DIR__ . '/../config_smtp.php';
    
    // Verificar se deve usar SMTP ou mail()
    if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
        return enviarEmailSMTP($vistoria, $fotos, $audio, $email_adicional);
    } else {
        return enviarEmailPHP($vistoria, $fotos, $audio, $email_adicional);
    }
}

// Incluir PHPMailer no topo do arquivo (antes das funções)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Função para enviar e-mail via SMTP (PHPMailer)
function enviarEmailSMTP($vistoria, $fotos, $audio, $email_adicional = '') {
    // Incluir arquivos PHPMailer
    require_once __DIR__ . '/../lib/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../lib/phpmailer/src/Exception.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Debug (desabilitar em produção)
        $mail->SMTPDebug  = SMTP_DEBUG;
        
        // Remetente
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Destinatários
        $mail->addAddress(EMAIL_DESTINO);
        
        // E-mail adicional
        if (!empty($email_adicional) && filter_var($email_adicional, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($email_adicional);
        }
        
        // Reply-To
        $mail->addReplyTo(SMTP_REPLY_TO, 'ENGERADIOS Operacional');
        
        // Assunto
        $subject = 'Nova Vistoria - ' . $vistoria['cliente_nome'] . ' - ' . date('d/m/Y H:i', strtotime($vistoria['data_vistoria']));
        $mail->Subject = $subject;
        
        // Corpo do e-mail em HTML
        $message = montarCorpoEmail($vistoria, $fotos, $audio);
        $mail->isHTML(true);
        $mail->Body = $message;
        
        // Versão texto (alternativa)
        $mail->AltBody = strip_tags($message);
        
        // Enviar
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log do erro (opcional)
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}

// Função para enviar e-mail via mail() do PHP (fallback)
function enviarEmailPHP($vistoria, $fotos, $audio, $email_adicional = '') {
    // E-mail principal
    $to = EMAIL_DESTINO;
    
    // Adicionar e-mail adicional se fornecido
    if (!empty($email_adicional) && filter_var($email_adicional, FILTER_VALIDATE_EMAIL)) {
        $to .= ', ' . $email_adicional;
    }
    
    $subject = 'Nova Vistoria - ' . $vistoria['cliente_nome'] . ' - ' . date('d/m/Y H:i', strtotime($vistoria['data_vistoria']));
    
    // Corpo do e-mail
    $message = montarCorpoEmail($vistoria, $fotos, $audio);
    
    // Headers do e-mail
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_REMETENTE . "\r\n";
    $headers .= "Reply-To: " . $vistoria['supervisor_email'] . "\r\n";
    
    // Enviar e-mail
    return mail($to, $subject, $message, $headers);
}

// Função para montar corpo do e-mail HTML
function montarCorpoEmail($vistoria, $fotos, $audio) {
    return '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { background: #c41e3a; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; margin: 20px 0; }
            .section { margin-bottom: 20px; }
            .section h3 { color: #c41e3a; border-bottom: 2px solid #c41e3a; padding-bottom: 5px; }
            .info-row { margin: 10px 0; }
            .label { font-weight: bold; color: #666; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>VISTORIA REMOTA ENGERADIOS</h1>
                <p>Relatório de Vistoria</p>
            </div>
            
            <div class="content">
                <div class="section">
                    <h3>Informações Gerais</h3>
                    <div class="info-row">
                        <span class="label">Cliente:</span> ' . htmlspecialchars($vistoria['cliente_nome']) . '
                    </div>
                    <div class="info-row">
                        <span class="label">Endereço:</span> ' . htmlspecialchars($vistoria['cliente_endereco']) . '
                    </div>
                    <div class="info-row">
                        <span class="label">Supervisor:</span> ' . htmlspecialchars($vistoria['supervisor_nome']) . '
                    </div>
                    <div class="info-row">
                        <span class="label">Data/Hora:</span> ' . date('d/m/Y H:i', strtotime($vistoria['data_vistoria'])) . '
                    </div>
                </div>
                
                <div class="section">
                    <h3>Laudo da Vistoria</h3>
                    <p>' . nl2br(htmlspecialchars($vistoria['laudo'])) . '</p>
                </div>
                
                <div class="section">
                    <h3>Orçamento de Adequação</h3>
                    <p>' . nl2br(htmlspecialchars($vistoria['orcamento_adequacao'])) . '</p>
                </div>
                
                <div class="section">
                    <h3>Anexos</h3>
                    <p><strong>Fotos:</strong> ' . count($fotos) . ' foto(s) anexada(s)</p>
                    ' . ($audio ? '<p><strong>Áudio:</strong> 1 arquivo de áudio anexado</p>' : '') . '
                </div>
            </div>
            
            <div class="footer">
                <p>Este é um e-mail automático do Sistema de Vistoria Remota ENGERADIOS</p>
                <p>© ' . date('Y') . ' ENGERADIOS - Segurança Eletrônica</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

// Função para enviar vistoria para WhatsApp
function enviarWhatsAppVistoria($vistoria_id, $vistoria_info) {
    // Incluir biblioteca WhatsApp
    require_once __DIR__ . '/../lib/whatsapp.php';
    
    // Verificar se WhatsApp está habilitado
    if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
        return false; // WhatsApp desabilitado, não é erro
    }
    
    try {
        // Gerar PDF da vistoria
        require_once __DIR__ . '/../lib/fpdf/fpdf.php';
        
        // Criar instância do PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Cabeçalho
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'VISTORIA REMOTA ENGERADIOS', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Informações gerais
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Informacoes Gerais', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Cliente: ' . utf8_decode($vistoria_info['cliente_nome']), 0, 1);
        $pdf->Cell(0, 6, 'Supervisor: ' . utf8_decode($vistoria_info['supervisor_nome']), 0, 1);
        $pdf->Cell(0, 6, 'Data/Hora: ' . date('d/m/Y H:i', strtotime($vistoria_info['data_vistoria'])), 0, 1);
        $pdf->Ln(5);
        
        // Laudo
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Laudo da Vistoria', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, utf8_decode($vistoria_info['laudo']));
        $pdf->Ln(5);
        
        // Orçamento
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Orcamento de Adequacao', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, utf8_decode($vistoria_info['orcamento_adequacao']));
        
        // Salvar PDF temporário
        $pdfPath = __DIR__ . '/../uploads/temp/vistoria_' . $vistoria_id . '_' . time() . '.pdf';
        
        // Criar diretório se não existir
        $tempDir = dirname($pdfPath);
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $pdf->Output('F', $pdfPath);
        
        // Preparar dados para mensagem
        $data = [
            'cliente' => $vistoria_info['cliente_nome'],
            'supervisor' => $vistoria_info['supervisor_nome'],
            'data' => date('d/m/Y', strtotime($vistoria_info['data_vistoria'])),
            'hora' => date('H:i', strtotime($vistoria_info['data_vistoria'])),
            'numero' => $vistoria_id
        ];
        
        // Enviar via WhatsApp
        $result = WhatsAppSender::sendVistoriaPDF($pdfPath, $data);
        
        // Remover PDF temporário após envio
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
        
        return $result['success'];
        
    } catch (Exception $e) {
        error_log('Erro ao enviar WhatsApp: ' . $e->getMessage());
        return false;
    }
}
?>
