<?php
/**
 * DnD Master Theme — functions.php
 *
 * Responsabilidades:
 *  1. Verificar dependência do plugin dnd-v6
 *  2. Registrar override do template (filtro dndm_template_path)
 *  3. Carregar painel admin do tema (🎨 DnD Tema)
 *  4. Remover o submenu "Landing Page" do painel do plugin
 *     (a LP agora é gerida aqui no tema)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DNDMT_VERSION', '0.0.3' );
define( 'DNDMT_DIR',     get_template_directory()     . '/' );
define( 'DNDMT_URL',     get_template_directory_uri() . '/' );

// ── Aviso se plugin base não estiver ativo ────────────────────────────────────
add_action( 'admin_notices', function() {
    if ( ! defined('DNDM_VERSION') ) {
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo '⚠ <strong>DnD Master Theme</strong> requer o plugin <strong>DnD Master Platform (dnd-v6)</strong> ativo.';
        echo '</p></div>';
    }
});

// ── Override do template ──────────────────────────────────────────────────────
add_filter( 'dndm_template_path', function() {
    return DNDMT_DIR . 'templates/plataforma.php';
});

// ── Painel admin do tema ──────────────────────────────────────────────────────
require_once DNDMT_DIR . 'admin/theme-admin.php';

// ── Remove "Landing Page" do menu do plugin ───────────────────────────────────
// A LP é gerida exclusivamente no painel do tema (🎨 DnD Tema).
// O plugin (v0.0.5+) não registra mais o submenu LP — remove_submenu_page não é necessário.
