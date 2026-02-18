<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard GLPI Pro - Complete Edition</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack.min.css"/>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="tv-indicator">
        <i class="fas fa-tv"></i> MODO TV ATIVO
    </div>

    <button class="floating-menu-btn" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-terminal"></i>
            </div>
            <div class="sidebar-title">GLPI Pro</div>
            <button class="sidebar-close" onclick="toggleMenu()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <nav class="menu-nav">
            <a href="#" class="menu-link active" onclick="showPage('dashboard', this)">
                <i class="fas fa-th-large"></i>
                <span>Visão Geral</span>
            </a>
            <a href="#" class="menu-link" onclick="showPage('sla', this)">
                <i class="fas fa-clock"></i>
                <span>Monitor SLA</span>
            </a>
            <a href="#" class="menu-link" onclick="showPage('ranking', this)">
                <i class="fas fa-trophy"></i>
                <span>Ranking Técnicos</span>
            </a>
            <a href="#" class="menu-link" onclick="showPage('tickets', this)">
                <i class="fas fa-ticket-alt"></i>
                <span>Chamados</span>
            </a>
            <a href="#" class="menu-link" onclick="showPage('assets', this)">
                <i class="fas fa-desktop"></i>
                <span>Ativos</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">SA</div>
                <div class="user-info">
                    <div class="user-name">System Admin</div>
                    <div class="user-role">Gestão de TI</div>
                </div>
                <button class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </aside>

    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h3 class="notification-title">Notificações</h3>
            <button class="notification-clear" onclick="clearNotifications()">Limpar Tudo</button>
        </div>
        <div class="notification-list" id="notificationList">
            </div>
    </div>

    <div class="edit-mode-indicator" id="editModeIndicator">
        <i class="fas fa-edit"></i>
        <span>Modo de Edição Ativo</span>
        <button class="edit-mode-btn" onclick="saveLayout()">Salvar Layout</button>
        <button class="edit-mode-btn" onclick="toggleEditMode()">Sair</button>
    </div>

    <main class="main-content" id="mainContent">
        <div class="page-section active" id="dashboardSection">
            <header class="page-header">
                <div class="page-title-wrapper">
                    <h1>Visão Geral do Serviço</h1>
                    <div class="page-subtitle">
                        <i class="fas fa-clock"></i>
                        <span>Última atualização: <span id="clock">Carregando...</span></span>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" placeholder="Busca rápida...">
                        <i class="fas fa-search"></i>
                    </div>
                    <button class="icon-btn" onclick="toggleTVMode()" title="Modo TV">
                        <i class="fas fa-tv"></i>
                    </button>
                    <button class="icon-btn" onclick="toggleNotifications()" title="Notificações">
                        <i class="fas fa-bell"></i>
                        <span class="badge-notification" id="notificationBadge">3</span>
                    </button>
                    <button class="icon-btn" onclick="requestNotificationPermission()" title="Ativar Notificações Desktop">
                        <i class="fas fa-desktop"></i>
                    </button>
                </div>
            </header>

            <div class="grid-kpi">
                <div class="glass-card kpi-card glow-success">
                    <div>
                        <span class="kpi-icon success">
                            <i class="fas fa-inbox"></i>
                        </span>
                        <div class="kpi-value" id="top-total">
                            <span class="skeleton">00</span>
                        </div>
                    </div>
                    <div class="kpi-label">Total de Chamados</div>
                </div>

                <div class="glass-card kpi-card glow-primary">
                    <div>
                        <span class="kpi-icon primary">
                            <i class="fas fa-tasks"></i>
                        </span>
                        <div class="kpi-value" id="top-andamento">
                            <span class="skeleton">00</span>
                        </div>
                    </div>
                    <div class="kpi-label">Em Andamento</div>
                </div>

                <div class="glass-card kpi-card glow-success">
                    <div>
                        <span class="kpi-icon success">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <div class="kpi-value" id="top-taxa">
                            <span class="skeleton">00%</span>
                        </div>
                    </div>
                    <div class="kpi-label">Taxa de Conclusão</div>
                </div>

                <div class="glass-card kpi-card glow-danger">
                    <div>
                        <span class="kpi-icon danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        <div class="kpi-value" id="top-sla">
                            <span class="skeleton">0</span>
                        </div>
                    </div>
                    <div class="kpi-label">SLA Vencido</div>
                </div>

                <div class="glass-card kpi-card glow-primary">
                    <div>
                        <span class="kpi-icon primary">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                        <div class="kpi-value" id="top-tempo">
                            <span class="skeleton">0h</span>
                        </div>
                    </div>
                    <div class="kpi-label">Tempo Médio</div>
                </div>

                <div class="glass-card kpi-card glow-warning">
                    <div>
                        <span class="kpi-icon warning">
                            <i class="fas fa-redo"></i>
                        </span>
                        <div class="kpi-value" id="top-reabertos">
                            <span class="skeleton">0</span>
                        </div>
                    </div>
                    <div class="kpi-label">Reabertos</div>
                </div>
            </div>

            <div class="grid-detail">
                <div class="glass-card detail-card glow-success">
                    <div class="detail-card-header">
                        <span class="kpi-icon success">
                            <i class="fas fa-plus-circle"></i>
                        </span>
                        <span class="detail-badge success">+2.4%</span>
                    </div>
                    <div class="detail-value" id="bot-abertos">0</div>
                    <div class="detail-label">Novos Chamados</div>
                </div>

                <div class="glass-card detail-card glow-primary">
                    <div class="detail-card-header">
                        <span class="kpi-icon primary">
                            <i class="fas fa-user-check"></i>
                        </span>
                        <span class="detail-badge success">+5.1%</span>
                    </div>
                    <div class="detail-value" id="bot-atribuidos">0</div>
                    <div class="detail-label">Com Técnico</div>
                </div>

                <div class="glass-card detail-card glow-warning">
                    <div class="detail-card-header">
                        <span class="kpi-icon warning">
                            <i class="fas fa-pause-circle"></i>
                        </span>
                        <span class="detail-badge danger">-0.5%</span>
                    </div>
                    <div class="detail-value" id="bot-pendentes">0</div>
                    <div class="detail-label">Aguardando</div>
                </div>

                <div class="glass-card detail-card glow-success">
                    <div class="detail-card-header">
                        <span class="kpi-icon success">
                            <i class="fas fa-check-double"></i>
                        </span>
                        <span class="detail-badge success">+12%</span>
                    </div>
                    <div class="detail-value" id="bot-finalizados">0</div>
                    <div class="detail-label">Resolvidos</div>
                </div>
            </div>

            <div class="grid-charts">
                <div class="glass-card chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Fluxo de Criação</h3>
                        <select class="chart-select">
                            <option>Últimos 30 Dias</option>
                            <option>Últimos 7 Dias</option>
                            <option>Este Mês</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>

                <div class="glass-card chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Top Categorias</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="glass-card table-card">
                <div class="table-header">
                    <h3 class="table-title">Atividade Recente</h3>
                    <a href="#" class="table-link" onclick="showPage('tickets', document.querySelectorAll('.menu-link')[3])">
                        Ver Todos os Chamados
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>ID & Título</th>
                                <th>Categoria</th>
                                <th>Criado</th>
                                <th style="text-align: right;">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="recent-tickets-body">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-muted);"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="page-section" id="slaSection">
            <header class="page-header">
                <div class="page-title-wrapper">
                    <h1>Monitor de SLA</h1>
                    <div class="page-subtitle">
                        <i class="fas fa-stopwatch"></i>
                        <span>Acompanhamento em tempo real</span>
                    </div>
                </div>
            </header>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px;">
                <div class="glass-card" style="padding: 32px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">
                        <i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--danger); margin-bottom: 8px;" id="slaCritical">0</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Crítico</div>
                </div>
                <div class="glass-card" style="padding: 32px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">
                        <i class="fas fa-clock" style="color: var(--warning);"></i>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--warning); margin-bottom: 8px;" id="slaWarning">0</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Atenção</div>
                </div>
                <div class="glass-card" style="padding: 32px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--success); margin-bottom: 8px;" id="slaOk">0</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">No Prazo</div>
                </div>
            </div>
            <div class="glass-card">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 24px; padding: 0 24px;">Chamados Próximos ao Vencimento</h3>
                <div class="sla-widget" id="slaList" style="padding: 0 24px 24px;"></div>
            </div>
        </div>

        <div class="page-section" id="rankingSection">
            <header class="page-header">
                <div class="page-title-wrapper">
                    <h1>Ranking de Técnicos</h1>
                    <div class="page-subtitle">
                        <i class="fas fa-trophy"></i>
                        <span>Performance e Gamificação</span>
                    </div>
                </div>
            </header>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="glass-card h-100">
                        <div class="chart-header">
                            <h3 class="chart-title">Líderes de Atendimento</h3>
                        </div>
                        <div id="leaderboard-container" class="leaderboard-container mt-3">
                            <div class="text-center p-5">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card text-center mb-4" style="border-color: var(--gold) !important;">
                        <i class="fas fa-crown text-warning fa-3x mb-3"></i>
                        <h5>Técnico do Mês</h5>
                        <h2 id="top-tech-name" class="fw-bold mt-2">--</h2>
                        <p class="text-muted small">Maior pontuação acumulada</p>
                    </div>
                    <div class="glass-card">
                        <h5 class="mb-3">Como pontuar?</h5>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Chamado Resolvido: +10 pts</li>
                            <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i> SLA no Prazo: +5 pts</li>
                            <li class="mb-2"><i class="fas fa-star text-warning me-2"></i> Avaliação 5 estrelas: +20 pts</li>
                            <li><i class="fas fa-times text-danger me-2"></i> Reabertura: -15 pts</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-section" id="ticketsSection">
            <header class="page-header">
                <h1>Todos os Chamados</h1>
            </header>
            <div class="glass-card table-card">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Técnico</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="tickets-full-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="page-section" id="assetsSection">
            <header class="page-header">
                <h1>Inventário de Ativos</h1>
            </header>
            <div class="glass-card table-card">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Serial</th>
                                <th>Tipo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="assets-full-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script src="script.js"></script>
</body>
</html>