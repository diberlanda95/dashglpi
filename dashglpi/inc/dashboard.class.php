<?php

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

/**
 * Plugin DashGLPI - Classe de queries do dashboard
 *
 * Todas as queries usam $DB->request() com array de critérios (GLPI 11 obrigatório).
 * Queries complexas usam QueryExpression, QueryFunction e QuerySubQuery.
 */
class PluginDashglpiDashboard
{
    /**
     * Retorna dados dos cards KPI + gráficos
     */
    public static function getDashboardData(): array
    {
        global $DB;

        $entityCriteria = getEntitiesRestrictCriteria('glpi_tickets');

        // --- Cards KPI ---
        $total       = self::countTickets($DB, $entityCriteria, []);
        $abertos     = self::countTickets($DB, $entityCriteria, ['status' => 1]);
        $andamento   = self::countTickets($DB, $entityCriteria, ['status' => [2, 3]]);
        $pendentes   = self::countTickets($DB, $entityCriteria, ['status' => 4]);
        $finalizados = self::countTickets($DB, $entityCriteria, ['status' => [5, 6]]);

        // SLA vencido: não finalizados com time_to_resolve no passado
        $slaVencido = self::countTickets($DB, $entityCriteria, [
            'NOT' => ['status' => [5, 6]],
            ['NOT' => ['time_to_resolve' => null]],
            new QueryExpression('`glpi_tickets`.`time_to_resolve` < NOW()'),
        ]);

        $taxa = ($total > 0) ? round(($finalizados / $total) * 100) : 0;

        // Tempo médio de resolução (últimos 30 dias)
        $tempoMedioHoras = 0;
        $result = $DB->request([
            'SELECT' => [
                QueryFunction::avg(
                    QueryFunction::timestampdiff('SECOND', 'glpi_tickets.date', 'glpi_tickets.solvedate'),
                    'avg_sec'
                ),
            ],
            'FROM'  => 'glpi_tickets',
            'WHERE' => array_merge([
                'status'     => [5, 6],
                'is_deleted' => 0,
                new QueryExpression('`glpi_tickets`.`solvedate` >= DATE(NOW()) - INTERVAL 30 DAY'),
            ], $entityCriteria),
        ]);
        $row = $result->current();
        if (!empty($row['avg_sec'])) {
            $tempoMedioHours = round($row['avg_sec'] / 3600);
            $tempoMedioHoras = $tempoMedioHours;
        }

        // Reabertos (heurística: não finalizados, criados há >3 dias, modificados recentemente)
        $reabertos = self::countTickets($DB, $entityCriteria, [
            'NOT' => ['status' => [5, 6]],
            new QueryExpression('`glpi_tickets`.`date` < DATE(NOW()) - INTERVAL 3 DAY'),
            new QueryExpression('`glpi_tickets`.`date_mod` >= DATE(NOW()) - INTERVAL 1 DAY'),
        ]);

        // --- Gráficos ---

        // Fluxo de criação (últimos 30 dias)
        $trendData = [];
        $iterator = $DB->request([
            'SELECT' => [
                QueryFunction::dateFormat('glpi_tickets.date', '%d/%m', 'dia'),
                QueryFunction::count('glpi_tickets.id', false, 'total'),
            ],
            'FROM'  => 'glpi_tickets',
            'WHERE' => array_merge([
                'is_deleted' => 0,
                new QueryExpression('`glpi_tickets`.`date` >= DATE(NOW()) - INTERVAL 30 DAY'),
            ], $entityCriteria),
            'GROUPBY' => [
                new QueryExpression('DATE(`glpi_tickets`.`date`)'),
            ],
            'ORDER' => [new QueryExpression('DATE(`glpi_tickets`.`date`) ASC')],
        ]);
        foreach ($iterator as $row) {
            $trendData[] = ['dia' => $row['dia'], 'total' => (int) $row['total']];
        }

        // Top 5 categorias
        $catData = [];
        $catEntityCriteria = getEntitiesRestrictCriteria('t');
        $iterator = $DB->request([
            'SELECT' => [
                'c.completename AS nome',
                QueryFunction::count('t.id', false, 'total'),
            ],
            'FROM'   => 'glpi_tickets AS t',
            'JOIN'   => [
                'glpi_itilcategories AS c' => [
                    'ON' => [
                        'c' => 'id',
                        't' => 'itilcategories_id',
                    ],
                ],
            ],
            'WHERE'  => array_merge([
                't.is_deleted' => 0,
            ], $catEntityCriteria),
            'GROUPBY' => ['c.id', 'c.completename'],
            'ORDER'   => ['total DESC'],
            'LIMIT'   => 5,
        ]);
        foreach ($iterator as $row) {
            $catData[] = ['nome' => $row['nome'], 'total' => (int) $row['total']];
        }

        // Abertos por mês (últimos 6 meses)
        $openedData = [];
        $iterator = $DB->request([
            'SELECT' => [
                QueryFunction::dateFormat('glpi_tickets.date', '%Y-%m', 'mes_ano'),
                QueryFunction::count('glpi_tickets.id', false, 'total'),
            ],
            'FROM'  => 'glpi_tickets',
            'WHERE' => array_merge([
                'is_deleted' => 0,
                new QueryExpression("`glpi_tickets`.`date` >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH, '%Y-%m-01')"),
            ], $entityCriteria),
            'GROUPBY' => [new QueryExpression("DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')")],
            'ORDER'   => ['mes_ano ASC'],
        ]);
        foreach ($iterator as $row) {
            $openedData[] = ['mes_ano' => $row['mes_ano'], 'total' => (int) $row['total']];
        }

        // Solucionados por mês (últimos 6 meses)
        $solvedData = [];
        $iterator = $DB->request([
            'SELECT' => [
                QueryFunction::dateFormat('glpi_tickets.solvedate', '%Y-%m', 'mes_ano'),
                QueryFunction::count('glpi_tickets.id', false, 'total'),
            ],
            'FROM'  => 'glpi_tickets',
            'WHERE' => array_merge([
                'status'     => [5, 6],
                'is_deleted' => 0,
                new QueryExpression("`glpi_tickets`.`solvedate` >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH, '%Y-%m-01')"),
            ], $entityCriteria),
            'GROUPBY' => [new QueryExpression("DATE_FORMAT(`glpi_tickets`.`solvedate`, '%Y-%m')")],
            'ORDER'   => ['mes_ano ASC'],
        ]);
        foreach ($iterator as $row) {
            $solvedData[] = ['mes_ano' => $row['mes_ano'], 'total' => (int) $row['total']];
        }

        return [
            'cards_top' => [
                'total'       => (int) $total,
                'andamento'   => (int) $andamento,
                'taxa'        => $taxa,
                'sla'         => (int) $slaVencido,
                'tempo_medio' => $tempoMedioHoras,
                'reabertos'   => (int) $reabertos,
            ],
            'cards_bottom' => [
                'abertos'     => (int) $abertos,
                'atribuidos'  => (int) $andamento,
                'pendentes'   => (int) $pendentes,
                'finalizados' => (int) $finalizados,
            ],
            'charts' => [
                'trend_line'     => $trendData,
                'cat_bar'        => $catData,
                'monthly_opened' => $openedData,
                'monthly_solved' => $solvedData,
            ],
        ];
    }

    /**
     * Retorna ranking de técnicos do mês
     */
    public static function getRanking(): array
    {
        global $DB;

        $entityCriteria = getEntitiesRestrictCriteria('t');

        $iterator = $DB->request([
            'SELECT' => [
                new QueryExpression("TRIM(CONCAT(IFNULL(`u`.`firstname`, `u`.`name`), ' ', IFNULL(`u`.`realname`, ''))) AS `name`"),
                new QueryExpression("UPPER(CONCAT(LEFT(IFNULL(`u`.`firstname`, `u`.`name`), 1), LEFT(IFNULL(`u`.`realname`, ''), 1))) AS `avatar`"),
                QueryFunction::count('t.id', false, 'tickets'),
                new QueryExpression('COUNT(`t`.`id`) * 10 AS `points`'),
            ],
            'FROM'  => 'glpi_tickets AS t',
            'INNER JOIN' => [
                'glpi_tickets_users AS tu' => [
                    'ON' => [
                        'tu' => 'tickets_id',
                        't'  => 'id',
                    ],
                ],
                'glpi_users AS u' => [
                    'ON' => [
                        'u'  => 'id',
                        'tu' => 'users_id',
                    ],
                ],
            ],
            'WHERE' => array_merge([
                'tu.type'      => 2,
                't.status'     => [5, 6],
                't.is_deleted' => 0,
                ['NOT' => ['t.solvedate' => null]],
                new QueryExpression('MONTH(`t`.`solvedate`) = MONTH(CURRENT_DATE())'),
                new QueryExpression('YEAR(`t`.`solvedate`) = YEAR(CURRENT_DATE())'),
            ], $entityCriteria),
            'GROUPBY' => ['u.id', 'u.firstname', 'u.name', 'u.realname'],
            'ORDER'   => ['points DESC'],
            'LIMIT'   => 20,
        ]);

        $colors = ['#3b82f6', '#a855f7', '#22c55e', '#f59e0b', '#06b6d4'];
        $ranking = [];

        foreach ($iterator as $key => $row) {
            $item = [
                'name'    => $row['name'],
                'avatar'  => !empty($row['avatar']) ? $row['avatar'] : 'T',
                'tickets' => (int) $row['tickets'],
                'points'  => (int) $row['points'],
                'color'   => $colors[$key % count($colors)],
            ];
            $ranking[] = $item;
        }

        return $ranking;
    }

    /**
     * Retorna lista de tickets ativos
     */
    public static function getTicketsList(): array
    {
        global $DB;

        $entityCriteria = getEntitiesRestrictCriteria('t');

        $iterator = $DB->request([
            'SELECT' => [
                't.id', 't.name', 't.status', 't.date', 't.priority',
                new QueryExpression("IFNULL(`c`.`completename`, 'Sem Categoria') AS `category`"),
                new QueryExpression("IFNULL(`u`.`name`, 'N/A') AS `user_name`"),
            ],
            'FROM' => 'glpi_tickets AS t',
            'LEFT JOIN' => [
                'glpi_itilcategories AS c' => [
                    'ON' => [
                        'c' => 'id',
                        't' => 'itilcategories_id',
                    ],
                ],
                'glpi_users AS u' => [
                    'ON' => [
                        'u' => 'id',
                        't' => 'users_id_recipient',
                    ],
                ],
            ],
            'WHERE' => array_merge([
                't.status'     => [1, 2, 3, 4],
                't.is_deleted' => 0,
            ], $entityCriteria),
            'ORDER' => ['t.priority DESC', 't.date DESC'],
            'LIMIT' => 100,
        ]);

        $tickets = [];
        foreach ($iterator as $row) {
            $tickets[] = $row;
        }

        return $tickets;
    }

    /**
     * Retorna lista de computadores/ativos
     */
    public static function getAssetsList(): array
    {
        global $DB;

        $entityCriteria = getEntitiesRestrictCriteria('c');

        $iterator = $DB->request([
            'SELECT' => [
                'c.id', 'c.name', 'c.serial',
                'loc.completename AS location',
                'cm.name AS model',
                'man.name AS manufacturer',
                'st.completename AS status',
            ],
            'FROM' => 'glpi_computers AS c',
            'LEFT JOIN' => [
                'glpi_locations AS loc' => [
                    'ON' => [
                        'loc' => 'id',
                        'c'   => 'locations_id',
                    ],
                ],
                'glpi_computermodels AS cm' => [
                    'ON' => [
                        'cm' => 'id',
                        'c'  => 'computermodels_id',
                    ],
                ],
                'glpi_manufacturers AS man' => [
                    'ON' => [
                        'man' => 'id',
                        'c'   => 'manufacturers_id',
                    ],
                ],
                'glpi_states AS st' => [
                    'ON' => [
                        'st' => 'id',
                        'c'  => 'states_id',
                    ],
                ],
            ],
            'WHERE' => array_merge([
                'c.is_deleted'  => 0,
                'c.is_template' => 0,
            ], $entityCriteria),
            'ORDER' => ['c.name ASC'],
            'LIMIT' => 100,
        ]);

        // Coletar IDs e dados base
        $assets = [];
        $ids    = [];
        foreach ($iterator as $row) {
            $ids[]                    = (int) $row['id'];
            $assets[(int) $row['id']] = $row;
        }

        if (empty($ids)) {
            return [];
        }

        // Bulk: OS
        $osMap      = [];
        $osIterator = $DB->request([
            'SELECT'    => ['ios.items_id', 'os.name'],
            'FROM'      => 'glpi_items_operatingsystems AS ios',
            'LEFT JOIN' => [
                'glpi_operatingsystems AS os' => [
                    'ON' => ['os' => 'id', 'ios' => 'operatingsystems_id'],
                ],
            ],
            'WHERE' => [
                'ios.items_id'   => $ids,
                'ios.itemtype'   => 'Computer',
                'ios.is_deleted' => 0,
            ],
        ]);
        foreach ($osIterator as $row) {
            $itemId = (int) $row['items_id'];
            if (!empty($row['name'])) {
                $osMap[$itemId][] = $row['name'];
            }
        }

        // Bulk: CPU
        $cpuMap      = [];
        $cpuIterator = $DB->request([
            'SELECT'     => ['idp.items_id', 'dp.designation'],
            'FROM'       => 'glpi_items_deviceprocessors AS idp',
            'INNER JOIN' => [
                'glpi_deviceprocessors AS dp' => [
                    'ON' => ['dp' => 'id', 'idp' => 'deviceprocessors_id'],
                ],
            ],
            'WHERE' => [
                'idp.items_id' => $ids,
                'idp.itemtype' => 'Computer',
            ],
        ]);
        foreach ($cpuIterator as $row) {
            $itemId = (int) $row['items_id'];
            if (!isset($cpuMap[$itemId]) && !empty($row['designation'])) {
                $cpuMap[$itemId] = $row['designation'];
            }
        }

        // Bulk: RAM
        $ramMap      = [];
        $ramIterator = $DB->request([
            'SELECT'  => [
                'items_id',
                QueryFunction::sum('size', false, 'total'),
            ],
            'FROM'    => 'glpi_items_devicememories',
            'WHERE'   => [
                'items_id'   => $ids,
                'itemtype'   => 'Computer',
                'is_deleted' => 0,
            ],
            'GROUPBY' => ['items_id'],
        ]);
        foreach ($ramIterator as $row) {
            $ramMap[(int) $row['items_id']] = (int) ($row['total'] ?? 0);
        }

        // Bulk: Disk
        $diskMap      = [];
        $diskIterator = $DB->request([
            'SELECT'  => [
                'items_id',
                QueryFunction::sum('totalsize', false, 'disk_total'),
                QueryFunction::sum('freesize', false, 'disk_free'),
            ],
            'FROM'    => 'glpi_items_disks',
            'WHERE'   => [
                'items_id'   => $ids,
                'itemtype'   => 'Computer',
                'is_deleted' => 0,
            ],
            'GROUPBY' => ['items_id'],
        ]);
        foreach ($diskIterator as $row) {
            $diskMap[(int) $row['items_id']] = [
                'total' => (int) ($row['disk_total'] ?? 0),
                'free'  => (int) ($row['disk_free'] ?? 0),
            ];
        }

        // Montar resultado final
        $result = [];
        foreach ($assets as $id => $asset) {
            $osNames             = $osMap[$id] ?? [];
            $asset['os_name']    = implode(', ', array_unique($osNames));
            $asset['cpu']        = $cpuMap[$id] ?? '';
            $asset['ram_total']  = $ramMap[$id] ?? 0;
            $asset['disk_total'] = $diskMap[$id]['total'] ?? 0;
            $asset['disk_free']  = $diskMap[$id]['free'] ?? 0;
            $result[] = $asset;
        }

        return $result;
    }

    // ==================== Helpers ====================

    /**
     * Contagem de tickets com filtros adicionais
     */
    private static function countTickets($DB, array $entityCriteria, array $extraWhere): int
    {
        $where = array_merge(['is_deleted' => 0], $extraWhere, $entityCriteria);

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => $where,
        ]);
        $row = $result->current();
        return (int) ($row['cpt'] ?? 0);
    }

}
