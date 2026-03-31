<?php

/**
 * Plugin DashGLPI - Dashboard avançado para GLPI
 *
 * @author  Diogo Berlanda
 * @license GPLv3+
 */

define('PLUGIN_DASHGLPI_VERSION', '1.0.0');
define('PLUGIN_DASHGLPI_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_DASHGLPI_MAX_GLPI_VERSION', '11.0.99');

/**
 * Retorna informações da versão do plugin
 */
function plugin_version_dashglpi()
{
    return [
        'name'         => 'Dashboard GLPI Pro',
        'version'      => PLUGIN_DASHGLPI_VERSION,
        'author'       => 'Diogo Berlanda',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/diberlanda95/dashglpi',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_DASHGLPI_MIN_GLPI_VERSION,
                'max' => PLUGIN_DASHGLPI_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

/**
 * Inicialização do plugin — registra hooks, menu, CSS e JS
 */
function plugin_init_dashglpi()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['dashglpi'] = true;

    if (Session::getLoginUserID()) {
        // Menu
        $PLUGIN_HOOKS['redefine_menus']['dashglpi'] = 'plugin_dashglpi_redefine_menus';

        // JS mínimo: força o link do menu a abrir em nova aba
        $PLUGIN_HOOKS['add_javascript']['dashglpi'] = 'js/menu.js';
    }
}

/**
 * Verifica pré-requisitos do plugin
 */
function plugin_dashglpi_check_prerequisites()
{
    return true;
}

/**
 * Verifica configuração do plugin
 */
function plugin_dashglpi_check_config()
{
    return true;
}
