<?php
// 1. PREVENÇÃO DE ERRO JSON: Desativa exibição de warnings no output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Bloco de conexão
try {
    if (!file_exists('db.php')) {
        throw new Exception("Arquivo db.php não encontrado.");
    }
    require 'db.php';
    
    if (!isset($pdo)) {
        throw new Exception("Falha na conexão: Variável \$pdo não definida em db.php");
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de Conexão: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'dashboard_data') {
        
        // --- 1. CÁLCULOS GERAIS ---
        $total = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE is_deleted = 0")->fetchColumn();
        
        $abertos     = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE status = 1 AND is_deleted = 0")->fetchColumn();
        $andamento   = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE status IN (2, 3) AND is_deleted = 0")->fetchColumn();
        $pendentes   = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE status = 4 AND is_deleted = 0")->fetchColumn();
        $finalizados = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE status IN (5, 6) AND is_deleted = 0")->fetchColumn();
        
        $slaVencido  = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE status NOT IN (5,6) AND time_to_resolve < NOW() AND is_deleted = 0")->fetchColumn();

        $taxa = ($total > 0) ? ($finalizados / $total) * 100 : 0;
        $taxaExibida = round($taxa); 

        $sqlTempo = "SELECT AVG(TIMESTAMPDIFF(SECOND, date, solvedate)) FROM glpi_tickets WHERE status IN (5,6) AND solvedate >= DATE(NOW()) - INTERVAL 30 DAY AND is_deleted = 0";
        $segundosMedios = $pdo->query($sqlTempo)->fetchColumn();
        $tempoMedioHoras = $segundosMedios ? round($segundosMedios / 3600) : 0;

        $reabertos = $pdo->query("SELECT COUNT(*) FROM glpi_tickets WHERE status NOT IN (5,6) AND is_deleted = 0 AND date < DATE(NOW()) - INTERVAL 3 DAY AND date_mod >= DATE(NOW()) - INTERVAL 1 DAY")->fetchColumn();

        // --- 2. GRÁFICOS ---
        
        // Fluxo de Criação (Aumentado para 30 dias para melhor visualização)
        $sqlTrend = "SELECT DATE_FORMAT(date, '%d/%m') as dia, COUNT(*) as total 
                     FROM glpi_tickets 
                     WHERE date >= DATE(NOW()) - INTERVAL 30 DAY 
                     AND is_deleted = 0
                     GROUP BY DATE_FORMAT(date, '%d/%m'), DATE(date) 
                     ORDER BY DATE(date) ASC";
        $trendData = $pdo->query($sqlTrend)->fetchAll(PDO::FETCH_ASSOC);

        // Top Categorias
        $sqlCats = "SELECT c.completename as nome, COUNT(t.id) as total 
                    FROM glpi_tickets t 
                    JOIN glpi_itilcategories c ON t.itilcategories_id = c.id 
                    WHERE t.is_deleted = 0 
                    GROUP BY c.id, c.completename 
                    ORDER BY total DESC LIMIT 5";
        $catData = $pdo->query($sqlCats)->fetchAll(PDO::FETCH_ASSOC);

        // --- NOVO: Abertos por Mês (Últimos 6 meses) ---
        $sqlOpenedMonth = "
            SELECT DATE_FORMAT(date, '%Y-%m') as mes_ano, COUNT(*) as total
            FROM glpi_tickets
            WHERE date >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH, '%Y-%m-01')
            AND is_deleted = 0
            GROUP BY mes_ano
            ORDER BY mes_ano ASC
        ";
        $openedData = $pdo->query($sqlOpenedMonth)->fetchAll(PDO::FETCH_ASSOC);

        // --- NOVO: Solucionados por Mês (Últimos 6 meses) ---
        $sqlSolvedMonth = "
            SELECT DATE_FORMAT(solvedate, '%Y-%m') as mes_ano, COUNT(*) as total
            FROM glpi_tickets
            WHERE solvedate >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH, '%Y-%m-01')
            AND status IN (5, 6) -- 5=Solucionado, 6=Fechado
            AND is_deleted = 0
            GROUP BY mes_ano
            ORDER BY mes_ano ASC
        ";
        $solvedData = $pdo->query($sqlSolvedMonth)->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'cards_top' => [
                'total'       => $total,
                'andamento'   => $andamento,
                'taxa'        => $taxaExibida,
                'sla'         => $slaVencido,
                'tempo_medio' => $tempoMedioHoras,
                'reabertos'   => $reabertos
            ],
            'cards_bottom' => [
                'abertos'     => $abertos,
                'atribuidos'  => $andamento,
                'pendentes'   => $pendentes,
                'finalizados' => $finalizados
            ],
            'charts' => [
                'trend_line'     => $trendData,
                'cat_bar'        => $catData,
                'monthly_opened' => $openedData, // Dados novos para o gráfico mensal
                'monthly_solved' => $solvedData  // Dados novos para o gráfico mensal
            ]
        ]);
    }

    // --- RANKING MELHORADO ---
    elseif ($action === 'get_ranking') {
        $sqlRanking = "
            SELECT 
                TRIM(CONCAT(IFNULL(u.firstname, u.name), ' ', IFNULL(u.realname, ''))) as name,
                UPPER(CONCAT(LEFT(IFNULL(u.firstname, u.name), 1), LEFT(IFNULL(u.realname, ''), 1))) as avatar,
                COUNT(t.id) as tickets,
                (COUNT(t.id) * 10) as points
            FROM glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id
            INNER JOIN glpi_users u ON tu.users_id = u.id
            WHERE tu.type = 2 
            AND t.status IN (5, 6) 
            AND t.is_deleted = 0
            AND MONTH(t.solvedate) = MONTH(CURRENT_DATE())
            AND YEAR(t.solvedate) = YEAR(CURRENT_DATE())
            GROUP BY u.id, u.firstname, u.name, u.realname
            ORDER BY points DESC
            LIMIT 20
        ";
        
        $res = $pdo->query($sqlRanking)->fetchAll(PDO::FETCH_ASSOC);
        $colors = ['#3b82f6', '#a855f7', '#22c55e', '#f59e0b', '#06b6d4'];
        
        foreach ($res as $key => $value) {
            $colorIndex = $key % count($colors);
            $res[$key]['color'] = $colors[$colorIndex];
            if (empty($res[$key]['avatar'])) { $res[$key]['avatar'] = 'T'; }
        }
        
        echo json_encode($res);
    }

    // --- LISTAS ---
    elseif ($action === 'tickets_list') {
        $sql = "SELECT t.id, t.name, t.status, t.date, t.priority, IFNULL(c.completename, 'Sem Categoria') AS category, IFNULL(u.name, 'N/A') AS user_name
                FROM glpi_tickets AS t
                LEFT JOIN glpi_itilcategories AS c ON t.itilcategories_id = c.id
                LEFT JOIN glpi_users AS u ON t.users_id_recipient = u.id
                WHERE t.status IN (1,2,3,4) AND t.is_deleted = 0
                ORDER BY t.priority DESC, t.date DESC LIMIT 100";
        echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    // --- ATIVOS COM TRATAMENTO DE NULOS (COALESCE) ---
    elseif ($action === 'assets_list') {
        $sql = "SELECT 
                c.id, 
                c.name, 
                c.serial, 
                loc.completename AS location,
                cm.name AS model, 
                man.name AS manufacturer,
                st.completename AS status,
                
                (SELECT GROUP_CONCAT(DISTINCT os.name SEPARATOR ', ') 
                 FROM glpi_items_operatingsystems ios 
                 LEFT JOIN glpi_operatingsystems os ON ios.operatingsystems_id = os.id 
                 WHERE ios.items_id = c.id AND ios.itemtype = 'Computer' AND ios.is_deleted = 0
                ) AS os_name,
                
                (SELECT designation 
                 FROM glpi_deviceprocessors dp 
                 INNER JOIN glpi_items_deviceprocessors idp ON dp.id = idp.deviceprocessors_id 
                 WHERE idp.items_id = c.id AND idp.itemtype = 'Computer' 
                 LIMIT 1
                ) AS cpu,
                
                -- COALESCE garante que retorne 0 se for nulo
                COALESCE((SELECT SUM(size) 
                 FROM glpi_items_devicememories 
                 WHERE items_id = c.id AND itemtype = 'Computer' AND is_deleted = 0
                ), 0) AS ram_total,
                
                -- DISCO LÓGICO (Total)
                COALESCE((SELECT SUM(totalsize) 
                 FROM glpi_items_disks 
                 WHERE items_id = c.id AND itemtype = 'Computer' AND is_deleted = 0
                ), 0) AS disk_total,

                -- DISCO LÓGICO (Livre)
                COALESCE((SELECT SUM(freesize) 
                 FROM glpi_items_disks 
                 WHERE items_id = c.id AND itemtype = 'Computer' AND is_deleted = 0
                ), 0) AS disk_free

            FROM glpi_computers c
            LEFT JOIN glpi_locations loc ON c.locations_id = loc.id
            LEFT JOIN glpi_computermodels cm ON c.computermodels_id = cm.id 
            LEFT JOIN glpi_manufacturers man ON c.manufacturers_id = man.id
            LEFT JOIN glpi_states st ON c.states_id = st.id
            
            WHERE c.is_deleted = 0 AND c.is_template = 0
            ORDER BY c.name ASC
            LIMIT 100
        ";
        
        echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>