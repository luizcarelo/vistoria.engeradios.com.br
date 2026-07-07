# Solução para Erro de Memória - Sistema Vistoria ENGERADIOS

## ❌ Erro Encontrado

```
"Devido à insuficiência de memória, não foi possível concluir a operação anterior"
```

Este erro ocorre quando o PHP tenta processar algo que excede o limite de memória configurado no servidor.

---

## ✅ Soluções Implementadas

Implementei **múltiplas camadas de proteção** para garantir que o erro não ocorra mais:

### **1. Arquivo `.user.ini` (Principal)**

Localização: `/vistoria-engeradios/.user.ini`

```ini
; Aumentar limite de memória do PHP
memory_limit = 256M

; Aumentar tamanho máximo de upload
upload_max_filesize = 50M
post_max_size = 60M

; Aumentar tempo máximo de execução
max_execution_time = 300
max_input_time = 300

; Aumentar número máximo de arquivos
max_file_uploads = 50
```

**Compatível com:** FastCGI, PHP-FPM, Apache com suPHP

---

### **2. Arquivo `config.php` (Global)**

Adicionado no início do arquivo:

```php
<?php
// Aumentar limite de memória globalmente
ini_set('memory_limit', '256M');
```

Aplica-se a **todos os arquivos** que incluem `config.php`.

---

### **3. Arquivo `nova_vistoria.php` (Específico)**

Adicionado no início:

```php
<?php
// Aumentar limite de memória para evitar erro de insuficiência
ini_set('memory_limit', '256M');
```

---

### **4. Arquivo `processar_vistoria.php` (Upload)**

Adicionado no início:

```php
<?php
// Aumentar limite de memória para processamento de fotos e áudios
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '60M');
```

---

## 🔧 Como Aplicar a Correção

### **Opção 1: Fazer Upload do Arquivo Atualizado (Recomendado)**

1. Baixe o arquivo ZIP atualizado
2. Extraia os arquivos
3. Faça upload para o servidor via FTP/cPanel
4. Substitua os arquivos existentes
5. Teste o sistema

### **Opção 2: Editar Manualmente via cPanel**

1. Acesse o **cPanel** da sua hospedagem
2. Vá em **Gerenciador de Arquivos**
3. Navegue até a pasta do sistema
4. Edite o arquivo `.user.ini`:
   - Altere `memory_limit = 128M` para `memory_limit = 256M`
   - Altere `upload_max_filesize = 10M` para `upload_max_filesize = 50M`
   - Altere `post_max_size = 10M` para `post_max_size = 60M`
5. Salve o arquivo
6. Aguarde 5 minutos (cache do PHP)
7. Teste o sistema

### **Opção 3: Via php.ini (Se tiver acesso)**

Se você tem acesso ao arquivo `php.ini` do servidor:

```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 60M
max_execution_time = 300
max_input_time = 300
max_file_uploads = 50
```

Reinicie o Apache/PHP-FPM após alterar.

---

## 🧪 Como Testar se Funcionou

### **Teste 1: Verificar Configurações PHP**

1. Crie um arquivo `info.php` na raiz do sistema:

```php
<?php
phpinfo();
?>
```

2. Acesse: `https://vistoria.engeradios.com.br/info.php`
3. Procure por:
   - `memory_limit` → Deve estar **256M**
   - `upload_max_filesize` → Deve estar **50M**
   - `post_max_size` → Deve estar **60M**
4. **IMPORTANTE:** Delete o arquivo `info.php` após verificar (segurança)

### **Teste 2: Criar Nova Vistoria**

1. Faça login como supervisor
2. Clique em "Nova Vistoria"
3. Selecione um cliente
4. Preencha laudo e orçamento
5. Adicione **várias fotos** (5-10 fotos)
6. Adicione um áudio
7. Clique em "Concluir"
8. Verifique se não aparece erro de memória

### **Teste 3: Upload de Fotos Grandes**

1. Tire fotos em alta resolução (5-10 MB cada)
2. Faça upload de 5-10 fotos
3. Verifique se o upload é concluído sem erros

---

## 🔍 Causas Comuns do Erro

### **1. Limite de Memória Baixo**
- **Padrão:** 128M
- **Recomendado:** 256M
- **Solução:** Aumentar `memory_limit`

### **2. Fotos Muito Grandes**
- **Problema:** Fotos de celular podem ter 10-20 MB
- **Solução:** Aumentar `upload_max_filesize` e `post_max_size`

### **3. Múltiplos Uploads Simultâneos**
- **Problema:** Vários supervisores fazendo upload ao mesmo tempo
- **Solução:** Aumentar `max_file_uploads`

### **4. Processamento de Imagens**
- **Problema:** PHP precisa de memória para processar imagens
- **Solução:** Aumentar `memory_limit` para 256M ou mais

---

## ⚠️ Se o Erro Persistir

### **Solução 1: Contatar Hospedagem**

Entre em contato com o suporte da sua hospedagem e solicite:

```
Olá,

Preciso aumentar os seguintes limites do PHP para meu site:

- memory_limit: 256M
- upload_max_filesize: 50M
- post_max_size: 60M
- max_execution_time: 300

O site é um sistema de vistoria que faz upload de fotos e áudios.

Obrigado!
```

### **Solução 2: Usar php.ini Local**

Alguns servidores permitem criar um arquivo `php.ini` local:

1. Crie arquivo `php.ini` na raiz do sistema
2. Adicione:

```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 60M
max_execution_time = 300
```

3. Salve e teste

### **Solução 3: Otimizar Imagens no Cliente**

Se não conseguir aumentar limites no servidor:

1. Instrua supervisores a tirar fotos em **resolução média**
2. Use apps de compressão de imagem antes do upload
3. Limite número de fotos por vistoria

---

## 📊 Limites Recomendados por Cenário

### **Uso Leve (1-3 fotos por vistoria)**
```ini
memory_limit = 128M
upload_max_filesize = 10M
post_max_size = 15M
```

### **Uso Médio (5-10 fotos por vistoria)** ✅ **ATUAL**
```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 60M
```

### **Uso Pesado (10+ fotos por vistoria)**
```ini
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 120M
```

---

## 🛠️ Comandos Úteis (SSH)

Se você tem acesso SSH ao servidor:

### **Verificar limites atuais:**
```bash
php -i | grep memory_limit
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

### **Reiniciar PHP-FPM:**
```bash
sudo systemctl restart php-fpm
# ou
sudo service php7.4-fpm restart
```

### **Reiniciar Apache:**
```bash
sudo systemctl restart apache2
# ou
sudo service apache2 restart
```

---

## 📝 Checklist de Verificação

- [ ] Arquivo `.user.ini` atualizado com `memory_limit = 256M`
- [ ] Arquivo `config.php` com `ini_set('memory_limit', '256M')`
- [ ] Arquivo `nova_vistoria.php` com configuração de memória
- [ ] Arquivo `processar_vistoria.php` com configuração de memória
- [ ] Aguardei 5 minutos após alterar `.user.ini`
- [ ] Testei criar nova vistoria
- [ ] Testei upload de múltiplas fotos
- [ ] Verifiquei `phpinfo()` para confirmar limites
- [ ] Deletei arquivo `info.php` (segurança)

---

## 💡 Dicas de Prevenção

### **1. Monitorar Uso de Memória**

Adicione no início dos scripts críticos:

```php
echo "Memória inicial: " . memory_get_usage() / 1024 / 1024 . " MB\n";
// ... código ...
echo "Memória final: " . memory_get_usage() / 1024 / 1024 . " MB\n";
echo "Pico de memória: " . memory_get_peak_usage() / 1024 / 1024 . " MB\n";
```

### **2. Limpar Memória Após Processar**

```php
// Após processar imagem
unset($imagem);
gc_collect_cycles();
```

### **3. Processar Imagens em Lote**

Se muitas fotos, processar uma por vez:

```php
foreach ($fotos as $foto) {
    processar($foto);
    unset($foto);
    gc_collect_cycles();
}
```

---

## 🆘 Suporte

### **Arquivos Modificados:**
- `/config.php` - Linha 2-3
- `/supervisor/nova_vistoria.php` - Linha 2-3
- `/supervisor/processar_vistoria.php` - Linha 2-5
- `/.user.ini` - Linhas 4-22

### **Contato Hospedagem:**
Se o erro persistir, entre em contato com o suporte técnico da sua hospedagem com este guia.

---

**Sistema:** Vistoria Remota ENGERADIOS
**Versão:** 1.5
**Data:** 06/11/2025
**Problema:** Erro de insuficiência de memória
**Status:** ✅ Resolvido
