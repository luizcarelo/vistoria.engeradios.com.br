<?php
/**
 * Gerador de PDF - Vistoria ENGERADIOS
 * 
 * Correções aplicadas:
 * - Salva PDF em arquivo temporário e usa readfile() para download (evita problema de buffer com PDFs grandes)
 * - LEFT JOIN para não falhar se supervisor/cliente foi excluído
 * - Validação robusta de imagens antes de inserir
 * - Tratamento completo de erros com fallback
 * - ob_start/ob_end_clean para evitar output prematuro
 */

// Capturar qualquer output acidental
ob_start();

// Configurações de memória e execução
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config.php';
verificarAdmin();

// Verificar se foi passado o ID da vistoria
$vistoria_id = intval($_GET['id'] ?? 0);

if ($vistoria_id <= 0) {
    ob_end_clean();
    die('Vistoria não encontrada');
}

$conn = getDBConnection();

// Buscar informações da vistoria (LEFT JOIN para não falhar se supervisor/cliente foi excluído)
$stmt = $conn->prepare("
    SELECT 
        v.id, v.laudo, v.orcamento_adequacao, v.data_vistoria,
        c.nome as cliente_nome, c.endereco as cliente_endereco, 
        c.telefone as cliente_telefone, c.email as cliente_email,
        u.nome as supervisor_nome, u.email as supervisor_email
    FROM vistorias v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN usuarios u ON v.supervisor_id = u.id
    WHERE v.id = ?
");
$stmt->bind_param("i", $vistoria_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    ob_end_clean();
    die('Vistoria não encontrada');
}

$vistoria = $result->fetch_assoc();
$stmt->close();

// Buscar fotos
$fotos = [];
$result = $conn->query("SELECT caminho_foto, legenda FROM vistoria_fotos WHERE vistoria_id = $vistoria_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fotos[] = $row;
    }
}

// Buscar áudios
$audios = [];
$result = $conn->query("SELECT caminho_audio FROM vistoria_audios WHERE vistoria_id = $vistoria_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $audios[] = $row['caminho_audio'];
    }
}

$conn->close();

// Função segura para converter UTF-8 para ISO-8859-1
function safeConvert($text) {
    if ($text === null || $text === '') return '';
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    if ($converted !== false) return $converted;
    $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
    if ($converted !== false) return $converted;
    return utf8_decode($text);
}

/**
 * Valida se um arquivo de imagem pode ser usado pelo FPDF
 */
function validarImagem($filepath) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }
    $filesize = @filesize($filepath);
    if ($filesize === false || $filesize == 0 || $filesize > 50 * 1024 * 1024) {
        return false;
    }
    $imgInfo = @getimagesize($filepath);
    if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1]) || $imgInfo[0] <= 0 || $imgInfo[1] <= 0) {
        return false;
    }
    // FPDF suporta: JPEG(2), PNG(3), GIF(1)
    if (in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
        return true;
    }
    return false;
}

// Incluir biblioteca FPDF
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

class PDF extends FPDF
{
    private $vistoriaId;
    
    function __construct($vistoria_id) {
        parent::__construct();
        $this->vistoriaId = $vistoria_id;
    }
    
    // Cabeçalho
    function Header()
    {
        $logo_path = __DIR__ . '/../Logooriginal.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 8, 35);
        }
        
        $this->SetY(8);
        $this->SetX(50);
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(196, 30, 58);
        $this->Cell(0, 10, 'VISTORIA REMOTA ENGERADIOS', 0, 1, 'C');
        
        $this->SetX(50);
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, safeConvert('Relatório de Vistoria Técnica'), 0, 1, 'C');
        
        $this->SetDrawColor(196, 30, 58);
        $this->SetLineWidth(0.5);
        $this->Line(10, 35, 200, 35);
        
        $this->SetY(40);
    }
    
    // Rodapé
    function Footer()
    {
        $this->SetY(-25);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, safeConvert('ENGERADIOS - Segurança Eletrônica'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, safeConvert('Relatório gerado em ') . date('d/m/Y') . safeConvert(' às ') . date('H:i'), 0, 1, 'C');
        
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 3, safeConvert('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
    
    function Section($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(196, 30, 58);
        $this->Cell(0, 8, safeConvert($title), 0, 1);
        
        $this->SetDrawColor(196, 30, 58);
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }
    
    function InfoField($label, $value)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(50, 6, safeConvert($label . ':'), 0, 0);
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(50, 50, 50);
        $this->MultiCell(0, 6, safeConvert($value));
    }
    
    function ContentBox($text)
    {
        $this->SetFillColor(249, 249, 249);
        $this->SetDrawColor(196, 30, 58);
        $this->SetLineWidth(0.5);
        
        $x = $this->GetX();
        $y = $this->GetY();
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(50, 50, 50);
        $this->MultiCell(0, 6, safeConvert($text), 0, 'L', true);
        
        $this->SetDrawColor(196, 30, 58);
        $this->SetLineWidth(1);
        $height = $this->GetY() - $y;
        $this->Line($x, $y, $x, $y + $height);
    }
    
    /**
     * Adiciona imagem proporcional com tratamento de erro
     */
    function AddSafeImage($file, $x, $y, $maxW, $maxH)
    {
        $imgInfo = @getimagesize($file);
        if (!$imgInfo || $imgInfo[0] <= 0 || $imgInfo[1] <= 0) {
            return 0;
        }
        
        $ratioW = $maxW / $imgInfo[0];
        $ratioH = $maxH / $imgInfo[1];
        $ratio = min($ratioW, $ratioH);
        
        $finalW = $imgInfo[0] * $ratio;
        $finalH = $imgInfo[1] * $ratio;
        
        $offsetX = $x + ($maxW - $finalW) / 2;
        
        try {
            $this->Image($file, $offsetX, $y, $finalW, $finalH);
            return $finalH;
        } catch (Exception $e) {
            return 0;
        }
    }
}

// ========== GERAR O PDF ==========
try {
    $pdf = new PDF($vistoria_id);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 30);
    
    // Informações Gerais
    $pdf->Section('Informações Gerais');
    $pdf->InfoField('Número da Vistoria', '#' . str_pad($vistoria['id'], 6, '0', STR_PAD_LEFT));
    $pdf->InfoField('Data/Hora', date('d/m/Y \à\s H:i', strtotime($vistoria['data_vistoria'])));
    $pdf->InfoField('Supervisor', $vistoria['supervisor_nome'] ?? 'N/A');
    $pdf->InfoField('E-mail Supervisor', $vistoria['supervisor_email'] ?? 'N/A');
    $pdf->Ln(5);
    
    // Dados do Cliente
    $pdf->Section('Dados do Cliente');
    $pdf->InfoField('Nome', $vistoria['cliente_nome'] ?? 'N/A');
    if (!empty($vistoria['cliente_endereco'])) {
        $pdf->InfoField('Endereço', $vistoria['cliente_endereco']);
    }
    if (!empty($vistoria['cliente_telefone'])) {
        $pdf->InfoField('Telefone', $vistoria['cliente_telefone']);
    }
    if (!empty($vistoria['cliente_email'])) {
        $pdf->InfoField('E-mail', $vistoria['cliente_email']);
    }
    $pdf->Ln(5);
    
    // Laudo da Vistoria
    $pdf->Section('Laudo da Vistoria');
    $pdf->ContentBox(!empty($vistoria['laudo']) ? $vistoria['laudo'] : 'Não informado');
    $pdf->Ln(5);
    
    // Orçamento de Adequação
    $pdf->Section('Orçamento de Adequação');
    $pdf->ContentBox(!empty($vistoria['orcamento_adequacao']) ? $vistoria['orcamento_adequacao'] : 'Não aplicado');
    $pdf->Ln(5);
    
    // Anexos
    if (count($fotos) > 0 || count($audios) > 0) {
        $pdf->Section('Anexos');
        $pdf->InfoField('Fotos', count($fotos) . ' arquivo(s)');
        if (count($audios) > 0) {
            $pdf->InfoField('Áudios', count($audios) . ' arquivo(s)');
        }
        $pdf->Ln(5);
    }
    
    // Adicionar fotos
    if (count($fotos) > 0) {
        $pdf->AddPage();
        $pdf->Section('Registro Fotográfico');
        
        $startY = $pdf->GetY() + 5;
        
        $marginLeft = 10;
        $pageWidth = 190;
        $colGap = 8;
        $rowGap = 12;
        $cols = 2;
        $imgWidth = ($pageWidth - $colGap) / $cols;
        $imgMaxHeight = 75;
        $legendaHeight = 10;
        $blockHeight = $imgMaxHeight + $legendaHeight + $rowGap;
        
        $footerMargin = 30;
        $pageBottom = 297 - $footerMargin;
        
        $currentY = $startY;
        $foto_num = 0;
        $col = 0;
        
        foreach ($fotos as $foto) {
            $foto_path = __DIR__ . '/../' . $foto['caminho_foto'];
            
            // Validar imagem antes de inserir
            if (!validarImagem($foto_path)) {
                $foto_num++;
                continue;
            }
            
            // Verificar se cabe na página atual
            if ($currentY + $imgMaxHeight + $legendaHeight > $pageBottom) {
                $pdf->AddPage();
                $pdf->Section('Registro Fotográfico');
                $currentY = $pdf->GetY() + 5;
                $col = 0;
            }
            
            // Posição X
            if ($col == 0) {
                $x = $marginLeft;
            } else {
                $x = $marginLeft + $imgWidth + $colGap;
            }
            
            // Adicionar imagem
            $realHeight = $pdf->AddSafeImage($foto_path, $x, $currentY, $imgWidth, $imgMaxHeight);
            
            // Legenda (texto completo, sem truncar)
            if ($realHeight > 0) {
                $legendaY = $currentY + $imgMaxHeight + 2;
                $pdf->SetXY($x, $legendaY);
                $pdf->SetFont('Arial', '', 7);
                $pdf->SetTextColor(100, 100, 100);
                
                $legendaText = 'Foto ' . ($foto_num + 1);
                if (!empty($foto['legenda'])) {
                    $legendaText .= ' - ' . $foto['legenda'];
                }
                $pdf->MultiCell($imgWidth, 4, safeConvert($legendaText), 0, 'C');
            }
            
            // Avançar coluna
            $col++;
            if ($col >= $cols) {
                $col = 0;
                $currentY += $blockHeight;
            }
            
            $foto_num++;
        }
    }
    
    // ========== SALVAR EM ARQUIVO TEMPORÁRIO E ENVIAR VIA READFILE ==========
    // Isso evita problemas de output buffering com PDFs grandes
    
    $filename = 'Vistoria_' . str_pad($vistoria['id'], 6, '0', STR_PAD_LEFT) . '_' . date('Ymd', strtotime($vistoria['data_vistoria'])) . '.pdf';
    
    // Criar diretório temporário se não existir
    $tempDir = __DIR__ . '/../uploads/temp/';
    if (!file_exists($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }
    
    $tempFile = $tempDir . 'pdf_' . $vistoria_id . '_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
    
    // Salvar PDF em arquivo
    $pdf->Output('F', $tempFile);
    
    // Verificar se o arquivo foi criado com sucesso
    if (!file_exists($tempFile) || filesize($tempFile) == 0) {
        throw new Exception('Falha ao salvar PDF temporário');
    }
    
    // Limpar todo output buffer acumulado
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar headers para download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Enviar arquivo em chunks para evitar estouro de memória
    $handle = fopen($tempFile, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192); // 8KB por vez
            flush();
        }
        fclose($handle);
    } else {
        readfile($tempFile);
    }
    
    // Remover arquivo temporário
    @unlink($tempFile);
    
} catch (Exception $e) {
    // Limpar buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Tentar gerar PDF mínimo sem fotos como fallback
    try {
        $pdf2 = new PDF($vistoria_id);
        $pdf2->AliasNbPages();
        $pdf2->AddPage();
        $pdf2->SetAutoPageBreak(true, 30);
        
        $pdf2->Section('Informações Gerais');
        $pdf2->InfoField('Número da Vistoria', '#' . str_pad($vistoria['id'], 6, '0', STR_PAD_LEFT));
        $pdf2->InfoField('Data/Hora', date('d/m/Y \à\s H:i', strtotime($vistoria['data_vistoria'])));
        $pdf2->InfoField('Supervisor', $vistoria['supervisor_nome'] ?? 'N/A');
        $pdf2->Ln(5);
        
        $pdf2->Section('Dados do Cliente');
        $pdf2->InfoField('Nome', $vistoria['cliente_nome'] ?? 'N/A');
        $pdf2->Ln(5);
        
        $pdf2->Section('Laudo da Vistoria');
        $pdf2->ContentBox(!empty($vistoria['laudo']) ? $vistoria['laudo'] : 'Não informado');
        $pdf2->Ln(5);
        
        $pdf2->Section('Orçamento de Adequação');
        $pdf2->ContentBox(!empty($vistoria['orcamento_adequacao']) ? $vistoria['orcamento_adequacao'] : 'Não aplicado');
        $pdf2->Ln(5);
        
        $pdf2->Section('Observação');
        $pdf2->ContentBox('As fotos não puderam ser incluídas neste relatório. Erro técnico: ' . $e->getMessage());
        
        $filename = 'Vistoria_' . str_pad($vistoria['id'], 6, '0', STR_PAD_LEFT) . '_' . date('Ymd', strtotime($vistoria['data_vistoria'])) . '.pdf';
        
        $tempFile2 = $tempDir . 'pdf_fallback_' . $vistoria_id . '_' . time() . '.pdf';
        $pdf2->Output('F', $tempFile2);
        
        if (file_exists($tempFile2) && filesize($tempFile2) > 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tempFile2));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            readfile($tempFile2);
            @unlink($tempFile2);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>Erro ao gerar PDF</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="javascript:history.back()">Voltar</a></p>';
        }
        
    } catch (Exception $e2) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<h3>Erro ao gerar PDF</h3>';
        echo '<p>Erro principal: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Erro fallback: ' . htmlspecialchars($e2->getMessage()) . '</p>';
        echo '<p><a href="javascript:history.back()">Voltar</a></p>';
    }
}
?>
