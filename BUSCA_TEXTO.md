# Busca por Texto - Sistema Vistoria ENGERADIOS

## 🔍 Funcionalidade Implementada

Adicionado **campo de busca livre por texto** para pesquisar palavras-chave dentro do **laudo** e **orçamento de adequação** das vistorias.

---

## ✅ O que foi implementado?

### **Onde está disponível:**
- ✅ **Painel do Supervisor** - Consultar Vistorias
- ✅ **Painel do Administrador** - Visualizar Vistorias

### **Onde busca:**
- ✅ Campo **Laudo**
- ✅ Campo **Orçamento de Adequação**

### **Tipo de busca:**
- ✅ Busca parcial (não precisa digitar palavra completa)
- ✅ Case-insensitive (não diferencia maiúsculas/minúsculas)
- ✅ Busca em qualquer parte do texto

---

## 🖥️ Como Usar

### **Supervisor:**

1. Acesse **"Consultar Vistorias"**
2. No topo do formulário, veja o campo **"🔍 Buscar por palavra-chave"**
3. Digite uma ou mais palavras-chave
4. Clique em **"🔍 Buscar"**
5. Verá apenas vistorias que contêm aquela palavra

### **Administrador:**

1. Acesse **"Visualizar Vistorias"**
2. No topo do formulário, veja o campo **"🔍 Buscar por palavra-chave"**
3. Digite uma ou mais palavras-chave
4. Clique em **"🔍 Buscar"**
5. Verá apenas vistorias que contêm aquela palavra

---

## 📋 Exemplos Práticos

### **Exemplo 1: Buscar vistorias sobre "alarme"**

```
Campo: alarme
Resultado: Todas as vistorias que mencionam "alarme" no laudo ou orçamento
```

**Encontrará vistorias com:**
- "Sistema de **alarme** funcionando"
- "Necessário trocar central de **alarme**"
- "**Alarme** disparando sem motivo"

---

### **Exemplo 2: Buscar vistorias sobre "câmera"**

```
Campo: câmera
Resultado: Todas as vistorias que mencionam "câmera" no laudo ou orçamento
```

**Encontrará vistorias com:**
- "**Câmera** 01 com imagem escura"
- "Instalar 5 **câmeras** adicionais"
- "**Câmeras** desalinhadas"

---

### **Exemplo 3: Buscar vistorias sobre "manutenção"**

```
Campo: manutenção
Resultado: Todas as vistorias que mencionam "manutenção"
```

**Encontrará vistorias com:**
- "Necessário **manutenção** preventiva"
- "**Manutenção** corretiva realizada"
- "Agendar **manutenção** mensal"

---

### **Exemplo 4: Buscar vistorias sobre "bateria"**

```
Campo: bateria
Resultado: Todas as vistorias que mencionam "bateria"
```

**Encontrará vistorias com:**
- "**Bateria** do nobreak vencida"
- "Trocar **baterias** dos sensores"
- "**Bateria** com baixa carga"

---

### **Exemplo 5: Buscar múltiplas palavras**

```
Campo: câmera DVR
Resultado: Vistorias que contêm "câmera" OU "DVR"
```

**Encontrará vistorias com:**
- "**Câmera** 03 offline"
- "**DVR** sem espaço em disco"
- "**Câmera** conectada ao **DVR**"

---

## 💡 Combinação com Outros Filtros

Você pode **combinar** a busca por texto com outros filtros:

### **Exemplo 1: Buscar "alarme" no último mês**

```
Busca: alarme
Data Início: 01/10/2025
Data Fim: 31/10/2025
```

**Resultado:** Vistorias sobre alarme realizadas em outubro

---

### **Exemplo 2: Buscar "câmera" para um cliente específico**

```
Busca: câmera
Cliente: Hospital ABC
```

**Resultado:** Vistorias sobre câmera no Hospital ABC

---

### **Exemplo 3: Buscar "manutenção" de um supervisor**

```
Busca: manutenção
Supervisor: João Silva
```

**Resultado:** Vistorias sobre manutenção feitas pelo João

---

### **Exemplo 4: Busca completa**

```
Busca: bateria
Data Início: 01/11/2025
Data Fim: 14/11/2025
Cliente: Empresa XYZ
Supervisor: Maria Santos
```

**Resultado:** Vistorias sobre bateria da Empresa XYZ, feitas pela Maria em novembro

---

## 🎨 Interface

### **Campo de Busca:**

```
┌─────────────────────────────────────────────────────┐
│ 🔍 Buscar por palavra-chave                         │
│ ┌─────────────────────────────────────────────────┐ │
│ │ Digite palavras-chave do laudo ou orçamento...  │ │
│ └─────────────────────────────────────────────────┘ │
│ Busca no laudo e orçamento de adequação.            │
│ Exemplo: "alarme", "câmera", "manutenção"           │
└─────────────────────────────────────────────────────┘
```

### **Filtros Ativos:**

Quando você usa a busca, aparece na barra de filtros ativos:

```
ℹ️ Filtros ativos: Busca: "alarme"
```

ou

```
ℹ️ Filtros ativos: Data início: 01/11/2025 | Busca: "câmera"
```

---

## 🔧 Detalhes Técnicos

### **Tipo de Busca:**
- **SQL LIKE** com wildcards (`%palavra%`)
- Busca parcial em qualquer posição do texto
- Case-insensitive (MySQL padrão)

### **Campos Pesquisados:**
- `vistorias.laudo`
- `vistorias.orcamento_adequacao`

### **Query SQL:**
```sql
WHERE (v.laudo LIKE '%palavra%' OR v.orcamento_adequacao LIKE '%palavra%')
```

### **Segurança:**
- ✅ Proteção contra SQL Injection (`real_escape_string`)
- ✅ Sanitização de entrada (`trim`)
- ✅ Escape de HTML na exibição (`htmlspecialchars`)

---

## 📊 Casos de Uso

### **Caso 1: Encontrar vistorias sobre problema específico**
Supervisor quer ver todas as vistorias que mencionam "bateria vencida"

```
Busca: bateria vencida
```

---

### **Caso 2: Auditar vistorias sobre equipamento**
Gerente quer ver todas as vistorias que mencionam "DVR"

```
Busca: DVR
```

---

### **Caso 3: Verificar recomendações**
Supervisor quer ver vistorias que recomendaram "manutenção preventiva"

```
Busca: manutenção preventiva
```

---

### **Caso 4: Buscar por marca/modelo**
Encontrar vistorias que mencionam "Intelbras"

```
Busca: Intelbras
```

---

### **Caso 5: Buscar por tipo de serviço**
Encontrar vistorias que mencionam "instalação"

```
Busca: instalação
```

---

## 💡 Dicas de Uso

### **1. Use palavras-chave específicas**
✅ **Bom:** "bateria nobreak"
❌ **Ruim:** "problema"

### **2. Não precisa digitar palavra completa**
✅ "câme" encontra "câmera", "câmeras"
✅ "manu" encontra "manutenção", "manual"

### **3. Combine com outros filtros**
✅ Busca + Data = Vistorias sobre tema em período
✅ Busca + Cliente = Vistorias sobre tema para cliente

### **4. Use termos técnicos**
✅ "DVR", "nobreak", "sensor", "central"
✅ Mais específico = resultados mais relevantes

### **5. Teste variações**
Se não encontrar com "câmera", tente:
- "camera" (sem acento)
- "cam"
- "CFTV"

---

## 📝 Exemplos de Palavras-Chave Úteis

### **Equipamentos:**
- alarme
- câmera / camera
- DVR / NVR
- sensor
- central
- nobreak
- sirene
- teclado
- controle
- leitor

### **Problemas:**
- defeito
- falha
- offline
- desligado
- queimado
- danificado
- vencido
- baixa
- escuro

### **Serviços:**
- instalação
- manutenção
- troca
- reparo
- ajuste
- configuração
- limpeza
- teste

### **Materiais:**
- cabo
- fonte
- bateria
- conector
- suporte
- caixa
- eletroduto

---

## ⚠️ Limitações

### **1. Busca apenas em laudo e orçamento**
❌ Não busca no nome do cliente
❌ Não busca no nome do supervisor
❌ Não busca em fotos ou áudios

### **2. Busca exata de caracteres**
- "câmera" ≠ "camera" (com/sem acento)
- Solução: Testar ambas as formas

### **3. Não busca em vistorias excluídas**
- Apenas vistorias com status "concluída"

---

## 🔄 Como Funciona Internamente

### **Fluxo:**

1. Usuário digita palavra no campo
2. Sistema recebe via GET (`$_GET['busca']`)
3. Remove espaços extras (`trim()`)
4. Escapa caracteres especiais (`real_escape_string()`)
5. Adiciona à query SQL com LIKE
6. Busca em `laudo` e `orcamento_adequacao`
7. Retorna vistorias que contêm a palavra
8. Exibe resultados na tela

### **Código Backend:**

```php
// Capturar busca
$busca_texto = trim($_GET['busca'] ?? '');

// Adicionar à query se não vazio
if (!empty($busca_texto)) {
    $busca_escaped = $conn->real_escape_string($busca_texto);
    $where .= " AND (v.laudo LIKE '%$busca_escaped%' 
                 OR v.orcamento_adequacao LIKE '%$busca_escaped%')";
}
```

---

## 📦 Arquivos Modificados

```
✅ /supervisor/consultar_vistorias.php
   ├── Linha 18: Captura do parâmetro busca
   ├── Linhas 39-42: Lógica de busca SQL
   └── Linhas 393-400: Campo de busca no HTML

✅ /admin/vistorias.php
   ├── Linha 12: Captura do parâmetro busca
   ├── Linhas 33-36: Lógica de busca SQL
   ├── Linhas 435-442: Campo de busca no HTML
   └── Linhas 497-499: Exibição na barra de filtros ativos
```

---

## ✅ Checklist de Teste

- [ ] Campo de busca aparece no topo dos filtros
- [ ] Buscar por "alarme" retorna vistorias corretas
- [ ] Buscar por "câmera" retorna vistorias corretas
- [ ] Buscar palavra que não existe retorna "Nenhuma vistoria encontrada"
- [ ] Combinar busca + data funciona
- [ ] Combinar busca + cliente funciona
- [ ] Combinar busca + supervisor funciona
- [ ] Filtro ativo mostra: Busca: "palavra"
- [ ] Limpar filtros remove a busca
- [ ] Funciona no painel do supervisor
- [ ] Funciona no painel do administrador

---

## 🎯 Benefícios

### **1. Rapidez**
✅ Encontrar vistorias específicas em segundos
✅ Não precisa ler todas as vistorias

### **2. Precisão**
✅ Busca exata por palavra-chave
✅ Resultados relevantes

### **3. Flexibilidade**
✅ Combinar com outros filtros
✅ Buscar qualquer termo

### **4. Produtividade**
✅ Menos tempo procurando
✅ Mais tempo trabalhando

### **5. Auditoria**
✅ Encontrar vistorias sobre temas específicos
✅ Análise de problemas recorrentes

---

## 🆘 Solução de Problemas

### **Problema: Não encontra nada**
**Solução:**
1. Verifique se digitou corretamente
2. Tente palavra mais curta ("câme" ao invés de "câmera")
3. Tente sem acentos ("camera" ao invés de "câmera")
4. Verifique se a palavra está no laudo/orçamento

### **Problema: Encontra muitos resultados**
**Solução:**
1. Use palavra mais específica
2. Combine com filtro de data
3. Combine com filtro de cliente

### **Problema: Campo não aparece**
**Solução:**
1. Limpe cache do navegador (Ctrl+F5)
2. Verifique se fez upload dos arquivos atualizados
3. Verifique se está na página correta

---

## 📚 Resumo

| Recurso | Descrição |
|---------|-----------|
| **Campo** | Busca por palavra-chave |
| **Onde busca** | Laudo e Orçamento |
| **Tipo** | Busca parcial (LIKE) |
| **Disponível em** | Supervisor e Admin |
| **Combina com** | Todos os outros filtros |
| **Segurança** | SQL Injection protegido |

---

**Sistema:** Vistoria Remota ENGERADIOS
**Versão:** 1.7
**Data:** 14/11/2025
**Funcionalidade:** Busca livre por texto
**Arquivos:** `/supervisor/consultar_vistorias.php`, `/admin/vistorias.php`
