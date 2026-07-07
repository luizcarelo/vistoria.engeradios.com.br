# Sistema de Vistoria Remota ENGERADIOS

Sistema web desenvolvido em PHP e MySQL para gestão de vistorias remotas realizadas por supervisores de campo.

## 📋 Funcionalidades

### Painel Administrativo
- Login seguro com autenticação
- Cadastro e gerenciamento de supervisores
- Cadastro e gerenciamento de clientes
- Visualização de todas as vistorias realizadas
- Filtros por data, cliente e supervisor
- Dashboard com estatísticas

### Painel do Supervisor
- Login seguro com autenticação
- Criação de novas vistorias
- Seleção de cliente
- Campo de laudo (texto)
- Campo de orçamento de adequação (texto)
- Upload de múltiplas fotos
- Upload de áudio (até 3 minutos)
- Consulta de vistorias realizadas com filtros
- Visualização detalhada de vistorias anteriores

### Recursos Técnicos
- Envio automático de e-mail ao concluir vistoria para operacional@engeradios.com.br
- Interface responsiva para uso em celulares e tablets
- Suporte a PWA (Progressive Web App) para instalação no celular
- Upload seguro de arquivos
- Armazenamento organizado de fotos e áudios
- Banco de dados MySQL com relacionamentos

## 🚀 Requisitos do Servidor

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache 2.4 ou superior
- Extensões PHP necessárias:
  - mysqli
  - gd
  - mbstring

## 📦 Instalação

### 1. Configurar Banco de Dados

Execute o arquivo `database.sql` no MySQL:

```bash
mysql -u root -p < database.sql
```

Ou importe manualmente através do phpMyAdmin.

### 2. Configurar Credenciais

Edite o arquivo `config.php` e configure:

- Credenciais do banco de dados (DB_HOST, DB_USER, DB_PASS, DB_NAME)
- E-mail de destino (EMAIL_DESTINO)
- E-mail remetente (EMAIL_REMETENTE)

### 3. Configurar Permissões

```bash
chmod -R 755 /caminho/para/vistoria-engeradios
chmod -R 777 /caminho/para/vistoria-engeradios/uploads
```

### 4. Configurar Apache

Crie um VirtualHost ou configure o DocumentRoot para apontar para o diretório do sistema.

Exemplo de configuração:

```apache
<VirtualHost *:80>
    ServerName vistoria.engeradios.com.br
    DocumentRoot /var/www/html/vistoria-engeradios
    
    <Directory /var/www/html/vistoria-engeradios>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/vistoria-error.log
    CustomLog ${APACHE_LOG_DIR}/vistoria-access.log combined
</VirtualHost>
```

Reinicie o Apache:

```bash
sudo service apache2 restart
```

## 🔐 Credenciais Padrão

**Administrador:**
- E-mail: `admin@engeradios.com.br`
- Senha: `admin123`

**IMPORTANTE:** Altere a senha padrão imediatamente após o primeiro acesso!

## 📱 Instalação no Celular (PWA)

### Android (Chrome)
1. Acesse o sistema pelo navegador Chrome
2. Toque no menu (três pontos)
3. Selecione "Adicionar à tela inicial"
4. Confirme a instalação

### iOS (Safari)
1. Acesse o sistema pelo Safari
2. Toque no ícone de compartilhamento
3. Selecione "Adicionar à Tela de Início"
4. Confirme a instalação

## 📧 Configuração de E-mail

O sistema utiliza a função `mail()` do PHP. Para produção, recomenda-se configurar um servidor SMTP.

Para configurar SMTP, você pode usar bibliotecas como PHPMailer. Edite a função `enviarEmailVistoria()` no arquivo `supervisor/processar_vistoria.php`.

## 🗂️ Estrutura de Diretórios

```
vistoria-engeradios/
├── admin/                      # Painel administrativo
│   ├── dashboard.php
│   ├── supervisores.php
│   ├── clientes.php
│   ├── vistorias.php
│   └── detalhes_vistoria_admin.php
├── supervisor/                 # Painel do supervisor
│   ├── dashboard.php
│   ├── nova_vistoria.php
│   ├── processar_vistoria.php
│   ├── consultar_vistorias.php
│   └── detalhes_vistoria.php
├── uploads/                    # Arquivos enviados
│   ├── fotos/
│   └── audios/
├── config.php                  # Configurações do sistema
├── index.php                   # Página de login
├── logout.php                  # Logout
├── database.sql                # Script do banco de dados
├── Logooriginal.png           # Logo da empresa
└── README.md                   # Este arquivo
```

## 🗄️ Estrutura do Banco de Dados

### Tabelas

- **usuarios**: Administradores e supervisores
- **clientes**: Clientes cadastrados
- **vistorias**: Relatórios de vistoria
- **vistoria_fotos**: Fotos das vistorias
- **vistoria_audios**: Áudios das vistorias

## 🔧 Manutenção

### Backup do Banco de Dados

```bash
mysqldump -u root -p vistoria_engeradios > backup_$(date +%Y%m%d).sql
```

### Limpeza de Arquivos Antigos

Para liberar espaço, você pode criar um script para remover arquivos de vistorias antigas:

```bash
find /caminho/para/uploads -type f -mtime +365 -delete
```

## 🐛 Solução de Problemas

### Erro ao fazer upload de arquivos

Verifique as configurações do PHP:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
```

### E-mails não estão sendo enviados

1. Verifique se a função `mail()` está habilitada no PHP
2. Configure um servidor SMTP
3. Verifique os logs do servidor

### Erro de permissão ao salvar arquivos

```bash
chmod -R 777 /caminho/para/vistoria-engeradios/uploads
chown -R www-data:www-data /caminho/para/vistoria-engeradios/uploads
```

## 📞 Suporte

Para suporte técnico, entre em contato com o desenvolvedor do sistema.

## 📄 Licença

Sistema desenvolvido exclusivamente para ENGERADIOS - Segurança Eletrônica.

---

© 2025 ENGERADIOS - Todos os direitos reservados.
