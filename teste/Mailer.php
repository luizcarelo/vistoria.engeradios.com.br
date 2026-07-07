<?php
// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carrega os ficheiros do PHPMailer manualmente
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

class Mailer
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        try {
            // Configurações do servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host       = 'smtp.office365.com';
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = 'suporte@engeradios.com.br';
            $this->mailer->Password   = 'Eng122024@';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = 587;

            // Configurações padrão do e-mail
            $this->mailer->setFrom('suporte@engeradios.com.br', 'Suporte EngeRadios');
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Erro ao configurar o PHPMailer: " . $e->getMessage());
        }
    }

    /**
     * Gera o corpo do email a partir de um template HTML.
     * @param array $dados Os dados para preencher no template.
     * @return string O HTML final do email.
     */
    private function gerarCorpoEmail($dados)
    {
        $templatePath = __DIR__ . '/../public/templates/email_template.php';
        if (!file_exists($templatePath)) {
            return $dados['corpo_html']; // Fallback para o corpo simples
        }

        $template = file_get_contents($templatePath);
        
        // Substitui os placeholders
        $template = str_replace('{{assunto}}', $dados['assunto'] ?? '', $template);
        $template = str_replace('{{saudacao}}', $dados['saudacao'] ?? '', $template);
        $template = str_replace('{{corpo_html}}', $dados['corpo_html'] ?? '', $template);
        $template = str_replace('{{detalhes_html}}', $dados['detalhes_html'] ?? '', $template);
        $template = str_replace('{{botao_html}}', $dados['botao_html'] ?? '', $template);

        return $template;
    }

    /**
     * Envia um email usando o template padrão.
     * @param string $paraEmail
     * @param string $paraNome
     * @param array $dadosEmail (contém 'assunto', 'saudacao', 'corpo_html', 'detalhes_html', 'botao_html')
     * @return bool
     */
    public function enviarEmail($paraEmail, $paraNome, $dadosEmail)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($paraEmail, $paraNome);
            
            $this->mailer->Subject = $dadosEmail['assunto'];
            $this->mailer->Body    = $this->gerarCorpoEmail($dadosEmail);
            $this->mailer->AltBody = strip_tags($dadosEmail['corpo_html']); // Versão em texto puro

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro do Mailer ao enviar para {$paraEmail}: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}
