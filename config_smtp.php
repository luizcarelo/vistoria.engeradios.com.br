<?php
/**
 * Configuração SMTP para Envio de E-mails
 * Sistema Vistoria Remota ENGERADIOS
 * 
 * INSTRUÇÕES:
 * 1. Preencha as configurações abaixo com os dados do seu servidor SMTP
 * 2. Salve o arquivo
 * 3. Teste o envio de e-mail criando uma vistoria
 */

// ============================================
// CONFIGURAÇÕES SMTP
// ============================================

// Habilitar envio via SMTP (true) ou usar mail() do PHP (false)
define('SMTP_ENABLED', true);

// Servidor SMTP
define('SMTP_HOST', 'smtp.engeradios.com.br');  // Ex: smtp.gmail.com, smtp.office365.com, mail.seudominio.com.br

// Porta SMTP
define('SMTP_PORT', 465);  // 587 (TLS) ou 465 (SSL)

// Tipo de criptografia
define('SMTP_SECURE', 'ssl');  // 'tls' ou 'ssl'

// Autenticação SMTP
define('SMTP_AUTH', true);  // true = requer autenticação, false = sem autenticação

// Usuário SMTP (geralmente o e-mail completo)
define('SMTP_USERNAME', 'noreply@engeradios.com.br');

// Senha SMTP
define('SMTP_PASSWORD', 'Engeradios@2026');

// Nome do remetente
define('SMTP_FROM_NAME', 'Vistoria Remota ENGERADIOS');

// E-mail do remetente (geralmente o mesmo do SMTP_USERNAME)
define('SMTP_FROM_EMAIL', 'noreply@engeradios.com.br');

// E-mail de resposta (Reply-To)
define('SMTP_REPLY_TO', 'operacional@engeradios.com.br');

// Ativar debug (0 = desativado, 1 = mensagens do cliente, 2 = mensagens do cliente e servidor)
define('SMTP_DEBUG', 0);


// ============================================
// CONFIGURAÇÕES ESPECÍFICAS POR PROVEDOR
// ============================================

/*
 * GMAIL:
 * - Host: smtp.gmail.com
 * - Port: 587
 * - Secure: tls
 * - Username: seu-email@gmail.com
 * - Password: Senha de App (não a senha normal)
 * - Como gerar senha de app: https://myaccount.google.com/apppasswords
 * 
 * OUTLOOK/OFFICE 365:
 * - Host: smtp.office365.com
 * - Port: 587
 * - Secure: tls
 * - Username: seu-email@outlook.com
 * - Password: Sua senha normal
 * 
 * YAHOO:
 * - Host: smtp.mail.yahoo.com
 * - Port: 587
 * - Secure: tls
 * - Username: seu-email@yahoo.com
 * - Password: Senha de App
 * 
 * HOSTGATOR / LOCAWEB / UOL HOST:
 * - Host: mail.seudominio.com.br
 * - Port: 587 ou 465
 * - Secure: tls ou ssl
 * - Username: seu-email@seudominio.com.br
 * - Password: Senha do e-mail
 * 
 * KINGHOST:
 * - Host: smtp.kinghost.net
 * - Port: 587
 * - Secure: tls
 * - Username: seu-email@seudominio.com.br
 * - Password: Senha do e-mail
 */

// ============================================
// VALIDAÇÃO DAS CONFIGURAÇÕES
// ============================================

if (SMTP_ENABLED) {
    if (empty(SMTP_HOST) || SMTP_HOST == 'smtp.gmail.com' && 
        (SMTP_USERNAME == 'seu-email@gmail.com' || SMTP_PASSWORD == 'sua-senha-ou-app-password')) {
        // Configurações padrão não foram alteradas
        // Comentar estas linhas após configurar
        // trigger_error('ATENÇÃO: Configure o arquivo config_smtp.php com suas credenciais SMTP reais!', E_USER_WARNING);
    }
}
?>
