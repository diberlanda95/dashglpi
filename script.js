// ==================== GLOBAL VARIABLES ====================
let lineChart, barChart, monthlyChart; // Adicionado monthlyChart
let tvMode = false;
let notificationCount = 3;
let assetsDataGlobal = []; // Variável global essencial para o Modal Cyberpunk

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initCharts();
    initNotifications();
    initSLAMonitor();
    updateData();
    
    // Carrega os ativos com o novo layout
    loadFullAssets();

    updateClock();
    setInterval(updateClock, 1000);
    setInterval(updateData, 60000); // Update every minute
});

// ==================== THEME ====================
function initTheme() {
    const savedTheme = localStorage.getItem('glpi-theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
        updateThemeIcon();
    }
}

function toggleTheme() {
    document.body.classList.toggle('light-mode');
    const isLight = document.body.classList.contains('light-mode');
    localStorage.setItem('glpi-theme', isLight ? 'light' : 'dark');
    updateThemeIcon();

    if (lineChart) lineChart.destroy();
    if (barChart) barChart.destroy();
    if (monthlyChart) monthlyChart.destroy(); // Destruir novo gráfico ao trocar tema
    initCharts();
    updateData();
}

function updateThemeIcon() {
    const icon = document.querySelector('.theme-toggle i');
    if (document.body.classList.contains('light-mode')) {
        icon.className = 'fas fa-sun';
    } else {
        icon.className = 'fas fa-moon';
    }
}

// ==================== MENU ====================
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');

    if (sidebar.classList.contains('collapsed')) {
        document.body.classList.add('menu-closed');
    } else {
        document.body.classList.remove('menu-closed');
    }

    setTimeout(() => {
        if (lineChart) lineChart.resize();
        if (barChart) barChart.resize();
        if (monthlyChart) monthlyChart.resize(); // Redimensionar novo gráfico
    }, 350);
}

// ==================== PAGE NAVIGATION ====================
function showPage(pageId, linkElement) {
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.remove('active');
    });

    document.getElementById(pageId + 'Section').classList.add('active');

    document.querySelectorAll('.menu-link').forEach(link => {
        link.classList.remove('active');
    });
    linkElement.classList.add('active');

    // Gatilhos específicos por página
    if (pageId === 'ranking') {
        renderLeaderboard();
    }
    if (pageId === 'assets') {
        loadFullAssets();
    }

    setTimeout(() => {
        if (lineChart) lineChart.resize();
        if (barChart) barChart.resize();
        if (monthlyChart) monthlyChart.resize(); // Redimensionar novo gráfico
    }, 150);
}

// ==================== CLOCK ====================
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        now.toLocaleDateString('pt-BR') + ' ' + now.toLocaleTimeString('pt-BR');
}

// ==================== TV MODE ====================
function toggleTVMode() {
    tvMode = !tvMode;
    document.body.classList.toggle('tv-mode');

    if (tvMode) {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        }
        startTVRotation();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
        stopTVRotation();
    }
}

let tvRotationInterval;
function startTVRotation() {
    const pages = ['dashboard', 'sla', 'ranking', 'assets']; 
    let currentIndex = 0;

    tvRotationInterval = setInterval(() => {
        currentIndex = (currentIndex + 1) % pages.length;
        const pageId = pages[currentIndex];

        const menuLinks = document.querySelectorAll('.menu-link');
        const targetLink = Array.from(menuLinks).find(l => l.getAttribute('onclick')?.includes(pageId));

        if (targetLink) showPage(pageId, targetLink);
    }, 15000); 
}

function stopTVRotation() {
    clearInterval(tvRotationInterval);
}

// ==================== DATA UPDATE ====================
async function updateData() {
    try {
        const response = await fetch('api.php?action=dashboard_data');
        const data = await response.json();

        setVal('top-total', data.cards_top.total);
        setVal('top-andamento', data.cards_top.andamento);
        setVal('top-taxa', data.cards_top.taxa + '%');
        setVal('top-sla', data.cards_top.sla);
        setVal('top-tempo', data.cards_top.tempo_medio + 'h');
        setVal('top-reabertos', data.cards_top.reabertos);
        setVal('bot-abertos', data.cards_bottom.abertos);
        setVal('bot-atribuidos', data.cards_bottom.atribuidos);
        setVal('bot-pendentes', data.cards_bottom.pendentes);
        setVal('bot-finalizados', data.cards_bottom.finalizados);

        // Atualiza Gráfico de Linha (Fluxo)
        if (lineChart) {
            lineChart.data.labels = data.charts.trend_line.map(x => x.dia);
            lineChart.data.datasets[0].data = data.charts.trend_line.map(x => x.total);
            lineChart.update('none');
        }

        // Atualiza Gráfico de Barras (Categorias)
        if (barChart) {
            barChart.data.labels = data.charts.cat_bar.map(x => x.nome);
            barChart.data.datasets[0].data = data.charts.cat_bar.map(x => x.total);
            barChart.update('none');
        }

        // Atualiza Gráfico Mensal (Comparativo) - NOVO
        if (monthlyChart && data.charts.monthly_opened && data.charts.monthly_solved) {
            // Processa dados para alinhar meses (mesmo se vazios)
            const processed = processMonthlyData(data.charts.monthly_opened, data.charts.monthly_solved);
            
            monthlyChart.data.labels = processed.labels;
            monthlyChart.data.datasets[0].data = processed.opened;
            monthlyChart.data.datasets[1].data = processed.solved;
            monthlyChart.update('none');
        }

        loadTicketLists();
    } catch (error) {
        console.error('Error updating data:', error);
    }
}

// Função auxiliar para alinhar os meses (Evita desalinhamento se um mês tiver 0 dados)
function processMonthlyData(openedData, solvedData) {
    const labels = [];
    const dataOpened = [];
    const dataSolved = [];
    
    // Gera os últimos 6 meses
    const today = new Date();
    for (let i = 5; i >= 0; i--) {
        const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
        
        // Chave para busca (ex: "2023-10")
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const key = `${year}-${month}`;
        
        // Label para exibição (ex: "10/23")
        labels.push(`${month}/${year.toString().substring(2)}`);

        // Busca nos dados da API (ou retorna 0 se não achar)
        const foundOpen = openedData.find(item => item.mes_ano === key);
        dataOpened.push(foundOpen ? parseInt(foundOpen.total) : 0);

        const foundSolved = solvedData.find(item => item.mes_ano === key);
        dataSolved.push(foundSolved ? parseInt(foundSolved.total) : 0);
    }

    return { labels: labels, opened: dataOpened, solved: dataSolved };
}

function setVal(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
        element.classList.remove('skeleton');
    }
}

// ==================== CHARTS ====================
function initCharts() {
    const isLight = document.body.classList.contains('light-mode');
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color = isLight ? '#94a3b8' : 'rgba(255, 255, 255, 0.3)';

    const gridColor = isLight ? 'rgba(0, 0, 0, 0.05)' : 'rgba(255, 255, 255, 0.05)';
    const primaryColor = '#3b82f6';

    // 1. Line Chart (Fluxo)
    const lineCtx = document.getElementById('lineChart');
    if (lineCtx) {
        const lineCtx2d = lineCtx.getContext('2d');
        const lineGradient = lineCtx2d.createLinearGradient(0, 0, 0, 300);
        lineGradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
        lineGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        lineChart = new Chart(lineCtx2d, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Chamados',
                    data: [],
                    borderColor: primaryColor,
                    backgroundColor: lineGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: primaryColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: Chart.defaults.color } },
                    y: { beginAtZero: true, border: { display: false }, grid: { color: gridColor }, ticks: { color: Chart.defaults.color } }
                }
            }
        });
    }

    // 2. Bar Chart (Categorias)
    const barCtx = document.getElementById('barChart');
    if (barCtx) {
        barChart = new Chart(barCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Quantidade',
                    data: [],
                    backgroundColor: primaryColor,
                    borderRadius: 8,
                    barThickness: 24
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { display: false }, ticks: { color: Chart.defaults.color } },
                    y: { grid: { display: false }, ticks: { color: Chart.defaults.color, autoSkip: false } }
                }
            }
        });
    }

    // 3. NOVO: Monthly Chart (Comparativo)
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        monthlyChart = new Chart(monthlyCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Abertos',
                        data: [],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)', // Azul (primary)
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Solucionados',
                        data: [],
                        backgroundColor: 'rgba(34, 197, 94, 0.8)', // Verde (success)
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: { 
                    legend: { 
                        display: true, 
                        labels: { color: Chart.defaults.color } 
                    },
                    tooltip: {
                        backgroundColor: isLight ? 'rgba(255, 255, 255, 0.95)' : 'rgba(0, 0, 0, 0.8)',
                        titleColor: isLight ? '#0f172a' : '#ffffff',
                        bodyColor: isLight ? '#64748b' : 'rgba(255, 255, 255, 0.7)',
                        borderColor: isLight ? 'rgba(0, 0, 0, 0.1)' : 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: Chart.defaults.color } 
                    },
                    y: { 
                        beginAtZero: true, 
                        grid: { color: gridColor }, 
                        ticks: { color: Chart.defaults.color } 
                    }
                }
            }
        });
    }
}

// ==================== LOAD TICKET LISTS ====================
async function loadTicketLists() {
    try {
        const response = await fetch('api.php?action=tickets_list');
        const tickets = await response.json();

        const recentBody = document.getElementById('recent-tickets-body');
        if (tickets.length > 0) {
            recentBody.innerHTML = tickets.slice(0, 5).map(ticket => `
                        <tr>
                            <td>
                                <div class="table-status-icon ${getStatusClass(ticket.status)}">
                                    <i class="fas ${getStatusIcon(ticket.status)}"></i>
                                </div>
                            </td>
                            <td>
                                <div class="table-ticket-info">
                                    <div class="table-ticket-title">${ticket.name}</div>
                                    <div class="table-ticket-id">#${ticket.id}</div>
                                </div>
                            </td>
                            <td>
                                <span class="table-badge">${ticket.category || 'Sem categoria'}</span>
                            </td>
                            <td>
                                <span class="table-date">${ticket.date}</span>
                            </td>
                            <td style="text-align: right;">
                                <button class="table-action">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
        } else {
            recentBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">Nenhum chamado encontrado</td></tr>';
        }

        const fullBody = document.getElementById('tickets-full-body');
        if (tickets.length > 0) {
            const statusMap = {
                1: 'Novo',
                2: 'Em Atendimento',
                3: 'Planejado',
                4: 'Pendente',
                5: 'Solucionado',
                6: 'Fechado'
            };

            fullBody.innerHTML = tickets.map(ticket => `
                        <tr>
                            <td><strong>#${ticket.id}</strong></td>
                            <td>${ticket.name}</td>
                            <td>
                                <span class="table-badge" style="background: ${getStatusBg(ticket.status)}; color: ${getStatusColor(ticket.status)}; border: none;">
                                    ${statusMap[ticket.status] || 'Outro'}
                                </span>
                            </td>
                            <td>${ticket.user_name || '-'}</td>
                            <td>${ticket.date}</td>
                        </tr>
                    `).join('');
        } else {
            fullBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">Nenhum chamado encontrado</td></tr>';
        }
    } catch (error) {
        console.error('Error loading tickets:', error);
    }
}

// ==================== CYBERPUNK ASSETS & MODAL ====================
async function loadFullAssets() {
    const gridContainer = document.getElementById('assets-grid');
    if (!gridContainer) return;

    try {
        const response = await fetch('api.php?action=assets_list');
        assetsDataGlobal = await response.json(); // Salva na variável global

        if (!assetsDataGlobal || assetsDataGlobal.length === 0) {
            gridContainer.innerHTML = '<div class="text-center p-5 text-muted">Nenhum ativo encontrado no sistema.</div>';
            return;
        }

        gridContainer.innerHTML = assetsDataGlobal.map((asset, index) => {
            const name = (asset.name || '').toLowerCase();
            const model = (asset.model || '').toLowerCase();
            
            // Ícone base
            let typeIcon = 'fa-desktop';
            if (name.includes('srv') || name.includes('server')) typeIcon = 'fa-server';
            if (name.includes('nb') || name.includes('laptop') || model.includes('latitude')) typeIcon = 'fa-laptop';

            // Cor de status
            const statusColor = (asset.status === 'Em manutenção') ? '#ffee00' : '#00f3ff';
            
            // --- LÓGICA DE DISCO PARA O GRID (CORRIGIDA) ---
            let diskHtml = '';
            
            // Converte para float e garante que é número (se falhar vira 0)
            const diskTotal = parseFloat(asset.disk_total) || 0;
            const diskFree = parseFloat(asset.disk_free) || 0;

            if (diskTotal > 0) {
                const used = diskTotal - diskFree;
                // Evita divisão por zero
                const percent = Math.min((used / diskTotal) * 100, 100).toFixed(0);
                
                let barColor = 'var(--neon-blue)';
                if(percent > 80) barColor = 'var(--warning)';
                if(percent > 90) barColor = 'var(--danger)';

                diskHtml = `
                    <div style="margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: rgba(255,255,255,0.7); font-family: 'Share Tech Mono';">
                            <span>STORAGE</span>
                            <span>${percent}%</span>
                        </div>
                        <div style="width: 100%; height: 4px; background: rgba(255,255,255,0.1); margin-top: 2px;">
                            <div style="width: ${percent}%; height: 100%; background: ${barColor}; box-shadow: 0 0 5px ${barColor};"></div>
                        </div>
                    </div>
                `;
            } else {
                // Caso não tenha disco (evita o NaN)
                diskHtml = `<div style="margin-top: 10px; font-size: 0.7rem; color: rgba(255,255,255,0.3); font-family: 'Share Tech Mono';">NO DISK DATA</div>`;
            }

            return `
                <div class="asset-card-cyber" onclick="openCyberModal(${index})">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div class="cyber-card-title">${asset.name}</div>
                            <div class="cyber-card-meta">
                                <i class="fas ${typeIcon}"></i> ${asset.model || 'Unknown Unit'}
                            </div>
                        </div>
                        <div style="width: 10px; height: 10px; background: ${statusColor}; border-radius: 50%; box-shadow: 0 0 8px ${statusColor};"></div>
                    </div>
                    
                    ${diskHtml}

                    <div style="margin-top: 10px; font-family: 'Share Tech Mono'; font-size: 0.7rem; color: rgba(255,255,255,0.5); text-align: right;">
                        :: CLICK DETALHES ::
                    </div>
                </div>
            `;
        }).join('');

    } catch (error) {
        console.error('Error loading assets:', error);
        gridContainer.innerHTML = '<div class="text-center p-5 text-danger">Erro ao carregar lista de ativos.</div>';
    }
}

// Função de abrir o Modal (Chamada pelo onclick do HTML gerado acima)
function openCyberModal(index) {
    const asset = assetsDataGlobal[index];
    if (!asset) return;

    // Preenche IDs básicos
    document.getElementById('modal-id').innerText = (asset.id || '0').toString().padStart(3, '0');
    document.getElementById('modal-name').innerText = asset.name || 'UNKNOWN';
    document.getElementById('modal-sub').innerText = (asset.model || 'GENERIC HARDWARE').toUpperCase();
    document.getElementById('modal-status').innerText = (asset.status || 'ONLINE').toUpperCase();
    document.getElementById('modal-loc').innerText = (asset.location || 'UNKNOWN SECTOR').toUpperCase();
    
    // OS com Ícone
    const osName = (asset.os_name || 'NO OS DETECTED');
    let osIcon = 'fa-microchip';
    if(osName.toLowerCase().includes('windows')) osIcon = 'fa-windows';
    if(osName.toLowerCase().includes('linux')) osIcon = 'fa-linux';
    if(osName.toLowerCase().includes('apple') || osName.toLowerCase().includes('mac')) osIcon = 'fa-apple';
    document.getElementById('modal-os').innerHTML = `<i class="fab ${osIcon}"></i> ${osName}`;

    // Hardware
    const cpuName = (asset.cpu || 'Generic Processor').replace(/Intel|AMD|Core|Ryzen/gi, '').trim();
    document.getElementById('modal-cpu').innerText = cpuName.substring(0, 20); // Limita tamanho
    document.getElementById('modal-serial').innerText = asset.serial || 'NO-SERIAL-KEY';

    // --- LÓGICA DE DISCO PARA O MODAL (CORRIGIDA) ---
    // parseFloat garante número, || 0 garante que não seja NaN
    const diskTotalMB = parseFloat(asset.disk_total) || 0;
    const diskFreeMB = parseFloat(asset.disk_free) || 0;
    const diskUsedMB = diskTotalMB - diskFreeMB;

    let hddLabel = 'SEM DADOS DE DISCO';
    let hddPercent = 0;

    if (diskTotalMB > 0) {
        // Converte para GB
        const totalGB = (diskTotalMB / 1024).toFixed(0);
        const freeGB = (diskFreeMB / 1024).toFixed(0);
        
        hddLabel = `${freeGB} GB LIVRES / ${totalGB} GB TOTAL`;
        hddPercent = (diskUsedMB / diskTotalMB) * 100;
    }

    // RAM (Mantém lógica anterior)
    const ramVal = asset.ram_total ? (parseInt(asset.ram_total)/1024).toFixed(0) : 0;
    document.getElementById('modal-ram').innerText = ramVal > 0 ? ramVal + ' GB INSTALADO' : 'N/A';
    
    // Atualiza Texto do HDD no Modal
    document.getElementById('modal-hdd').innerText = hddLabel;

    // Cálculo visual das barras
    const ramPercent = Math.min((ramVal / 32) * 100, 100) || 10; 
    
    // Exibir Modal
    const modal = document.getElementById('cyber-modal');
    modal.classList.add('active');

    // Animação CSS das barras
    setTimeout(() => {
        document.getElementById('bar-ram').style.width = ramPercent + '%';
        
        // Barra do HD reflete a PORCENTAGEM DE USO (Quanto maior, mais cheio)
        const hddBar = document.getElementById('bar-hdd');
        hddBar.style.width = hddPercent + '%';
        
        // Muda cor se estiver cheio
        if(hddPercent > 90) {
            hddBar.style.background = 'var(--danger)';
            hddBar.style.boxShadow = '0 0 10px var(--danger)';
        } else {
            hddBar.style.background = 'linear-gradient(90deg, var(--neon-blue), var(--neon-pink))';
            hddBar.style.boxShadow = '0 0 10px var(--neon-blue)';
        }

    }, 100);
}

function closeCyberModal() {
    const modal = document.getElementById('cyber-modal');
    modal.classList.remove('active');
    
    // Reseta barras para próxima animação
    document.getElementById('bar-ram').style.width = '0%';
    document.getElementById('bar-hdd').style.width = '0%';
}

// Fechar ao clicar fora (Overlay)
document.addEventListener('click', (e) => {
    if (e.target.id === 'cyber-modal') {
        closeCyberModal();
    }
});

// ==================== SLA MONITOR ====================
function initSLAMonitor() {
    updateSLAData();
    setInterval(updateSLACountdowns, 1000);
}

function updateSLAData() {
    const slaItems = [
        { id: 1, title: 'Problema de rede - Setor Financeiro', ticket: '#2234', deadline: new Date(Date.now() + 30 * 60000), status: 'critical' },
        { id: 2, title: 'Instalação Office 365', ticket: '#2231', deadline: new Date(Date.now() + 120 * 60000), status: 'warning' },
        { id: 3, title: 'Manutenção preventiva', ticket: '#2228', deadline: new Date(Date.now() + 480 * 60000), status: 'ok' },
        { id: 4, title: 'Impressora offline', ticket: '#2225', deadline: new Date(Date.now() + 15 * 60000), status: 'critical' },
        { id: 5, title: 'Novo usuário - Onboarding', ticket: '#2220', deadline: new Date(Date.now() + 300 * 60000), status: 'ok' }
    ];

    renderSLAList(slaItems);
    updateSLASummary(slaItems);
}

function renderSLAList(items) {
    const container = document.getElementById('slaList');
    if (!container) return;

    container.innerHTML = items.map(item => `
                <div class="sla-item ${item.status}">
                    <div class="sla-icon-item ${item.status}">
                        <i class="fas ${item.status === 'critical' ? 'fa-exclamation-triangle' : item.status === 'warning' ? 'fa-clock' : 'fa-check-circle'}"></i>
                    </div>
                    <div class="sla-info">
                        <div class="sla-title">${item.title}</div>
                        <div class="sla-subtitle">Chamado ${item.ticket}</div>
                    </div>
                    <div class="sla-countdown">
                        <div class="sla-time ${item.status}" data-deadline="${item.deadline.getTime()}">--:--</div>
                        <div class="sla-label">Restante</div>
                    </div>
                </div>
            `).join('');
}

function updateSLACountdowns() {
    document.querySelectorAll('.sla-time[data-deadline]').forEach(el => {
        const deadline = parseInt(el.getAttribute('data-deadline'));
        const now = Date.now();
        const diff = deadline - now;

        if (diff <= 0) {
            el.textContent = 'VENCIDO';
            return;
        }

        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        el.textContent = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });
}

function updateSLASummary(items) {
    const critical = items.filter(i => i.status === 'critical').length;
    const warning = items.filter(i => i.status === 'warning').length;
    const ok = items.filter(i => i.status === 'ok').length;

    setVal('slaCritical', critical);
    setVal('slaWarning', warning);
    setVal('slaOk', ok);
}

// ==================== NOTIFICATIONS ====================
function initNotifications() {
    const initialNotifications = [
        { id: 1, type: 'danger', text: 'SLA crítico: Chamado #2234 vence em 30 minutos', time: 'Agora', unread: true },
        { id: 2, type: 'success', text: 'Chamado #2220 foi resolvido', time: '5 min atrás', unread: true },
        { id: 3, type: 'info', text: 'Novo chamado #2235 atribuído a você', time: '15 min atrás', unread: true }
    ];

    renderNotifications(initialNotifications);
}

function renderNotifications(notifications) {
    const list = document.getElementById('notificationList');

    if (notifications.length === 0) {
        list.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-muted);">Nenhuma notificação</p>';
        return;
    }

    list.innerHTML = notifications.map(n => `
                <div class="notification-item ${n.unread ? 'unread' : ''}">
                    <div class="notification-item-header">
                        <div class="notification-icon ${n.type}">
                            <i class="fas ${getNotificationIcon(n.type)}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-text">${n.text}</div>
                            <div class="notification-time">${n.time}</div>
                        </div>
                    </div>
                </div>
            `).join('');
}

function getNotificationIcon(type) {
    const icons = {
        info: 'fa-info-circle',
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        danger: 'fa-exclamation-circle'
    };
    return icons[type] || 'fa-bell';
}

function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    panel.classList.toggle('show');
}

function clearNotifications() {
    renderNotifications([]);
    notificationCount = 0;
    document.getElementById('notificationBadge').style.display = 'none';
}

function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification('GLPI Dashboard', {
                    body: 'Notificações desktop ativadas com sucesso!',
                    icon: '/favicon.ico'
                });
            }
        });
    }
}

// ==================== UTILITY FUNCTIONS ====================
function getStatusClass(status) {
    if (status == 5 || status == 6) return 'success';
    if (status == 2 || status == 3) return 'primary';
    return 'primary';
}

function getStatusIcon(status) {
    if (status == 5 || status == 6) return 'fa-check-circle';
    if (status == 2 || status == 3) return 'fa-spinner fa-pulse';
    if (status == 4) return 'fa-pause-circle';
    return 'fa-ticket-alt';
}

function getStatusColor(status) {
    if (status == 1) return '#22c55e';
    if (status == 2 || status == 3) return '#3b82f6';
    if (status == 4) return '#f59e0b';
    if (status == 5 || status == 6) return '#64748b';
    return '#ef4444';
}

function getStatusBg(status) {
    if (status == 1) return 'rgba(34, 197, 94, 0.15)';
    if (status == 2 || status == 3) return 'rgba(59, 130, 246, 0.15)';
    if (status == 4) return 'rgba(245, 158, 11, 0.15)';
    if (status == 5 || status == 6) return 'rgba(100, 116, 139, 0.15)';
    return 'rgba(239, 68, 68, 0.15)';
}

document.addEventListener('click', (e) => {
    const panel = document.getElementById('notificationPanel');
    const btn = e.target.closest('.icon-btn');

    if (panel && !panel.contains(e.target) && !btn) {
        panel.classList.remove('show');
    }
});

// ==================== GAMIFICAÇÃO: RENDER RANKING ====================
async function renderLeaderboard() {
    const container = document.getElementById('leaderboard-container');
    if (!container) return;

    try {
        const response = await fetch('api.php?action=get_ranking');
        const technicians = await response.json();

        if (!technicians || technicians.length === 0) {
            container.innerHTML = '<div class="text-center p-5 text-muted">Nenhum chamado finalizado este mês.</div>';
            return;
        }

        const maxPoints = Math.max(...technicians.map(t => t.points)) || 1;

        container.innerHTML = technicians.map((tech, index) => {
            const rank = index + 1;
            const percent = (tech.points / maxPoints) * 100;

            let rankDisplay = `<span style="font-weight: 600; color: var(--text-muted); width: 30px; text-align:center;">#${rank}</span>`;
            if (index === 0) rankDisplay = `<span style="font-size: 1.4rem; width: 30px; text-align:center;">👑</span>`;

            const itemClass = index < 3 ? `rank-${rank}` : 'rank-other';

            return `
                <div class="leaderboard-item ${itemClass}" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--card-bg); border-bottom: 1px solid var(--border-color); margin-bottom: 8px; border-radius: 12px;">
                    
                    <div>${rankDisplay}</div>

                    <div style="
                        width: 46px; 
                        height: 46px; 
                        border-radius: 50%; 
                        background: ${tech.color}20; 
                        color: ${tech.color}; 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        font-weight: 700;
                        border: 2px solid ${tech.color};
                        font-size: 1.1rem;
                        flex-shrink: 0;
                    ">
                        ${tech.avatar}
                    </div>

                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-weight: 600; color: var(--text-main); font-size: 0.95rem;">
                                ${tech.name}
                            </span>
                            <span style="
                                background: ${tech.color}15; 
                                color: ${tech.color}; 
                                padding: 2px 8px; 
                                border-radius: 12px; 
                                font-size: 0.85rem; 
                                font-weight: 700;
                            ">
                                ${tech.points} pts
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: var(--text-sec); margin-bottom: 6px;">
                            <i class="fas fa-check-circle" style="color: var(--success);"></i>
                            <span>${tech.tickets} Chamados resolvidos</span>
                        </div>

                        <div style="width: 100%; height: 6px; background: var(--bg-main); border-radius: 10px; overflow: hidden;">
                            <div style="
                                width: 0%; 
                                height: 100%; 
                                background: ${tech.color}; 
                                border-radius: 10px;
                                transition: width 1s ease-in-out;
                            " class="progress-bar-anim" data-width="${percent}%"></div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        setTimeout(() => {
            const bars = document.querySelectorAll('.progress-bar-anim');
            bars.forEach(bar => {
                bar.style.width = bar.getAttribute('data-width');
            });
        }, 100);

    } catch (error) {
        console.error('Erro ao carregar ranking:', error);
        container.innerHTML = '<div class="text-center p-5 text-danger">Erro de conexão com a API.</div>';
    }
}