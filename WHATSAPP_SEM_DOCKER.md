# WhatsApp sem Docker - Alternativas Simples

## 🎯 Opções Disponíveis (Sem Docker)

### **Opção 1: Serviços Hospedados (Mais Fácil) ⭐**
Não precisa instalar nada, apenas configurar

### **Opção 2: Evolution API Manual**
Instalar Evolution API sem Docker (Node.js)

### **Opção 3: WPPConnect Server**
Servidor Node.js simples

---

## 🌐 OPÇÃO 1: Serviços Hospedados (RECOMENDADO)

### **A. Z-API (Brasileiro, Pago mas Barato)**

**Vantagens:**
- ✅ **Não precisa instalar nada**
- ✅ **5 minutos para configurar**
- ✅ **Suporte em português**
- ✅ **Muito estável**
- ✅ **R$ 35/mês** (plano básico)

**Como usar:**

#### **1. Criar conta:**
- Acesse: https://www.z-api.io
- Crie conta gratuita (7 dias de teste)
- Escolha plano (R$ 35/mês básico)

#### **2. Conectar WhatsApp:**
- No painel, clique em "Conectar Instância"
- Escaneie QR Code com WhatsApp
- Pronto! Conectado

#### **3. Obter credenciais:**
- **Instance ID:** Copie da dashboard
- **Token:** Copie da dashboard
- **Client Token:** Copie da dashboard

#### **4. Obter ID do Grupo:**
- Envie mensagem no grupo
- Acesse: Webhooks > Mensagens Recebidas
- Copie o `chatId` do grupo (formato: `5511999999999-1234567890@g.us`)

#### **5. Configurar no sistema:**

Edite `config_whatsapp.php`:

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'zapi'); // Novo tipo

// Configurações Z-API
define('ZAPI_INSTANCE_ID', 'SEU_INSTANCE_ID');
define('ZAPI_TOKEN', 'SEU_TOKEN');
define('ZAPI_CLIENT_TOKEN', 'SEU_CLIENT_TOKEN');
define('WHATSAPP_GROUP_ID', '5511999999999-1234567890@g.us');
```

---

### **B. Hocketzap (Brasileiro, Gratuito 30 dias)**

**Vantagens:**
- ✅ **30 dias grátis**
- ✅ **Fácil de usar**
- ✅ **Suporte em português**
- ✅ **R$ 29,90/mês** após trial

**Como usar:**

#### **1. Criar conta:**
- Acesse: https://hocketzap.com
- Crie conta (30 dias grátis)

#### **2. Conectar WhatsApp:**
- Clique em "Nova Conexão"
- Escaneie QR Code
- Aguarde conectar

#### **3. Obter API Key:**
- Menu > API
- Copie a API Key

#### **4. Obter ID do Grupo:**
- Menu > Grupos
- Encontre seu grupo
- Copie o ID

#### **5. Configurar:**

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'hocketzap');

define('HOCKETZAP_API_KEY', 'SUA_API_KEY');
define('HOCKETZAP_INSTANCE_ID', 'SEU_INSTANCE_ID');
define('WHATSAPP_GROUP_ID', 'ID_DO_GRUPO');
```

---

### **C. WhatSender (Gratuito com Limitações)**

**Vantagens:**
- ✅ **Plano gratuito disponível**
- ✅ **Fácil configuração**
- ✅ **100 mensagens/mês grátis**

**Como usar:**

1. Acesse: https://whatsender.io
2. Crie conta gratuita
3. Conecte WhatsApp
4. Copie API Key
5. Configure no sistema

---

## 🔧 OPÇÃO 2: Evolution API sem Docker

### **Instalação Manual (Node.js)**

#### **Requisitos:**
- Node.js 18+ instalado
- Git instalado
- Servidor Linux/Windows

#### **Passo 1: Instalar Node.js**

**Ubuntu/Debian:**
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

**Windows:**
- Baixe: https://nodejs.org
- Instale normalmente

**Verificar:**
```bash
node --version  # Deve mostrar v18.x ou superior
npm --version
```

#### **Passo 2: Baixar Evolution API**

```bash
cd /var/www
git clone https://github.com/EvolutionAPI/evolution-api.git
cd evolution-api
```

#### **Passo 3: Instalar Dependências**

```bash
npm install
```

#### **Passo 4: Configurar**

Crie arquivo `.env`:

```bash
nano .env
```

Cole:

```env
# URL da API
SERVER_URL=http://localhost:8080

# Porta
PORT=8080

# Chave de autenticação
AUTHENTICATION_API_KEY=minha-chave-secreta-123

# Modo de desenvolvimento
NODE_ENV=production

# Database (opcional)
DATABASE_ENABLED=false
```

#### **Passo 5: Iniciar**

```bash
npm run start:prod
```

Ou para rodar em background:

```bash
npm install -g pm2
pm2 start dist/src/main.js --name evolution-api
pm2 save
pm2 startup
```

#### **Passo 6: Testar**

Acesse: http://localhost:8080

#### **Passo 7: Criar Instância**

```bash
curl -X POST http://localhost:8080/instance/create \
  -H "apikey: minha-chave-secreta-123" \
  -H "Content-Type: application/json" \
  -d '{"instanceName": "vistoria-engeradios", "qrcode": true}'
```

Escaneie o QR Code que aparecer

#### **Passo 8: Obter ID do Grupo**

```bash
curl -X GET http://localhost:8080/group/findGroupInfos/vistoria-engeradios \
  -H "apikey: minha-chave-secreta-123"
```

#### **Passo 9: Configurar Sistema**

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'evolution');
define('EVOLUTION_API_URL', 'http://localhost:8080');
define('EVOLUTION_INSTANCE_NAME', 'vistoria-engeradios');
define('EVOLUTION_API_KEY', 'minha-chave-secreta-123');
define('WHATSAPP_GROUP_ID', '120363XXXXXXXXXX@g.us');
```

---

## 📱 OPÇÃO 3: WPPConnect Server

### **Instalação Simples**

#### **Passo 1: Instalar Node.js**
(Mesmo da Opção 2)

#### **Passo 2: Instalar WPPConnect**

```bash
npm install -g @wppconnect-team/wppconnect-server
```

#### **Passo 3: Iniciar**

```bash
wppconnect-server
```

Acesse: http://localhost:21465

#### **Passo 4: Conectar WhatsApp**

1. Abra navegador: http://localhost:21465
2. Clique em "Start Session"
3. Escaneie QR Code
4. Aguarde conectar

#### **Passo 5: Obter Token**

No painel, copie o token gerado

#### **Passo 6: Listar Grupos**

```bash
curl http://localhost:21465/api/sessionName/all-chats
```

Procure pelo nome do grupo e copie o `id`

#### **Passo 7: Configurar**

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'wppconnect');
define('BAILEYS_API_URL', 'http://localhost:21465');
define('BAILEYS_API_TOKEN', 'SEU_TOKEN');
define('WHATSAPP_GROUP_ID', 'ID_DO_GRUPO@g.us');
```

---

## 💰 Comparação de Custos

| Opção | Custo Mensal | Instalação | Dificuldade |
|-------|--------------|------------|-------------|
| **Z-API** | R$ 35 | Nenhuma | ⭐ Fácil |
| **Hocketzap** | R$ 29,90 | Nenhuma | ⭐ Fácil |
| **WhatSender** | Grátis (limitado) | Nenhuma | ⭐ Fácil |
| **Evolution Manual** | Grátis | Node.js | ⭐⭐ Médio |
| **WPPConnect** | Grátis | Node.js | ⭐⭐ Médio |

---

## 🎯 Qual Escolher?

### **Para Facilidade:**
✅ **Z-API** ou **Hocketzap** (pago, mas sem dor de cabeça)

### **Para Economia:**
✅ **Evolution API Manual** ou **WPPConnect** (grátis, mas precisa manter servidor)

### **Para Testar:**
✅ **Hocketzap** (30 dias grátis) ou **WhatSender** (100 msg/mês grátis)

---

## 📝 Minha Recomendação

### **Melhor Custo-Benefício:**

**Hocketzap** (R$ 29,90/mês)
- 30 dias grátis para testar
- Muito fácil de configurar
- Suporte em português
- Estável e confiável

### **Totalmente Grátis:**

**Evolution API Manual**
- Grátis para sempre
- Precisa de servidor com Node.js
- Mais trabalho para configurar
- Você tem controle total

---

## 🚀 Configuração Rápida (Z-API)

### **5 Minutos:**

1. **Criar conta:** https://www.z-api.io (7 dias grátis)
2. **Conectar WhatsApp:** Escanear QR Code
3. **Copiar credenciais:** Instance ID, Token, Client Token
4. **Editar config_whatsapp.php:**

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'zapi');
define('ZAPI_INSTANCE_ID', 'SEU_INSTANCE_ID');
define('ZAPI_TOKEN', 'SEU_TOKEN');
define('ZAPI_CLIENT_TOKEN', 'SEU_CLIENT_TOKEN');
define('WHATSAPP_GROUP_ID', 'ID_DO_GRUPO@g.us');
```

5. **Testar:** Criar vistoria e concluir

---

## 🆘 Precisa de Ajuda?

### **Z-API:**
- Suporte: https://developer.z-api.io
- WhatsApp: +55 11 91234-5678

### **Hocketzap:**
- Suporte: https://hocketzap.com/suporte
- Chat ao vivo no site

### **Evolution API:**
- Documentação: https://doc.evolution-api.com
- Discord: https://evolution-api.com/discord

### **WPPConnect:**
- Documentação: https://wppconnect.io/docs
- GitHub: https://github.com/wppconnect-team

---

## ✅ Próximos Passos

1. **Escolha uma opção** (recomendo Hocketzap ou Z-API)
2. **Crie conta** no serviço escolhido
3. **Conecte WhatsApp** (escanear QR Code)
4. **Copie credenciais**
5. **Configure config_whatsapp.php**
6. **Teste** criando uma vistoria

---

**Atualização:** 14/11/2025
**Versão:** 1.9
**Alternativas:** Sem Docker
