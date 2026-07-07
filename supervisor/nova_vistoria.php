<?php
// Aumentar limite de memória para evitar erro de insuficiência
ini_set('memory_limit', '256M');

require_once '../config.php';
verificarLogin();

if ($_SESSION['usuario_tipo'] != 'supervisor') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$conn = getDBConnection();

// Buscar clientes ativos
$clientes = $conn->query("SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome ASC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Vistoria - ENGERADIOS</title>
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
        
        .btn-voltar {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-voltar:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c41e3a;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #c41e3a;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .upload-section {
            margin-bottom: 20px;
        }
        
        .upload-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .upload-btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .upload-btn:hover {
            background: #0056b3;
        }
        
        .upload-btn input[type="file"] {
            display: none;
        }
        
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .preview-item {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            z-index: 10;
        }
        
        .preview-item .legenda-input {
            width: 100%;
            padding: 8px;
            border: none;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            background: white;
        }
        
        .preview-item .legenda-input:focus {
            outline: none;
            border-top-color: #c41e3a;
        }
        
        .audio-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .audio-preview audio {
            width: 100%;
            margin-top: 10px;
        }
        
        .audio-preview .remove-audio {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-submit {
            width: 100%;
            background: #28a745;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-submit:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .recording-indicator {
            display: none;
            background: #dc3545;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-top: 10px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px auto;
            }
            
            .card {
                padding: 20px 15px;
            }
            
            .preview-container {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            
            .preview-item img {
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../Logooriginal.png" alt="ENGERADIOS">
            <h1>Nova Vistoria</h1>
        </div>
        <a href="dashboard.php" class="btn-voltar">← Voltar</a>
    </div>
    
    <div class="container">
        <form id="formVistoria" method="POST" action="processar_vistoria.php" enctype="multipart/form-data">
            <div class="card">
                <h2>Informações da Vistoria</h2>
                
                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Selecione o cliente</option>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $cliente['id']; ?>">
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="laudo">Laudo da Vistoria *</label>
                    <textarea id="laudo" name="laudo" required placeholder="Descreva detalhadamente as observações encontradas no local..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="orcamento_adequacao">Orçamento de Adequação *</label>
                    <textarea id="orcamento_adequacao" name="orcamento_adequacao" required placeholder="Liste os materiais e serviços necessários para adequação..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="email_adicional">E-mail Adicional (Opcional)</label>
                    <input type="email" id="email_adicional" name="email_adicional" placeholder="Digite um e-mail adicional para receber o relatório">
                    <small style="color: #666; font-size: 13px; margin-top: 5px; display: block;">💡 O relatório será enviado automaticamente para operacional@engeradios.com.br e para o e-mail adicional informado acima (se preenchido).</small>
                </div>
            </div>
            
            <div class="card">
                <div class="upload-section">
                    <h3>📷 Fotos da Vistoria</h3>
                    <label class="upload-btn">
                        Adicionar Fotos
                        <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple>
                    </label>
                    <div id="fotoCounter" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
                    <div id="fotoLimiteAlerta" class="alert error" style="display: none; margin-top: 10px;"></div>
                    <div class="alert info" style="margin-top: 10px;">
                        💡 Limite máximo: <strong>60 fotos</strong> por vistoria. Você pode selecionar da câmera ou galeria.
                    </div>
                    <div id="previewFotos" class="preview-container"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="upload-section">
                    <h3>🎤 Áudio da Vistoria (máx. 3 minutos)</h3>
                    <label class="upload-btn">
                        Adicionar Áudio
                        <input type="file" id="audio" name="audio" accept="audio/*">
                    </label>
                    <div class="alert info">
                        💡 Dica: Você pode gravar um áudio diretamente pelo seu celular ou fazer upload de um arquivo existente.
                    </div>
                    <div id="previewAudio"></div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit" id="btnSubmit">
                ✓ Concluir e Enviar Vistoria
            </button>
        </form>
    </div>
    
    <script>
        // Preview de fotos
        const inputFotos = document.getElementById('fotos');
        const previewFotos = document.getElementById('previewFotos');
        let fotosArray = [];
        
        const MAX_FOTOS = 60;
        const fotoCounter = document.getElementById('fotoCounter');
        const fotoLimiteAlerta = document.getElementById('fotoLimiteAlerta');
        
        function atualizarContadorFotos() {
            const count = fotosArray.length;
            fotoCounter.textContent = `📸 ${count} de ${MAX_FOTOS} fotos selecionadas`;
            fotoCounter.style.color = count >= MAX_FOTOS ? '#dc3545' : count >= 50 ? '#ff9800' : '#666';
            
            if (count >= MAX_FOTOS) {
                fotoLimiteAlerta.style.display = 'block';
                fotoLimiteAlerta.textContent = `⚠️ Limite máximo de ${MAX_FOTOS} fotos atingido! Remova fotos existentes para adicionar novas.`;
            } else if (count >= 50) {
                fotoLimiteAlerta.style.display = 'block';
                fotoLimiteAlerta.style.background = '#fff3cd';
                fotoLimiteAlerta.style.color = '#856404';
                fotoLimiteAlerta.style.borderLeftColor = '#ff9800';
                fotoLimiteAlerta.textContent = `⚠️ Atenção: ${count} fotos selecionadas. O limite é ${MAX_FOTOS}. Muitas fotos podem tornar o envio mais lento.`;
            } else {
                fotoLimiteAlerta.style.display = 'none';
            }
        }
        
        inputFotos.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            
            // Verificar limite
            const espacoDisponivel = MAX_FOTOS - fotosArray.length;
            if (espacoDisponivel <= 0) {
                alert(`Limite de ${MAX_FOTOS} fotos atingido! Remova algumas fotos antes de adicionar novas.`);
                e.target.value = '';
                return;
            }
            
            if (files.length > espacoDisponivel) {
                alert(`Você pode adicionar no máximo mais ${espacoDisponivel} foto(s). Foram selecionadas ${files.length}. Apenas as primeiras ${espacoDisponivel} serão adicionadas.`);
            }
            
            const filesToAdd = files.slice(0, espacoDisponivel);
            
            filesToAdd.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const fotoObj = { file: file, legenda: '' };
                    fotosArray.push(fotoObj);
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-btn" onclick="removerFoto(${fotosArray.length - 1})">×</button>
                            <input type="text" class="legenda-input" placeholder="Legenda da foto (opcional)" 
                                   onchange="atualizarLegenda(${fotosArray.length - 1}, this.value)">
                        `;
                        previewFotos.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            atualizarContadorFotos();
            
            // Limpar input para permitir adicionar mais fotos
            e.target.value = '';
        });
        
        function removerFoto(index) {
            fotosArray.splice(index, 1);
            atualizarPreviewFotos();
            atualizarContadorFotos();
        }
        
        function atualizarLegenda(index, legenda) {
            if (fotosArray[index]) {
                fotosArray[index].legenda = legenda;
            }
        }
        
        function atualizarPreviewFotos() {
            previewFotos.innerHTML = '';
            fotosArray.forEach((fotoObj, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-btn" onclick="removerFoto(${index})">×</button>
                        <input type="text" class="legenda-input" placeholder="Legenda da foto (opcional)" 
                               value="${fotoObj.legenda || ''}" 
                               onchange="atualizarLegenda(${index}, this.value)">
                    `;
                    previewFotos.appendChild(div);
                };
                reader.readAsDataURL(fotoObj.file);
            });
        }
        
        // Preview de áudio
        const inputAudio = document.getElementById('audio');
        const previewAudio = document.getElementById('previewAudio');
        let audioFile = null;
        
        inputAudio.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('audio/')) {
                audioFile = file;
                
                const url = URL.createObjectURL(file);
                previewAudio.innerHTML = `
                    <div class="audio-preview">
                        <strong>Áudio selecionado:</strong> ${file.name}
                        <audio controls src="${url}"></audio>
                        <button type="button" class="remove-audio" onclick="removerAudio()">Remover Áudio</button>
                    </div>
                `;
            }
        });
        
        function removerAudio() {
            audioFile = null;
            inputAudio.value = '';
            previewAudio.innerHTML = '';
        }
        
        // Submissão do formulário
        document.getElementById('formVistoria').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Remover fotos antigas e adicionar as novas com legendas
            formData.delete('fotos[]');
            formData.delete('legendas[]');
            fotosArray.forEach(fotoObj => {
                formData.append('fotos[]', fotoObj.file);
                formData.append('legendas[]', fotoObj.legenda || '');
            });
            
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Enviando...';
            
            fetch('processar_vistoria.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vistoria concluída e enviada com sucesso!');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Erro: ' + data.message);
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = '✓ Concluir e Enviar Vistoria';
                }
            })
            .catch(error => {
                alert('Erro ao enviar vistoria. Tente novamente.');
                btnSubmit.disabled = false;
                btnSubmit.textContent = '✓ Concluir e Enviar Vistoria';
            });
        });
    </script>
</body>
</html>
