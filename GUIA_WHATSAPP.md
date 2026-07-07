# Guia de Configuração WhatsApp - Sistema Vistoria ENGERADIOS

## 📱 Funcionalidade Implementada

Sistema de **envio automático** do relatório de vistoria em PDF para um grupo do WhatsApp assim que a vistoria for concluída.

### **O que é enviado:**
- ✅ **Mensagem** com informações da vistoria (Cliente, Supervisor, Data, Horário)
- ✅ **PDF** com relatório completo da vistoria
- ✅ **Automático** - Enviado assim que o supervisor concluir a vistoria

---

## 🎯 Mensagem Enviada

Quando uma vistoria é concluída, o grupo recebe:

```
🔔 *Nova Vistoria Concluída*

📋 *Vistoria:* #000123
🏢 *Cliente:* Hospital ABC
👤 *Supervisor:* João Silva
📅 *Data:* 14/11/2025
🕐 *Horário:* 15:30

📄 Relatório em PDF anexado.

_Sistema Vistoria Remota ENGERADIOS_
```

+ **Arquivo PDF** anexado

---

## 🚀 Como Configurar

### **Opção 1: Evolution API (Recomendada - Gratuita)**

A Evolution API é uma solução open-source brasileira, gratuita e fácil de usar.

#### **Passo 1: Instalar Evolution API**

**Opção A - Servidor Próprio:**
```bash
# Via Docker (recomendado)
docker run -d \
  --name evolution-api \
  -p 8080:8080 \
  -e AUTHENTICATION_API_KEY=SUA_CHAVE_SECRETA \
  atendai/evolution-api:latest
```

**Opção B - Serviço Hospedado:**
- Use serviços como [EvolutionAPI.app](https://evolutionapi.app)
- Ou contrate hospedagem que oferece Evolution API

#### **Passo 2: Criar Instância**

1. Acesse a Evolution API: `http://seu-servidor:8080`
2. Crie uma nova instância:
```bash
curl -X POST http://seu-servidor:8080/instance/create \
  -H "apikey: SUA_CHAVE_SECRETA" \
  -H "Content-Type: application/json" \
  -d '{
    "instanceName": "vistoria-engeradios",
    "qrcode": true
  }'
```

3. Escaneie o QR Code com o WhatsApp

#### **Passo 3: Obter ID do Grupo**

```bash
curl -X GET http://seu-servidor:8080/group/findGroupInfos/vistoria-engeradios \
  -H "apikey: SUA_CHAVE_SECRETA"
```

Procure pelo nome do grupo e copie o `id` (formato: `120363XXXXXXXXXX@g.us`)

#### **Passo 4: Configurar no Sistema**

Edite o arquivo `config_whatsapp.php`:

```php
// Ativar WhatsApp
define('WHATSAPP_ENABLED', true);

// Tipo de API
define('WHATSAPP_API_TYPE', 'evolution');

// Configurações Evolution
define('EVOLUTION_API_URL', 'http://seu-servidor:8080');
define('EVOLUTION_INSTANCE_NAME', 'vistoria-engeradios');
define('EVOLUTION_API_KEY', 'SUA_CHAVE_SECRETA');

// ID do Grupo
define('WHATSAPP_GROUP_ID', '120363XXXXXXXXXX@g.us');
define('WHATSAPP_GROUP_NAME', 'Supervisores ENGERADIOS');
```

---

### **Opção 2: Twilio (Paga - Oficial)**

Twilio é a solução oficial do WhatsApp Business, mas é paga.

#### **Passo 1: Criar Conta Twilio**

1. Acesse [twilio.com](https://www.twilio.com)
2. Crie uma conta
3. Ative WhatsApp Business API

#### **Passo 2: Obter Credenciais**

1. Account SID
2. Auth Token
3. Número do WhatsApp Business

#### **Passo 3: Configurar no Sistema**

Edite `config_whatsapp.php`:

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'twilio');

define('TWILIO_ACCOUNT_SID', 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('TWILIO_AUTH_TOKEN', 'seu_auth_token');
define('TWILIO_WHATSAPP_NUMBER', '+5511999999999');

define('WHATSAPP_GROUP_ID', '+5511988888888'); // Número do grupo
```

---

### **Opção 3: Baileys/WPPConnect (Gratuita)**

Alternativa gratuita que requer servidor Node.js.

#### **Passo 1: Instalar WPPConnect**

```bash
npm install -g @wppconnect-team/wppconnect-server
wppconnect-server
```

#### **Passo 2: Escanear QR Code**

Acesse `http://localhost:3000` e escaneie o QR Code

#### **Passo 3: Configurar no Sistema**

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'wppconnect');

define('BAILEYS_API_URL', 'http://localhost:3000');
define('BAILEYS_API_TOKEN', 'SEU_TOKEN');

define('WHATSAPP_GROUP_ID', '120363XXXXXXXXXX@g.us');
```

---

## 🔧 Arquivo de Configuração

### **Localização:**
`/config_whatsapp.php`

### **Configurações Principais:**

```php
// Ativar/Desativar
define('WHATSAPP_ENABLED', false); // true para ativar

// Tipo de API
define('WHATSAPP_API_TYPE', 'evolution'); // evolution, twilio, baileys

// ID do Grupo
define('WHATSAPP_GROUP_ID', '120363XXXXXXXXXX@g.us');

// Mensagem personalizada
define('WHATSAPP_MESSAGE_TEMPLATE', 
"🔔 *Nova Vistoria Concluída*
...
");
```

### **Personalizar Mensagem:**

Variáveis disponíveis:
- `{cliente}` - Nome do cliente
- `{supervisor}` - Nome do supervisor
- `{data}` - Data da vistoria (dd/mm/aaaa)
- `{hora}` - Horário da vistoria (HH:MM)
- `{numero_vistoria}` - Número da vistoria

---

## 📝 Como Funciona

### **Fluxo Automático:**

1. Supervisor preenche vistoria no app
2. Clica em "Concluir"
3. Sistema:
   - Salva vistoria no banco de dados
   - Envia e-mail para operacional@engeradios.com.br
   - **Gera PDF automaticamente**
   - **Envia PDF + mensagem para grupo do WhatsApp**
4. Supervisores recebem notificação no grupo

### **PDF Gerado:**

O PDF enviado contém:
- Informações gerais (Cliente, Supervisor, Data/Hora)
- Laudo completo
- Orçamento de adequação

---

## 🧪 Como Testar

### **Teste 1: Verificar Configuração**

Crie arquivo `teste_whatsapp.php`:

```php
<?php
require_once 'config_whatsapp.php';

$validation = whatsappValidateConfig();

if ($validation['valid']) {
    echo "✅ Configurações OK\n";
} else {
    echo "❌ Erro: " . $validation['message'] . "\n";
}
?>
```

Execute:
```bash
php teste_whatsapp.php
```

### **Teste 2: Testar Conexão**

```php
<?php
require_once 'lib/whatsapp.php';

$result = WhatsAppSender::testConnection();

if ($result['success']) {
    echo "✅ Conexão OK\n";
} else {
    echo "❌ Erro: " . $result['message'] . "\n";
}
?>
```

### **Teste 3: Enviar Vistoria de Teste**

1. Faça login como supervisor
2. Crie uma vistoria de teste
3. Preencha os campos
4. Clique em "Concluir"
5. Verifique se chegou no grupo do WhatsApp

---

## 📊 Logs

### **Habilitar Logs:**

Em `config_whatsapp.php`:

```php
define('WHATSAPP_LOG_ENABLED', true);
define('WHATSAPP_LOG_FILE', __DIR__ . '/logs/whatsapp.log');
```

### **Visualizar Logs:**

```bash
tail -f logs/whatsapp.log
```

### **Exemplo de Log:**

```
[2025-11-14 15:30:45] [INFO] Iniciando envio para grupo: Supervisores ENGERADIOS
[2025-11-14 15:30:45] [INFO] Cliente: Hospital ABC, Supervisor: João Silva
[2025-11-14 15:30:47] [SUCCESS] PDF enviado com sucesso via Evolution API
```

---

## ⚠️ Solução de Problemas

### **Problema: "WhatsApp desativado"**

**Solução:**
```php
define('WHATSAPP_ENABLED', true); // Alterar para true
```

### **Problema: "Configurações Evolution API incompletas"**

**Solução:**
Verifique se preencheu:
- `EVOLUTION_API_URL`
- `EVOLUTION_API_KEY`
- `WHATSAPP_GROUP_ID`

### **Problema: "Erro de conexão"**

**Solução:**
1. Verifique se a Evolution API está rodando
2. Teste: `curl http://seu-servidor:8080/instance/connectionState/vistoria-engeradios`
3. Verifique firewall/porta

### **Problema: "Arquivo PDF não encontrado"**

**Solução:**
1. Verifique permissões da pasta `uploads/temp/`
2. Execute: `chmod 755 uploads/temp/`

### **Problema: "ID do grupo inválido"**

**Solução:**
1. Liste grupos: `curl http://seu-servidor:8080/group/findGroupInfos/vistoria-engeradios`
2. Copie o ID correto (formato: `120363XXXXXXXXXX@g.us`)

### **Problema: "QR Code expirado"**

**Solução:**
1. Gere novo QR Code
2. Escaneie novamente com WhatsApp

---

## 🔐 Segurança

### **Boas Práticas:**

1. **Proteja as credenciais:**
```php
// Nunca commite config_whatsapp.php no Git
// Adicione ao .gitignore:
config_whatsapp.php
```

2. **Use HTTPS:**
```php
define('EVOLUTION_API_URL', 'https://evolution.seudominio.com.br');
```

3. **Restrinja acesso:**
```apache
# .htaccess
<Files "config_whatsapp.php">
    Order Allow,Deny
    Deny from all
</Files>
```

4. **API Key forte:**
```php
define('EVOLUTION_API_KEY', 'chave-longa-e-complexa-aqui');
```

---

## 💡 Dicas

### **1. Grupo Separado**

Crie um grupo específico para vistorias:
- Nome: "Vistorias ENGERADIOS"
- Membros: Apenas supervisores + gerente
- Silenciar notificações individuais (opcional)

### **2. Mensagem Personalizada**

Customize a mensagem em `config_whatsapp.php`:

```php
define('WHATSAPP_MESSAGE_TEMPLATE', 
"🚨 *VISTORIA CONCLUÍDA*

Cliente: {cliente}
Supervisor: {supervisor}
Data: {data} às {hora}

Vistoria #{numero_vistoria}

Relatório completo em anexo.");
```

### **3. Horário de Envio**

Se quiser evitar envios fora do horário comercial:

```php
// Adicionar em enviarWhatsAppVistoria()
$hora_atual = (int)date('H');
if ($hora_atual < 8 || $hora_atual > 18) {
    return false; // Não enviar fora do horário
}
```

### **4. Notificação Sonora**

Use emojis para chamar atenção:
- 🔔 🚨 ⚠️ 📢 🔴

---

## 📱 Recursos das APIs

### **Evolution API:**
- ✅ Gratuita e open-source
- ✅ Fácil de instalar
- ✅ Suporta grupos
- ✅ Envia arquivos
- ✅ Multi-instância
- ✅ Documentação em português

### **Twilio:**
- ✅ Oficial do WhatsApp
- ✅ Confiável
- ✅ Suporte 24/7
- ❌ Paga (US$ 0.005/msg)
- ❌ Requer aprovação

### **Baileys/WPPConnect:**
- ✅ Gratuita
- ✅ Open-source
- ❌ Requer servidor Node.js
- ❌ Menos estável

---

## 🆘 Suporte

### **Evolution API:**
- Documentação: https://doc.evolution-api.com
- GitHub: https://github.com/EvolutionAPI/evolution-api
- Comunidade: Discord oficial

### **Twilio:**
- Documentação: https://www.twilio.com/docs/whatsapp
- Suporte: https://support.twilio.com

### **WPPConnect:**
- Documentação: https://wppconnect.io
- GitHub: https://github.com/wppconnect-team

---

## 📦 Arquivos do Sistema

```
vistoria-engeradios/
├── config_whatsapp.php         (Configurações)
├── lib/
│   └── whatsapp.php            (Biblioteca de envio)
├── supervisor/
│   └── processar_vistoria.php  (Integração)
├── uploads/
│   └── temp/                   (PDFs temporários)
└── logs/
    └── whatsapp.log            (Logs de envio)
```

---

## ✅ Checklist de Configuração

- [ ] Evolution API instalada e rodando
- [ ] QR Code escaneado
- [ ] Grupo criado no WhatsApp
- [ ] ID do grupo obtido
- [ ] `config_whatsapp.php` configurado
- [ ] `WHATSAPP_ENABLED` = true
- [ ] Teste de conexão OK
- [ ] Vistoria de teste enviada
- [ ] PDF recebido no grupo
- [ ] Logs funcionando

---

## 🎯 Resumo

| Item | Descrição |
|------|-----------|
| **Quando envia** | Ao concluir vistoria |
| **Para onde** | Grupo do WhatsApp |
| **O que envia** | Mensagem + PDF |
| **Automático** | Sim |
| **Custo** | Gratuito (Evolution) |
| **Configuração** | 10-15 minutos |

---

**Sistema:** Vistoria Remota ENGERADIOS
**Versão:** 1.8
**Data:** 14/11/2025
**Funcionalidade:** Envio automático para WhatsApp
**Status:** ✅ Implementado
