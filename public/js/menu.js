/**
 * DashGLPI — Força o link do menu a abrir em nova aba.
 * Arquivo mínimo carregado globalmente pelo plugin.
 */
document.addEventListener('DOMContentLoaded', function () {
    var links = document.querySelectorAll('a[href*="/plugins/dashglpi/"]');
    for (var i = 0; i < links.length; i++) {
        links[i].setAttribute('target', '_blank');
    }
});
