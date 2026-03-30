<?php

/**
 * Plugin DashGLPI - Hook functions
 */

/**
 * Instalação do plugin
 * Não precisa criar tabelas — o dashboard usa dados nativos do GLPI
 */
function plugin_dashglpi_install()
{
    return true;
}

/**
 * Desinstalação do plugin
 */
function plugin_dashglpi_uninstall()
{
    return true;
}

/**
 * Adiciona o Dashboard no menu de navegação do GLPI
 */
function plugin_dashglpi_redefine_menus($menus)
{
    global $CFG_GLPI;

    if (Session::getLoginUserID()) {
        $menus['helpdesk']['content']['dashglpi'] = [
            'title' => __('Dashboard Pro', 'dashglpi'),
            'page'  => $CFG_GLPI['root_doc'] . '/plugins/dashglpi/front/dashboard.php',
            'icon'  => 'ti ti-chart-dots',
        ];
    }

    return $menus;
}
