<?php
/**
 * Plugin Name:  DnD Master Platform
 * Plugin URI:   https://github.com/dnd-master
 * Description:  Plataforma SaaS para mestrar e jogar D&D 5e diretamente no WordPress.
 * Version: 0.0.5
 * Author:       DnD Master
 * Text Domain:  dnd-master
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DNDM_VERSION',    '0.0.5' );
define( 'DNDM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DNDM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// Aliases usados pelas classes internas (compatibilidade)
define( 'DNDM_PATH',       DNDM_PLUGIN_DIR );
define( 'DNDM_URL',        DNDM_PLUGIN_URL );
// Upload de arquivos (PDFs, imagens geradas)
define( 'DNDM_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/dnd-master' );
define( 'DNDM_UPLOAD_URL', WP_CONTENT_URL . '/uploads/dnd-master' );

// ── Autoload de classes ───────────────────────────────────────────────────────
foreach ( array(
    'class-database', 'class-auth', 'class-personagem',
    'class-campanha', 'class-groq', 'class-imagem',
    'class-achievements', 'class-dlc-registry', 'class-api', 'class-platform', 'class-lp-editor',
) as $class ) {
    require_once DNDM_PLUGIN_DIR . 'includes/' . $class . '.php';
}

// ── Ativação / Desativação ────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'dndm_ativar' );
function dndm_ativar() {
    DNDM_Database::instalar();
    DNDM_Platform::registrar_rewrite();
    flush_rewrite_rules();
    error_log( '[DnD Master] Plugin ativado — v' . DNDM_VERSION );
}

register_deactivation_hook( __FILE__, 'dndm_desativar' );
function dndm_desativar() {
    flush_rewrite_rules();
}

// ── Hooks principais ─────────────────────────────────────────────────────────
add_action( 'init', array( 'DNDM_Auth',         'init'             ) );
add_action( 'init', array( 'DNDM_Platform',     'init'             ) );
add_action( 'rest_api_init',   array( 'DNDM_API',          'registrar_rotas' ) );
add_action( 'admin_menu',      'dndm_menu_admin' );
add_action( 'admin_init',      'dndm_admin_acoes' );

// ── DLC Registry ──────────────────────────────────────────────────────────────
DNDM_DLC_Registry::init();

// ── Hooks de conquistas ───────────────────────────────────────────────────────
add_action( 'dndm_personagem_criado', array( 'DNDM_Achievements', 'on_personagem_criado' ), 10, 3 );
add_action( 'dndm_dano_aplicado',     array( 'DNDM_Achievements', 'on_dano_aplicado'     ), 10, 5 );
add_action( 'dndm_dado_rolado',       array( 'DNDM_Achievements', 'on_rolagem_dado'      ), 10, 3 );
add_action( 'dndm_kill_registrado',   array( 'DNDM_Achievements', 'on_kill_registrado'   ), 10, 2 );

// Auto-atualiza o schema do banco quando a versão muda
add_action( 'init', function() {
    if ( get_option('dndm_db_version') !== DNDM_VERSION ) {
        DNDM_Database::instalar();
        update_option( 'dndm_db_version', DNDM_VERSION );
    }
});

// ════════════════════════════════════════════════════════════════════════════════
// WP-ADMIN — Menu principal + submenus
// ════════════════════════════════════════════════════════════════════════════════

function dndm_menu_admin() {
    // Menu raiz
    add_menu_page(
        'DnD Master', '⚔ DnD Master', 'manage_options',
        'dnd-master', 'dndm_page_dashboard',
        'dashicons-shield', 30
    );
    // Subpáginas
    add_submenu_page( 'dnd-master', 'Dashboard',      'Dashboard',        'manage_options', 'dnd-master',          'dndm_page_dashboard' );
    add_submenu_page( 'dnd-master', 'Módulos',        '📜 Módulos',       'manage_options', 'dnd-master-modulos',  'dndm_page_modulos' );
    add_submenu_page( 'dnd-master', 'Jogadores',      '👥 Jogadores',     'manage_options', 'dnd-master-jogadores','dndm_page_jogadores' );
    add_submenu_page( 'dnd-master', 'Personagens',    '🧙 Personagens',   'manage_options', 'dnd-master-chars',    'dndm_page_personagens' );
    add_submenu_page( 'dnd-master', 'Conquistas',     '🏆 Conquistas',    'manage_options', 'dnd-master-conquistas','dndm_page_conquistas' );
    add_submenu_page( 'dnd-master', 'Configurações',  '⚙ Configurações',  'manage_options', 'dnd-master-config',   'dndm_page_config' );
}

// ── Processar ações admin ─────────────────────────────────────────────────────
function dndm_admin_acoes() {
    if ( ! current_user_can('manage_options') ) return;

    // Flush rewrite
    if ( isset($_GET['dndm_flush']) && check_admin_referer('dndm_flush') ) {
        DNDM_Platform::registrar_rewrite(); flush_rewrite_rules();
        wp_redirect( admin_url('admin.php?page=dnd-master&msg=flushed') ); exit;
    }
    // Reset DB
    if ( isset($_GET['dndm_reset_db']) && check_admin_referer('dndm_reset_db') ) {
        DNDM_Database::instalar();
        wp_redirect( admin_url('admin.php?page=dnd-master&msg=db_updated') ); exit;
    }
    // Salvar config
    if ( isset($_POST['dndm_salvar_config']) && check_admin_referer('dndm_config') ) {
        update_option( 'dndm_groq_key',         sanitize_text_field($_POST['dndm_groq_key'] ?? '') );
        update_option( 'dndm_pollinations_key', sanitize_text_field($_POST['dndm_pollinations_key'] ?? '') );
        wp_redirect( admin_url('admin.php?page=dnd-master-config&msg=saved') ); exit;
    }
    // Excluir módulo
    if ( isset($_POST['dndm_excluir_modulo']) && check_admin_referer('dndm_excluir_modulo') ) {
        global $wpdb;
        $id = intval($_POST['modulo_id']);
        // Se force_delete, limpa campanha_ativa de todos os usuários vinculados a este módulo
        if ( !empty($_POST['force_delete']) ) {
            $camp_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}dnd_campanhas WHERE modulo_id=%d", $id
            ));
            foreach ( $camp_ids as $cid ) {
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key='dndm_campanha_ativa' AND meta_value=%s",
                    $cid
                ));
            }
        }
        $wpdb->update( $wpdb->prefix.'dnd_campanhas', array('status'=>'inativa','modulo_id'=>null), array('modulo_id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'dnd_checklist', array('modulo_id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'dnd_npcs',      array('modulo_id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'dnd_modulos',   array('id'=>$id) );
        wp_redirect( admin_url('admin.php?page=dnd-master-modulos&msg=deleted') ); exit;
    }
    // Criar jogador/usuário (com tier)
    if ( isset($_POST['dndm_criar_jogador']) && check_admin_referer('dndm_criar_jogador') ) {
        $tier = sanitize_text_field($_POST['tier'] ?? 'tier3');
        $res = DNDM_Auth::criar_usuario(
            sanitize_text_field($_POST['nome']  ?? ''),
            sanitize_email     ($_POST['email'] ?? ''),
            sanitize_text_field($_POST['senha'] ?? ''),
            $tier
        );
        $msg = is_wp_error($res) ? 'erro_'.urlencode($res->get_error_message()) : 'jogador_criado';
        wp_redirect( admin_url('admin.php?page=dnd-master-jogadores&msg='.$msg) ); exit;
    }
    // Excluir jogador
    if ( isset($_POST['dndm_excluir_jogador']) && check_admin_referer('dndm_excluir_jogador') ) {
        $wp_user_id = intval($_POST['wp_user_id']);
        if ( $wp_user_id && $wp_user_id !== get_current_user_id() ) {
            require_once ABSPATH.'wp-admin/includes/user.php';
            wp_delete_user($wp_user_id);
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-jogadores&msg=deleted') ); exit;
    }
}

// ════════════════════════════════════════════════════════════════════════════════
// PÁGINAS ADMIN
// ════════════════════════════════════════════════════════════════════════════════

function dndm_admin_header( $titulo ) {
    $msg = $_GET['msg'] ?? '';
    $msgs = array(
        'saved'         => array('success','✅ Salvo com sucesso!'),
        'flushed'       => array('success','✅ Rotas atualizadas!'),
        'db_updated'    => array('success','✅ Banco atualizado!'),
        'deleted'       => array('success','✅ Excluído com sucesso!'),
        'jogador_criado'=> array('success','✅ Jogador criado!'),
    );
    echo '<div class="wrap">';
    echo '<h1>⚔ DnD Master <span style="font-size:13px;color:#999;font-weight:400;">v'.DNDM_VERSION.'</span> — '.$titulo.'</h1>';
    if ( $msg && isset($msgs[$msg]) ) {
        echo '<div class="notice notice-'.$msgs[$msg][0].' is-dismissible"><p>'.$msgs[$msg][1].'</p></div>';
    } elseif ( strpos($msg, 'erro_') === 0 ) {
        echo '<div class="notice notice-error"><p>❌ '.esc_html(urldecode(substr($msg,5))).'</p></div>';
    }
}

// ── Dashboard ─────────────────────────────────────────────────────────────────
function dndm_page_dashboard() {
    global $wpdb;
    $total_modulos    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dnd_modulos");
    $total_campanhas  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dnd_campanhas WHERE status='ativa'");
    $total_jogadores  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dnd_usuarios WHERE papel='jogador'");
    $total_personagens= (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dnd_personagens");
    dndm_admin_header('Dashboard');
    ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:20px 0;">
        <?php foreach([
            ['📜','Módulos',$total_modulos,'#7c3aed'],
            ['⚔','Campanhas Ativas',$total_campanhas,'#dc2626'],
            ['👥','Jogadores',$total_jogadores,'#2563eb'],
            ['🧙','Personagens',$total_personagens,'#16a34a'],
        ] as [$ico,$label,$val,$cor]): ?>
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center;border-top:4px solid <?=$cor?>;">
            <div style="font-size:28px;margin-bottom:8px;"><?=$ico?></div>
            <div style="font-size:32px;font-weight:700;color:<?=$cor?>;"><?=$val?></div>
            <div style="color:#666;font-size:13px;margin-top:4px;"><?=$label?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:20px 0;">
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">🔗 Links Rápidos</h3>
            <ul style="line-height:2.4;">
                <li>🏠 <a href="<?=home_url('/')?>" target="_blank">Landing Page</a></li>
                <li>⚔ <a href="<?=home_url('/dnd-mestre')?>" target="_blank">Painel do Mestre</a></li>
                <li>🎮 <a href="<?=home_url('/dnd-aventura')?>" target="_blank">Tela do Jogador</a></li>
            </ul>
        </div>
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">🛠 Ferramentas</h3>
            <p style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="<?=wp_nonce_url(admin_url('admin.php?page=dnd-master&dndm_flush=1'),'dndm_flush')?>" class="button">🔄 Flush Rotas</a>
                <a href="<?=wp_nonce_url(admin_url('admin.php?page=dnd-master&dndm_reset_db=1'),'dndm_reset_db')?>" class="button button-secondary" onclick="return confirm('Recriar tabelas?')">🗄️ Atualizar Banco</a>
            </p>
        </div>
    </div>
    </div>
    <?php
}

// ── Módulos ────────────────────────────────────────────────────────────────────
function dndm_page_modulos() {
    global $wpdb;

    // ── Subpágina: Materiais de Apoio ─────────────────────────────────────────
    $mat_id = isset($_GET['materiais']) ? intval($_GET['materiais']) : 0;
    if ( $mat_id ) {
        dndm_page_materiais( $mat_id );
        return;
    }

    // Handle JSON import via admin
    if ( isset($_POST['dndm_importar_modulo']) && check_admin_referer('dndm_importar_modulo') ) {
        $import_msg = '';
        if ( !empty($_FILES['modulo_json']['tmp_name']) ) {
            $conteudo = file_get_contents($_FILES['modulo_json']['tmp_name']);
            $json = json_decode($conteudo, true);
            if (!$json) {
                $import_msg = '<div class="notice notice-error"><p>❌ Arquivo JSON inválido.</p></div>';
            } else {
                require_once DNDM_PATH . 'includes/class-campanha.php';
                $id = DNDM_Campanha::importar_modulo_json($json, sanitize_text_field($json['nome'] ?? 'Módulo'));
                if (is_wp_error($id)) {
                    $import_msg = '<div class="notice notice-error"><p>❌ ' . esc_html($id->get_error_message()) . '</p></div>';
                } else {
                    $import_msg = '<div class="notice notice-success"><p>✅ Módulo "' . esc_html($json['nome'] ?? 'Módulo') . '" importado com sucesso!</p></div>';
                }
            }
        } else {
            $import_msg = '<div class="notice notice-warning"><p>⚠ Selecione um arquivo JSON.</p></div>';
        }
    }

    $modulos = $wpdb->get_results(
        "SELECT m.*, 
         (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_checklist WHERE modulo_id=m.id) as total_cenas,
         (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id=m.id) as total_npcs,
         (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_campanhas WHERE modulo_id=m.id AND status='ativa') as campanhas_ativas
         FROM {$wpdb->prefix}dnd_modulos m ORDER BY m.criado_em DESC"
    );
    dndm_admin_header('Módulos / Aventuras');
    echo $import_msg ?? '';
    ?>
    <!-- Importar JSON -->
    <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin:16px 0;">
        <h3 style="margin-top:0;">📦 Importar Módulo JSON</h3>
        <p style="color:#666;margin-bottom:12px;">Selecione o arquivo <code>.json</code> gerado pelo Claude a partir de uma aventura em PDF.</p>
        <form method="post" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <?php wp_nonce_field('dndm_importar_modulo'); ?>
            <input type="file" name="modulo_json" accept=".json,application/json" required style="flex:1;min-width:200px;">
            <button type="submit" name="dndm_importar_modulo" class="button button-primary">📤 Importar</button>
        </form>
    </div>
    <table class="widefat striped">
        <thead><tr>
            <th>Nome</th><th>Sistema</th><th>Etapas</th><th>NPCs</th><th>Campanhas Ativas</th><th>Importado em</th><th>Ações</th>
        </tr></thead>
        <tbody>
        <?php if ( empty($modulos) ): ?>
            <tr><td colspan="7" style="text-align:center;padding:24px;color:#999;">Nenhum módulo importado ainda.</td></tr>
        <?php else: foreach ($modulos as $m): ?>
            <tr>
                <td><strong><?=esc_html($m->nome)?></strong><br><small style="color:#999;"><?=esc_html(wp_trim_words($m->descricao,12))?></small></td>
                <td><?=esc_html($m->sistema)?></td>
                <td><?=(int)$m->total_cenas?></td>
                <td><?=(int)$m->total_npcs?></td>
                <td><?=$m->campanhas_ativas > 0 ? '<span style="color:#dc2626;">⚔ '.$m->campanhas_ativas.' ativa(s)</span>' : '<span style="color:#999;">—</span>'?></td>
                <td><?=date('d/m/Y',strtotime($m->criado_em))?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="<?=admin_url('admin.php?page=dnd-master-modulos&materiais='.(int)$m->id)?>" class="button button-small">📎 Materiais</a>
                    <?php if ($m->campanhas_ativas > 0): ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('⚠ Excluir módulo \'<?=esc_js($m->nome)?>\' e desvincular todas as campanhas ativas?');">
                            <?php wp_nonce_field('dndm_excluir_modulo'); ?>
                            <input type="hidden" name="modulo_id" value="<?=(int)$m->id?>">
                            <input type="hidden" name="force_delete" value="1">
                            <button type="submit" name="dndm_excluir_modulo" class="button button-small" style="color:#dc2626;border-color:#dc2626;">🗑 Forçar Exclusão</button>
                        </form>
                    <?php else: ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Excluir módulo \'<?=esc_js($m->nome)?>\' permanentemente?');">
                            <?php wp_nonce_field('dndm_excluir_modulo'); ?>
                            <input type="hidden" name="modulo_id" value="<?=(int)$m->id?>">
                            <button type="submit" name="dndm_excluir_modulo" class="button button-small" style="color:#dc2626;border-color:#dc2626;">🗑 Excluir</button>
                        </form>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    <?php
}

// ── Materiais de Apoio do Módulo ──────────────────────────────────────────────
function dndm_page_materiais( $modulo_id ) {
    global $wpdb;
    $modulo = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dnd_modulos WHERE id=%d", $modulo_id
    ));
    if ( !$modulo ) { echo '<p>Módulo não encontrado.</p></div>'; return; }

    $msg = '';

    // Upload PDF
    if ( isset($_POST['dndm_upload_pdf']) && check_admin_referer('dndm_mat_'.$modulo_id) ) {
        if ( !empty($_FILES['mat_pdf']['tmp_name']) ) {
            $dir = DNDM_UPLOAD_DIR . '/pdfs'; wp_mkdir_p($dir);
            $fname = 'modulo-'.$modulo_id.'-'.sanitize_file_name($_FILES['mat_pdf']['name']);
            $old = get_option('dndm_pdf_modulo_'.$modulo_id,'');
            if ($old && file_exists(DNDM_UPLOAD_DIR.'/pdfs/'.basename($old))) @unlink(DNDM_UPLOAD_DIR.'/pdfs/'.basename($old));
            if (move_uploaded_file($_FILES['mat_pdf']['tmp_name'], $dir.'/'.$fname)) {
                update_option('dndm_pdf_modulo_'.$modulo_id, DNDM_UPLOAD_URL.'/pdfs/'.$fname);
                $msg = '<div class="notice notice-success is-dismissible"><p>✅ PDF enviado!</p></div>';
            } else { $msg = '<div class="notice notice-error"><p>❌ Falha ao salvar PDF.</p></div>'; }
        }
    }

    // Upload Mapa
    if ( isset($_POST['dndm_upload_mapa']) && check_admin_referer('dndm_mat_'.$modulo_id) ) {
        if ( !empty($_FILES['mat_mapa']['tmp_name']) ) {
            $dir = DNDM_UPLOAD_DIR . '/mapas'; wp_mkdir_p($dir);
            $ext = strtolower(pathinfo($_FILES['mat_mapa']['name'], PATHINFO_EXTENSION));
            $fname = 'mapa-'.$modulo_id.'-'.time().'.'.$ext;
            if (move_uploaded_file($_FILES['mat_mapa']['tmp_name'], $dir.'/'.$fname)) {
                $mapas = get_option('dndm_mapas_modulo_'.$modulo_id, []);
                $mapas[] = ['url'=>DNDM_UPLOAD_URL.'/mapas/'.$fname,'nome'=>sanitize_text_field($_FILES['mat_mapa']['name']),'ts'=>time()];
                update_option('dndm_mapas_modulo_'.$modulo_id, $mapas);
                $msg = '<div class="notice notice-success is-dismissible"><p>✅ Mapa adicionado!</p></div>';
            }
        }
    }

    // Remover mapa
    if ( isset($_POST['dndm_remover_mapa']) && check_admin_referer('dndm_mat_'.$modulo_id) ) {
        $idx = intval($_POST['mapa_idx']);
        $mapas = get_option('dndm_mapas_modulo_'.$modulo_id, []);
        if (isset($mapas[$idx])) { @unlink(DNDM_UPLOAD_DIR.'/mapas/'.basename($mapas[$idx]['url'])); array_splice($mapas,$idx,1); update_option('dndm_mapas_modulo_'.$modulo_id, array_values($mapas)); }
        $msg = '<div class="notice notice-success is-dismissible"><p>✅ Mapa removido.</p></div>';
    }

    // Upload imagem de monstro/NPC via form admin
    if ( isset($_POST['dndm_upload_img_entidade']) && check_admin_referer('dndm_mat_'.$modulo_id) ) {
        $tipo  = sanitize_text_field($_POST['entidade_tipo'] ?? 'npc');  // npc | monster
        $idx   = intval($_POST['entidade_idx'] ?? 0);
        if ( !empty($_FILES['entidade_img']['tmp_name']) ) {
            $dir = DNDM_UPLOAD_DIR . '/entidades'; wp_mkdir_p($dir);
            $ext = strtolower(pathinfo($_FILES['entidade_img']['name'], PATHINFO_EXTENSION));
            $fname = $tipo.'-'.$modulo_id.'-'.$idx.'-'.time().'.'.$ext;
            if (move_uploaded_file($_FILES['entidade_img']['tmp_name'], $dir.'/'.$fname)) {
                $url = DNDM_UPLOAD_URL.'/entidades/'.$fname;
                $dados = json_decode($modulo->dados_json ?? '{}', true) ?: [];
                if ($tipo === 'npc' && isset($dados['npcs'][$idx])) {
                    $dados['npcs'][$idx]['imagem_url'] = $url;
                } elseif ($tipo === 'monster' && isset($dados['monsters'][$idx])) {
                    $dados['monsters'][$idx]['imagem_url'] = $url;
                }
                $wpdb->update($wpdb->prefix.'dnd_modulos', ['dados_json'=>json_encode($dados, JSON_UNESCAPED_UNICODE)], ['id'=>$modulo_id]);
                $modulo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dnd_modulos WHERE id=%d", $modulo_id));
                $msg = '<div class="notice notice-success is-dismissible"><p>✅ Imagem enviada!</p></div>';
            }
        }
    }

    // Gerar imagem IA para monstro/NPC
    if ( isset($_POST['dndm_gerar_img_entidade']) && check_admin_referer('dndm_mat_'.$modulo_id) ) {
        $tipo   = sanitize_text_field($_POST['entidade_tipo'] ?? 'npc');
        $idx    = intval($_POST['entidade_idx'] ?? 0);
        $prompt = sanitize_textarea_field($_POST['entidade_prompt'] ?? '');
        if ( $prompt && class_exists('DNDM_Imagem') ) {
            $url = DNDM_Imagem::gerar_por_prompt($prompt);
            if ($url && !is_wp_error($url)) {
                $dados = json_decode($modulo->dados_json ?? '{}', true) ?: [];
                if ($tipo === 'npc' && isset($dados['npcs'][$idx])) $dados['npcs'][$idx]['imagem_url'] = $url;
                elseif ($tipo === 'monster' && isset($dados['monsters'][$idx])) $dados['monsters'][$idx]['imagem_url'] = $url;
                $wpdb->update($wpdb->prefix.'dnd_modulos', ['dados_json'=>json_encode($dados, JSON_UNESCAPED_UNICODE)], ['id'=>$modulo_id]);
                $modulo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dnd_modulos WHERE id=%d", $modulo_id));
                $msg = '<div class="notice notice-success is-dismissible"><p>✅ Imagem gerada pela IA!</p></div>';
            } else { $msg = '<div class="notice notice-error"><p>❌ Falha ao gerar imagem.</p></div>'; }
        }
    }

    // Gerar NPC com IA
    if ( isset($_POST['dndm_gerar_npc']) && check_admin_referer('dndm_mat_'.$modulo_id) ) {
        $papel = sanitize_text_field($_POST['npc_papel'] ?? 'Antagonista');
        $tom   = sanitize_text_field($_POST['npc_tom']   ?? 'Sombrio');
        $desc  = sanitize_textarea_field($_POST['npc_desc'] ?? '');
        if ( class_exists('DNDM_Groq') ) {
            $npc = DNDM_Groq::gerar_npc(['descricao'=>$desc,'papel'=>$papel,'tom'=>$tom]);
            if ( !is_wp_error($npc) ) {
                $img = class_exists('DNDM_Imagem') ? DNDM_Imagem::gerar_imagem_npc($npc) : '';
                $wpdb->insert($wpdb->prefix.'dnd_npcs', [
                    'modulo_id'=>$modulo_id,'nome'=>$npc['nome']??'NPC','raca'=>$npc['raca']??'','papel'=>$papel,
                    'personalidade'=>($npc['personalidade']??'')."\n\nSegredo: ".($npc['segredo']??'')."\nGanchos: ".($npc['ganchos']??''),
                    'imagem_url'=>$img,
                ]);
                $msg = '<div class="notice notice-success is-dismissible"><p>✅ NPC "'.esc_html($npc['nome']??'NPC').'" gerado!</p></div>';
            } else { $msg = '<div class="notice notice-error"><p>❌ Groq: '.esc_html($npc->get_error_message()).'</p></div>'; }
        } else { $msg = '<div class="notice notice-warning"><p>⚠ Groq não está disponível.</p></div>'; }
    }

    // ── Ler dados do módulo ───────────────────────────────────────────────────
    $pdf_url   = get_option('dndm_pdf_modulo_'.$modulo_id, '');
    $mapas     = get_option('dndm_mapas_modulo_'.$modulo_id, []);
    $npcs_db   = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id=%d ORDER BY id DESC", $modulo_id));
    $dados_json = json_decode($modulo->dados_json ?? '{}', true) ?: [];
    $monsters  = $dados_json['monsters'] ?? [];
    $npcs_json = $dados_json['npcs']     ?? [];
    $loot      = $dados_json['loot']     ?? [];
    $objectives= $dados_json['objectives']?? [];

    dndm_admin_header('Materiais — '.esc_html($modulo->nome));
    echo $msg;
    ?>
    <style>
    .dndm-tab-nav { display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid #ddd; padding-bottom:0; }
    .dndm-tab-btn { background:#f6f7f7; border:1px solid #ddd; border-bottom:none; border-radius:4px 4px 0 0; padding:8px 18px; cursor:pointer; font-size:13px; color:#444; margin-bottom:-2px; }
    .dndm-tab-btn.ativo { background:#fff; border-bottom-color:#fff; font-weight:700; color:#2271b1; }
    .dndm-tab-section { display:none; }
    .dndm-tab-section.ativo { display:block; }
    .dndm-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
    .dndm-entidade-card { border:1px solid #e0e0e0; border-radius:10px; overflow:hidden; background:#fafafa; }
    .dndm-entidade-img { width:100%; height:120px; object-fit:cover; display:block; background:#f0f0f0; }
    .dndm-entidade-img-placeholder { width:100%; height:120px; display:flex; align-items:center; justify-content:center; font-size:36px; background:#f0f0f0; }
    .dndm-entidade-body { padding:10px 12px; }
    .dndm-entidade-nome { font-weight:700; font-size:13px; margin-bottom:2px; }
    .dndm-entidade-sub { font-size:11px; color:#888; margin-bottom:8px; }
    .dndm-stat-pill { display:inline-block; background:#e8e0f8; color:#5b21b6; border-radius:12px; padding:2px 8px; font-size:10px; font-weight:700; margin:2px; }
    .dndm-img-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; padding-top:8px; border-top:1px solid #eee; }
    .dndm-loot-item { display:flex; justify-content:space-between; padding:5px 10px; background:#fff; border:1px solid #eee; border-radius:6px; margin-bottom:5px; font-size:12px; }
    .dndm-magic-item { background:#f9f5ff; border:1px solid #c4b5fd; border-radius:8px; padding:10px 14px; margin-bottom:8px; }
    </style>

    <a href="<?=admin_url('admin.php?page=dnd-master-modulos')?>" class="button" style="margin-bottom:16px;">← Voltar</a>

    <!-- Synopsis e Info -->
    <?php if (!empty($dados_json['synopsis'])): ?>
    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#5a4a00;">
        <strong>📖 Sinopse:</strong> <?=esc_html($dados_json['synopsis'])?>
        <?php if(!empty($dados_json['nivel_recomendado'])): ?>
        <span style="margin-left:16px;background:#ffc107;color:#5a4a00;border-radius:4px;padding:1px 8px;font-size:11px;font-weight:700;">Nv <?=esc_html($dados_json['nivel_recomendado'])?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Navegação por abas -->
    <div class="dndm-tab-nav">
        <button class="dndm-tab-btn ativo" onclick="dndmTab('pdf',this)">📄 PDF</button>
        <button class="dndm-tab-btn" onclick="dndmTab('npcs',this)">🧙 NPCs <?=count($npcs_db)+count($npcs_json)>0?'('.( count($npcs_db)+count($npcs_json)).')':''?></button>
        <button class="dndm-tab-btn" onclick="dndmTab('monsters',this)">⚔ Monstros <?=count($monsters)>0?'('.count($monsters).')':''?></button>
        <button class="dndm-tab-btn" onclick="dndmTab('loot',this)">💰 Loot <?=!empty($loot)?'✓':''?></button>
        <button class="dndm-tab-btn" onclick="dndmTab('mapas',this)">🗺 Mapas <?=count($mapas)>0?'('.count($mapas).')':''?></button>
        <button class="dndm-tab-btn" onclick="dndmTab('gerar',this)">🤖 Gerar com IA</button>
    </div>

    <!-- ═══ ABA: PDF ══════════════════════════════════════════════════════ -->
    <div id="dndm-tab-pdf" class="dndm-tab-section ativo">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
                <h3 style="margin-top:0;">📄 PDF da Aventura</h3>
                <?php if ($pdf_url): ?>
                    <p><a href="<?=esc_url($pdf_url)?>" target="_blank" class="button button-primary">📥 Abrir PDF</a></p>
                    <p style="font-size:12px;color:#666;">Disponível na aba PDF do HUD do Mestre durante a sessão.</p>
                <?php else: ?>
                    <p style="color:#999;font-size:13px;">Nenhum PDF vinculado ainda.</p>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" style="margin-top:12px;">
                    <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                    <input type="file" name="mat_pdf" accept=".pdf,application/pdf" required style="margin-bottom:8px;display:block;">
                    <button type="submit" name="dndm_upload_pdf" class="button button-primary">📤 <?=$pdf_url?'Substituir':'Enviar'?> PDF</button>
                </form>
            </div>
            <?php if (!empty($objectives)): ?>
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
                <h3 style="margin-top:0;">🎯 Objetivos (<?=count($objectives)?>)</h3>
                <?php foreach($objectives as $obj): ?>
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f0f0f0;">
                    <span style="<?=$obj['tipo']==='obrigatoria'?'color:#dc2626':'color:#2563eb'?>;font-size:11px;font-weight:700;min-width:70px;">
                        <?=$obj['tipo']==='obrigatoria'?'🔴 Principal':'🔵 Secundário'?>
                    </span>
                    <span style="font-size:13px;"><?=esc_html($obj['titulo']??$obj['title']??'')?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ ABA: NPCs ══════════════════════════════════════════════════════ -->
    <div id="dndm-tab-npcs" class="dndm-tab-section">
        <?php
        // Combina NPCs do banco e do JSON
        $todos_npcs = [];
        foreach ($npcs_db as $n) $todos_npcs[] = ['fonte'=>'db','id'=>$n->id,'nome'=>$n->nome,'raca'=>$n->raca,'papel'=>$n->papel,'personalidade'=>$n->personalidade,'segredo'=>$n->segredo,'ganchos'=>$n->ganchos,'imagem_url'=>$n->imagem_url,'prompt_imagem'=>''];
        foreach ($npcs_json as $i=>$n) $todos_npcs[] = ['fonte'=>'json','idx'=>$i,'nome'=>$n['nome']??'','raca'=>$n['raca']??'','papel'=>$n['papel']??'','personalidade'=>$n['personalidade']??'','segredo'=>$n['segredo']??'','ganchos'=>$n['ganchos']??'','imagem_url'=>$n['imagem_url']??'','prompt_imagem'=>$n['prompt_imagem']??''];
        ?>
        <?php if (empty($todos_npcs)): ?>
            <p style="color:#999;padding:24px;text-align:center;">Nenhum NPC. Use a aba "🤖 Gerar com IA" ou importe um JSON com NPCs.</p>
        <?php else: ?>
        <div class="dndm-card-grid">
        <?php foreach($todos_npcs as $npc): ?>
            <div class="dndm-entidade-card">
                <?php if (!empty($npc['imagem_url'])): ?>
                    <img src="<?=esc_url($npc['imagem_url'])?>" class="dndm-entidade-img" onclick="dndmZoom('<?=esc_js($npc['imagem_url'])?>') " style="cursor:zoom-in;">
                <?php else: ?>
                    <div class="dndm-entidade-img-placeholder">🧙</div>
                <?php endif; ?>
                <div class="dndm-entidade-body">
                    <div class="dndm-entidade-nome"><?=esc_html($npc['nome'])?></div>
                    <div class="dndm-entidade-sub"><?=esc_html($npc['raca'])?><?=$npc['papel']?' · '.esc_html($npc['papel']):''?></div>
                    <?php if ($npc['personalidade']): ?>
                        <p style="font-size:11px;color:#555;line-height:1.4;margin:0 0 6px;"><?=esc_html(wp_trim_words($npc['personalidade'],18))?></p>
                    <?php endif; ?>
                    <?php if ($npc['segredo']): ?>
                        <div style="background:#fff5f5;border-left:3px solid #dc2626;padding:5px 8px;font-size:11px;color:#7a2222;border-radius:0 4px 4px 0;margin-bottom:6px;">🔒 <?=esc_html(wp_trim_words($npc['segredo'],14))?></div>
                    <?php endif; ?>
                    <!-- Ações de imagem -->
                    <div class="dndm-img-actions">
                        <form method="post" enctype="multipart/form-data" style="display:flex;gap:4px;align-items:center;flex:1;">
                            <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                            <input type="hidden" name="entidade_tipo" value="npc">
                            <input type="hidden" name="entidade_idx" value="<?=$npc['fonte']==='json'?$npc['idx']:0?>">
                            <input type="file" name="entidade_img" accept="image/*" style="font-size:10px;flex:1;min-width:0;" onchange="this.form.submit()">
                            <button type="submit" name="dndm_upload_img_entidade" class="button button-small" style="white-space:nowrap;">📁</button>
                        </form>
                        <?php if (!empty($npc['prompt_imagem'])): ?>
                        <form method="post" style="flex-shrink:0;">
                            <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                            <input type="hidden" name="entidade_tipo" value="npc">
                            <input type="hidden" name="entidade_idx" value="<?=$npc['fonte']==='json'?$npc['idx']:0?>">
                            <input type="hidden" name="entidade_prompt" value="<?=esc_attr($npc['prompt_imagem'])?>">
                            <button type="submit" name="dndm_gerar_img_entidade" class="button button-small" title="Gerar imagem com IA">🎨 IA</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══ ABA: MONSTROS ═══════════════════════════════════════════════════ -->
    <div id="dndm-tab-monsters" class="dndm-tab-section">
        <?php if (empty($monsters)): ?>
            <p style="color:#999;padding:24px;text-align:center;">Nenhum monstro no JSON deste módulo.</p>
        <?php else: ?>
        <div class="dndm-card-grid">
        <?php foreach($monsters as $i=>$m): ?>
            <div class="dndm-entidade-card" style="border-color:#fca5a5;">
                <?php if (!empty($m['imagem_url'])): ?>
                    <img src="<?=esc_url($m['imagem_url'])?>" class="dndm-entidade-img" onclick="dndmZoom('<?=esc_js($m['imagem_url'])?>') " style="cursor:zoom-in;">
                <?php else: ?>
                    <div class="dndm-entidade-img-placeholder" style="background:#fff1f1;">💀</div>
                <?php endif; ?>
                <div class="dndm-entidade-body">
                    <div class="dndm-entidade-nome" style="color:#b91c1c;"><?=esc_html($m['name']??'')?></div>
                    <div class="dndm-entidade-sub"><?=esc_html($m['type']??'')?></div>
                    <!-- Stats pills -->
                    <?php if (!empty($m['stats'])): ?>
                    <div style="margin:4px 0 6px;">
                        <?php foreach($m['stats'] as $s): ?>
                            <span class="dndm-stat-pill" style="background:#fee2e2;color:#b91c1c;"><?=esc_html($s['l'])?>: <?=esc_html($s['v'])?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <!-- Ações -->
                    <?php if (!empty($m['actions'])): ?>
                    <div style="margin-bottom:6px;">
                        <?php foreach(array_slice($m['actions'],0,3) as $a): ?>
                        <div style="font-size:11px;color:#555;border-bottom:1px solid #f0f0f0;padding:2px 0;"><strong style="color:#b91c1c;"><?=esc_html($a['name']??'')?>:</strong> <?=esc_html(wp_trim_words($a['desc']??'',12))?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <!-- Traits -->
                    <?php if (!empty($m['traits'])): ?>
                    <div style="margin-bottom:6px;">
                        <?php foreach(array_slice($m['traits'],0,2) as $t): ?>
                        <div style="font-size:11px;color:#555;padding:2px 0;"><em><?=esc_html($t['name']??'')?></em></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <!-- Ações de imagem -->
                    <div class="dndm-img-actions">
                        <form method="post" enctype="multipart/form-data" style="display:flex;gap:4px;align-items:center;flex:1;">
                            <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                            <input type="hidden" name="entidade_tipo" value="monster">
                            <input type="hidden" name="entidade_idx" value="<?=$i?>">
                            <input type="file" name="entidade_img" accept="image/*" style="font-size:10px;flex:1;min-width:0;" onchange="this.form.submit()">
                            <button type="submit" name="dndm_upload_img_entidade" class="button button-small">📁</button>
                        </form>
                        <?php if (!empty($m['image_prompt'])): ?>
                        <form method="post" style="flex-shrink:0;">
                            <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                            <input type="hidden" name="entidade_tipo" value="monster">
                            <input type="hidden" name="entidade_idx" value="<?=$i?>">
                            <input type="hidden" name="entidade_prompt" value="<?=esc_attr($m['image_prompt'])?>">
                            <button type="submit" name="dndm_gerar_img_entidade" class="button button-small" title="Gerar com prompt do JSON">🎨 IA</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══ ABA: LOOT ══════════════════════════════════════════════════════ -->
    <div id="dndm-tab-loot" class="dndm-tab-section">
        <?php if (empty($loot)): ?>
            <p style="color:#999;padding:24px;text-align:center;">Nenhum loot definido neste módulo.</p>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <!-- Itens por área -->
            <?php if (!empty($loot['areas'])): ?>
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
                <h3 style="margin-top:0;">💰 Itens por Área</h3>
                <?php foreach($loot['areas'] as $area): ?>
                    <h4 style="margin:12px 0 6px;font-size:13px;color:#555;"><?=esc_html($area['title']??'')?></h4>
                    <?php foreach($area['items']??[] as $item): ?>
                        <div class="dndm-loot-item">
                            <span><?=esc_html($item['name']??'')?></span>
                            <strong style="color:#2563eb;"><?=esc_html($item['value']??'')?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Itens mágicos -->
            <?php if (!empty($loot['magic'])): ?>
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
                <h3 style="margin-top:0;">✨ Itens Mágicos</h3>
                <?php foreach($loot['magic'] as $item): ?>
                    <div class="dndm-magic-item">
                        <strong style="font-size:13px;color:#5b21b6;"><?=esc_html($item['name']??'')?></strong>
                        <span style="font-size:11px;color:#7c3aed;margin-left:8px;background:#ede9fe;padding:1px 8px;border-radius:10px;"><?=esc_html($item['rarity']??'')?></span>
                        <?php if (!empty($item['desc'])): ?>
                        <p style="font-size:12px;color:#555;margin:6px 0 0;line-height:1.5;"><?=esc_html($item['desc'])?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tabela de encontro aleatório -->
            <?php if (!empty($loot['table'])): ?>
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;grid-column:1/-1;">
                <h3 style="margin-top:0;">🎲 Tabela Aleatória de Loot</h3>
                <table class="widefat" style="font-size:12px;">
                    <thead><tr><th style="width:80px;">Rolagem</th><th>Item</th></tr></thead>
                    <tbody>
                    <?php foreach($loot['table'] as $row): ?>
                        <tr><td><strong><?=esc_html($row['roll']??'')?></strong></td><td><?=esc_html($row['item']??'')?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══ ABA: MAPAS ═════════════════════════════════════════════════════ -->
    <div id="dndm-tab-mapas" class="dndm-tab-section">
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">🗺 Mapas e Plantas</h3>
            <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
                <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                <input type="file" name="mat_mapa" accept="image/*" required style="flex:1;min-width:200px;">
                <button type="submit" name="dndm_upload_mapa" class="button button-primary">🗺 Adicionar Mapa</button>
            </form>
            <?php if (!empty($mapas)): ?>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php foreach($mapas as $i=>$mapa): ?>
                <div style="position:relative;border:1px solid #ddd;border-radius:6px;overflow:hidden;">
                    <a href="<?=esc_url($mapa['url'])?>" target="_blank">
                        <img src="<?=esc_url($mapa['url'])?>" style="width:140px;height:110px;object-fit:cover;display:block;">
                    </a>
                    <form method="post" style="position:absolute;top:4px;right:4px;">
                        <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                        <input type="hidden" name="mapa_idx" value="<?=$i?>">
                        <button type="submit" name="dndm_remover_mapa" style="background:rgba(200,0,0,.85);color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;padding:2px 6px;" onclick="return confirm('Remover mapa?')">✕</button>
                    </form>
                    <div style="font-size:10px;color:#666;padding:3px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;"><?=esc_html($mapa['nome'])?></div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="color:#999;font-size:13px;">Nenhum mapa adicionado ainda.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ ABA: GERAR COM IA ══════════════════════════════════════════════ -->
    <div id="dndm-tab-gerar" class="dndm-tab-section">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <!-- Gerar NPC -->
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
                <h3 style="margin-top:0;">🧙 Gerar NPC com IA</h3>
                <p style="color:#666;font-size:13px;margin-bottom:12px;">O Groq cria a ficha e a Pollinations gera o retrato automaticamente.</p>
                <form method="post">
                    <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                    <table class="form-table" style="margin:0;">
                        <tr><th style="padding:6px 0;"><label>Descrição</label></th>
                            <td><textarea name="npc_desc" class="large-text" rows="2" placeholder="Ex: Governador corrupto de meia-idade, cicatriz no rosto..."></textarea></td></tr>
                        <tr><th style="padding:6px 0;"><label>Papel</label></th>
                            <td><select name="npc_papel" class="regular-text">
                                <option>Antagonista</option><option>Aliado</option><option>Informante</option>
                                <option>Neutro</option><option>Boss Final</option><option>Comerciante</option>
                            </select></td></tr>
                        <tr><th style="padding:6px 0;"><label>Tom</label></th>
                            <td><select name="npc_tom" class="regular-text">
                                <option>Sombrio</option><option>Misterioso</option><option>Épico</option>
                                <option>Cômico</option><option>Trágico</option><option>Neutro</option>
                            </select></td></tr>
                    </table>
                    <p style="margin-top:10px;"><button type="submit" name="dndm_gerar_npc" class="button button-primary">🤖 Gerar NPC</button></p>
                </form>
            </div>
            <!-- Gerar imagem customizada -->
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
                <h3 style="margin-top:0;">🎨 Gerar Imagem por Prompt</h3>
                <p style="color:#666;font-size:13px;margin-bottom:12px;">Gera uma imagem via Pollinations e salva no monstro ou NPC pelo índice JSON.</p>
                <form method="post">
                    <?php wp_nonce_field('dndm_mat_'.$modulo_id); ?>
                    <table class="form-table" style="margin:0;">
                        <tr><th style="padding:6px 0;"><label>Tipo</label></th>
                            <td><select name="entidade_tipo" class="regular-text">
                                <option value="npc">NPC (JSON)</option>
                                <option value="monster">Monstro (JSON)</option>
                            </select></td></tr>
                        <tr><th style="padding:6px 0;"><label>Índice</label></th>
                            <td><input type="number" name="entidade_idx" value="0" min="0" class="small-text"> <span style="font-size:11px;color:#888;">(posição no array JSON, começando em 0)</span></td></tr>
                        <tr><th style="padding:6px 0;"><label>Prompt</label></th>
                            <td><textarea name="entidade_prompt" class="large-text" rows="3" placeholder="Ex: orc warrior with battle axe, dark fantasy portrait, dramatic lighting"></textarea></td></tr>
                    </table>
                    <p style="margin-top:10px;"><button type="submit" name="dndm_gerar_img_entidade" class="button button-primary">🎨 Gerar Imagem</button></p>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal zoom de imagem -->
    <div id="dndm-zoom-overlay" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.88);align-items:center;justify-content:center;cursor:zoom-out;" onclick="this.style.display='none'">
        <img id="dndm-zoom-img" style="max-width:90vw;max-height:90vh;border-radius:10px;box-shadow:0 0 60px rgba(0,0,0,.6);">
    </div>

    <script>
    function dndmTab(id, btn) {
        document.querySelectorAll('.dndm-tab-section').forEach(function(el){ el.classList.remove('ativo'); });
        document.querySelectorAll('.dndm-tab-btn').forEach(function(el){ el.classList.remove('ativo'); });
        document.getElementById('dndm-tab-' + id).classList.add('ativo');
        btn.classList.add('ativo');
    }
    function dndmZoom(url) {
        var ov = document.getElementById('dndm-zoom-overlay');
        document.getElementById('dndm-zoom-img').src = url;
        ov.style.display = 'flex';
    }
    </script>
    </div>
    <?php
}

// ── Jogadores ─────────────────────────────────────────────────────────────────
function dndm_page_jogadores() {
    global $wpdb;

    // Alterar tier de usuário
    if ( isset($_POST['dndm_alterar_tier']) && check_admin_referer('dndm_alterar_tier') ) {
        $wp_id = intval($_POST['wp_user_id']);
        $tier  = sanitize_text_field($_POST['novo_tier']);
        if ( in_array($tier, array('tier1','tier2','tier3')) ) {
            $wpdb->update( $wpdb->prefix.'dnd_usuarios', array('tier'=>$tier), array('wp_user_id'=>$wp_id) );
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-jogadores&msg=tier_updated') ); exit;
    }

    $usuarios = $wpdb->get_results(
        "SELECT u.*, u.tier, wp.display_name, wp.user_email, wp.ID as wp_id,
         (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_personagens WHERE usuario_id=u.id) as total_personagens
         FROM {$wpdb->prefix}dnd_usuarios u
         LEFT JOIN {$wpdb->prefix}users wp ON u.wp_user_id = wp.ID
         WHERE u.tier != 'admin'
         ORDER BY u.tier ASC, u.criado_em DESC"
    );

    $tier_labels = array('tier1'=>'Tier 1 — Mestre+','tier2'=>'Tier 2 — Mestre','tier3'=>'Tier 3 — Jogador');
    $tier_colors = array('tier1'=>'#dc2626','tier2'=>'#2563eb','tier3'=>'#16a34a');

    dndm_admin_header('Usuários & Tiers');
    if (isset($_GET['msg']) && $_GET['msg']==='tier_updated') echo '<div class="notice notice-success is-dismissible"><p>Tier atualizado com sucesso.</p></div>';
    ?>
    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;margin-top:16px;align-items:start;">
        <!-- Lista de usuários -->
        <div>
            <table class="widefat striped" style="table-layout:fixed;word-break:break-word;">
                <thead><tr>
                    <th style="width:18%;">Nome</th>
                    <th style="width:24%;">Email</th>
                    <th style="width:11%;">Tier</th>
                    <th style="width:7%;">Pers.</th>
                    <th style="width:28%;">Alterar Tier</th>
                    <th style="width:12%;">Ações</th>
                </tr></thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:#999;">Nenhum usuário cadastrado.</td></tr>
                <?php else: foreach ($usuarios as $j):
                    $tier_atual = $j->tier ?: 'tier3';
                    $cor = $tier_colors[$tier_atual] ?? '#999';
                ?>
                    <tr>
                        <td><strong><?=esc_html($j->display_name)?></strong></td>
                        <td style="font-size:12px;"><?=esc_html($j->user_email)?></td>
                        <td><span style="background:<?=$cor?>22;color:<?=$cor?>;border:1px solid <?=$cor?>44;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;"><?=$tier_labels[$tier_atual]??$tier_atual?></span></td>
                        <td><?=(int)$j->total_personagens?></td>
                        <td>
                            <form method="post">
                                <?php wp_nonce_field('dndm_alterar_tier'); ?>
                                <input type="hidden" name="wp_user_id" value="<?=(int)$j->wp_id?>">
                                <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
                                <select name="novo_tier" style="font-size:11px;padding:2px 4px;max-width:140px;">
                                    <?php foreach($tier_labels as $t=>$l): ?>
                                    <option value="<?=$t?>" <?=$tier_atual===$t?'selected':''?>><?=$l?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="dndm_alterar_tier" class="button button-small">✓</button>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Excluir <?=esc_js($j->display_name)?>?');">
                                <?php wp_nonce_field('dndm_excluir_jogador'); ?>
                                <input type="hidden" name="wp_user_id" value="<?=(int)$j->wp_id?>">
                                <button type="submit" name="dndm_excluir_jogador" class="button button-small" style="color:#dc2626;border-color:#dc2626;">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Form criar -->
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">➕ Novo Usuário</h3>
            <form method="post">
                <?php wp_nonce_field('dndm_criar_jogador'); ?>
                <table class="form-table" style="margin:0;">
                    <tr><th style="padding:8px 0;"><label>Nome *</label></th><td><input type="text" name="nome" class="regular-text" required></td></tr>
                    <tr><th style="padding:8px 0;"><label>Email *</label></th><td><input type="email" name="email" class="regular-text" required></td></tr>
                    <tr><th style="padding:8px 0;"><label>Senha</label></th><td><input type="text" name="senha" class="regular-text" placeholder="Gerar automaticamente"></td></tr>
                    <tr><th style="padding:8px 0;"><label>Tier *</label></th>
                    <td>
                        <select name="tier" class="regular-text">
                            <option value="tier3">Tier 3 — Jogador</option>
                            <option value="tier2">Tier 2 — Mestre</option>
                            <option value="tier1">Tier 1 — Mestre+</option>
                        </select>
                        <p class="description" style="margin-top:4px;">Tier 1: mestrar+jogar+módulos+usuários<br>Tier 2: mestrar+jogar+usuários(T3)<br>Tier 3: somente jogar</p>
                    </td></tr>
                </table>
                <p style="margin-top:12px;"><button type="submit" name="dndm_criar_jogador" class="button button-primary">✅ Criar Usuário</button></p>
                <p style="color:#666;font-size:12px;">A senha é enviada por email se deixada em branco.</p>
            </form>
        </div>
    </div>
    </div>
    <?php
}

// ── Personagens ───────────────────────────────────────────────────────────────
function dndm_page_personagens() {
    global $wpdb;

    // Handle delete
    if ( isset($_POST['dndm_excluir_personagem']) && check_admin_referer('dndm_excluir_personagem') ) {
        $pid = intval($_POST['personagem_id']);
        $wpdb->delete( $wpdb->prefix . 'dnd_personagens', array('id' => $pid) );
        wp_redirect( admin_url('admin.php?page=dnd-master-chars&msg=deleted') ); exit;
    }

    $ver_id = isset($_GET['ver']) ? intval($_GET['ver']) : 0;

    dndm_admin_header('Personagens');

    // ── Detalhe de um personagem ──────────────────────────────────────────────
    if ( $ver_id ) {
        $p = $wpdb->get_row( $wpdb->prepare(
            "SELECT p.*, u.display_name as jogador_nome, u.user_email as jogador_email
             FROM {$wpdb->prefix}dnd_personagens p
             LEFT JOIN {$wpdb->prefix}users u ON p.usuario_id = (
                 SELECT du.id FROM {$wpdb->prefix}dnd_usuarios du WHERE du.wp_user_id = u.ID LIMIT 1
             )
             WHERE p.id = %d", $ver_id
        ));
        if ( !$p ) { echo '<p>Personagem não encontrado.</p></div>'; return; }

        $atributos  = json_decode($p->atributos, true) ?: [];
        $aparencia  = json_decode($p->aparencia, true) ?: [];
        $equipamento= json_decode($p->equipamento, true) ?: [];
        $inventario = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_inventario WHERE personagem_id=%d", $ver_id
        ));
        $hp_pct = $p->hp_max > 0 ? round(($p->hp_atual / $p->hp_max) * 100) : 0;
        $hp_cor  = $hp_pct > 50 ? '#16a34a' : ($hp_pct > 25 ? '#d97706' : '#dc2626');
        $attrs   = ['FOR'=>'for','DES'=>'des','CON'=>'con','INT'=>'int','SAB'=>'sab','CAR'=>'car'];
        ?>
        <a href="<?=admin_url('admin.php?page=dnd-master-chars')?>" class="button" style="margin-bottom:16px;">← Voltar</a>
        <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;margin-top:12px;align-items:start;">
            <!-- Sidebar: imagem + info básica -->
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php if ($p->imagem_url): ?>
                    <img src="<?=esc_url($p->imagem_url)?>" style="width:100%;border-radius:10px;border:2px solid #7c3aed;">
                <?php else: ?>
                    <div style="background:#1a1a2e;border:2px solid #333;border-radius:10px;height:200px;display:flex;align-items:center;justify-content:center;font-size:48px;">⚔</div>
                <?php endif; ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:14px;">
                    <h3 style="margin:0 0 4px;"><?=esc_html($p->nome)?></h3>
                    <p style="margin:0;color:#666;font-size:13px;"><?=esc_html($p->classe)?> · <?=esc_html($p->raca)?> · Nível <?=(int)$p->nivel?></p>
                    <p style="margin:6px 0 0;font-size:12px;color:#999;">Jogador: <?=esc_html($p->jogador_nome ?: 'Desconhecido')?></p>
                    <p style="margin:2px 0 0;font-size:12px;color:#999;">Antecedente: <?=esc_html($p->antecedente)?></p>
                    <p style="margin:2px 0 0;font-size:12px;color:#999;">Alinhamento: <?=esc_html($p->alinhamento)?></p>
                    <!-- HP -->
                    <div style="margin-top:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                            <span style="color:<?=$hp_cor?>;font-weight:700;">❤ <?=(int)$p->hp_atual?>/<?=(int)$p->hp_max?> HP</span>
                            <span style="color:#666;">CA <?=(int)$p->ca?></span>
                        </div>
                        <div style="background:#eee;border-radius:4px;height:8px;overflow:hidden;">
                            <div style="width:<?=$hp_pct?>%;height:100%;background:<?=$hp_cor?>;border-radius:4px;"></div>
                        </div>
                    </div>
                    <!-- XP -->
                    <p style="margin:8px 0 0;font-size:12px;color:#666;">✨ XP: <strong><?=number_format((int)$p->xp)?></strong></p>
                </div>
                <!-- Aparência -->
                <?php if (!empty($aparencia)): ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:14px;">
                    <strong style="font-size:12px;color:#555;">APARÊNCIA</strong>
                    <?php foreach(['cabelo'=>'Cabelo','olhos'=>'Olhos','porte'=>'Porte','pele'=>'Pele','traco'=>'Traço'] as $k=>$l): ?>
                        <?php if(!empty($aparencia[$k])): ?>
                        <p style="margin:4px 0;font-size:12px;"><span style="color:#888;"><?=$l?>:</span> <?=esc_html($aparencia[$k])?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main content -->
            <div style="display:flex;flex-direction:column;gap:14px;">
                <!-- Atributos -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;">
                    <strong style="font-size:12px;letter-spacing:1px;color:#555;">ATRIBUTOS</strong>
                    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:10px;">
                        <?php foreach($attrs as $label=>$key):
                            $val = intval($atributos[$key] ?? 10);
                            $mod = floor(($val-10)/2);
                        ?>
                        <div style="border:1px solid #e2d9f3;border-radius:8px;padding:10px 4px;text-align:center;background:#faf8ff;">
                            <div style="font-size:9px;color:#7c3aed;letter-spacing:2px;margin-bottom:2px;"><?=$label?></div>
                            <div style="font-size:22px;font-weight:700;color:#1a1a2e;"><?=$val?></div>
                            <div style="font-size:11px;color:#666;"><?=($mod>=0?'+':'').$mod?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Backstory / Lore -->
                <?php if ($p->backstory): ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;">
                    <strong style="font-size:12px;letter-spacing:1px;color:#555;">📖 HISTÓRIA DE ORIGEM</strong>
                    <p style="margin:10px 0 0;font-size:13px;line-height:1.7;color:#333;white-space:pre-wrap;"><?=esc_html($p->backstory)?></p>
                </div>
                <?php endif; ?>

                <!-- Personalidade (se gerada pela IA) -->
                <?php
                $lore_fields = [];
                foreach(['personalidade'=>'Personalidade','ideal'=>'Ideal','vinculo'=>'Vínculo','fraqueza'=>'Fraqueza'] as $col=>$label) {
                    $val = $p->$col ?? '';
                    if ($val) $lore_fields[$label] = $val;
                }
                if (!empty($lore_fields)): ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;">
                    <strong style="font-size:12px;letter-spacing:1px;color:#555;">🎭 TRAÇOS DE PERSONALIDADE</strong>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
                    <?php foreach($lore_fields as $label=>$val): ?>
                        <div style="background:#f8f8f8;border-radius:6px;padding:10px;">
                            <div style="font-size:10px;color:#888;letter-spacing:1px;margin-bottom:4px;"><?=$label?></div>
                            <div style="font-size:13px;color:#333;"><?=esc_html($val)?></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Equipamento -->
                <?php if (!empty($equipamento)): ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;">
                    <strong style="font-size:12px;letter-spacing:1px;color:#555;">🎒 EQUIPAMENTO INICIAL</strong>
                    <ul style="margin:8px 0 0;padding-left:18px;column-count:2;font-size:13px;color:#444;line-height:1.8;">
                        <?php foreach((array)$equipamento as $item): ?>
                        <li><?=esc_html($item)?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Inventário -->
                <?php if (!empty($inventario)): ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;">
                    <strong style="font-size:12px;letter-spacing:1px;color:#555;">🏺 INVENTÁRIO (<?=count($inventario)?> itens)</strong>
                    <table class="widefat striped" style="margin-top:10px;font-size:13px;">
                        <thead><tr><th>Item</th><th>Qtd</th><th>Tipo</th><th>Descrição</th></tr></thead>
                        <tbody>
                        <?php foreach($inventario as $item): ?>
                        <tr>
                            <td><strong><?=esc_html($item->nome)?></strong></td>
                            <td><?=(int)($item->quantidade??1)?></td>
                            <td><?=esc_html($item->tipo??'—')?></td>
                            <td style="color:#666;"><?=esc_html(wp_trim_words($item->descricao??'',12))?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Ações -->
                <div style="display:flex;gap:10px;">
                    <form method="post" onsubmit="return confirm('Excluir personagem <?=esc_js($p->nome)?>?');">
                        <?php wp_nonce_field('dndm_excluir_personagem'); ?>
                        <input type="hidden" name="personagem_id" value="<?=(int)$p->id?>">
                        <button type="submit" name="dndm_excluir_personagem" class="button" style="color:#dc2626;border-color:#dc2626;">🗑 Excluir Personagem</button>
                    </form>
                </div>
            </div>
        </div>
        </div>
        <?php
        return;
    }

    // ── Lista de todos os personagens ──────────────────────────────────────────
    $personagens = $wpdb->get_results(
        "SELECT p.*, u.display_name as jogador_nome
         FROM {$wpdb->prefix}dnd_personagens p
         LEFT JOIN {$wpdb->prefix}users u ON p.usuario_id = (
             SELECT du.id FROM {$wpdb->prefix}dnd_usuarios du
             LEFT JOIN {$wpdb->prefix}users wu ON du.wp_user_id = wu.ID
             WHERE du.id = p.usuario_id LIMIT 1
         )
         ORDER BY p.criado_em DESC"
    );

    // Busca correta: join via dnd_usuarios
    $personagens = $wpdb->get_results(
        "SELECT p.*, wu.display_name as jogador_nome
         FROM {$wpdb->prefix}dnd_personagens p
         LEFT JOIN {$wpdb->prefix}dnd_usuarios du ON du.id = p.usuario_id
         LEFT JOIN {$wpdb->prefix}users wu ON wu.ID = du.wp_user_id
         ORDER BY p.criado_em DESC"
    );
    ?>
    <table class="widefat striped" style="margin-top:16px;">
        <thead><tr>
            <th style="width:60px;">Retrato</th>
            <th>Nome</th><th>Classe</th><th>Raça</th><th>Nível</th>
            <th>HP</th><th>Jogador</th><th>Criado em</th><th>Ações</th>
        </tr></thead>
        <tbody>
        <?php if (empty($personagens)): ?>
            <tr><td colspan="9" style="text-align:center;padding:24px;color:#999;">Nenhum personagem criado ainda.</td></tr>
        <?php else: foreach ($personagens as $p):
            $hp_pct = $p->hp_max > 0 ? round(($p->hp_atual / $p->hp_max)*100) : 0;
            $hp_cor = $hp_pct > 50 ? '#16a34a' : ($hp_pct > 25 ? '#d97706' : '#dc2626');
        ?>
            <tr>
                <td>
                    <?php if ($p->imagem_url): ?>
                        <img src="<?=esc_url($p->imagem_url)?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:2px solid #7c3aed;">
                    <?php else: ?>
                        <div style="width:44px;height:44px;background:#f0f0f8;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;">⚔</div>
                    <?php endif; ?>
                </td>
                <td><strong><?=esc_html($p->nome)?></strong><br><small style="color:#999;"><?=esc_html($p->alinhamento)?></small></td>
                <td><?=esc_html($p->classe)?></td>
                <td><?=esc_html($p->raca)?></td>
                <td style="text-align:center;"><?=(int)$p->nivel?></td>
                <td>
                    <span style="color:<?=$hp_cor?>;font-weight:600;"><?=(int)$p->hp_atual?>/<?=(int)$p->hp_max?></span>
                    <div style="background:#eee;border-radius:4px;height:4px;width:60px;margin-top:3px;overflow:hidden;">
                        <div style="width:<?=$hp_pct?>%;height:100%;background:<?=$hp_cor?>;"></div>
                    </div>
                </td>
                <td><?=esc_html($p->jogador_nome ?: '—')?></td>
                <td><?=date('d/m/Y', strtotime($p->criado_em))?></td>
                <td>
                    <a href="<?=admin_url('admin.php?page=dnd-master-chars&ver='.(int)$p->id)?>" class="button button-small">👁 Ver Ficha</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    <?php
}

// ── Conquistas (admin) ────────────────────────────────────────────────────────
function dndm_page_conquistas() {
    global $wpdb;

    // ── Processar concessão manual ──
    if ( isset($_POST['dndm_conceder_badge']) && check_admin_referer('dndm_conceder_badge') ) {
        $wp_uid   = intval($_POST['wp_user_id']);
        $badge    = sanitize_text_field($_POST['badge_slug']);
        $aventura = sanitize_text_field($_POST['aventura_nome'] ?? '');
        $char_id  = intval($_POST['char_id'] ?? 0) ?: null;
        if ( $wp_uid && $badge ) {
            $nova = DNDM_Achievements::award( $wp_uid, $badge, $char_id, $aventura );
            $msg  = $nova ? 'badge_awarded' : 'badge_already';
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-conquistas&msg=' . ($msg??'badge_awarded')) ); exit;
    }

    // ── Processar revogação ──
    if ( isset($_POST['dndm_revogar_badge']) && check_admin_referer('dndm_revogar_badge') ) {
        $achievement_id = intval($_POST['achievement_id']);
        $wpdb->delete( $wpdb->prefix . 'dnd_achievements', array('id' => $achievement_id) );
        wp_redirect( admin_url('admin.php?page=dnd-master-conquistas&msg=badge_revoked') ); exit;
    }

    dndm_admin_header('Conquistas');
    $msg = $_GET['msg'] ?? '';
    if ($msg === 'badge_awarded') echo '<div class="notice notice-success is-dismissible"><p>✅ Conquista concedida!</p></div>';
    if ($msg === 'badge_already') echo '<div class="notice notice-warning is-dismissible"><p>⚠ Jogador já possui esta conquista.</p></div>';
    if ($msg === 'badge_revoked') echo '<div class="notice notice-success is-dismissible"><p>✅ Conquista revogada.</p></div>';

    $catalogo = DNDM_Achievements::catalogo();
    $usuarios = $wpdb->get_results("SELECT u.ID, u.display_name FROM {$wpdb->prefix}users u
        INNER JOIN {$wpdb->prefix}dnd_usuarios du ON du.wp_user_id = u.ID ORDER BY u.display_name");
    $conquistas_all = $wpdb->get_results("SELECT a.*, u.display_name
        FROM {$wpdb->prefix}dnd_achievements a
        LEFT JOIN {$wpdb->prefix}users u ON a.user_id = u.ID
        ORDER BY a.conquistado_em DESC LIMIT 200");

    $raridade_cor = array('bronze'=>'#cd7f32','prata'=>'#a8a9ad','ouro'=>'#ffd700');
    ?>
    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;margin-top:16px;align-items:start;">

        <!-- Lista de conquistas concedidas -->
        <div>
            <h3>🏅 Conquistas Concedidas Recentemente</h3>
            <table class="widefat striped" style="table-layout:fixed;">
                <thead><tr>
                    <th style="width:20%;">Jogador</th>
                    <th style="width:28%;">Conquista</th>
                    <th style="width:12%;">Raridade</th>
                    <th style="width:20%;">Aventura</th>
                    <th style="width:13%;">Data</th>
                    <th style="width:7%;">Ação</th>
                </tr></thead>
                <tbody>
                <?php if (empty($conquistas_all)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:#999;">Nenhuma conquista registrada ainda.</td></tr>
                <?php else: foreach ($conquistas_all as $c):
                    $badge_info = $catalogo[$c->badge_slug] ?? array('titulo'=>$c->badge_slug,'raridade'=>'bronze');
                    $cor = $raridade_cor[$badge_info['raridade']] ?? '#999';
                ?>
                    <tr>
                        <td><strong><?=esc_html($c->display_name ?: 'ID:'.$c->user_id)?></strong></td>
                        <td><?=esc_html($badge_info['titulo'])?><br><small style="color:#999;"><?=esc_html($c->badge_slug)?></small></td>
                        <td><span style="background:<?=$cor?>;color:#111;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;"><?=ucfirst($badge_info['raridade'])?></span></td>
                        <td style="font-size:12px;"><?=esc_html($c->aventura_nome ?: '—')?></td>
                        <td style="font-size:12px;"><?=date('d/m/y H:i', strtotime($c->conquistado_em))?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Revogar esta conquista?');">
                                <?php wp_nonce_field('dndm_revogar_badge'); ?>
                                <input type="hidden" name="achievement_id" value="<?=(int)$c->id?>">
                                <button type="submit" name="dndm_revogar_badge" class="button button-small" style="color:#dc2626;border-color:#dc2626;">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>

            <!-- Catálogo completo -->
            <h3 style="margin-top:28px;">📚 Catálogo Completo (<?=count($catalogo)?> badges)</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
                <?php foreach ($catalogo as $slug => $b):
                    $cor = $raridade_cor[$b['raridade']] ?? '#999';
                    $cat_labels = array('classe'=>'Iniciação','combate'=>'Combate','progressao'=>'Progressão');
                ?>
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px;border-left:3px solid <?=$cor?>;">
                        <div style="font-size:11px;color:#999;margin-bottom:4px;"><?=$cat_labels[$b['categoria']]?? ucfirst($b['categoria'])?></div>
                        <div style="font-weight:700;font-size:13px;"><?=esc_html($b['titulo'])?></div>
                        <div style="font-size:11px;color:#666;margin-top:4px;"><?=esc_html($b['descricao'])?></div>
                        <div style="margin-top:6px;"><code style="font-size:10px;"><?=$slug?></code></div>
                        <span style="display:inline-block;background:<?=$cor?>;color:#111;padding:1px 6px;border-radius:3px;font-size:10px;font-weight:700;margin-top:4px;"><?=ucfirst($b['raridade'])?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Formulário de concessão manual -->
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;position:sticky;top:60px;">
            <h3 style="margin-top:0;">+ Conceder Badge Manualmente</h3>
            <form method="post">
                <?php wp_nonce_field('dndm_conceder_badge'); ?>
                <table class="form-table" style="margin:0;">
                    <tr>
                        <th><label>Jogador *</label></th>
                        <td>
                            <select name="wp_user_id" class="regular-text" required>
                                <option value="">— selecionar —</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?=(int)$u->ID?>"><?=esc_html($u->display_name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Badge *</label></th>
                        <td>
                            <select name="badge_slug" class="regular-text" required>
                                <option value="">— selecionar —</option>
                                <?php
                                $grupos = array('progressao'=>'🏆 Progressão','classe'=>'🛡 Classe','combate'=>'⚔ Combate');
                                $agrupados = array();
                                foreach ($catalogo as $s => $b) $agrupados[$b['categoria']][$s] = $b;
                                foreach ($grupos as $cat => $label):
                                    if (empty($agrupados[$cat])) continue;
                                    echo "<optgroup label='{$label}'>";
                                    foreach ($agrupados[$cat] as $s => $b):
                                        echo "<option value='{$s}'>".esc_html($b['titulo'])."</option>";
                                    endforeach;
                                    echo "</optgroup>";
                                endforeach;
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Aventura</label></th>
                        <td><input type="text" name="aventura_nome" class="regular-text" placeholder="Nome da sessão/aventura"></td>
                    </tr>
                </table>
                <p style="margin-top:12px;">
                    <button type="submit" name="dndm_conceder_badge" class="button button-primary">🏅 Conceder Badge</button>
                </p>
                <p style="font-size:11px;color:#999;">A concessão manual não pode ser desfeita automaticamente. A badge será marcada como concedida imediatamente.</p>
            </form>
        </div>
    </div>
    <?php
}

// ── Configurações ─────────────────────────────────────────────────────────────
function dndm_page_config() {
    dndm_admin_header('Configurações');
    $groq_key = get_option('dndm_groq_key','');
    $poll_key = get_option('dndm_pollinations_key','');
    ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;">
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">📊 Status</h3>
            <ul style="line-height:2.2;">
                <li><?=!empty($groq_key)?'✅':'❌'?> Groq API Key <?=!empty($groq_key)?'<strong>(configurada)</strong>':'(não configurada)'?></li>
                <li><?=!empty($poll_key)?'✅':'⚠️'?> Pollinations Key <?=!empty($poll_key)?'<strong>(configurada)</strong>':'(opcional)'?></li>
                <li>✅ Versão: <strong><?=DNDM_VERSION?></strong></li>
            </ul>
        </div>
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">📖 Setup</h3>
            <ol style="line-height:2;font-size:13px;">
                <li>Configure as API keys abaixo</li>
                <li><strong>Configurações → Permalinks → Salvar</strong></li>
                <li>Acesse a home — LP deve aparecer</li>
                <li>Login admin → /dnd-mestre</li>
            </ol>
        </div>
    </div>
    <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;max-width:700px;">
        <h3 style="margin-top:0;">🔑 API Keys</h3>
        <form method="post">
            <?php wp_nonce_field('dndm_config'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="dndm_groq_key">🤖 Groq API Key <span style="color:red">*</span></label></th>
                    <td>
                        <input type="password" id="dndm_groq_key" name="dndm_groq_key" value="<?=esc_attr($groq_key)?>" class="regular-text" autocomplete="new-password">
                        <p class="description">Obrigatória para IA narrativa. Gratuita em <a href="https://console.groq.com" target="_blank">console.groq.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="dndm_pollinations_key">🎨 Pollinations Key</label></th>
                    <td>
                        <input type="password" id="dndm_pollinations_key" name="dndm_pollinations_key" value="<?=esc_attr($poll_key)?>" class="regular-text" autocomplete="new-password">
                        <p class="description">Opcional. Aumenta o rate limit de imagens.</p>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="dndm_salvar_config" class="button-primary" value="⚔ Salvar"></p>
        </form>
    </div>
    </div>
    <?php
}


