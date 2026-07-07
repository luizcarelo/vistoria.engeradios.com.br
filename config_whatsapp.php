<?php
// Configuração do WhatsApp para Sistema Vistoria ENGERADIOS
// Este arquivo contém as configurações para envio automático de relatórios via WhatsApp

// ============================================================================
// ATIVAR/DESATIVAR ENVIO PARA WHATSAPP
// ============================================================================
define('WHATSAPP_ENABLED', false); // Altere para true para ativar o envio

// ============================================================================
// CONFIGURAÇÕES DA API DO WHATSAPP
// ============================================================================

// Escolha qual API você vai usar:
// 'evolution' - Evolution API (gratuita, open-source)
// 'zapi' - Z-API (paga, hospedada, fácil) - RECOMENDADA SEM DOCKER
// 'hocketzap' - Hocketzap (paga, hospedada, 30 dias grátis)
// 'twilio' - Twilio (paga, oficial)
// 'baileys' - Baileys (gratuita, requer servidor Node.js)
// 'wppconnect' - WPPConnect (gratuita, open-source)
define('WHATSAPP_API_TYPE', 'evolution');

// ============================================================================
// EVOLUTION API (Recomendada - Gratuita e Open Source)
// ============================================================================
// URL da sua instância Evolution API
// Exemplo: https://evolution.seudominio.com.br
define('EVOLUTION_API_URL', 'https://evolution.seudominio.com.br');

// Nome da instância criada no Evolution
define('EVOLUTION_INSTANCE_NAME', 'vistoria-engeradios');

// API Key do Evolution (gerada ao criar a instância)
define('EVOLUTION_API_KEY', 'SUA_API_KEY_AQUI');

// ============================================================================
// TWILIO (Alternativa Paga - Oficial do WhatsApp)
// ============================================================================
// Account SID do Twilio
define('TWILIO_ACCOUNT_SID', 'SEU_ACCOUNT_SID');

// Auth Token do Twilio
define('TWILIO_AUTH_TOKEN', 'SEU_AUTH_TOKEN');

// Número do WhatsApp Business do Twilio (formato: +5511999999999)
define('TWILIO_WHATSAPP_NUMBER', '+5511999999999');

// ============================================================================
// Z-API (Serviço Hospedado - Recomendado sem Docker)
// ============================================================================
// Instance ID da Z-API
define('ZAPI_INSTANCE_ID', 'SEU_INSTANCE_ID');

// Token da Z-API
define('ZAPI_TOKEN', 'SEU_TOKEN');

// Client Token da Z-API (opcional)
define('ZAPI_CLIENT_TOKEN', 'SEU_CLIENT_TOKEN');

// ============================================================================
// HOCKETZAP (Serviço Hospedado - 30 dias grátis)
// ============================================================================
// API Key do Hocketzap
define('HOCKETZAP_API_KEY', 'SUA_API_KEY');

// Instance ID do Hocketzap
define('HOCKETZAP_INSTANCE_ID', 'SEU_INSTANCE_ID');

// ============================================================================
// BAILEYS / WPPCONNECT (Alternativas Gratuitas)
// ============================================================================
// URL da API Baileys/WPPConnect
define('BAILEYS_API_URL', 'http://localhost:3000');

// Token de autenticação
define('BAILEYS_API_TOKEN', 'SEU_TOKEN_AQUI');

// ============================================================================
// CONFIGURAÇÕES DO GRUPO
// ============================================================================

// ID do grupo do WhatsApp para enviar as vistorias
// Formato Evolution API: 120363XXXXXXXXXX@g.us
// Formato Twilio: whatsapp:+5511999999999
// 
// COMO OBTER O ID DO GRUPO:
// 1. Evolution API: Use o endpoint /group/findGroupInfos
// 2. Twilio: Use o número do grupo
// 3. Baileys: Use o endpoint /group/list
define('WHATSAPP_GROUP_ID', '120363XXXXXXXXXX@g.us');

// Nome do grupo (apenas para referência)
define('WHATSAPP_GROUP_NAME', 'Supervisores ENGERADIOS');

// ============================================================================
// CONFIGURAÇÕES DE MENSAGEM
// ============================================================================

// Mensagem que será enviada junto com o PDF
// Variáveis disponíveis: {cliente}, {supervisor}, {data}, {hora}, {numero_vistoria}
define('WHATSAPP_MESSAGE_TEMPLATE', 
"🔔 *Nova Vistoria Concluída*

📋 *Vistoria:* #{numero_vistoria}
🏢 *Cliente:* {cliente}
👤 *Supervisor:* {supervisor}
📅 *Data:* {data}
🕐 *Horário:* {hora}

📄 Relatório em PDF anexado.

_Sistema Vistoria Remota ENGERADIOS_");

// ============================================================================
// CONFIGURAÇÕES AVANÇADAS
// ============================================================================

// Timeout para requisições HTTP (segundos)
define('WHATSAPP_TIMEOUT', 30);

// Tentar reenviar em caso de falha
define('WHATSAPP_RETRY_ON_FAIL', true);

// Número de tentativas de reenvio
define('WHATSAPP_MAX_RETRIES', 3);

// Log de envios (salvar em arquivo)
define('WHATSAPP_LOG_ENABLED', true);

// Arquivo de log
define('WHATSAPP_LOG_FILE', __DIR__ . '/logs/whatsapp.log');

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

/**
 * Criar diretório de logs se não existir
 */
if (WHATSAPP_LOG_ENABLED && !file_exists(dirname(WHATSAPP_LOG_FILE))) {
    mkdir(dirname(WHATSAPP_LOG_FILE), 0755, true);
}

/**
 * Registrar log de envio
 */
function whatsappLog($message, $type = 'INFO') {
    if (!WHATSAPP_LOG_ENABLED) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message\n";
    file_put_contents(WHATSAPP_LOG_FILE, $logMessage, FILE_APPEND);
}

/**
 * Validar configurações
 */
function whatsappValidateConfig() {
    if (!WHATSAPP_ENABLED) {
        return ['valid' => false, 'message' => 'WhatsApp desativado'];
    }
    
    switch (WHATSAPP_API_TYPE) {
        case 'evolution':
            if (empty(EVOLUTION_API_URL) || empty(EVOLUTION_API_KEY)) {
                return ['valid' => false, 'message' => 'Configurações Evolution API incompletas'];
            }
            break;
            
        case 'zapi':
            if (empty(ZAPI_INSTANCE_ID) || empty(ZAPI_TOKEN)) {
                return ['valid' => false, 'message' => 'Configurações Z-API incompletas'];
            }
            break;
            
        case 'hocketzap':
            if (empty(HOCKETZAP_API_KEY) || empty(HOCKETZAP_INSTANCE_ID)) {
                return ['valid' => false, 'message' => 'Configurações Hocketzap incompletas'];
            }
            break;
            
        case 'twilio':
            if (empty(TWILIO_ACCOUNT_SID) || empty(TWILIO_AUTH_TOKEN)) {
                return ['valid' => false, 'message' => 'Configurações Twilio incompletas'];
            }
            break;
            
        case 'baileys':
        case 'wppconnect':
            if (empty(BAILEYS_API_URL)) {
                return ['valid' => false, 'message' => 'URL da API não configurada'];
            }
            break;
            
        default:
            return ['valid' => false, 'message' => 'Tipo de API inválido'];
    }
    
    if (empty(WHATSAPP_GROUP_ID)) {
        return ['valid' => false, 'message' => 'ID do grupo não configurado'];
    }
    
    return ['valid' => true, 'message' => 'Configurações válidas'];
}
?>
