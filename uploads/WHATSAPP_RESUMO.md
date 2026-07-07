# WhatsApp - Resumo Rápido

## ✅ O que foi implementado

**Envio automático** do relatório de vistoria em PDF para grupo do WhatsApp ao concluir vistoria.

## 📱 Mensagem Enviada

```
🔔 *Nova Vistoria Concluída*

📋 *Vistoria:* #000123
🏢 *Cliente:* Hospital ABC
👤 *Supervisor:* João Silva
📅 *Data:* 14/11/2025
🕐 *Horário:* 15:30

📄 Relatório em PDF anexado.
```

## ⚙️ Configuração Rápida (Evolution API)

### **1. Instalar Evolution API**

```bash
docker run -d \
  --name evolution-api \
  -p 8080:8080 \
  -e AUTHENTICATION_API_KEY=minha-chave-secreta \
  atendai/evolution-api:latest
```

### **2. Criar Instância e Escanear QR Code**

```bash
curl -X POST http://localhost:8080/instance/create \
  -H "apikey: minha-chave-secreta" \
  -H "Content-Type: application/json" \
  -d '{"instanceName": "vistoria-engeradios", "qrcode": true}'
```

### **3. Obter ID do Grupo**

```bash
curl -X GET http://localhost:8080/group/findGroupInfos/vistoria-engeradios \
  -H "apikey: minha-chave-secreta"
```

Copie o `id` do grupo (formato: `120363XXXXXXXXXX@g.us`)

### **4. Editar config_whatsapp.php**

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_API_TYPE', 'evolution');
define('EVOLUTION_API_URL', 'http://localhost:8080');
define('EVOLUTION_INSTANCE_NAME', 'vistoria-engeradios');
define('EVOLUTION_API_KEY', 'minha-chave-secreta');
define('WHATSAPP_GROUP_ID', '120363XXXXXXXXXX@g.us');
```

### **5. Testar**

Crie uma vistoria de teste e conclua. O PDF será enviado para o grupo!

## 📚 Documentação Completa

Consulte **GUIA_WHATSAPP.md** para:
- Instruções detalhadas
- Outras APIs (Twilio, Baileys)
- Solução de problemas
- Personalização da mensagem
- Logs e testes

## 🎯 APIs Suportadas

| API | Custo | Facilidade | Recomendação |
|-----|-------|------------|--------------|
| **Evolution API** | Gratuita | ⭐⭐⭐⭐⭐ | ✅ Recomendada |
| Twilio | Paga | ⭐⭐⭐⭐ | Para empresas |
| Baileys/WPPConnect | Gratuita | ⭐⭐⭐ | Alternativa |

## ⚠️ Importante

- WhatsApp vem **desativado** por padrão
- Altere `WHATSAPP_ENABLED` para `true` para ativar
- Requer Evolution API ou outra API configurada
- Grupo deve existir antes de configurar

## 🆘 Problemas Comuns

**"WhatsApp desativado"**
→ Altere `WHATSAPP_ENABLED` para `true`

**"Erro de conexão"**
→ Verifique se Evolution API está rodando

**"ID do grupo inválido"**
→ Use o endpoint `/group/findGroupInfos` para obter ID correto

---

**Versão:** 1.8 | **Data:** 14/11/2025
