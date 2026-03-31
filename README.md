# DashGLPI — Dashboard Pro para GLPI 11

Dashboard avançado e independente para visualização de dados do GLPI, com interface glassmorphism, modo escuro/claro, modo TV e tema cyberpunk para inventário de ativos.

**Autor:** Diogo Berlanda
**Licença:** GPLv3+
**Compatibilidade:** GLPI 11.0.x
**Repositório:** https://github.com/diberlanda95/dashglpi

---

## Funcionalidades

### Visão Geral (Dashboard)
- **6 KPIs em tempo real:** Total de chamados, Em andamento, Taxa de conclusão, SLA vencido, Tempo médio de resolução, Reabertos
- **4 cards de detalhe:** Novos chamados, Com técnico, Aguardando, Resolvidos
- **3 gráficos interativos:**
  - Fluxo de Criação (linha, últimos 30 dias)
  - Top 5 Categorias (barra horizontal)
  - Abertos vs Solucionados (barra comparativa, últimos 6 meses)
- **Tabela de Atividade Recente** com os 5 últimos chamados

### Monitor de SLA (Demonstração)
- Contadores de SLA: Crítico, Atenção, No Prazo
- Lista de chamados próximos ao vencimento com countdown em tempo real
- *Nota: seção com dados de demonstração — não conectada ao SLA real do GLPI*

### Ranking de Técnicos (Gamificação)
- Ranking mensal baseado em chamados resolvidos
- Sistema de pontos (10 pts por chamado resolvido)
- Destaque visual para o Técnico do Mês
- Barras de progresso animadas

### Inventário de Ativos (Grid Cyberpunk)
- Grid de cards com estilo cyberpunk/neon
- Indicador visual de disco (barra de uso com cores por criticidade)
- Modal detalhado com: OS, CPU, RAM, Disco, Serial, Localização
- Detecção automática de ícone por tipo (desktop, laptop, servidor)

### Recursos Gerais
- **Modo Escuro/Claro** com persistência via localStorage
- **Modo TV** com fullscreen e rotação automática entre seções (15s)
- **Atualização automática** dos dados a cada 60 segundos
- **Relógio** em tempo real no header
- **Painel de Notificações** (demonstração)

---

## Instalação

### Pré-requisitos
- GLPI 11.0.x instalado e funcionando
- Acesso de administrador ao GLPI

### Passos

1. Copie a pasta `dashglpi/` para o diretório de plugins do GLPI:
   ```bash
   cp -r dashglpi/ /usr/share/glpi/plugins/
   ```

2. Acesse o GLPI como administrador

3. Vá em **Configurar > Plugins**

4. Localize "Dashboard GLPI Pro" e clique em **Instalar**, depois **Ativar**

5. O dashboard aparecerá no menu **Assistência > Dashboard Pro**

> **Nota:** Se o ambiente usa OPcache com `validate_timestamps=Off`, reinicie o PHP-FPM após copiar os arquivos:
> ```bash
> pkill -USR2 php-fpm
> ```

---

## Estrutura de Arquivos

```
dashglpi/
├── setup.php                  # Registro do plugin, versão, hooks
├── hook.php                   # Install/uninstall, menu (redefine_menus)
├── front/
│   └── dashboard.php          # Página standalone (HTML completo, autenticada)
├── ajax/
│   └── dashboard.php          # Endpoint AJAX (4 actions, retorna JSON)
├── inc/
│   └── dashboard.class.php    # Backend: queries SQL via $DB->request()
├── public/
│   ├── css/
│   │   └── style.css          # Estilos (glassmorphism, cyberpunk, dark/light)
│   ├── js/
│   │   ├── script.js          # Lógica frontend (charts, modals, AJAX)
│   │   └── menu.js            # Script mínimo: força links em nova aba
│   └── vendor/
│       ├── css/
│       │   ├── bootstrap.min.css
│       │   └── fontawesome.min.css
│       ├── js/
│       │   └── chart.umd.min.js
│       └── webfonts/          # FontAwesome (woff2, ttf)
└── README.md
```

---

## Arquitetura

### Fluxo de Dados

```
Navegador (front/dashboard.php)
    │
    ├─ JS (script.js) ──→ AJAX GET /ajax/dashboard.php?action=...
    │                           │
    │                           └─→ PluginDashglpiDashboard (inc/)
    │                                   │
    │                                   └─→ $DB->request() (tabelas GLPI nativas)
    │
    └─ Resposta JSON ──→ Atualiza KPIs, gráficos, tabelas
```

### Design Standalone

O dashboard renderiza seu próprio HTML completo — **não usa** o header/footer do GLPI. Isso permite:
- Layout fullscreen sem interferência visual do GLPI
- Modo TV dedicado
- Tema independente (dark/light)

A autenticação é garantida via `Session::checkLoginUser()` no topo de cada arquivo PHP.

### Isolamento de CSS/JS

O plugin **não injeta CSS** globalmente no GLPI (evita conflitos visuais). Apenas um JS mínimo (`menu.js`, 10 linhas) é carregado globalmente para forçar o link do menu a abrir em nova aba.

Bibliotecas de terceiros (Bootstrap, FontAwesome, Chart.js) são servidas localmente a partir de `public/vendor/` — sem dependência de CDN.

---

## API AJAX

Endpoint: `plugins/dashglpi/ajax/dashboard.php`

Todas as requests requerem sessão GLPI autenticada (cookie de sessão).

| Action | Método | Descrição | Resposta |
|--------|--------|-----------|----------|
| `dashboard_data` | GET | KPIs + dados dos gráficos | `{cards_top, cards_bottom, charts}` |
| `get_ranking` | GET | Ranking de técnicos do mês | `[{name, avatar, tickets, points, color}]` |
| `tickets_list` | GET | Lista de chamados ativos (máx. 100) | `[{id, name, status, date, category, user_name}]` |
| `assets_list` | GET | Lista de computadores (máx. 100) | `[{id, name, serial, location, model, os_name, cpu, ram_total, disk_total, disk_free}]` |

### Exemplo de uso

```javascript
const response = await fetch('/plugins/dashglpi/ajax/dashboard.php?action=dashboard_data');
const data = await response.json();
// data.cards_top.total → total de chamados
// data.charts.trend_line → [{dia, total}, ...]
```

### Tratamento de erros

- **400** — Action inválida: `{"error": "Ação inválida."}`
- **500** — Erro interno: `{"error": "Erro interno do servidor."}` (detalhes logados no `php-errors.log`)
- **302** — Sem autenticação: redirect para login

---

## Queries e Performance

Todas as queries usam `$DB->request()` com arrays de critérios (obrigatório no GLPI 11). Nenhum SQL raw.

### Otimização de Assets (Bulk Queries)

O endpoint `assets_list` busca dados complementares (OS, CPU, RAM, Disco) usando **4 queries bulk** em vez de queries individuais por asset:

| Dado | Tabela | Estratégia |
|------|--------|------------|
| OS | `glpi_items_operatingsystems` + `glpi_operatingsystems` | LEFT JOIN, WHERE IN |
| CPU | `glpi_items_deviceprocessors` + `glpi_deviceprocessors` | INNER JOIN, WHERE IN |
| RAM | `glpi_items_devicememories` | SUM + GROUP BY |
| Disco | `glpi_items_disks` | SUM (total + free) + GROUP BY |

Resultado: **5 queries fixas** independente do volume de assets (vs. N*4+1 na abordagem ingênua).

### Respeito a Entidades

Todas as queries usam `getEntitiesRestrictCriteria()` para filtrar dados conforme a entidade ativa do usuário logado.

---

## Temas e Personalização

### Variáveis CSS principais

O tema é controlado por CSS custom properties definidas em `style.css`:

| Variável | Uso |
|----------|-----|
| `--bg-body` | Fundo da página |
| `--card-bg` | Fundo dos cards |
| `--glass-bg` | Fundo glassmorphism (translúcido) |
| `--text-main` | Texto principal |
| `--text-sec` | Texto secundário |
| `--text-muted` | Texto desabilitado |
| `--border-color` | Bordas |
| `--primary` | Cor primária (azul) |
| `--success` | Verde |
| `--warning` | Amarelo |
| `--danger` | Vermelho |
| `--neon-blue` | Azul neon (cyberpunk) |
| `--neon-pink` | Rosa neon (cyberpunk) |

### Modo Claro

Ativado via classe `light-mode` no `<body>`. As variáveis são sobrescritas para tons claros.

---

## Seções de Demonstração

As seguintes seções usam dados estáticos de exemplo (não conectadas a dados reais do GLPI):

- **Monitor de SLA** — dados fictícios com countdown. Marcado com badge "DEMONSTRAÇÃO".
- **Notificações** — lista fixa de 3 notificações. Marcado com badge "DEMO".

Estas seções servem como placeholder para futuras integrações com SLA real e sistema de notificações do GLPI.

---

## Desenvolvimento

### Requisitos para desenvolvimento
- GLPI 11.0.x com acesso de administrador
- Navegador moderno (Chrome, Firefox, Edge)

### Limpeza de cache após alterações

Se o ambiente usa OPcache:
```bash
# PHP
pkill -USR2 php-fpm

# Nginx (se aplicável)
rm -rf /var/lib/nginx/cache/public/*
```

### Adicionando novos endpoints AJAX

1. Criar o método estático em `inc/dashboard.class.php`
2. Adicionar o `case` no `switch` de `ajax/dashboard.php`
3. Chamar via `fetch()` no `script.js`

### Segurança

- `Session::checkLoginUser()` em todos os entry points PHP
- `escHtml()` no JS para sanitização de output (prevenção de XSS)
- Sem exposição de informações de debug em respostas de erro
- CSRF compliant (declarado via `$PLUGIN_HOOKS['csrf_compliant']`)
