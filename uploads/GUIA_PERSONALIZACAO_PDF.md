# Guia de Personalização do PDF - Sistema Vistoria ENGERADIOS

## 📄 Arquivo: `/admin/gerar_pdf.php`

Este guia explica como personalizar o layout e aparência dos relatórios PDF gerados pelo sistema.

---

## 🎨 Estrutura do Código

### 1. **Classe PDF Personalizada**

```php
class PDF extends FPDF
{
    // Métodos personalizados
}
```

A classe `PDF` estende a classe `FPDF` e adiciona métodos personalizados para o relatório.

---

## 🔧 Personalizações Disponíveis

### 📌 **1. Cabeçalho do PDF**

**Localização:** Método `Header()` (linhas 66-90)

```php
function Header()
{
    // Logo (se existir)
    $logo_path = __DIR__ . '/../Logooriginal.png';
    if (file_exists($logo_path)) {
        $this->Image($logo_path, 10, 6, 40);  // X, Y, Largura
    }
    
    // Título
    $this->SetFont('Arial', 'B', 20);  // Fonte, Estilo, Tamanho
    $this->SetTextColor(196, 30, 58);  // RGB - Vermelho ENGERADIOS
    $this->Cell(0, 10, 'VISTORIA REMOTA ENGERADIOS', 0, 1, 'C');
    
    // Subtítulo
    $this->SetFont('Arial', '', 12);
    $this->SetTextColor(100, 100, 100);  // RGB - Cinza
    $this->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1', 'Relatório de Vistoria Técnica'), 0, 1, 'C');
    
    // Linha separadora
    $this->SetDrawColor(196, 30, 58);  // Cor da linha
    $this->SetLineWidth(0.5);  // Espessura
    $this->Line(10, 30, 200, 30);  // X1, Y1, X2, Y2
}
```

**Como personalizar:**
- **Logo:** Altere `10, 6, 40` para mudar posição (X, Y) e tamanho
- **Título:** Altere o texto `'VISTORIA REMOTA ENGERADIOS'`
- **Cores:** Altere os valores RGB em `SetTextColor()` e `SetDrawColor()`
- **Fonte:** Altere `'Arial', 'B', 20` (fonte, estilo, tamanho)

---

### 📌 **2. Rodapé do PDF**

**Localização:** Método `Footer()` (linhas 93-109)

```php
function Footer()
{
    $this->SetY(-25);  // Posição do rodapé (25mm do fim)
    $this->SetDrawColor(200, 200, 200);  // Linha cinza clara
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    
    $this->Ln(2);
    $this->SetFont('Arial', 'B', 9);
    $this->SetTextColor(100, 100, 100);
    $this->Cell(0, 5, 'ENGERADIOS - Segurança Eletrônica', 0, 1, 'C');
    
    $this->SetFont('Arial', '', 8);
    $this->Cell(0, 4, 'Relatório gerado em ' . date('d/m/Y às H:i'), 0, 1, 'C');
    
    $this->SetFont('Arial', 'I', 7);  // Itálico
    $this->Cell(0, 3, 'Página ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
}
```

**Como personalizar:**
- **Posição:** Altere `-25` para mudar distância do fim da página
- **Texto:** Altere `'ENGERADIOS - Segurança Eletrônica'`
- **Tamanhos de fonte:** Altere `9`, `8`, `7`

---

### 📌 **3. Seções (Títulos)**

**Localização:** Método `Section()` (linhas 112-122)

```php
function Section($title)
{
    $this->SetFont('Arial', 'B', 12);  // Negrito, tamanho 12
    $this->SetTextColor(196, 30, 58);  // Vermelho ENGERADIOS
    $this->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1', $title), 0, 1);
    
    $this->SetDrawColor(196, 30, 58);  // Linha vermelha
    $this->SetLineWidth(0.3);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    $this->Ln(5);  // Espaço após a linha
}
```

**Como personalizar:**
- **Tamanho do título:** Altere `12`
- **Cor:** Altere RGB em `SetTextColor()` e `SetDrawColor()`
- **Espessura da linha:** Altere `0.3`
- **Espaçamento:** Altere `Ln(5)`

---

### 📌 **4. Campos de Informação**

**Localização:** Método `InfoField()` (linhas 125-134)

```php
function InfoField($label, $value)
{
    $this->SetFont('Arial', 'B', 10);  // Label em negrito
    $this->SetTextColor(100, 100, 100);  // Cinza
    $this->Cell(50, 6, iconv('UTF-8', 'ISO-8859-1', $label . ':'), 0, 0);
    
    $this->SetFont('Arial', '', 10);  // Valor em fonte normal
    $this->SetTextColor(50, 50, 50);  // Cinza escuro
    $this->MultiCell(0, 6, iconv('UTF-8', 'ISO-8859-1', $value));
}
```

**Como personalizar:**
- **Largura do label:** Altere `50` (em mm)
- **Tamanho da fonte:** Altere `10`
- **Cores:** Altere RGB em `SetTextColor()`

---

### 📌 **5. Caixa de Conteúdo (Laudo/Orçamento)**

**Localização:** Método `ContentBox()` (linhas 137-157)

```php
function ContentBox($text)
{
    $this->SetFillColor(249, 249, 249);  // Fundo cinza claro
    $this->SetDrawColor(196, 30, 58);  // Borda vermelha
    $this->SetLineWidth(0.5);
    
    $x = $this->GetX();
    $y = $this->GetY();
    
    $this->SetFont('Arial', '', 10);
    $this->SetTextColor(50, 50, 50);
    
    // Texto com fundo
    $this->MultiCell(0, 6, iconv('UTF-8', 'ISO-8859-1', $text), 0, 'L', true);
    
    // Borda esquerda vermelha
    $this->SetDrawColor(196, 30, 58);
    $this->SetLineWidth(1);  // Espessura da borda
    $height = $this->GetY() - $y;
    $this->Line($x, $y, $x, $y + $height);
}
```

**Como personalizar:**
- **Cor de fundo:** Altere RGB em `SetFillColor()`
- **Cor da borda:** Altere RGB em `SetDrawColor()`
- **Espessura da borda:** Altere `1`
- **Tamanho da fonte:** Altere `10`

---

### 📌 **6. Registro Fotográfico**

**Localização:** Linhas 212-250

```php
// Adicionar fotos
if (count($fotos) > 0) {
    $pdf->AddPage();
    $pdf->Section('Registro Fotográfico');
    
    $foto_num = 1;
    $x = 10;  // Margem esquerda
    $y = $pdf->GetY();
    
    foreach ($fotos as $foto) {
        $foto_path = __DIR__ . '/../' . $foto;
        if (file_exists($foto_path)) {
            // Verificar se precisa de nova página
            if ($y > 220) {  // Limite da página
                $pdf->AddPage();
                $y = 40;
                $x = 10;
            }
            
            // Adicionar imagem
            $pdf->Image($foto_path, $x, $y, 90);  // X, Y, Largura
            
            // Legenda
            $pdf->SetXY($x, $y + 68);  // Posição da legenda
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(90, 5, 'Foto ' . $foto_num . ' - ' . basename($foto), 0, 0, 'C');
            
            // Alternar posição (esquerda/direita)
            if ($foto_num % 2 == 1) {
                $x = 110;  // Segunda coluna
            } else {
                $x = 10;   // Primeira coluna
                $y += 80;  // Próxima linha
            }
            
            $foto_num++;
        }
    }
}
```

**Como personalizar:**
- **Tamanho das fotos:** Altere `90` (largura em mm)
- **Posição das fotos:** Altere `$x = 10` e `$x = 110`
- **Espaçamento vertical:** Altere `$y += 80`
- **Fotos por linha:** Altere lógica `if ($foto_num % 2 == 1)`
  - Para 3 fotos por linha: `if ($foto_num % 3 == 1)` e ajuste posições X

---

## 🎨 Cores Padrão ENGERADIOS

```php
// Vermelho ENGERADIOS
$this->SetTextColor(196, 30, 58);   // RGB: #c41e3a
$this->SetDrawColor(196, 30, 58);

// Cinza escuro (textos)
$this->SetTextColor(50, 50, 50);    // RGB: #323232

// Cinza médio (labels)
$this->SetTextColor(100, 100, 100); // RGB: #646464

// Cinza claro (fundos)
$this->SetFillColor(249, 249, 249); // RGB: #f9f9f9
```

---

## 📐 Dimensões da Página (A4)

```php
// Margens padrão
@page {
    margin: 2cm;  // Todas as margens
}

// Área útil (A4 = 210mm x 297mm)
// Com margem de 2cm: 170mm x 257mm

// Coordenadas
$x = 10;   // Margem esquerda (10mm)
$y = 10;   // Margem superior (10mm)
$width = 190;  // Largura útil (190mm)
```

---

## 🔤 Fontes Disponíveis

```php
// Fontes padrão FPDF
'Arial'      // Recomendada (usada no sistema)
'Times'      // Serifada
'Courier'    // Monoespaçada
'Helvetica'  // Similar à Arial

// Estilos
''   // Normal
'B'  // Bold (Negrito)
'I'  // Italic (Itálico)
'BI' // Bold + Italic
```

---

## 📝 Exemplos de Personalização

### **Exemplo 1: Mudar cor principal para azul**

```php
// Substituir todas as ocorrências de:
$this->SetTextColor(196, 30, 58);   // Vermelho
$this->SetDrawColor(196, 30, 58);

// Por:
$this->SetTextColor(0, 102, 204);   // Azul
$this->SetDrawColor(0, 102, 204);
```

### **Exemplo 2: Aumentar tamanho do título**

```php
// No método Header(), alterar:
$this->SetFont('Arial', 'B', 20);  // De 20 para 24
$this->SetFont('Arial', 'B', 24);
```

### **Exemplo 3: Adicionar mais informações no rodapé**

```php
function Footer()
{
    $this->SetY(-30);  // Mais espaço
    $this->SetDrawColor(200, 200, 200);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    
    $this->Ln(2);
    $this->SetFont('Arial', 'B', 9);
    $this->SetTextColor(100, 100, 100);
    $this->Cell(0, 5, 'ENGERADIOS - Segurança Eletrônica', 0, 1, 'C');
    
    // NOVO: Adicionar telefone e site
    $this->SetFont('Arial', '', 8);
    $this->Cell(0, 4, 'Tel: (11) 1234-5678 | www.engeradios.com.br', 0, 1, 'C');
    
    $this->Cell(0, 4, 'Relatório gerado em ' . date('d/m/Y às H:i'), 0, 1, 'C');
    
    $this->SetFont('Arial', 'I', 7);
    $this->Cell(0, 3, 'Página ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
}
```

### **Exemplo 4: Colocar 3 fotos por linha**

```php
// Alterar largura das fotos
$pdf->Image($foto_path, $x, $y, 60);  // De 90 para 60

// Alterar posições
if ($foto_num % 3 == 1) {
    $x = 10;   // Primeira coluna
} elseif ($foto_num % 3 == 2) {
    $x = 75;   // Segunda coluna
} else {
    $x = 140;  // Terceira coluna
    $y += 70;  // Próxima linha após 3 fotos
}
```

---

## 🛠️ Métodos Úteis do FPDF

```php
// Posicionamento
$pdf->SetXY($x, $y);        // Define posição X e Y
$pdf->GetX();               // Obtém posição X atual
$pdf->GetY();               // Obtém posição Y atual
$pdf->Ln($height);          // Quebra de linha

// Texto
$pdf->Cell($w, $h, $txt, $border, $ln, $align);
$pdf->MultiCell($w, $h, $txt, $border, $align, $fill);
$pdf->Write($h, $txt);

// Imagens
$pdf->Image($file, $x, $y, $w, $h);

// Linhas e formas
$pdf->Line($x1, $y1, $x2, $y2);
$pdf->Rect($x, $y, $w, $h, $style);

// Páginas
$pdf->AddPage();            // Nova página
$pdf->PageNo();             // Número da página atual

// Cores
$pdf->SetTextColor($r, $g, $b);
$pdf->SetDrawColor($r, $g, $b);
$pdf->SetFillColor($r, $g, $b);

// Fontes
$pdf->SetFont($family, $style, $size);
```

---

## 📚 Documentação Completa

Para mais detalhes sobre a biblioteca FPDF, consulte:
- **Site oficial:** http://www.fpdf.org/
- **Documentação:** http://www.fpdf.org/en/doc/index.php
- **Tutoriais:** http://www.fpdf.org/en/tutorial/index.php

---

## 💡 Dicas

1. **Teste sempre:** Após fazer alterações, gere um PDF de teste
2. **Backup:** Faça backup do arquivo original antes de modificar
3. **Cores RGB:** Use valores de 0 a 255 para cada componente (R, G, B)
4. **Margens:** Respeite as margens para não cortar conteúdo na impressão
5. **Caracteres especiais:** Use `iconv('UTF-8', 'ISO-8859-1', $texto)` para acentos

---

**Desenvolvido por:** Manus AI
**Data:** 06/11/2025
**Sistema:** Vistoria Remota ENGERADIOS
