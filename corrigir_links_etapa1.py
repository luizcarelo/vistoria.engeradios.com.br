from pathlib import Path

arquivos = [
    "index.php",
    "admin/dashboard.php",
    "supervisor/dashboard.php",
    "trocar_senha.php",
    "esqueci_senha.php",
    "redefinir_senha.php",
    "admin/alterar_senha.php",
]

substituicoes = {
    "esqueci_senha.phpEsqueci minha senha</a>": "\"esqueci_senha.php\"Esqueci minha senha</a>",
    "../trocar_senha.phpTrocar senha</a>": "\"../trocar_senha.php\"Trocar senha</a>",
    "trocar_senha.phpTrocar senha</a>": "\"trocar_senha.php\"Trocar senha</a>",
    "usuarios.phpVoltar para usuarios</a>": "\"usuarios.php\"Voltar para usuarios</a>",
    "index.phpVoltar ao login</a>": "\"index.php\"Voltar ao login</a>",
    "index.phpIr para o login</a>": "\"index.php\"Ir para o login</a>",
    "esqueci_senha.phpSolicitar novo link</a>": "\"esqueci_senha.php\"Solicitar novo link</a>",
    "javascript:history.back()Voltar</a>": "\">Voltar</a>",
}

alterados = []

for rel in arquivos:
    p = Path(rel)
    if not p.exists():
        continue

    texto = p.read_text(encoding="utf-8", errors="replace")
    original = texto

    for errado, correto in substituicoes.items():
        texto = texto.replace(errado, correto)

    if texto != original:
        p.write_text(texto, encoding="utf-8", newline="\n")
        alterados.append(rel)

print("Arquivos corrigidos:")
if alterados:
    for item in alterados:
        print("- " + item)
else:
    print("Nenhum arquivo precisava de correcao.")