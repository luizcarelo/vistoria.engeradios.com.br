#!/usr/bin/env python3
# -*- coding: ascii -*-

import hashlib
import json
import os
import shutil
import subprocess
import sys
from datetime import datetime
from pathlib import Path

ETAPA = "etapa1_senhas"

DOCS = [
    "CONTEXTO_PROJETO.md",
    "CHANGELOG.md",
    "DECISOES_TECNICAS.md",
    "PENDENCIAS.md",
]

BACKUP_ALVOS = [
    "index.php",
    "config.php",
    "admin/usuarios.php",
    "admin/alterar_senha.php",
    "admin/dashboard.php",
    "supervisor/dashboard.php",
]

GERADOS = [
    "trocar_senha.php",
    "esqueci_senha.php",
    "redefinir_senha.php",
    "admin/alterar_senha.php",
    "database_senhas_etapa1.sql",
]


def agora():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")


def stamp():
    return datetime.now().strftime("%Y%m%d_%H%M%S")


def fail(msg):
    print("[ERRO] " + msg)
    sys.exit(1)


def ok(msg):
    print("[OK] " + msg)


def sha256(path):
    p = Path(path)
    if not p.exists() or not p.is_file():
        return None
    h = hashlib.sha256()
    with p.open("rb") as f:
        for b in iter(lambda: f.read(65536), b""):
            h.update(b)
    return h.hexdigest()


def read(path):
    return Path(path).read_text(encoding="utf-8", errors="replace")


def write(path, text):
    p = Path(path)
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(text, encoding="utf-8", newline="\n")


def join(lines):
    return "\n".join(lines) + "\n"


def localizar_raiz():
    atual = Path.cwd()
    candidatos = [atual]
    for item in atual.iterdir():
        if item.is_dir():
            candidatos.append(item)

    for c in candidatos:
        if (c / "config.php").exists() and (c / "index.php").exists():
            if (c / "admin").exists() and (c / "supervisor").exists():
                return c.resolve()

    fail("Nao encontrei a raiz do projeto.")


def backup(root):
    pasta = root / "backups" / (ETAPA + "_" + stamp())
    pasta.mkdir(parents=True, exist_ok=True)
    copiados = []

    for rel in BACKUP_ALVOS + DOCS:
        src = root / rel
        if src.exists() and src.is_file():
            dst = pasta / rel
            dst.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(src, dst)
            copiados.append(rel)

    ok("Backup criado em " + str(pasta))
    return pasta, copiados


def validar_ascii_sem_asterisco(root):
    problemas = []

    for rel in GERADOS:
        p = root / rel
        if not p.exists():
            problemas.append(rel + ": nao existe")
            continue

        data = p.read_bytes()
        try:
            text = data.decode("ascii")
        except UnicodeDecodeError:
            problemas.append(rel + ": contem caractere nao ASCII")
            continue

        if "*" in text:
            problemas.append(rel + ": contem asterisco")

    if problemas:
        for p in problemas:
            print("[VALIDACAO] " + p)
        fail("Validacao anti-corrupcao falhou.")

    ok("Validacao ASCII e sem asterisco concluida.")


def inserir_bloco(texto, ini, fim, bloco, antes_de):
    if ini in texto and fim in texto:
        a = texto.find(ini)
        b = texto.find(fim, a) + len(fim)
        return texto[:a] + bloco + texto[b:]

    if antes_de in texto:
        pos = texto.find(antes_de)
        return texto[:pos] + bloco + "\n" + texto[pos:]

    return texto.rstrip() + "\n" + bloco + "\n"


def trocar_senha_php():
    return join([
        "<?php",
        "require_once 'config.php';",
        "verificarLogin();",
        "if (session_status() === PHP_SESSION_NONE) { session_start(); }",
        "$erros = [];",
        "$sucesso = '';",
        "$usuario_id = intval($_SESSION['usuario_id'] ?? 0);",
        "if ($usuario_id <= 0) { header('Location: index.php'); exit; }",
        "if ($_SERVER['REQUEST_METHOD'] === 'POST') {",
        "    $senha_atual = $_POST['senha_atual'] ?? '';",
        "    $nova_senha = $_POST['nova_senha'] ?? '';",
        "    $confirmar_senha = $_POST['confirmar_senha'] ?? '';",
        "    if ($senha_atual === '') { $erros[] = 'Informe a senha atual.'; }",
        "    if ($nova_senha === '') { $erros[] = 'Informe a nova senha.'; }",
        "    if (strlen($nova_senha) < 6) { $erros[] = 'A nova senha deve ter no minimo 6 caracteres.'; }",
        "    if ($nova_senha !== $confirmar_senha) { $erros[] = 'As senhas nao coincidem.'; }",
        "    if (empty($erros)) {",
        "        $conn = getDBConnection();",
        "        $stmt = $conn->prepare('SELECT senha FROM usuarios WHERE id = ? AND ativo = 1');",
        "        $stmt->bind_param('i', $usuario_id);",
        "        $stmt->execute();",
        "        $result = $stmt->get_result();",
        "        if ($result->num_rows === 0) {",
        "            $erros[] = 'Usuario nao encontrado ou inativo.';",
        "        } else {",
        "            $row = $result->fetch_assoc();",
        "            if (!password_verify($senha_atual, $row['senha'])) {",
        "                $erros[] = 'Senha atual incorreta.';",
        "            } else {",
        "                $hash = password_hash($nova_senha, PASSWORD_DEFAULT);",
        "                $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');",
        "                $up->bind_param('si', $hash, $usuario_id);",
        "                if ($up->execute()) {",
        "                    $sucesso = 'Senha alterada com sucesso.';",
        "                    error_log('Senha alterada pelo proprio usuario ID ' . $usuario_id);",
        "                } else {",
        "                    $erros[] = 'Erro ao salvar nova senha.';",
        "                }",
        "                $up->close();",
        "            }",
        "        }",
        "        $stmt->close();",
        "        $conn->close();",
        "    }",
        "}",
        "?>",
        "<!DOCTYPE html>",
        "<html lang=\"pt-BR\">",
        "<head>",
        "<meta charset=\"UTF-8\">",
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">",
        "<title>Trocar Senha - ENGERADIOS</title>",
        "<style>",
        "body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}",
        ".box{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}",
        "h1{margin-top:0;color:#b00020}",
        "label{display:block;margin-top:14px;font-weight:bold}",
        "input{width:100%;padding:12px;margin-top:6px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}",
        ".btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}",
        ".erro{background:#ffe8e8;border-left:4px solid #b00020;padding:12px;margin:12px 0}",
        ".sucesso{background:#e8fff0;border-left:4px solid #18864b;padding:12px;margin:12px 0}",
        ".link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}",
        "</style>",
        "</head>",
        "<body>",
        "<div class=\"box\">",
        "<h1>Trocar Senha</h1>",
        "<?php if (!empty($erros)): ?>",
        "<div class=\"erro\"><?php foreach ($erros as $e) { echo htmlspecialchars($e) . '<br>'; } ?></div>",
        "<?php endif; ?>",
        "<?php if ($sucesso !== ''): ?>",
        "<div class=\"sucesso\"><?php echo htmlspecialchars($sucesso); ?></div>",
        "<?php endif; ?>",
        "<form method=\"post\" autocomplete=\"off\">",
        "<label>Senha atual</label>",
        "<input type=\"password\" name=\"senha_atual\" required>",
        "<label>Nova senha</label>",
        "<input type=\"password\" name=\"nova_senha\" minlength=\"6\" required>",
        "<label>Confirmar nova senha</label>",
        "<input type=\"password\" name=\"confirmar_senha\" minlength=\"6\" required>",
        "<button class=\"btn\" type=\"submit\">Alterar senha</button>",
        "</form>",
        "\"javascript:history.back()\"Voltar</a>",
        "</div>",
        "</body>",
        "</html>",
    ])


def admin_alterar_senha_php():
    return join([
        "<?php",
        "require_once '../config.php';",
        "verificarAdmin();",
        "$usuario_id = intval($_GET['id'] ?? 0);",
        "if ($usuario_id <= 0) {",
        "    $_SESSION['erro'] = 'ID de usuario nao informado.';",
        "    header('Location: usuarios.php');",
        "    exit;",
        "}",
        "$conn = getDBConnection();",
        "$stmt = $conn->prepare('SELECT id, nome, email, tipo FROM usuarios WHERE id = ?');",
        "$stmt->bind_param('i', $usuario_id);",
        "$stmt->execute();",
        "$result = $stmt->get_result();",
        "if ($result->num_rows === 0) {",
        "    $_SESSION['erro'] = 'Usuario nao encontrado.';",
        "    header('Location: usuarios.php');",
        "    exit;",
        "}",
        "$usuario = $result->fetch_assoc();",
        "$stmt->close();",
        "$erros = [];",
        "if ($_SERVER['REQUEST_METHOD'] === 'POST') {",
        "    $nova_senha = $_POST['nova_senha'] ?? '';",
        "    $confirmar_senha = $_POST['confirmar_senha'] ?? '';",
        "    if ($nova_senha === '') { $erros[] = 'Nova senha e obrigatoria.'; }",
        "    if (strlen($nova_senha) < 6) { $erros[] = 'Senha deve ter no minimo 6 caracteres.'; }",
        "    if ($nova_senha !== $confirmar_senha) { $erros[] = 'As senhas nao coincidem.'; }",
        "    if (empty($erros)) {",
        "        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);",
        "        $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');",
        "        $up->bind_param('si', $hash, $usuario_id);",
        "        if ($up->execute()) {",
        "            $admin = $_SESSION['usuario_nome'] ?? 'admin';",
        "            error_log('Senha do usuario ID ' . $usuario_id . ' alterada por ' . $admin);",
        "            $_SESSION['sucesso'] = 'Senha alterada com sucesso.';",
        "            header('Location: usuarios.php');",
        "            exit;",
        "        } else {",
        "            $erros[] = 'Erro ao alterar senha.';",
        "        }",
        "        $up->close();",
        "    }",
        "}",
        "$conn->close();",
        "?>",
        "<!DOCTYPE html>",
        "<html lang=\"pt-BR\">",
        "<head>",
        "<meta charset=\"UTF-8\">",
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">",
        "<title>Alterar Senha - ENGERADIOS</title>",
        "<style>",
        "body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}",
        ".box{max-width:560px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}",
        "h1{margin-top:0;color:#b00020}",
        ".dados{background:#f7f7f7;padding:12px;border-radius:6px;margin-bottom:18px}",
        "label{display:block;margin-top:14px;font-weight:bold}",
        "input{width:100%;padding:12px;margin-top:6px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}",
        ".btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}",
        ".erro{background:#ffe8e8;border-left:4px solid #b00020;padding:12px;margin:12px 0}",
        ".link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}",
        "</style>",
        "</head>",
        "<body>",
        "<div class=\"box\">",
        "<h1>Alterar Senha</h1>",
        "<div class=\"dados\">",
        "<strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['nome']); ?><br>",
        "<strong>E-mail:</strong> <?php echo htmlspecialchars($usuario['email']); ?><br>",
        "<strong>Tipo:</strong> <?php echo htmlspecialchars($usuario['tipo']); ?>",
        "</div>",
        "<?php if (!empty($erros)): ?>",
        "<div class=\"erro\"><?php foreach ($erros as $e) { echo htmlspecialchars($e) . '<br>'; } ?></div>",
        "<?php endif; ?>",
        "<form method=\"post\" autocomplete=\"off\">",
        "<label>Nova senha</label>",
        "<input type=\"password\" name=\"nova_senha\" minlength=\"6\" required>",
        "<label>Confirmar nova senha</label>",
        "<input type=\"password\" name=\"confirmar_senha\" minlength=\"6\" required>",
        "<button class=\"btn\" type=\"submit\">Alterar senha</button>",
        "</form>",
        "\"usuarios.php\"Voltar para usuarios</a>",
        "</div>",
        "</body>",
        "</html>",
    ])


def esqueci_senha_php():
    return join([
        "<?php",
        "require_once 'config.php';",
        "$mensagem = '';",
        "function garantirTabelaRecuperacao($conn) {",
        "    $sql = \"CREATE TABLE IF NOT EXISTS recuperacao_senha (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, token_hash VARCHAR(255) NOT NULL, expira_em DATETIME NOT NULL, usado_em DATETIME NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ip_solicitante VARCHAR(45) NULL, INDEX idx_token_hash (token_hash), INDEX idx_usuario_id (usuario_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\";",
        "    $conn->query($sql);",
        "}",
        "function baseUrlSistema() {",
        "    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');",
        "    $scheme = $https ? 'https' : 'http';",
        "    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';",
        "    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\\\');",
        "    return $scheme . '://' . $host . $dir;",
        "}",
        "if ($_SERVER['REQUEST_METHOD'] === 'POST') {",
        "    $email = trim($_POST['email'] ?? '');",
        "    $mensagem = 'Se o e-mail estiver cadastrado, enviaremos as instrucoes de redefinicao.';",
        "    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {",
        "        $conn = getDBConnection();",
        "        garantirTabelaRecuperacao($conn);",
        "        $stmt = $conn->prepare('SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');",
        "        $stmt->bind_param('s', $email);",
        "        $stmt->execute();",
        "        $result = $stmt->get_result();",
        "        if ($result->num_rows === 1) {",
        "            $usuario = $result->fetch_assoc();",
        "            $token = bin2hex(random_bytes(32));",
        "            $token_hash = hash('sha256', $token);",
        "            $ip = $_SERVER['REMOTE_ADDR'] ?? '';",
        "            $ins = $conn->prepare('INSERT INTO recuperacao_senha (usuario_id, token_hash, expira_em, ip_solicitante) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)');",
        "            $ins->bind_param('iss', $usuario['id'], $token_hash, $ip);",
        "            if ($ins->execute()) {",
        "                $link = baseUrlSistema() . '/redefinir_senha.php?token=' . urlencode($token);",
        "                $assunto = 'Redefinicao de senha - Vistoria Engeradios';",
        "                $corpo = 'Ola, ' . $usuario['nome'] . \"\\n\\n\";",
        "                $corpo .= 'Para redefinir sua senha, acesse o link abaixo:' . \"\\n\";",
        "                $corpo .= $link . \"\\n\\n\";",
        "                $corpo .= 'Este link expira em 1 hora. Se voce nao solicitou, ignore esta mensagem.';",
        "                $headers = 'From: noreply@engeradios.com.br' . \"\\r\\n\";",
        "                if (!@mail($usuario['email'], $assunto, $corpo, $headers)) {",
        "                    error_log('Falha ao enviar e-mail de recuperacao para usuario ID ' . $usuario['id']);",
        "                }",
        "            }",
        "            $ins->close();",
        "        }",
        "        $stmt->close();",
        "        $conn->close();",
        "    }",
        "}",
        "?>",
        "<!DOCTYPE html>",
        "<html lang=\"pt-BR\">",
        "<head>",
        "<meta charset=\"UTF-8\">",
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">",
        "<title>Esqueci Minha Senha - ENGERADIOS</title>",
        "<style>",
        "body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}",
        ".box{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}",
        "h1{margin-top:0;color:#b00020}",
        "input{width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}",
        ".btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}",
        ".msg{background:#eef6ff;border-left:4px solid #2b70b8;padding:12px;margin:12px 0}",
        ".link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}",
        "</style>",
        "</head>",
        "<body>",
        "<div class=\"box\">",
        "<h1>Esqueci Minha Senha</h1>",
        "<?php if ($mensagem !== ''): ?><div class=\"msg\"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>",
        "<form method=\"post\" autocomplete=\"off\">",
        "<label>E-mail cadastrado</label>",
        "<input type=\"email\" name=\"email\" required>",
        "<button class=\"btn\" type=\"submit\">Enviar instrucoes</button>",
        "</form>",
        "\"index.php\"Voltar ao login</a>",
        "</div>",
        "</body>",
        "</html>",
    ])


def redefinir_senha_php():
    return join([
        "<?php",
        "require_once 'config.php';",
        "$token = $_GET['token'] ?? $_POST['token'] ?? '';",
        "$erros = [];",
        "$sucesso = '';",
        "function buscarTokenValido($conn, $token) {",
        "    if ($token === '') { return null; }",
        "    $hash = hash('sha256', $token);",
        "    $stmt = $conn->prepare('SELECT r.id, r.usuario_id FROM recuperacao_senha r JOIN usuarios u ON u.id = r.usuario_id WHERE r.token_hash = ? AND r.usado_em IS NULL AND r.expira_em >= NOW() AND u.ativo = 1 LIMIT 1');",
        "    $stmt->bind_param('s', $hash);",
        "    $stmt->execute();",
        "    $result = $stmt->get_result();",
        "    $row = $result->num_rows === 1 ? $result->fetch_assoc() : null;",
        "    $stmt->close();",
        "    return $row;",
        "}",
        "$conn = getDBConnection();",
        "$registro = buscarTokenValido($conn, $token);",
        "if ($_SERVER['REQUEST_METHOD'] === 'POST') {",
        "    $nova_senha = $_POST['nova_senha'] ?? '';",
        "    $confirmar_senha = $_POST['confirmar_senha'] ?? '';",
        "    if (!$registro) { $erros[] = 'Link invalido ou expirado.'; }",
        "    if ($nova_senha === '') { $erros[] = 'Informe a nova senha.'; }",
        "    if (strlen($nova_senha) < 6) { $erros[] = 'Senha deve ter no minimo 6 caracteres.'; }",
        "    if ($nova_senha !== $confirmar_senha) { $erros[] = 'As senhas nao coincidem.'; }",
        "    if (empty($erros)) {",
        "        $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);",
        "        $up = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');",
        "        $up->bind_param('si', $hash_senha, $registro['usuario_id']);",
        "        if ($up->execute()) {",
        "            $mk = $conn->prepare('UPDATE recuperacao_senha SET usado_em = NOW() WHERE id = ?');",
        "            $mk->bind_param('i', $registro['id']);",
        "            $mk->execute();",
        "            $mk->close();",
        "            $sucesso = 'Senha redefinida com sucesso.';",
        "            $registro = null;",
        "        } else {",
        "            $erros[] = 'Erro ao salvar nova senha.';",
        "        }",
        "        $up->close();",
        "    }",
        "}",
        "$conn->close();",
        "?>",
        "<!DOCTYPE html>",
        "<html lang=\"pt-BR\">",
        "<head>",
        "<meta charset=\"UTF-8\">",
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">",
        "<title>Redefinir Senha - ENGERADIOS</title>",
        "<style>",
        "body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;color:#222}",
        ".box{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 4px 18px rgba(0,0,0,.12)}",
        "h1{margin-top:0;color:#b00020}",
        "input{width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}",
        ".btn{margin-top:18px;background:#b00020;color:#fff;border:0;border-radius:6px;padding:12px 18px;cursor:pointer;font-weight:bold}",
        ".erro{background:#ffe8e8;border-left:4px solid #b00020;padding:12px;margin:12px 0}",
        ".sucesso{background:#e8fff0;border-left:4px solid #18864b;padding:12px;margin:12px 0}",
        ".link{display:inline-block;margin-top:16px;color:#555;text-decoration:none}",
        "</style>",
        "</head>",
        "<body>",
        "<div class=\"box\">",
        "<h1>Redefinir Senha</h1>",
        "<?php if (!empty($erros)): ?><div class=\"erro\"><?php foreach ($erros as $e) { echo htmlspecialchars($e) . '<br>'; } ?></div><?php endif; ?>",
        "<?php if ($sucesso !== ''): ?><div class=\"sucesso\"><?php echo htmlspecialchars($sucesso); ?></div>\"index.php\"Ir para o login</a><?php endif; ?>",
        "<?php if ($sucesso === '' && $registro): ?>",
        "<form method=\"post\" autocomplete=\"off\">",
        "<input type=\"hidden\" name=\"token\" value=\"<?php echo htmlspecialchars($token); ?>\">",
        "<label>Nova senha</label>",
        "<input type=\"password\" name=\"nova_senha\" minlength=\"6\" required>",
        "<label>Confirmar nova senha</label>",
        "<input type=\"password\" name=\"confirmar_senha\" minlength=\"6\" required>",
        "<button class=\"btn\" type=\"submit\">Redefinir senha</button>",
        "</form>",
        "<?php elseif ($sucesso === ''): ?>",
        "<div class=\"erro\">Link invalido ou expirado.</div>",
        "\"esqueci_senha.php\"Solicitar novo link</a>",
        "<?php endif; ?>",
        "</div>",
        "</body>",
        "</html>",
    ])


def sql_migracao():
    return join([
        "-- Etapa 1 Senhas",
        "CREATE TABLE IF NOT EXISTS recuperacao_senha (",
        "  id INT AUTO_INCREMENT PRIMARY KEY,",
        "  usuario_id INT NOT NULL,",
        "  token_hash VARCHAR(255) NOT NULL,",
        "  expira_em DATETIME NOT NULL,",
        "  usado_em DATETIME NULL,",
        "  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,",
        "  ip_solicitante VARCHAR(45) NULL,",
        "  INDEX idx_token_hash (token_hash),",
        "  INDEX idx_usuario_id (usuario_id)",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ])


def patch_index(root):
    p = root / "index.php"
    if not p.exists():
        return False

    text = read(p)
    bloco = join([
        '<!-- INICIO ETAPA1 SENHAS -->',
        '<div style="text-align:center;margin-top:12px">',
        'esqueci_senha.phpEsqueci minha senha</a>',
        '</div>',
        '<!-- FIM ETAPA1 SENHAS -->',
    ])

    novo = inserir_bloco(
        text,
        '<!-- INICIO ETAPA1 SENHAS -->',
        '<!-- FIM ETAPA1 SENHAS -->',
        bloco,
        '</form>',
    )

    if novo != text:
        write(p, novo)
        return True

    return False


def patch_dashboard(path):
    if not path.exists():
        return False

    text = read(path)
    rel = '../trocar_senha.php'

    bloco = join([
        '<!-- INICIO ETAPA1 TROCAR SENHA -->',
        '' + rel + 'Trocar senha</a>',
        '<!-- FIM ETAPA1 TROCAR SENHA -->',
    ])

    novo = inserir_bloco(
        text,
        '<!-- INICIO ETAPA1 TROCAR SENHA -->',
        '<!-- FIM ETAPA1 TROCAR SENHA -->',
        bloco,
        'Sair',
    )

    if novo != text:
        write(path, novo)
        return True

    return False


def atualizar_docs(root):
    secao = join([
        "",
        "## Etapa 1 Senhas - " + agora(),
        "",
        "Alteracao aprovada e aplicada para o modulo de senhas.",
        "",
        "Itens contemplados:",
        "- Troca de senha pelo usuario logado em trocar_senha.php.",
        "- Reforco da troca de senha pelo administrador em admin/alterar_senha.php.",
        "- Link Esqueci minha senha na tela de login.",
        "- Recuperacao por token temporario em esqueci_senha.php e redefinir_senha.php.",
        "- Criacao automatica da tabela recuperacao_senha quando necessario.",
        "- Backup e manifesto gerados pelo script aplicar_etapa1_senhas.py.",
    ])

    for doc in DOCS:
        p = root / doc
        atual = read(p) if p.exists() else "# " + doc + "\n"
        write(p, atual.rstrip() + "\n" + secao)


def php_lint(root):
    php = shutil.which("php")
    if not php:
        return ["PHP CLI nao encontrado. Validacao php -l ignorada."]

    resultados = []
    for rel in [
        "trocar_senha.php",
        "esqueci_senha.php",
        "redefinir_senha.php",
        "admin/alterar_senha.php",
    ]:
        p = root / rel
        proc = subprocess.run([php, "-l", str(p)], capture_output=True, text=True)
        if proc.returncode == 0:
            resultados.append(rel + ": sintaxe OK")
        else:
            resultados.append(rel + ": ERRO: " + proc.stderr.strip())

    return resultados


def main():
    root = localizar_raiz()
    os.chdir(root)
    ok("Raiz encontrada: " + str(root))

    antes = {}
    for rel in BACKUP_ALVOS + DOCS:
        antes[rel] = sha256(root / rel)

    backup_dir, copiados = backup(root)

    write(root / "trocar_senha.php", trocar_senha_php())
    write(root / "esqueci_senha.php", esqueci_senha_php())
    write(root / "redefinir_senha.php", redefinir_senha_php())
    write(root / "admin" / "alterar_senha.php", admin_alterar_senha_php())
    write(root / "database_senhas_etapa1.sql", sql_migracao())

    patchados = []
    if patch_index(root):
        patchados.append("index.php")
    if patch_dashboard(root / "admin" / "dashboard.php"):
        patchados.append("admin/dashboard.php")
    if patch_dashboard(root / "supervisor" / "dashboard.php"):
        patchados.append("supervisor/dashboard.php")

    atualizar_docs(root)
    validar_ascii_sem_asterisco(root)
    lint = php_lint(root)

    depois = {}
    todos = sorted(set(BACKUP_ALVOS + DOCS + GERADOS + patchados))
    for rel in todos:
        depois[rel] = sha256(root / rel)

    manifesto = {
        "etapa": ETAPA,
        "data": agora(),
        "raiz": str(root),
        "backup": str(backup_dir),
        "arquivos_backup": copiados,
        "arquivos_gerados": GERADOS,
        "arquivos_patcheados": patchados,
        "hash_antes": antes,
        "hash_depois": depois,
        "validacao": {
            "ascii_sem_asterisco": True,
            "php_lint": lint,
        },
    }

    manifesto_path = root / ("MANIFESTO_" + ETAPA + "_" + stamp() + ".json")
    write(manifesto_path, json.dumps(manifesto, indent=2, ensure_ascii=True))

    ok("Etapa 1 concluida.")
    ok("Manifesto: " + str(manifesto_path))

    print("")
    print("Resumo:")
    print("- Backup: " + str(backup_dir))
    print("- Criado: trocar_senha.php")
    print("- Criado: esqueci_senha.php")
    print("- Criado: redefinir_senha.php")
    print("- Refeito: admin/alterar_senha.php")
    print("- Criado: database_senhas_etapa1.sql")
    print("- Atualizados: " + ", ".join(DOCS))
    print("")
    print("Validacao PHP:")
    for item in lint:
        print("- " + item)


if __name__ == "__main__":
    main()