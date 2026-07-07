<?php
/**
 * DIAGNÓSTICO DE GERAÇÃO DE PDF
 * Acesse: /admin/diagnostico_pdf.php?id=61
 * (troque 61 pelo ID da vistoria que está falhando)
 * 
 * Este arquivo mostra exatamente onde o erro ocorre.
 * REMOVA ESTE ARQUIVO APÓS RESOLVER O PROBLEMA.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');

require_once '../config.php';
verificarAdmin();

$vistoria_id = intval($_GET['id'] ?? 0);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Diagnóstico PDF</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee;} ";
echo ".ok{color:#4caf50;} .erro{color:#f44336;} .warn{color:#ff9800;} ";
echo ".box{background:#16213e;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #4caf50;} ";
echo ".box.fail{border-left-color:#f44336;} .box.warn{border-left-color:#ff9800;}</style></head><body>";
echo "<h1>🔍 Diagnóstico PDF - Vistoria #{$vistoria_id}</h1>";

if ($vistoria_id <= 0) {
    echo "<p class='erro'>❌ ID da vistoria não informado. Use: ?id=NUMERO</p>";
    echo "</body></html>";
    exit;
}

// ETAPA 1: Conexão com banco
echo "<h2>1. Conexão com Banco de Dados</h2>";
try {
    $conn = getDBConnection();
    echo "<div class='box'><span class='ok'>✅ Conexão OK</span></div>";
} catch (Exception $e) {
    echo "<div class='box fail'><span class='erro'>❌ FALHA: " . htmlspecialchars($e->getMessage()) . "</span></div>";
    echo "</body></html>";
    exit;
}

// ETAPA 2: Buscar vistoria
echo "<h2>2. Dados da Vistoria</h2>";
$stmt = $conn->prepare("
    SELECT 
        v.id, v.laudo, v.orcamento_adequacao, v.data_vistoria,
        c.nome as cliente_nome, c.endereco as cliente_endereco, 
        c.telefone as cliente_telefone, c.email as cliente_email,
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
    echo "<div class='box fail'><span class='erro'>❌ Vistoria #{$vistoria_id} NÃO ENCONTRADA no banco de dados</span></div>";
    
    // Verificar se existe sem o JOIN
    $check = $conn->query("SELECT id, cliente_id, supervisor_id FROM vistorias WHERE id = $vistoria_id");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        echo "<div class='box warn'><span class='warn'>⚠️ A vistoria EXISTE mas o JOIN falhou:</span><br>";
        echo "cliente_id = " . $row['cliente_id'] . "<br>";
        echo "supervisor_id = " . $row['supervisor_id'] . "<br>";
        
        // Verificar cliente
        $checkC = $conn->query("SELECT id, nome FROM clientes WHERE id = " . intval($row['cliente_id']));
        if (!$checkC || $checkC->num_rows == 0) {
            echo "<span class='erro'>❌ Cliente ID {$row['cliente_id']} NÃO EXISTE na tabela clientes!</span><br>";
        } else {
            echo "<span class='ok'>✅ Cliente encontrado</span><br>";
        }
        
        // Verificar supervisor
        $checkS = $conn->query("SELECT id, nome FROM usuarios WHERE id = " . intval($row['supervisor_id']));
        if (!$checkS || $checkS->num_rows == 0) {
            echo "<span class='erro'>❌ Supervisor/Usuário ID {$row['supervisor_id']} NÃO EXISTE na tabela usuarios!</span><br>";
            echo "<strong>⚡ ESTA É PROVAVELMENTE A CAUSA DO PDF ZERADO!</strong><br>";
            echo "O supervisor que criou esta vistoria foi excluído do sistema.";
        } else {
            echo "<span class='ok'>✅ Supervisor encontrado</span><br>";
        }
        echo "</div>";
    } else {
        echo "<div class='box fail'><span class='erro'>❌ Vistoria realmente não existe no banco</span></div>";
    }
    
    $conn->close();
    echo "</body></html>";
    exit;
}

$vistoria = $result->fetch_assoc();
$stmt->close();
echo "<div class='box'><span class='ok'>✅ Vistoria encontrada</span><br>";
echo "Cliente: " . htmlspecialchars($vistoria['cliente_nome']) . "<br>";
echo "Supervisor: " . htmlspecialchars($vistoria['supervisor_nome']) . "<br>";
echo "Data: " . $vistoria['data_vistoria'] . "<br>";
echo "Laudo: " . (empty($vistoria['laudo']) ? '<span class="warn">VAZIO</span>' : mb_strlen($vistoria['laudo']) . ' caracteres') . "<br>";
echo "Orçamento: " . (empty($vistoria['orcamento_adequacao']) ? '<span class="warn">VAZIO</span>' : mb_strlen($vistoria['orcamento_adequacao']) . ' caracteres') . "</div>";

// ETAPA 3: Verificar fotos
echo "<h2>3. Fotos da Vistoria</h2>";
$fotos = [];
$result = $conn->query("SELECT caminho_foto, legenda FROM vistoria_fotos WHERE vistoria_id = $vistoria_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fotos[] = $row;
    }
}

if (count($fotos) == 0) {
    echo "<div class='box'><span class='warn'>⚠️ Nenhuma foto encontrada para esta vistoria</span></div>";
} else {
    echo "<div class='box'><span class='ok'>✅ " . count($fotos) . " foto(s) no banco</span></div>";
    
    foreach ($fotos as $i => $foto) {
        $foto_path = __DIR__ . '/../' . $foto['caminho_foto'];
        $num = $i + 1;
        
        echo "<div class='box";
        
        if (!file_exists($foto_path)) {
            echo " fail'>";
            echo "<span class='erro'>❌ Foto {$num}: ARQUIVO NÃO ENCONTRADO</span><br>";
            echo "Caminho DB: " . htmlspecialchars($foto['caminho_foto']) . "<br>";
            echo "Caminho absoluto: " . htmlspecialchars($foto_path) . "<br>";
        } else {
            $filesize = filesize($foto_path);
            $imgInfo = @getimagesize($foto_path);
            
            if ($filesize == 0) {
                echo " fail'>";
                echo "<span class='erro'>❌ Foto {$num}: ARQUIVO VAZIO (0 bytes)</span><br>";
            } elseif (!$imgInfo) {
                echo " fail'>";
                echo "<span class='erro'>❌ Foto {$num}: NÃO É UMA IMAGEM VÁLIDA</span><br>";
                echo "Tamanho: " . number_format($filesize / 1024, 1) . " KB<br>";
                // Verificar os primeiros bytes
                $handle = fopen($foto_path, 'rb');
                $header = fread($handle, 16);
                fclose($handle);
                echo "Header hex: " . bin2hex(substr($header, 0, 8)) . "<br>";
                echo "Extensão: " . pathinfo($foto_path, PATHINFO_EXTENSION) . "<br>";
            } else {
                $types = [1=>'GIF', 2=>'JPEG', 3=>'PNG', 6=>'BMP', 15=>'WBMP', 16=>'XBM', 18=>'WEBP'];
                $typeName = $types[$imgInfo[2]] ?? 'TIPO ' . $imgInfo[2];
                
                $supported = in_array($imgInfo[2], [1, 2, 3]); // GIF, JPEG, PNG
                
                if (!$supported) {
                    echo " warn'>";
                    echo "<span class='warn'>⚠️ Foto {$num}: FORMATO NÃO SUPORTADO PELO FPDF ({$typeName})</span><br>";
                    echo "⚡ Isso pode causar erro fatal se não tratado!<br>";
                } else {
                    echo "'>";
                    echo "<span class='ok'>✅ Foto {$num}: OK ({$typeName})</span><br>";
                }
                
                echo "Dimensões: {$imgInfo[0]}x{$imgInfo[1]} px<br>";
                echo "Tamanho: " . number_format($filesize / 1024, 1) . " KB<br>";
                
                // Verificar se a memória suporta
                $memNeeded = $imgInfo[0] * $imgInfo[1] * 4; // 4 bytes por pixel (RGBA)
                if ($memNeeded > 100 * 1024 * 1024) {
                    echo "<span class='warn'>⚠️ Imagem muito grande, pode causar estouro de memória (" . number_format($memNeeded / 1024 / 1024, 1) . " MB necessários)</span><br>";
                }
            }
        }
        
        echo "Legenda: " . (empty($foto['legenda']) ? '<em>sem legenda</em>' : htmlspecialchars($foto['legenda'])) . "<br>";
        echo "</div>";
    }
}

// ETAPA 4: Verificar FPDF
echo "<h2>4. Biblioteca FPDF</h2>";
$fpdf_path = __DIR__ . '/../lib/fpdf/fpdf.php';
if (file_exists($fpdf_path)) {
    echo "<div class='box'><span class='ok'>✅ FPDF encontrado em: " . htmlspecialchars($fpdf_path) . "</span></div>";
} else {
    echo "<div class='box fail'><span class='erro'>❌ FPDF NÃO ENCONTRADO!</span></div>";
}

// ETAPA 5: Verificar logo
echo "<h2>5. Logo</h2>";
$logo_path = __DIR__ . '/../Logooriginal.png';
if (file_exists($logo_path)) {
    $logoInfo = @getimagesize($logo_path);
    if ($logoInfo) {
        echo "<div class='box'><span class='ok'>✅ Logo OK - {$logoInfo[0]}x{$logoInfo[1]} px</span></div>";
    } else {
        echo "<div class='box fail'><span class='erro'>❌ Logo existe mas não é imagem válida</span></div>";
    }
} else {
    echo "<div class='box warn'><span class='warn'>⚠️ Logo não encontrado (não é crítico)</span></div>";
}

// ETAPA 6: Tentar gerar PDF
echo "<h2>6. Teste de Geração do PDF</h2>";
echo "<div class='box'>";

require_once $fpdf_path;

function safeConvert($text) {
    if ($text === null || $text === '') return '';
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    if ($converted !== false) return $converted;
    $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
    if ($converted !== false) return $converted;
    return utf8_decode($text);
}

try {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Teste', 0, 1);
    
    // Testar com o logo
    if (file_exists($logo_path)) {
        try {
            $pdf->Image($logo_path, 10, 30, 30);
            echo "<span class='ok'>✅ Logo inserido no PDF com sucesso</span><br>";
        } catch (Exception $e) {
            echo "<span class='erro'>❌ Erro ao inserir logo: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    }
    
    // Testar cada foto
    foreach ($fotos as $i => $foto) {
        $foto_path = __DIR__ . '/../' . $foto['caminho_foto'];
        $num = $i + 1;
        
        if (!file_exists($foto_path)) {
            echo "<span class='warn'>⚠️ Foto {$num}: arquivo não existe, pulando</span><br>";
            continue;
        }
        
        try {
            $pdf->AddPage();
            $pdf->Image($foto_path, 10, 30, 80);
            echo "<span class='ok'>✅ Foto {$num}: inserida com sucesso</span><br>";
        } catch (Exception $e) {
            echo "<span class='erro'>❌ Foto {$num}: ERRO - " . htmlspecialchars($e->getMessage()) . "</span><br>";
            echo "<strong>⚡ Esta foto está causando o PDF zerado!</strong><br>";
        }
    }
    
    // Testar output
    $pdfContent = $pdf->Output('S');
    $pdfSize = strlen($pdfContent);
    echo "<br><span class='ok'>✅ PDF gerado com sucesso! Tamanho: " . number_format($pdfSize / 1024, 1) . " KB</span><br>";
    
} catch (Exception $e) {
    echo "<span class='erro'>❌ ERRO FATAL na geração: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    echo "<strong>⚡ Este é o erro que causa o PDF zerado!</strong><br>";
}

echo "</div>";

// ETAPA 7: Verificar configuração PHP
echo "<h2>7. Configuração PHP</h2>";
echo "<div class='box'>";
echo "PHP Version: " . phpversion() . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "GD: " . (function_exists('imagecreatefromjpeg') ? '<span class="ok">✅ Disponível</span>' : '<span class="warn">⚠️ Não disponível</span>') . "<br>";
echo "iconv: " . (function_exists('iconv') ? '<span class="ok">✅ Disponível</span>' : '<span class="warn">⚠️ Não disponível</span>') . "<br>";
echo "output_buffering: " . ini_get('output_buffering') . "<br>";
echo "</div>";

$conn->close();
echo "<br><p><em>Após identificar o problema, remova este arquivo do servidor.</em></p>";
echo "</body></html>";
?>
