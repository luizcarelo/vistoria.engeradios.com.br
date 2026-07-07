# Credenciais de Acesso - Sistema Vistoria Remota ENGERADIOS

## 🔐 Credenciais de Teste

### Administrador
- **E-mail:** admin@engeradios.com.br
- **Senha:** admin123
- **Permissões:** Acesso total ao sistema, cadastro de supervisores e clientes, visualização de todas as vistorias

### Supervisor (Teste)
- **E-mail:** supervisor@engeradios.com.br
- **Senha:** admin123
- **Permissões:** Criar vistorias, consultar vistorias próprias

## 📊 Dados de Teste Cadastrados

### Clientes
1. **Empresa ABC Ltda**
   - Endereço: Rua das Flores, 123 - Centro - São Paulo/SP
   - Telefone: (11) 3333-4444
   - E-mail: contato@empresaabc.com.br

2. **Comércio XYZ**
   - Endereço: Av. Principal, 456 - Jardim América - São Paulo/SP
   - Telefone: (11) 5555-6666
   - E-mail: comercio@xyz.com.br

3. **Indústria 123**
   - Endereço: Rodovia SP-123, Km 45 - Distrito Industrial - Guarulhos/SP
   - Telefone: (11) 7777-8888
   - E-mail: industria@123.com.br

## 🔄 Alteração de Senhas

Para alterar a senha de um usuário, execute no MySQL:

```sql
USE vistoria_engeradios;

-- Alterar senha do administrador
UPDATE usuarios 
SET senha = PASSWORD_HASH('nova_senha_aqui', PASSWORD_DEFAULT) 
WHERE email = 'admin@engeradios.com.br';
```

Ou utilize o seguinte script PHP:

```php
<?php
$nova_senha = 'sua_nova_senha';
$hash = password_hash($nova_senha, PASSWORD_DEFAULT);
echo "Hash da senha: " . $hash;
?>
```

## 📧 Configuração de E-mail

O sistema está configurado para enviar relatórios de vistoria para:
- **E-mail de destino:** operacional@engeradios.com.br

Para alterar, edite o arquivo `config.php`:

```php
define('EMAIL_DESTINO', 'seu_email@engeradios.com.br');
define('EMAIL_REMETENTE', 'noreply@engeradios.com.br');
```

## ⚠️ Segurança

**IMPORTANTE:**
1. Altere TODAS as senhas padrão imediatamente após a instalação
2. Use senhas fortes (mínimo 8 caracteres, letras maiúsculas, minúsculas, números e símbolos)
3. Não compartilhe as credenciais por e-mail ou mensagens não criptografadas
4. Faça backup regular do banco de dados
5. Mantenha o PHP e MySQL atualizados

## 🗄️ Banco de Dados

- **Nome do banco:** vistoria_engeradios
- **Usuário:** root (alterar em produção)
- **Senha:** (configurar em produção)
- **Host:** localhost

## 📱 Acesso Mobile

O sistema é totalmente responsivo e pode ser instalado como PWA (Progressive Web App) no celular dos supervisores.

### Como instalar no celular:

**Android (Chrome):**
1. Acesse o sistema pelo navegador
2. Menu (⋮) → "Adicionar à tela inicial"

**iOS (Safari):**
1. Acesse o sistema pelo navegador
2. Botão compartilhar → "Adicionar à Tela de Início"

---

**Data de criação:** 05/11/2025
**Sistema desenvolvido para:** ENGERADIOS - Segurança Eletrônica
