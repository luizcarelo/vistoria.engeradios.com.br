# Guia de Configuração SMTP - Sistema Vistoria ENGERADIOS

## 📧 Configuração de Envio Automático de E-mails

Este guia explica como configurar o envio automático de e-mails do sistema usando SMTP com autenticação.

---

## 🎯 O que foi implementado?

O sistema agora suporta **duas formas de envio de e-mail**:

1. **SMTP com autenticação** (PHPMailer) - **RECOMENDADO**
   - Mais confiável
   - Funciona com Gmail, Outlook, Yahoo, etc.
   - Suporta autenticação segura
   - Melhor taxa de entrega

2. **mail() do PHP** (Fallback)
   - Método tradicional
   - Pode não funcionar em alguns servidores
   - Menor taxa de entrega

---

## 📁 Arquivos Envolvidos

```
vistoria-engeradios/
├── config_smtp.php (NOVO)          # Configurações SMTP
├── lib/
│   └── phpmailer/                  # Biblioteca PHPMailer
└── supervisor/
    └── processar_vistoria.php      # Função de envio atualizada
```

---

## ⚙️ Passo a Passo - Configuração

### **1. Editar arquivo `config_smtp.php`**

Abra o arquivo `/config_smtp.php` e configure as seguintes opções:

```php
// Habilitar envio via SMTP
define('SMTP_ENABLED', true);  // true = usar SMTP, false = usar mail()

// Servidor SMTP
define('SMTP_HOST', 'smtp.gmail.com');

// Porta SMTP
define('SMTP_PORT', 587);

// Tipo de criptografia
define('SMTP_SECURE', 'tls');  // 'tls' ou 'ssl'

// Autenticação
define('SMTP_AUTH', true);

// Usuário SMTP
define('SMTP_USERNAME', 'seu-email@gmail.com');

// Senha SMTP
define('SMTP_PASSWORD', 'sua-senha-ou-app-password');

// Nome do remetente
define('SMTP_FROM_NAME', 'Vistoria Remota ENGERADIOS');

// E-mail do remetente
define('SMTP_FROM_EMAIL', 'seu-email@gmail.com');

// E-mail de resposta
define('SMTP_REPLY_TO', 'operacional@engeradios.com.br');
```

---

## 📮 Configurações por Provedor

### **🔵 GMAIL**

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'seu-email@gmail.com');
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');  // Senha de App
```

**⚠️ IMPORTANTE:** Gmail requer **Senha de App** (não a senha normal)

**Como gerar Senha de App no Gmail:**
1. Acesse: https://myaccount.google.com/apppasswords
2. Faça login na sua conta Google
3. Selecione "E-mail" e "Outro (nome personalizado)"
4. Digite "Vistoria ENGERADIOS"
5. Clique em "Gerar"
6. Copie a senha de 16 dígitos (sem espaços)
7. Cole em `SMTP_PASSWORD`

**Observação:** Se não conseguir acessar, ative a verificação em 2 etapas primeiro:
https://myaccount.google.com/security

---

### **🔵 OUTLOOK / OFFICE 365**

```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'seu-email@outlook.com');
define('SMTP_PASSWORD', 'sua-senha-normal');
```

**Observação:** Outlook usa a senha normal da conta.

---

### **🔵 YAHOO MAIL**

```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'seu-email@yahoo.com');
define('SMTP_PASSWORD', 'senha-de-app');
```

**Como gerar Senha de App no Yahoo:**
1. Acesse: https://login.yahoo.com/account/security
2. Ative a verificação em duas etapas
3. Clique em "Gerar senha de app"
4. Selecione "Outro app" e digite "Vistoria"
5. Copie a senha gerada

---

### **🔵 HOSTGATOR / LOCAWEB / UOL HOST**

```php
define('SMTP_HOST', 'mail.seudominio.com.br');
define('SMTP_PORT', 587);  // ou 465
define('SMTP_SECURE', 'tls');  // ou 'ssl'
define('SMTP_USERNAME', 'seu-email@seudominio.com.br');
define('SMTP_PASSWORD', 'senha-do-email');
```

**Observação:** Use o e-mail criado no painel de hospedagem.

**Dica:** Consulte a documentação da sua hospedagem para confirmar:
- Hostgator: https://www.hostgator.com.br/suporte
- Locaweb: https://ajuda.locaweb.com.br/
- UOL Host: https://ajuda.uol.com.br/

---

### **🔵 KINGHOST**

```php
define('SMTP_HOST', 'smtp.kinghost.net');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'seu-email@seudominio.com.br');
define('SMTP_PASSWORD', 'senha-do-email');
```

---

### **🔵 TITAN (EMAIL PROFISSIONAL)**

```php
define('SMTP_HOST', 'smtp.titan.email');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'seu-email@seudominio.com.br');
define('SMTP_PASSWORD', 'senha-do-email');
```

---

## 🔍 Teste de Configuração

### **1. Teste Rápido**

Após configurar, crie uma vistoria de teste no sistema:

1. Faça login como supervisor
2. Crie uma nova vistoria
3. Preencha todos os campos
4. Clique em "Concluir"
5. Verifique se o e-mail foi recebido em `operacional@engeradios.com.br`

### **2. Ativar Debug (se houver problemas)**

No arquivo `config_smtp.php`, altere:

```php
define('SMTP_DEBUG', 2);  // 0 = desativado, 1 = mensagens do cliente, 2 = todas
```

Isso mostrará mensagens detalhadas de erro no navegador.

**⚠️ IMPORTANTE:** Desative o debug após resolver os problemas:

```php
define('SMTP_DEBUG', 0);
```

---

## ❌ Problemas Comuns e Soluções

### **Erro: "SMTP connect() failed"**

**Causas:**
- Host SMTP incorreto
- Porta bloqueada pelo firewall
- Credenciais inválidas

**Soluções:**
1. Verifique se o `SMTP_HOST` está correto
2. Teste portas alternativas: 587 (TLS) ou 465 (SSL)
3. Verifique se as credenciais estão corretas
4. Contate seu provedor de hospedagem

---

### **Erro: "Authentication failed"**

**Causas:**
- Senha incorreta
- Gmail sem senha de app
- Verificação em 2 etapas não ativada

**Soluções:**
1. Verifique a senha no `config_smtp.php`
2. Para Gmail: use senha de app (não a senha normal)
3. Para Gmail: ative verificação em 2 etapas
4. Para Yahoo: gere senha de app

---

### **Erro: "Could not instantiate mail function"**

**Causas:**
- Função `mail()` desabilitada no servidor
- PHPMailer não encontrado

**Soluções:**
1. Certifique-se de que `SMTP_ENABLED` está como `true`
2. Verifique se a pasta `/lib/phpmailer/` existe
3. Contate seu provedor de hospedagem

---

### **E-mail não chega (sem erro)**

**Causas:**
- E-mail na caixa de spam
- Filtro de e-mail do destinatário
- Domínio sem SPF/DKIM configurado

**Soluções:**
1. Verifique a caixa de spam
2. Adicione o remetente aos contatos
3. Configure SPF/DKIM no seu domínio (consulte seu provedor)

---

## 🔐 Segurança

### **Proteger arquivo `config_smtp.php`**

**1. Permissões do arquivo:**

```bash
chmod 600 config_smtp.php
```

**2. Adicionar ao `.htaccess`:**

```apache
<Files "config_smtp.php">
    Order Allow,Deny
    Deny from all
</Files>
```

**3. Não versionar credenciais:**

Se usar Git, adicione ao `.gitignore`:

```
config_smtp.php
```

---

## 📊 Monitoramento

### **Logs de E-mail**

Os erros de envio são registrados no log do PHP. Para visualizar:

```bash
tail -f /var/log/php_errors.log
```

Ou consulte o painel de controle da sua hospedagem.

---

## 🔄 Desabilitar SMTP (usar mail() do PHP)

Se preferir usar a função `mail()` do PHP:

```php
define('SMTP_ENABLED', false);
```

O sistema voltará a usar o método tradicional.

---

## 📝 Exemplo Completo - Gmail

```php
<?php
// Habilitar SMTP
define('SMTP_ENABLED', true);

// Configurações Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'vistoria@engeradios.com.br');
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop');  // Senha de App

// Remetente
define('SMTP_FROM_NAME', 'Vistoria Remota ENGERADIOS');
define('SMTP_FROM_EMAIL', 'vistoria@engeradios.com.br');
define('SMTP_REPLY_TO', 'operacional@engeradios.com.br');

// Debug desativado
define('SMTP_DEBUG', 0);
?>
```

---

## 📝 Exemplo Completo - Hospedagem Compartilhada

```php
<?php
// Habilitar SMTP
define('SMTP_ENABLED', true);

// Configurações Hospedagem
define('SMTP_HOST', 'mail.engeradios.com.br');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'vistoria@engeradios.com.br');
define('SMTP_PASSWORD', 'SuaSenhaSegura123!');

// Remetente
define('SMTP_FROM_NAME', 'Vistoria Remota ENGERADIOS');
define('SMTP_FROM_EMAIL', 'vistoria@engeradios.com.br');
define('SMTP_REPLY_TO', 'operacional@engeradios.com.br');

// Debug desativado
define('SMTP_DEBUG', 0);
?>
```

---

## 🆘 Suporte

### **Recursos Úteis:**

- **PHPMailer Documentação:** https://github.com/PHPMailer/PHPMailer
- **Gmail SMTP:** https://support.google.com/mail/answer/7126229
- **Outlook SMTP:** https://support.microsoft.com/en-us/office/pop-imap-and-smtp-settings

### **Contato com Provedor:**

Se os problemas persistirem, entre em contato com seu provedor de hospedagem e informe:
- Que está usando SMTP para envio de e-mails
- Porta que está tentando usar (587 ou 465)
- Se há firewall bloqueando conexões SMTP

---

## ✅ Checklist de Configuração

- [ ] Editei o arquivo `config_smtp.php`
- [ ] Configurei `SMTP_HOST` corretamente
- [ ] Configurei `SMTP_PORT` (587 ou 465)
- [ ] Configurei `SMTP_USERNAME` (e-mail completo)
- [ ] Configurei `SMTP_PASSWORD` (senha ou senha de app)
- [ ] Para Gmail: gerei senha de app
- [ ] Testei enviando uma vistoria
- [ ] E-mail foi recebido com sucesso
- [ ] Desativei debug (`SMTP_DEBUG = 0`)
- [ ] Protegi arquivo com permissões adequadas

---

**Desenvolvido por:** Manus AI
**Data:** 06/11/2025
**Sistema:** Vistoria Remota ENGERADIOS
**Versão:** 1.3
