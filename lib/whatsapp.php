<?php
/**
 * Biblioteca de Envio para WhatsApp
 * Sistema Vistoria Remota ENGERADIOS
 * 
 * Suporta múltiplas APIs:
 * - Evolution API (recomendada)
 * - Twilio
 * - Baileys
 * - WPPConnect
 */

require_once __DIR__ . '/../config_whatsapp.php';

class WhatsAppSender {
    
    /**
     * Enviar mensagem com PDF para o grupo
     * 
     * @param string $pdfPath Caminho do arquivo PDF
     * @param array $data Dados da vistoria (cliente, supervisor, data, hora, numero)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendVistoriaPDF($pdfPath, $data) {
        // Validar configurações
        $validation = whatsappValidateConfig();
        if (!$validation['valid']) {
            whatsappLog('Configurações inválidas: ' . $validation['message'], 'ERROR');
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Validar arquivo PDF
        if (!file_exists($pdfPath)) {
            whatsappLog('Arquivo PDF não encontrado: ' . $pdfPath, 'ERROR');
            return ['success' => false, 'message' => 'Arquivo PDF não encontrado'];
        }
        
        // Preparar mensagem
        $message = self::prepareMessage($data);
        
        // Log
        whatsappLog("Iniciando envio para grupo: " . WHATSAPP_GROUP_NAME);
        whatsappLog("Cliente: {$data['cliente']}, Supervisor: {$data['supervisor']}");
        
        // Enviar conforme tipo de API
        switch (WHATSAPP_API_TYPE) {
            case 'evolution':
                return self::sendViaEvolutionAPI($message, $pdfPath);
                
            case 'zapi':
                return self::sendViaZAPI($message, $pdfPath);
                
            case 'hocketzap':
                return self::sendViaHocketzap($message, $pdfPath);
                
            case 'twilio':
                return self::sendViaTwilio($message, $pdfPath);
                
            case 'baileys':
            case 'wppconnect':
                return self::sendViaBaileys($message, $pdfPath);
                
            default:
                return ['success' => false, 'message' => 'Tipo de API não suportado'];
        }
    }
    
    /**
     * Preparar mensagem substituindo variáveis
     */
    private static function prepareMessage($data) {
        $message = WHATSAPP_MESSAGE_TEMPLATE;
        
        $replacements = [
            '{cliente}' => $data['cliente'],
            '{supervisor}' => $data['supervisor'],
            '{data}' => $data['data'],
            '{hora}' => $data['hora'],
            '{numero_vistoria}' => str_pad($data['numero'], 6, '0', STR_PAD_LEFT)
        ];
        
        foreach ($replacements as $key => $value) {
            $message = str_replace($key, $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Enviar via Evolution API
     */
    private static function sendViaEvolutionAPI($message, $pdfPath) {
        try {
            $url = rtrim(EVOLUTION_API_URL, '/') . '/message/sendMedia/' . EVOLUTION_INSTANCE_NAME;
            
            // Converter PDF para base64
            $pdfBase64 = base64_encode(file_get_contents($pdfPath));
            $fileName = basename($pdfPath);
            
            $payload = [
                'number' => WHATSAPP_GROUP_ID,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ],
                'mediaMessage' => [
                    'mediatype' => 'document',
                    'fileName' => $fileName,
                    'media' => $pdfBase64,
                    'caption' => $message
                ]
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . EVOLUTION_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, WHATSAPP_TIMEOUT);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                whatsappLog('Erro cURL: ' . $error, 'ERROR');
                return ['success' => false, 'message' => 'Erro de conexão: ' . $error];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                whatsappLog('PDF enviado com sucesso via Evolution API', 'SUCCESS');
                return ['success' => true, 'message' => 'PDF enviado com sucesso'];
            } else {
                whatsappLog('Erro HTTP ' . $httpCode . ': ' . $response, 'ERROR');
                return ['success' => false, 'message' => 'Erro ao enviar: HTTP ' . $httpCode];
            }
            
        } catch (Exception $e) {
            whatsappLog('Exceção: ' . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar via Twilio
     */
    private static function sendViaTwilio($message, $pdfPath) {
        try {
            // Primeiro, fazer upload do PDF para um servidor público
            // Twilio requer URL pública do arquivo
            $pdfUrl = self::uploadPDFToPublicServer($pdfPath);
            
            if (!$pdfUrl) {
                return ['success' => false, 'message' => 'Erro ao fazer upload do PDF'];
            }
            
            $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json";
            
            $data = [
                'From' => TWILIO_WHATSAPP_NUMBER,
                'To' => 'whatsapp:' . WHATSAPP_GROUP_ID,
                'Body' => $message,
                'MediaUrl' => $pdfUrl
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
            curl_setopt($ch, CURLOPT_TIMEOUT, WHATSAPP_TIMEOUT);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                whatsappLog('PDF enviado com sucesso via Twilio', 'SUCCESS');
                return ['success' => true, 'message' => 'PDF enviado com sucesso'];
            } else {
                whatsappLog('Erro Twilio HTTP ' . $httpCode . ': ' . $response, 'ERROR');
                return ['success' => false, 'message' => 'Erro ao enviar via Twilio'];
            }
            
        } catch (Exception $e) {
            whatsappLog('Exceção Twilio: ' . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar via Baileys/WPPConnect
     */
    private static function sendViaBaileys($message, $pdfPath) {
        try {
            $url = rtrim(BAILEYS_API_URL, '/') . '/send-file';
            
            // Converter PDF para base64
            $pdfBase64 = base64_encode(file_get_contents($pdfPath));
            $fileName = basename($pdfPath);
            
            $payload = [
                'chatId' => WHATSAPP_GROUP_ID,
                'file' => $pdfBase64,
                'fileName' => $fileName,
                'caption' => $message
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . BAILEYS_API_TOKEN
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, WHATSAPP_TIMEOUT);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                whatsappLog('PDF enviado com sucesso via Baileys/WPPConnect', 'SUCCESS');
                return ['success' => true, 'message' => 'PDF enviado com sucesso'];
            } else {
                whatsappLog('Erro Baileys HTTP ' . $httpCode . ': ' . $response, 'ERROR');
                return ['success' => false, 'message' => 'Erro ao enviar'];
            }
            
        } catch (Exception $e) {
            whatsappLog('Exceção Baileys: ' . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar via Z-API
     */
    private static function sendViaZAPI($message, $pdfPath) {
        try {
            $url = "https://api.z-api.io/instances/" . ZAPI_INSTANCE_ID . "/token/" . ZAPI_TOKEN . "/send-document";
            
            // Converter PDF para base64
            $pdfBase64 = base64_encode(file_get_contents($pdfPath));
            $fileName = basename($pdfPath);
            
            $payload = [
                'phone' => WHATSAPP_GROUP_ID,
                'document' => 'data:application/pdf;base64,' . $pdfBase64,
                'fileName' => $fileName,
                'caption' => $message
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Client-Token: ' . ZAPI_CLIENT_TOKEN
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, WHATSAPP_TIMEOUT);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                whatsappLog('Erro cURL Z-API: ' . $error, 'ERROR');
                return ['success' => false, 'message' => 'Erro de conexão: ' . $error];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                whatsappLog('PDF enviado com sucesso via Z-API', 'SUCCESS');
                return ['success' => true, 'message' => 'PDF enviado com sucesso'];
            } else {
                whatsappLog('Erro Z-API HTTP ' . $httpCode . ': ' . $response, 'ERROR');
                return ['success' => false, 'message' => 'Erro ao enviar: HTTP ' . $httpCode];
            }
            
        } catch (Exception $e) {
            whatsappLog('Exceção Z-API: ' . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar via Hocketzap
     */
    private static function sendViaHocketzap($message, $pdfPath) {
        try {
            $url = "https://api.hocketzap.com/message/sendFile/" . HOCKETZAP_INSTANCE_ID;
            
            // Converter PDF para base64
            $pdfBase64 = base64_encode(file_get_contents($pdfPath));
            $fileName = basename($pdfPath);
            
            $payload = [
                'number' => WHATSAPP_GROUP_ID,
                'mediaBase64' => $pdfBase64,
                'fileName' => $fileName,
                'caption' => $message
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . HOCKETZAP_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, WHATSAPP_TIMEOUT);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                whatsappLog('Erro cURL Hocketzap: ' . $error, 'ERROR');
                return ['success' => false, 'message' => 'Erro de conexão: ' . $error];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                whatsappLog('PDF enviado com sucesso via Hocketzap', 'SUCCESS');
                return ['success' => true, 'message' => 'PDF enviado com sucesso'];
            } else {
                whatsappLog('Erro Hocketzap HTTP ' . $httpCode . ': ' . $response, 'ERROR');
                return ['success' => false, 'message' => 'Erro ao enviar: HTTP ' . $httpCode];
            }
            
        } catch (Exception $e) {
            whatsappLog('Exceção Hocketzap: ' . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Upload de PDF para servidor público (necessário para Twilio)
     */
    private static function uploadPDFToPublicServer($pdfPath) {
        // Copiar PDF para pasta pública
        $publicDir = __DIR__ . '/../uploads/temp/';
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        $fileName = 'vistoria_' . time() . '_' . basename($pdfPath);
        $publicPath = $publicDir . $fileName;
        
        if (copy($pdfPath, $publicPath)) {
            // Retornar URL pública
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                       "://" . $_SERVER['HTTP_HOST'];
            return $baseUrl . '/uploads/temp/' . $fileName;
        }
        
        return false;
    }
    
    /**
     * Testar conexão com a API
     */
    public static function testConnection() {
        $validation = whatsappValidateConfig();
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        switch (WHATSAPP_API_TYPE) {
            case 'evolution':
                $url = rtrim(EVOLUTION_API_URL, '/') . '/instance/connectionState/' . EVOLUTION_INSTANCE_NAME;
                $headers = ['apikey: ' . EVOLUTION_API_KEY];
                break;
                
            case 'zapi':
                $url = "https://api.z-api.io/instances/" . ZAPI_INSTANCE_ID . "/token/" . ZAPI_TOKEN . "/status";
                $headers = ['Client-Token: ' . ZAPI_CLIENT_TOKEN];
                break;
                
            case 'hocketzap':
                $url = "https://api.hocketzap.com/status/" . HOCKETZAP_INSTANCE_ID;
                $headers = ['apikey: ' . HOCKETZAP_API_KEY];
                break;
                
            case 'twilio':
                $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . ".json";
                $headers = [];
                break;
                
            case 'baileys':
            case 'wppconnect':
                $url = rtrim(BAILEYS_API_URL, '/') . '/status';
                $headers = ['Authorization: Bearer ' . BAILEYS_API_TOKEN];
                break;
                
            default:
                return ['success' => false, 'message' => 'Tipo de API não suportado'];
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        if (WHATSAPP_API_TYPE === 'twilio') {
            curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Conexão OK'];
        } else {
            return ['success' => false, 'message' => 'Erro de conexão: HTTP ' . $httpCode];
        }
    }
}
?>
