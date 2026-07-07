#!/usr/bin/env python3
# -*- coding: ascii -*-

import hashlib
import json
import os
import re
from datetime import datetime
from pathlib import Path

NOME_RELATORIO_MD = "RELATORIO_MAPEAMENTO_SISTEMA.md"
NOME_RELATORIO_JSON = "RELATORIO_MAPEAMENTO_SISTEMA.json"
NOME_MANIFESTO = "MANIFESTO_MAPEAMENTO_SISTEMA.json"

IGNORAR_DIRS = {
    ".git",
    ".idea",
    ".vscode",
    "node_modules",
    "vendor",
    "backups",
    "backup",
    "tmp",
    "temp",
    "cache",
    "logs",
    "uploads",
}

EXT_TEXTO = {
    ".php",
    ".html",
    ".htm",
    ".css",
    ".js",
    ".json",
    ".md",
    ".txt",
    ".sql",
    ".ini",
    ".htaccess",
    ".xml",
    ".yml",
    ".yaml",
    ".py",
}

PADROES = {
    "login_sessao": [
        "session_start",
        "verificarLogin",
        "verificarAdmin",
        "usuario_id",
        "usuario_tipo",
    ],
    "senhas": [
        "password_hash",
        "password_verify",
        "senha",
        "recuperacao_senha",
        "token_hash",
    ],
    "banco_dados": [
        "mysqli",
        "getDBConnection",
        "SELECT ",
        "INSERT ",
        "UPDATE ",
        "DELETE ",
        "CREATE TABLE",
    ],
    "uploads": [
        "$_FILES",
        "move_uploaded_file",
        "UPLOAD_DIR",
        "fotos",
        "audios",
    ],
    "email": [
        "mail(",
        "SMTP",
        "EMAIL_DESTINO",
        "EMAIL_REMETENTE",
        "config_smtp",
    ],
    "whatsapp": [
        "WHATSAPP",
        "Evolution",
        "ZAPI",
        "Twilio",
        "Hocketzap",
    ],
    "pdf": [
        "FPDF",
        "gerar_pdf",
        "Output(",
        "Image(",
    ],
    "seguranca_risco": [
        "$_GET",
        "$_POST",
        "real_escape_string",
        "prepare(",
        "bind_param",
        "eval(",
        "shell_exec",
        "exec(",
        "system(",
    ],
}


def agora():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")


def sha256(path):
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for bloco in iter(lambda: f.read(65536), b""):
            h.update(bloco)
    return h.hexdigest()


def tamanho_humano(n):
    unidades = ["B", "KB", "MB", "GB"]
    v = float(n)
    for u in unidades:
        if v < 1024 or u == unidades[-1]:
            return "%.2f %s" % (v, u)
        v = v / 1024
    return str(n) + " B"


def ler_texto(path, limite=300000):
    try:
        data = Path(path).read_bytes()
        if len(data) > limite:
            data = data[:limite]
        return data.decode("utf-8", errors="replace")
    except Exception:
        return ""


def eh_texto(path):
    p = Path(path)
    if p.name == ".htaccess":
        return True
    return p.suffix.lower() in EXT_TEXTO


def listar_arquivos(root):
    arquivos = []
    for base, dirs, files in os.walk(root):
        dirs[:] = [d for d in dirs if d not in IGNORAR_DIRS]
        for nome in files:
            caminho = Path(base) / nome
            rel = caminho.relative_to(root).as_posix()
            if rel in [NOME_RELATORIO_MD, NOME_RELATORIO_JSON, NOME_MANIFESTO]:
                continue
            arquivos.append(caminho)
    return sorted(arquivos, key=lambda p: p.as_posix().lower())


def detectar_funcoes_php(texto):
    funcoes = re.findall(r"function\s+([A-Za-z0-9_]+)\s*\(", texto)
    return sorted(set(funcoes))


def detectar_includes(texto):
    padrao = r"(?:require|require_once|include|include_once)\s*[^'\"]+['\"]"
    return sorted(set(re.findall(padrao, texto)))


def detectar_tabelas_sql(texto):
    tabelas = set()
    padroes = [
        r"CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?",
        r"FROM\s+`?([A-Za-z0-9_]+)`?",
        r"JOIN\s+`?([A-Za-z0-9_]+)`?",
        r"INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?",
        r"UPDATE\s+`?([A-Za-z0-9_]+)`?",
    ]

    for pat in padroes:
        for m in re.findall(pat, texto, flags=re.IGNORECASE):
            tabelas.add(m)

    return sorted(tabelas)


def contar_linhas(texto):
    if texto == "":
        return 0
    return texto.count("\n") + 1


def analisar_arquivo(root, path):
    rel = path.relative_to(root).as_posix()
    stat = path.stat()

    item = {
        "arquivo": rel,
        "extensao": path.suffix.lower() if path.name != ".htaccess" else ".htaccess",
        "tamanho_bytes": stat.st_size,
        "tamanho": tamanho_humano(stat.st_size),
        "sha256": sha256(path),
        "texto": eh_texto(path),
        "linhas": 0,
        "categorias": [],
        "funcoes_php": [],
        "includes": [],
        "tabelas_sql": [],
        "achados": {},
    }

    if not item["texto"]:
        return item

    texto = ler_texto(path)
    item["linhas"] = contar_linhas(texto)

    if path.suffix.lower() == ".php":
        item["funcoes_php"] = detectar_funcoes_php(texto)
        item["includes"] = detectar_includes(texto)

    if path.suffix.lower() in [".php", ".sql"]:
        item["tabelas_sql"] = detectar_tabelas_sql(texto)

    for categoria, padroes in PADROES.items():
        encontrados = []
        for padrao in padroes:
            if padrao.lower() in texto.lower():
                encontrados.append(padrao)
        if encontrados:
            item["categorias"].append(categoria)
            item["achados"][categoria] = encontrados

    return item


def agrupar_por_extensao(itens):
    resumo = {}

    for item in itens:
        ext = item["extensao"] or "sem_extensao"
        if ext not in resumo:
            resumo[ext] = {
                "quantidade": 0,
                "bytes": 0,
            }
        resumo[ext]["quantidade"] += 1
        resumo[ext]["bytes"] += item["tamanho_bytes"]

    for ext in resumo:
        resumo[ext]["tamanho"] = tamanho_humano(resumo[ext]["bytes"])

    return dict(sorted(resumo.items()))


def agrupar_por_categoria(itens):
    resumo = {}

    for item in itens:
        for cat in item["categorias"]:
            resumo.setdefault(cat, []).append(item["arquivo"])

    return resumo


def markdown(dados):
    linhas = []

    linhas.append("# Relatorio de Mapeamento do Sistema")
    linhas.append("")
    linhas.append("Gerado em: " + dados["gerado_em"])
    linhas.append("Raiz analisada: `" + dados["raiz"] + "`")
    linhas.append("")

    linhas.append("## Resumo")
    linhas.append("")
    linhas.append("- Total de arquivos analisados: " + str(dados["total_arquivos"]))
    linhas.append("- Arquivos de texto: " + str(dados["total_texto"]))
    linhas.append("- Arquivos binarios/outros: " + str(dados["total_binarios"]))
    linhas.append("- Tamanho total analisado: " + dados["tamanho_total"])
    linhas.append("")

    linhas.append("## Arquivos por extensao")
    linhas.append("")
    linhas.append("| Extensao | Quantidade | Tamanho |")
    linhas.append("|---|---:|---:|")
    for ext, info in dados["por_extensao"].items():
        linhas.append(
            "| `" + ext + "` | " +
            str(info["quantidade"]) +
            " | " +
            info["tamanho"] +
            " |"
        )
    linhas.append("")

    linhas.append("## Categorias detectadas")
    linhas.append("")
    for cat, arquivos in dados["por_categoria"].items():
        linhas.append("### " + cat)
        linhas.append("")
        for arq in arquivos:
            linhas.append("- `" + arq + "`")
        linhas.append("")

    linhas.append("## Arquivos PHP com funcoes")
    linhas.append("")
    for item in dados["arquivos"]:
        if item["funcoes_php"]:
            linhas.append("### `" + item["arquivo"] + "`")
            for fn in item["funcoes_php"]:
                linhas.append("- " + fn)
            linhas.append("")

    linhas.append("## Includes e dependencias PHP")
    linhas.append("")
    for item in dados["arquivos"]:
        if item["includes"]:
            linhas.append("### `" + item["arquivo"] + "`")
            for inc in item["includes"]:
                linhas.append("- `" + inc + "`")
            linhas.append("")

    linhas.append("## Tabelas SQL mencionadas")
    linhas.append("")
    tabelas = {}
    for item in dados["arquivos"]:
        for tab in item["tabelas_sql"]:
            tabelas.setdefault(tab, []).append(item["arquivo"])

    for tab in sorted(tabelas):
        linhas.append("### `" + tab + "`")
        for arq in sorted(set(tabelas[tab])):
            linhas.append("- `" + arq + "`")
        linhas.append("")

    linhas.append("## Inventario detalhado")
    linhas.append("")
    linhas.append("| Arquivo | Tipo | Linhas | Tamanho | Categorias |")
    linhas.append("|---|---|---:|---:|---|")

    for item in dados["arquivos"]:
        cats = ", ".join(item["categorias"]) if item["categorias"] else "-"
        tipo = "texto" if item["texto"] else "binario/outro"
        linhas.append(
            "| `" + item["arquivo"] + "` | " +
            tipo +
            " | " +
            str(item["linhas"]) +
            " | " +
            item["tamanho"] +
            " | " +
            cats +
            " |"
        )

    linhas.append("")
    linhas.append("## Observacoes")
    linhas.append("")
    linhas.append("- Pastas ignoradas: `" + "`, `".join(sorted(IGNORAR_DIRS)) + "`")
    linhas.append("- A pasta `uploads` foi ignorada para evitar relatorio gigante com anexos de vistorias.")
    linhas.append("- O relatorio identifica padroes por busca textual. Ele nao substitui auditoria manual de seguranca.")
    linhas.append("")

    return "\n".join(linhas) + "\n"


def main():
    root = Path.cwd().resolve()

    arquivos = listar_arquivos(root)
    itens = []
    total_bytes = 0

    for path in arquivos:
        try:
            item = analisar_arquivo(root, path)
            itens.append(item)
            total_bytes += item["tamanho_bytes"]
        except Exception as e:
            rel = path.relative_to(root).as_posix()
            itens.append({
                "arquivo": rel,
                "erro": str(e),
                "extensao": path.suffix.lower(),
                "tamanho_bytes": 0,
                "tamanho": "0 B",
                "sha256": None,
                "texto": False,
                "linhas": 0,
                "categorias": ["erro_analise"],
                "funcoes_php": [],
                "includes": [],
                "tabelas_sql": [],
                "achados": {},
            })

    dados = {
        "gerado_em": agora(),
        "raiz": str(root),
        "total_arquivos": len(itens),
        "total_texto": sum(1 for i in itens if i.get("texto")),
        "total_binarios": sum(1 for i in itens if not i.get("texto")),
        "tamanho_total_bytes": total_bytes,
        "tamanho_total": tamanho_humano(total_bytes),
        "por_extensao": agrupar_por_extensao(itens),
        "por_categoria": agrupar_por_categoria(itens),
        "arquivos": itens,
    }

    Path(NOME_RELATORIO_JSON).write_text(
        json.dumps(dados, indent=2, ensure_ascii=True),
        encoding="utf-8",
        newline="\n",
    )

    Path(NOME_RELATORIO_MD).write_text(
        markdown(dados),
        encoding="utf-8",
        newline="\n",
    )

    manifesto = {
        "script": "mapear_sistema_vistoria.py",
        "gerado_em": dados["gerado_em"],
        "relatorio_md": NOME_RELATORIO_MD,
        "relatorio_json": NOME_RELATORIO_JSON,
        "total_arquivos": dados["total_arquivos"],
        "sha256_md": sha256(NOME_RELATORIO_MD),
        "sha256_json": sha256(NOME_RELATORIO_JSON),
    }

    Path(NOME_MANIFESTO).write_text(
        json.dumps(manifesto, indent=2, ensure_ascii=True),
        encoding="utf-8",
        newline="\n",
    )

    print("Mapeamento concluido.")
    print("Relatorio Markdown: " + NOME_RELATORIO_MD)
    print("Relatorio JSON: " + NOME_RELATORIO_JSON)
    print("Manifesto: " + NOME_MANIFESTO)
    print("Arquivos analisados: " + str(dados["total_arquivos"]))


if __name__ == "__main__":
    main()