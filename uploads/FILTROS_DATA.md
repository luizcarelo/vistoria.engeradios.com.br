# Filtros de Data - Sistema Vistoria ENGERADIOS

## 📅 Funcionalidade Implementada

O sistema de visualização de vistorias do administrador possui **filtros avançados por data** para facilitar a consulta de relatórios.

---

## 🎯 Recursos Disponíveis

### **1. Filtro por Período Personalizado**

Permite buscar vistorias entre duas datas específicas:

- **Data Início:** Data inicial do período
- **Data Fim:** Data final do período

**Exemplo:**
- Data Início: 01/10/2025
- Data Fim: 31/10/2025
- Resultado: Todas as vistorias de outubro/2025

---

### **2. Atalhos Rápidos** ⚡

Botões para aplicar filtros comuns com um único clique:

#### **📅 Hoje**
Filtra vistorias realizadas hoje

#### **📅 Ontem**
Filtra vistorias realizadas ontem

#### **📅 Últimos 7 dias**
Filtra vistorias dos últimos 7 dias (incluindo hoje)

#### **📅 Esta semana**
Filtra vistorias desde domingo até hoje

#### **📅 Este mês**
Filtra vistorias desde o dia 1º do mês atual até hoje

#### **📅 Mês passado**
Filtra vistorias do mês anterior completo

---

### **3. Filtros Combinados**

É possível combinar filtros de data com outros filtros:

- **Data + Cliente:** Vistorias de um cliente específico em um período
- **Data + Supervisor:** Vistorias de um supervisor em um período
- **Data + Cliente + Supervisor:** Combinação completa

**Exemplo:**
- Período: Últimos 7 dias
- Cliente: Empresa ABC
- Supervisor: João Silva
- Resultado: Vistorias do João na Empresa ABC nos últimos 7 dias

---

### **4. Indicador de Filtros Ativos**

Quando filtros estão aplicados, uma barra azul aparece mostrando:

```
ℹ️ Filtros ativos: Data início: 01/11/2025 | Data fim: 06/11/2025 | Cliente selecionado
```

---

### **5. Botão Limpar Filtros**

Remove todos os filtros aplicados e mostra todas as vistorias.

**Localização:** Ao lado do botão "🔍 Buscar"

---

## 🖥️ Como Usar

### **Método 1: Atalhos Rápidos (Recomendado)**

1. Acesse "Visualizar Vistorias" no painel admin
2. Clique em um dos atalhos rápidos:
   - Exemplo: "📅 Últimos 7 dias"
3. Clique em "🔍 Buscar"
4. Visualize os resultados

### **Método 2: Período Personalizado**

1. Acesse "Visualizar Vistorias" no painel admin
2. Preencha "Data Início" (ex: 01/10/2025)
3. Preencha "Data Fim" (ex: 31/10/2025)
4. Clique em "🔍 Buscar"
5. Visualize os resultados

### **Método 3: Filtros Combinados**

1. Use um atalho rápido OU preencha as datas manualmente
2. Selecione um cliente (opcional)
3. Selecione um supervisor (opcional)
4. Clique em "🔍 Buscar"
5. Visualize os resultados filtrados

---

## 📊 Exemplos Práticos

### **Exemplo 1: Vistorias de Hoje**

```
1. Clique em "📅 Hoje"
2. Clique em "🔍 Buscar"
```

**Resultado:** Todas as vistorias realizadas hoje

---

### **Exemplo 2: Vistorias do Mês Atual**

```
1. Clique em "📅 Este mês"
2. Clique em "🔍 Buscar"
```

**Resultado:** Todas as vistorias desde o dia 1º do mês até hoje

---

### **Exemplo 3: Vistorias de um Cliente Específico em Outubro**

```
1. Data Início: 01/10/2025
2. Data Fim: 31/10/2025
3. Cliente: Selecione o cliente desejado
4. Clique em "🔍 Buscar"
```

**Resultado:** Vistorias do cliente selecionado em outubro/2025

---

### **Exemplo 4: Vistorias de um Supervisor na Última Semana**

```
1. Clique em "📅 Últimos 7 dias"
2. Supervisor: Selecione o supervisor desejado
3. Clique em "🔍 Buscar"
```

**Resultado:** Vistorias do supervisor nos últimos 7 dias

---

## 🔍 Detalhes Técnicos

### **Formato de Data**

- **Interface:** dd/mm/yyyy (ex: 06/11/2025)
- **Banco de dados:** yyyy-mm-dd (ex: 2025-11-06)
- **Conversão:** Automática pelo sistema

### **Lógica de Filtragem**

```sql
-- Data início
DATE(v.data_vistoria) >= 'data_inicio'

-- Data fim
DATE(v.data_vistoria) <= 'data_fim'

-- Combinado
DATE(v.data_vistoria) BETWEEN 'data_inicio' AND 'data_fim'
```

### **Ordenação**

Vistorias são sempre ordenadas por data/hora decrescente (mais recentes primeiro).

---

## 💡 Dicas de Uso

### **Dica 1: Use Atalhos Rápidos**
Os atalhos são mais rápidos que digitar datas manualmente.

### **Dica 2: Combine Filtros**
Use data + cliente para relatórios específicos.

### **Dica 3: Limpe Filtros Regularmente**
Clique em "🗑️ Limpar" para voltar à visualização completa.

### **Dica 4: Exporte para PDF**
Após filtrar, clique em "📄 PDF" para gerar relatórios.

### **Dica 5: Filtros Persistem**
Os filtros permanecem ativos ao navegar entre páginas.

---

## 🎨 Interface

### **Layout dos Filtros**

```
┌─────────────────────────────────────────────────┐
│ Filtros de Busca                                │
├─────────────────────────────────────────────────┤
│ Atalhos: [Hoje] [Ontem] [Últimos 7 dias] ...   │
│                                                 │
│ [Data Início] [Data Fim] [Cliente] [Supervisor]│
│ [🔍 Buscar] [🗑️ Limpar]                         │
│                                                 │
│ ℹ️ Filtros ativos: Data início: 01/11/2025 ... │
└─────────────────────────────────────────────────┘
```

### **Cores**

- **Atalhos:** Cinza claro (#f8f9fa)
- **Atalhos (hover):** Vermelho ENGERADIOS (#c41e3a)
- **Botão Buscar:** Azul (#007bff)
- **Botão Limpar:** Cinza (#6c757d)
- **Indicador de Filtros:** Azul claro (#e7f3ff)

---

## ✅ Vantagens

✅ **Rápido:** Atalhos aplicam filtros com 1 clique
✅ **Flexível:** Combine múltiplos filtros
✅ **Intuitivo:** Interface clara e fácil de usar
✅ **Visual:** Indicador mostra filtros ativos
✅ **Eficiente:** Busca otimizada no banco de dados

---

## 📱 Responsividade

O sistema de filtros é **totalmente responsivo** e funciona em:

- 💻 Desktop
- 📱 Tablet
- 📱 Smartphone

Os atalhos se reorganizam automaticamente em telas menores.

---

## 🔧 Manutenção

### **Adicionar Novo Atalho**

Para adicionar um novo atalho rápido, edite o arquivo `/admin/vistorias.php`:

**1. Adicionar botão HTML:**
```html
<button type="button" class="quick-filter-btn" onclick="setNovoAtalho()">
    📅 Novo Atalho
</button>
```

**2. Adicionar função JavaScript:**
```javascript
function setNovoAtalho() {
    const hoje = new Date();
    // Lógica de cálculo de datas
    document.getElementById('data_inicio').value = formatDate(dataInicio);
    document.getElementById('data_fim').value = formatDate(dataFim);
}
```

---

## 📚 Arquivos Relacionados

```
/admin/vistorias.php
├── Linhas 8-22: Lógica de filtragem (backend)
├── Linhas 417-426: Atalhos rápidos (HTML)
├── Linhas 428-465: Formulário de filtros
├── Linhas 467-483: Indicador de filtros ativos
└── Linhas 545-595: Funções JavaScript dos atalhos
```

---

## 🆘 Solução de Problemas

### **Filtro não funciona?**
1. Verifique se clicou em "🔍 Buscar"
2. Verifique se as datas estão corretas
3. Limpe os filtros e tente novamente

### **Atalho não preenche as datas?**
1. Verifique se JavaScript está habilitado
2. Atualize a página (F5)
3. Limpe o cache do navegador

### **Nenhuma vistoria encontrada?**
1. Verifique se existem vistorias no período
2. Amplie o período de busca
3. Remova filtros de cliente/supervisor

---

**Sistema:** Vistoria Remota ENGERADIOS
**Versão:** 1.4
**Data:** 06/11/2025
**Arquivo:** /admin/vistorias.php
