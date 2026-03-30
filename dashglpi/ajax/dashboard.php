<?php

/**
 * Plugin DashGLPI - Endpoint AJAX
 *
 * Recebe ?action=<nome> e retorna JSON.
 * Requer sessão GLPI ativa (usuário logado).
 */

// Carrega o GLPI
require_once __DIR__ . '/../../../inc/includes.php';

// Carrega as classes do plugin
require_once __DIR__ . '/../inc/dashboard.class.php';

// Verifica se o usuário está logado
Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'dashboard_data':
            $data = PluginDashglpiDashboard::getDashboardData();
            echo json_encode($data);
            break;

        case 'get_ranking':
            echo json_encode(PluginDashglpiDashboard::getRanking());
            break;

        case 'tickets_list':
            echo json_encode(PluginDashglpiDashboard::getTicketsList());
            break;

        case 'assets_list':
            echo json_encode(PluginDashglpiDashboard::getAssetsList());
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida.']);
            break;
    }
} catch (\Throwable $e) {
    $errorMsg = $e->getMessage() . " (Line: " . $e->getLine() . ", File: " . basename($e->getFile()) . ")";
    error_log("[DashGLPI] AJAX error: " . $errorMsg);
    error_log("[DashGLPI] Stack: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
