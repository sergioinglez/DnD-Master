<?php
/**
 * Plugin Name:  DnD Master Platform
 * Plugin URI:   https://github.com/dnd-master
 * Description:  Plataforma SaaS para mestrar e jogar D&D 5e diretamente no WordPress.
 * Version: 0.9.6RC
 * Author:       DnD Master
 * Text Domain:  dnd-master
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DNDM_VERSION', '0.9.6RC' );
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
    add_submenu_page( 'dnd-master', 'Mestres Solo',   '🎭 Mestres Solo',  'manage_options', 'dnd-master-mestres',  'dndm_page_mestres_solo' );
    add_submenu_page( 'dnd-master', 'Missões Solo',   '📜 Missões Solo',  'manage_options', 'dnd-master-goblin',   'dndm_page_goblin' );
    add_submenu_page( 'dnd-master', 'Editor Aventura','✏ Editor',         'manage_options', 'dnd-master-editor',   'dndm_page_editor_aventura' );
    add_submenu_page( 'dnd-master', 'Em Destaque',    '⭐ Em Destaque',   'manage_options', 'dnd-master-destaque', 'dndm_page_destaque' );
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
    // Salvar destaques
    if ( isset($_POST['dndm_salvar_destaque']) && check_admin_referer('dndm_destaque') ) {
        $config = DNDM_LP_Editor::get_config();
        $config['modulos_secao']['titulo']    = sanitize_text_field($_POST['destaque_titulo'] ?? 'Aventuras em Destaque');
        $config['modulos_secao']['subtitulo'] = sanitize_text_field($_POST['destaque_subtitulo'] ?? 'MÓDULOS DISPONÍVEIS');
        $config['modulos_secao']['modo']      = in_array($_POST['destaque_modo']??'dinamico',array('dinamico','manual')) ? $_POST['destaque_modo'] : 'dinamico';
        // Salva destaques manuais
        $destaques = array();
        $ids   = $_POST['dest_id']   ?? array();
        $tipos = $_POST['dest_tipo'] ?? array();
        $nomes = $_POST['dest_nome'] ?? array();
        $tags  = $_POST['dest_tag']  ?? array();
        $descs = $_POST['dest_desc'] ?? array();
        $capas = $_POST['dest_capa'] ?? array();
        foreach ( $ids as $i => $id ) {
            if ( empty($id) ) continue;
            $destaques[] = array(
                'id'         => intval($id),
                'tipo'       => sanitize_text_field($tipos[$i] ?? 'solo'),
                'nome_custom'=> sanitize_text_field($nomes[$i] ?? ''),
                'tagline'    => sanitize_text_field($tags[$i]  ?? ''),
                'descricao'  => sanitize_textarea_field($descs[$i] ?? ''),
                'capa_url'   => esc_url_raw($capas[$i] ?? ''),
            );
        }
        $config['modulos_secao']['destaques'] = $destaques;
        update_option('dndm_lp_config', wp_json_encode($config));
        wp_redirect( admin_url('admin.php?page=dnd-master-destaque&msg=saved') ); exit;
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
    // Salvar/criar Mestre Solo
    if ( isset($_POST['dndm_salvar_mestre_solo']) && check_admin_referer('dndm_mestre_solo') ) {
        global $wpdb;
        $id     = intval($_POST['mestre_id'] ?? 0);
        $nome   = sanitize_text_field($_POST['mestre_nome']   ?? '');
        $titulo = sanitize_text_field($_POST['mestre_titulo'] ?? '');
        $slug   = sanitize_title($nome).'-'.time();
        $persona= sanitize_textarea_field($_POST['mestre_persona'] ?? '');
        $emocoes= array('neutro','entusiasmado','suspense','assustado','debochado','satisfeito','comemorando');
        $dir    = DNDM_UPLOAD_DIR.'/mestres';
        wp_mkdir_p($dir);

        if ( $id ) {
            // Update
            $wpdb->update($wpdb->prefix.'dnd_solo_mestres',
                array('nome'=>$nome,'titulo'=>$titulo,'persona'=>$persona),
                array('id'=>$id)
            );
        } else {
            // Insert
            $wpdb->insert($wpdb->prefix.'dnd_solo_mestres',
                array('slug'=>$slug,'nome'=>$nome,'titulo'=>$titulo,'persona'=>$persona,'status'=>'ativo')
            );
            $id = $wpdb->insert_id;
        }

        // Upload de expressões
        foreach ($emocoes as $em) {
            if ( !empty($_FILES['mestre_img_'.$em]['tmp_name']) ) {
                $ext = strtolower(pathinfo($_FILES['mestre_img_'.$em]['name'], PATHINFO_EXTENSION));
                if ( in_array($ext, array('jpg','jpeg')) ) {
                    $fname = 'mestre-'.$id.'-'.$em.'-'.time().'.'.$ext;
                    $old   = get_option('dndm_mestre_'.$id.'_'.$em,'');
                    if ($old) { $old_f = str_replace(DNDM_UPLOAD_URL,DNDM_UPLOAD_DIR,$old); if(file_exists($old_f)) @unlink($old_f); }
                    if (move_uploaded_file($_FILES['mestre_img_'.$em]['tmp_name'], $dir.'/'.$fname)) {
                        update_option('dndm_mestre_'.$id.'_'.$em, DNDM_UPLOAD_URL.'/mestres/'.$fname);
                    }
                }
            }
            if ( !empty($_POST['dndm_remover_mestre_'.$em.'_'.$id]) ) {
                $old = get_option('dndm_mestre_'.$id.'_'.$em,'');
                if ($old) { $old_f = str_replace(DNDM_UPLOAD_URL,DNDM_UPLOAD_DIR,$old); if(file_exists($old_f)) @unlink($old_f); }
                delete_option('dndm_mestre_'.$id.'_'.$em);
            }
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-mestres&msg=saved') ); exit;
    }

    // Excluir Mestre Solo
    if ( isset($_POST['dndm_excluir_mestre_solo']) && check_admin_referer('dndm_mestre_solo') ) {
        global $wpdb;
        $id = intval($_POST['mestre_id']);
        $wpdb->delete($wpdb->prefix.'dnd_solo_mestres', array('id'=>$id));
        wp_redirect( admin_url('admin.php?page=dnd-master-mestres&msg=deleted') ); exit;
    }

    // Salvar expressões do Mestre Goblin (legado)
    if ( isset($_POST['dndm_salvar_goblin']) && check_admin_referer('dndm_goblin') ) {
        $emocoes = array('neutro','entusiasmado','suspense','assustado','debochado','satisfeito','comemorando');
        $dir = DNDM_UPLOAD_DIR . '/goblin';
        wp_mkdir_p($dir);

        foreach ( $emocoes as $emocao ) {
            // Upload de nova imagem
            if ( ! empty($_FILES['goblin_img_'.$emocao]['tmp_name']) ) {
                $ext   = strtolower(pathinfo($_FILES['goblin_img_'.$emocao]['name'], PATHINFO_EXTENSION));
                $allowed = array('jpg','jpeg','png','gif','webp');
                if ( in_array($ext, $allowed) ) {
                    $fname = 'goblin-'.$emocao.'-'.time().'.'.$ext;
                    // Remove imagem antiga se existir
                    $old_url = get_option('dndm_goblin_'.$emocao, '');
                    if ($old_url) {
                        $old_file = str_replace(DNDM_UPLOAD_URL, DNDM_UPLOAD_DIR, $old_url);
                        if (file_exists($old_file)) @unlink($old_file);
                    }
                    if (move_uploaded_file($_FILES['goblin_img_'.$emocao]['tmp_name'], $dir.'/'.$fname)) {
                        update_option('dndm_goblin_'.$emocao, DNDM_UPLOAD_URL.'/goblin/'.$fname);
                    }
                }
            }
            // Remover imagem
            if ( ! empty($_POST['dndm_remover_goblin_'.$emocao]) ) {
                $old_url = get_option('dndm_goblin_'.$emocao, '');
                if ($old_url) {
                    $old_file = str_replace(DNDM_UPLOAD_URL, DNDM_UPLOAD_DIR, $old_url);
                    if (file_exists($old_file)) @unlink($old_file);
                }
                delete_option('dndm_goblin_'.$emocao);
            }
        }

        // Salvar nome e título do goblin
        update_option('dndm_goblin_nome',   sanitize_text_field($_POST['dndm_goblin_nome']   ?? 'Dockside Extortionist'));
        update_option('dndm_goblin_titulo', sanitize_text_field($_POST['dndm_goblin_titulo'] ?? 'Mestre das Fofocas'));

        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&msg=saved') ); exit;
    }

    // Importar aventura solo
    if ( isset($_POST['dndm_importar_solo']) && check_admin_referer('dndm_goblin_solo') ) {
        global $wpdb;
        if ( !empty($_FILES['solo_json']['tmp_name']) ) {
            $texto = file_get_contents($_FILES['solo_json']['tmp_name']);
            $json  = json_decode($texto, true);
            if ( !$json ) {
                wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=erro_json_invalido') ); exit;
            }

            // Suporte a múltiplos formatos:
            // v1: { nome, synopsis, chapters }
            // v2: { aventura: { nome }, chapters }
            // v3: { campanha: { titulo }, atos }
            $meta = $json;
            if ( isset($json['aventura']) ) $meta = $json['aventura'];
            if ( isset($json['campanha']) ) $meta = $json['campanha'];

            // Extrai nome de qualquer campo possível
            $nome = $meta['nome']   ?? $meta['title']  ?? $meta['titulo']
                 ?? $json['nome']   ?? $json['title']  ?? $json['titulo']
                 ?? null;

            if ( empty($nome) ) {
                wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=erro_json_invalido') ); exit;
            }

            // Synopsis — pode estar em vários lugares
            $synopsis    = $meta['synopsis'] ?? $meta['introducao_ia_contexto'] ?? $json['synopsis'] ?? '';
            $duracao     = $meta['duracao']   ?? $json['duracao']  ?? '';
            $nivel       = $meta['sistema']   ?? $meta['nivel']    ?? $meta['jogadores'] ?? $json['sistema'] ?? $json['nivel'] ?? $json['jogadores'] ?? '';
            $capa_prompt = $meta['capa_prompt'] ?? $json['capa_prompt'] ?? '';

            // Extrai nivel_minimo do campo sistema/nivel (ex: "D&D 5e Solo - Nível 1" → 1)
            $nivel_minimo = 1;
            $nivel_str    = $nivel . ' ' . ($meta['estilo'] ?? '');
            if ( preg_match('/n[íi]vel\s*(\d+)/i', $nivel_str, $m) ) $nivel_minimo = (int)$m[1];

            $wpdb->insert( $wpdb->prefix . 'dnd_solo_aventuras', array(
                'nome'         => sanitize_text_field($nome),
                'synopsis'     => sanitize_textarea_field($synopsis),
                'duracao'      => sanitize_text_field($duracao),
                'nivel'        => sanitize_text_field($nivel),
                'nivel_minimo' => $nivel_minimo,
                'mestre_id'    => null,
                'capa_prompt'  => sanitize_textarea_field($capa_prompt),
                'json_content' => wp_json_encode($json),
                'status'       => 'ativa',
            ));
            wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=saved') ); exit;
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=erro_sem_arquivo') ); exit;
    }

    // Atualizar configurações de aventura solo (mestre, nivel_minimo)
    if ( isset($_POST['dndm_atualizar_solo']) && check_admin_referer('dndm_goblin_solo') ) {
        global $wpdb;
        $id           = intval($_POST['aventura_id']);
        $mestre_id    = intval($_POST['mestre_id'] ?? 0) ?: null;
        $nivel_minimo = max(1, intval($_POST['nivel_minimo'] ?? 1));
        $wpdb->update($wpdb->prefix.'dnd_solo_aventuras',
            array('mestre_id'=>$mestre_id, 'nivel_minimo'=>$nivel_minimo),
            array('id'=>$id)
        );
        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=saved') ); exit;
    }

    // Gerar capa de aventura solo
    if ( isset($_POST['dndm_gerar_capa_solo']) && check_admin_referer('dndm_goblin_solo') ) {
        global $wpdb;
        $id = intval($_POST['aventura_id']);
        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $id
        ));
        if ($av && class_exists('DNDM_Imagem')) {
            $prompt = !empty($av->capa_prompt)
                ? $av->capa_prompt
                : 'D&D 5e fantasy adventure cover art, ' . $av->synopsis . ', cinematic, detailed illustration';
            $url = DNDM_Imagem::gerar_e_salvar(
                $prompt . ', cover art, book cover style, wide format, epic fantasy',
                'solo-capa-' . $id,
                'solo'
            );
            if ($url) {
                $wpdb->update( $wpdb->prefix.'dnd_solo_aventuras', array('capa_url'=>$url), array('id'=>$id) );
                wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=saved') ); exit;
            }
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=erro_capa') ); exit;
    }

    // Upload manual de capa de aventura solo
    if ( isset($_POST['dndm_upload_capa_solo']) && check_admin_referer('dndm_goblin_solo') ) {
        global $wpdb;
        $id = intval($_POST['aventura_id']);
        if ( !empty($_FILES['solo_capa']['tmp_name']) ) {
            $dir  = DNDM_UPLOAD_DIR . '/solo'; wp_mkdir_p($dir);
            $ext  = strtolower(pathinfo($_FILES['solo_capa']['name'], PATHINFO_EXTENSION));
            $fname= 'capa-' . $id . '-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['solo_capa']['tmp_name'], $dir.'/'.$fname)) {
                $url = DNDM_UPLOAD_URL . '/solo/' . $fname;
                $wpdb->update( $wpdb->prefix.'dnd_solo_aventuras', array('capa_url'=>$url), array('id'=>$id) );
                wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=saved') ); exit;
            }
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=erro_upload') ); exit;
    }

    // Alternar status aventura solo (ativa/inativa)
    if ( isset($_POST['dndm_toggle_solo']) && check_admin_referer('dndm_goblin_solo') ) {
        global $wpdb;
        $id     = intval($_POST['aventura_id']);
        $status = sanitize_text_field($_POST['novo_status'] ?? 'inativa');
        $wpdb->update( $wpdb->prefix.'dnd_solo_aventuras', array('status'=>$status), array('id'=>$id) );
        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=saved') ); exit;
    }

    // Excluir aventura solo
    if ( isset($_POST['dndm_excluir_solo']) && check_admin_referer('dndm_goblin_solo') ) {
        global $wpdb;
        $id = intval($_POST['aventura_id']);
        $wpdb->delete( $wpdb->prefix.'dnd_solo_aventuras',  array('id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'dnd_solo_sessoes',    array('aventura_id'=>$id) );
        wp_redirect( admin_url('admin.php?page=dnd-master-goblin&tab=missoes&msg=deleted') ); exit;
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

// ── Mestres Solo ──────────────────────────────────────────────────────────────
function dndm_page_mestres_solo() {
    global $wpdb;
    dndm_admin_header('🎭 Mestres Solo');

    $editando = isset($_GET['editar']) ? intval($_GET['editar']) : 0;
    $mestre   = $editando ? $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE id=%d", $editando
    )) : null;

    $emocoes = array(
        'neutro'=>'😐 Neutro','entusiasmado'=>'😄 Entusiasmado','suspense'=>'👀 Suspense',
        'assustado'=>'😱 Assustado','debochado'=>'😏 Debochado','satisfeito'=>'😌 Satisfeito',
        'comemorando'=>'🎉 Comemorando',
    );
    ?>
    <style>
        .mestre-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin:20px 0}
        .mestre-card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px}
        .expr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin:16px 0}
        .expr-item{background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:12px}
        .expr-img{width:100%;height:120px;object-fit:cover;border-radius:6px;display:block;margin-bottom:8px}
        .expr-empty{width:100%;height:120px;background:linear-gradient(135deg,#1a1208,#2a1e0a);border-radius:6px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;font-size:28px;opacity:.4}
    </style>

    <?php if ($mestre || $editando === -1): // Formulário de edição/criação ?>
    <div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:24px;max-width:900px;">
        <h3 style="margin:0 0 20px;"><?=$mestre ? 'Editar: '.esc_html($mestre->nome) : '+ Novo Mestre'?></h3>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('dndm_mestre_solo'); ?>
            <input type="hidden" name="mestre_id" value="<?=(int)($mestre->id??0)?>">

            <table class="form-table" style="margin-bottom:20px;">
                <tr>
                    <th><label>Nome *</label></th>
                    <td><input type="text" name="mestre_nome" class="regular-text" required
                        value="<?=esc_attr($mestre->nome??'')?>" placeholder="Ex: Dockside Extortionist"></td>
                </tr>
                <tr>
                    <th><label>Título</label></th>
                    <td><input type="text" name="mestre_titulo" class="regular-text"
                        value="<?=esc_attr($mestre->titulo??'')?>" placeholder="Ex: Mestre das Fofocas"></td>
                </tr>
                <tr>
                    <th><label>Persona & Instruções</label></th>
                    <td>
                        <textarea name="mestre_persona" rows="8" class="large-text"
                            placeholder="Descreva a personalidade, tom de voz, maneirismos e como este mestre deve conduzir a aventura. Este texto vai direto para a IA como instrução base."
                            style="font-size:13px;line-height:1.6;"><?=esc_textarea($mestre->persona??'')?></textarea>
                        <p class="description">Este campo define como a IA se comportará. Seja específico sobre o tom, frases características, reações, etc.</p>
                    </td>
                </tr>
            </table>

            <?php if ($mestre): ?>
            <h4>🎭 Expressões (apenas JPG, máx. 600kb)</h4>
            <div class="expr-grid">
            <?php foreach ($emocoes as $em => $label):
                $url = get_option('dndm_mestre_'.$mestre->id.'_'.$em,'');
            ?>
                <div class="expr-item">
                    <strong style="font-size:12px;"><?=esc_html($label)?></strong><br>
                    <?php if ($url): ?>
                        <img src="<?=esc_url($url)?>" class="expr-img">
                        <label style="font-size:11px;color:#dc3545;">
                            <input type="checkbox" name="dndm_remover_mestre_<?=$em?>_<?=$mestre->id?>" value="1"> Remover
                        </label><br>
                    <?php else: ?>
                        <div class="expr-empty">🎭</div>
                    <?php endif; ?>
                    <input type="file" name="mestre_img_<?=$em?>" accept="image/jpeg"
                        style="font-size:11px;margin-top:6px;width:100%">
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p class="description">💡 Salve primeiro para depois fazer upload das expressões.</p>
            <?php endif; ?>

            <div style="display:flex;gap:10px;margin-top:16px;">
                <input type="submit" name="dndm_salvar_mestre_solo" class="button-primary"
                    value="<?=$mestre?'💾 Salvar Alterações':'✅ Criar Mestre'?>">
                <a href="<?=admin_url('admin.php?page=dnd-master-mestres')?>" class="button">← Cancelar</a>
                <?php if ($mestre): ?>
                <form method="post" style="margin:0;" onsubmit="return confirm('Excluir este mestre?');">
                    <?php wp_nonce_field('dndm_mestre_solo'); ?>
                    <input type="hidden" name="mestre_id" value="<?=$mestre->id?>">
                    <input type="submit" name="dndm_excluir_mestre_solo" class="button" style="color:#dc3545;border-color:#dc3545;" value="🗑 Excluir">
                </form>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php else: // Lista de mestres ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <p style="color:#666;font-size:13px;margin:0;">Cada aventura solo pode ter um mestre diferente com personalidade e expressões únicas.</p>
        <a href="<?=admin_url('admin.php?page=dnd-master-mestres&editar=-1')?>" class="button-primary">+ Novo Mestre</a>
    </div>

    <?php
    $mestres = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dnd_solo_mestres ORDER BY criado_em DESC");
    if (empty($mestres)): ?>
        <div style="text-align:center;padding:60px;color:#666;">
            <div style="font-size:48px;margin-bottom:16px;opacity:.3;">🎭</div>
            <p>Nenhum mestre cadastrado ainda. Crie o primeiro!</p>
        </div>
    <?php else: ?>
        <div class="mestre-grid">
        <?php foreach ($mestres as $m):
            $neutro = get_option('dndm_mestre_'.$m->id.'_neutro','');
            $exprs  = 0;
            foreach (array_keys($emocoes) as $em) { if (get_option('dndm_mestre_'.$m->id.'_'.$em,'')) $exprs++; }
        ?>
            <div class="mestre-card">
                <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                    <?php if ($neutro): ?>
                        <img src="<?=esc_url($neutro)?>" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #c9a84c;">
                    <?php else: ?>
                        <div style="width:56px;height:56px;border-radius:50%;background:#1a1208;display:flex;align-items:center;justify-content:center;font-size:24px;">🎭</div>
                    <?php endif; ?>
                    <div>
                        <strong style="font-size:15px;"><?=esc_html($m->nome)?></strong><br>
                        <span style="font-size:12px;color:#888;"><?=esc_html($m->titulo)?></span>
                    </div>
                </div>
                <div style="font-size:12px;color:#666;margin-bottom:12px;">
                    🎭 <?=$exprs?>/7 expressões · <?=$m->persona ? '✅ Persona definida' : '⚠ Sem persona'?>
                </div>
                <?php
                $aventuras_vinculadas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_solo_aventuras WHERE mestre_id=%d", $m->id
                ));
                if ($aventuras_vinculadas > 0):
                ?>
                <div style="font-size:11px;color:#888;margin-bottom:10px;">📜 <?=$aventuras_vinculadas?> aventura(s) vinculada(s)</div>
                <?php endif; ?>
                <a href="<?=admin_url('admin.php?page=dnd-master-mestres&editar='.$m->id)?>"
                    class="button button-small">✏ Editar</a>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif;
    endif; ?>
    </div>
    <?php
}

// ── Missões Solo ──────────────────────────────────────────────────────────────
function dndm_page_goblin() {
    global $wpdb;
    dndm_admin_header('📜 Missões Solo');
    dndm_goblin_tab_missoes();
    echo '</div>';
}

// ── Aba: Missões Solo ─────────────────────────────────────────────────────────
function dndm_goblin_tab_missoes() {
    global $wpdb;
    $aventuras = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}dnd_solo_aventuras ORDER BY criado_em DESC"
    );
    ?>
    <style>
        .solo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; margin:20px 0; }
        .solo-card { background:#fff; border:1px solid #ddd; border-radius:10px; overflow:hidden; }
        .solo-card-capa { width:100%; height:160px; object-fit:cover; display:block; background:#1a1208; }
        .solo-card-capa-empty { width:100%; height:160px; background:linear-gradient(135deg,#1a1208,#2a1e0a); display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; }
        .solo-card-body { padding:16px; }
        .solo-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; }
        .solo-badge.ativa { background:#d4edda; color:#155724; }
        .solo-badge.inativa { background:#f8d7da; color:#721c24; }
    </style>

    <div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px;margin-bottom:24px;max-width:600px;">
        <h3 style="margin:0 0 14px;">📤 Importar Nova Aventura Solo</h3>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('dndm_goblin_solo'); ?>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="file" name="solo_json" accept=".json,application/json"
                    style="flex:1;min-width:200px;padding:8px;border:1px solid #ddd;border-radius:4px;background:#fafafa;font-size:13px;">
                <input type="submit" name="dndm_importar_solo" class="button-primary" value="📤 Importar JSON">
            </div>
            <p class="description" style="margin-top:8px;">JSON no formato padrão da plataforma (campos: nome, synopsis, chapters, npcs, loot, checklist).</p>
        </form>
    </div>

    <?php if (empty($aventuras)): ?>
        <div style="text-align:center;padding:60px;color:#666;">
            <div style="font-size:48px;margin-bottom:16px;opacity:0.3;">📜</div>
            <p>Nenhuma aventura solo importada ainda.</p>
        </div>
    <?php else: ?>
        <div class="solo-grid">
        <?php foreach ($aventuras as $av): ?>
            <div class="solo-card">
                <?php if ($av->capa_url): ?>
                    <img src="<?=esc_url($av->capa_url)?>" class="solo-card-capa" alt="Capa">
                <?php else: ?>
                    <div class="solo-card-capa-empty">
                        <span style="font-size:36px;opacity:0.3;">🎭</span>
                        <span style="color:#5a4828;font-size:12px;">Sem capa</span>
                    </div>
                <?php endif; ?>
                <div class="solo-card-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <strong style="font-size:14px;"><?=esc_html($av->nome)?></strong>
                        <span class="solo-badge <?=esc_attr($av->status)?>"><?=$av->status==='ativa'?'✅ Ativa':'⏸ Inativa'?></span>
                    </div>
                    <?php if ($av->synopsis): ?>
                        <p style="font-size:12px;color:#666;margin:0 0 10px;line-height:1.5;"><?=esc_html(mb_substr($av->synopsis, 0, 100))?>...</p>
                    <?php endif; ?>
                    <div style="font-size:11px;color:#888;margin-bottom:12px;">
                        <?php if($av->duracao): ?><span>⏱ <?=esc_html($av->duracao)?></span> · <?php endif; ?>
                        <?php if($av->nivel): ?><span>⚔ <?=esc_html($av->nivel)?></span><?php endif; ?>
                    </div>

                    <!-- Mestre & Nível -->
                    <details style="margin-bottom:10px;">
                        <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#555;">⚙ Configurações da Aventura</summary>
                        <form method="post" style="margin-top:10px;">
                            <?php
                            wp_nonce_field('dndm_goblin_solo');
                            $mestres_list = $wpdb->get_results("SELECT id,nome FROM {$wpdb->prefix}dnd_solo_mestres WHERE status='ativo' ORDER BY nome");
                            ?>
                            <input type="hidden" name="aventura_id" value="<?=$av->id?>">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                                <div>
                                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px;">🎭 Mestre</label>
                                    <select name="mestre_id" style="width:100%;font-size:12px;padding:4px;">
                                        <option value="">— Padrão (primeiro ativo) —</option>
                                        <?php foreach ($mestres_list as $ml): ?>
                                            <option value="<?=$ml->id?>" <?=selected($av->mestre_id,$ml->id,false)?>><?=esc_html($ml->nome)?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px;">⚔ Nível Mínimo</label>
                                    <input type="number" name="nivel_minimo" min="1" max="20"
                                        value="<?=(int)($av->nivel_minimo??1)?>"
                                        style="width:100%;font-size:12px;padding:4px;">
                                </div>
                            </div>
                            <input type="submit" name="dndm_atualizar_solo" class="button" value="💾 Salvar" style="font-size:11px;">
                        </form>
                    </details>

                    <!-- Assets Visuais: Mapas e Imagens de Cena -->
                    <?php
                    $av_json_data = json_decode($av->json_content, true);
                    $tem_mapas   = !empty($av_json_data['mapas']);
                    $tem_imagens = !empty($av_json_data['imagens_cena']);
                    $total_assets = ($tem_mapas ? count($av_json_data['mapas']) : 0)
                                  + ($tem_imagens ? count($av_json_data['imagens_cena']) : 0);

                    // Conta assets já gerados
                    $gerados = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_solo_assets WHERE aventura_id=%d", $av->id
                    ));
                    ?>
                    <?php if ($tem_mapas || $tem_imagens): ?>
                    <details style="margin-bottom:10px;">
                        <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#555;">
                            🗺 Assets Visuais
                            <span style="font-size:10px;color:<?=$gerados>=$total_assets?'#16a34a':'#d97706'?>;margin-left:6px;">
                                <?=$gerados?>/<?=$total_assets?> gerados
                            </span>
                        </summary>
                        <div style="margin-top:12px;">
                            <!-- Botão gerar todos -->
                            <button type="button"
                                onclick="dndmGerarTodosAssets(<?=$av->id?>, this)"
                                style="background:linear-gradient(135deg,#1e3a5f,#2563eb);border:none;border-radius:6px;color:#fff;font-size:11px;padding:6px 14px;cursor:pointer;margin-bottom:12px;">
                                ✨ Gerar Todos com IA
                            </button>
                            <span id="assets-status-<?=$av->id?>" style="font-size:11px;color:#666;margin-left:8px;"></span>

                            <?php
                            // Lista mapas
                            if ($tem_mapas):
                                foreach ($av_json_data['mapas'] as $mapa_id => $mapa_data):
                                    $url_atual = $wpdb->get_var($wpdb->prepare(
                                        "SELECT url FROM {$wpdb->prefix}dnd_solo_assets
                                         WHERE aventura_id=%d AND tipo='mapa' AND asset_id=%s",
                                        $av->id, $mapa_id
                                    ));
                                    $nome_mapa = is_array($mapa_data) ? ($mapa_id) : $mapa_id;
                            ?>
                            <div style="background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:10px;margin-bottom:8px;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                    <span style="font-size:16px;">🗺</span>
                                    <strong style="font-size:12px;"><?=esc_html($mapa_id)?></strong>
                                    <?php if ($url_atual): ?>
                                        <span style="font-size:10px;color:#16a34a;">✅ Gerado</span>
                                    <?php else: ?>
                                        <span style="font-size:10px;color:#d97706;">⏳ Pendente</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($url_atual): ?>
                                    <img src="<?=esc_url($url_atual)?>" style="width:100%;height:100px;object-fit:cover;border-radius:4px;margin-bottom:6px;">
                                <?php endif; ?>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button type="button"
                                        onclick="dndmGerarAsset(<?=$av->id?>, 'mapa', '<?=esc_js($mapa_id)?>', this)"
                                        style="font-size:10px;padding:3px 8px;border:1px solid #2563eb;background:none;color:#2563eb;border-radius:4px;cursor:pointer;">
                                        🔄 <?=$url_atual ? 'Regerar' : 'Gerar'?>
                                    </button>
                                    <?php if ($url_atual): ?>
                                    <a href="<?=esc_url($url_atual)?>" target="_blank"
                                        style="font-size:10px;padding:3px 8px;border:1px solid #666;background:none;color:#666;border-radius:4px;text-decoration:none;">
                                        🔍 Ver
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>

                            <?php
                            // Lista imagens de cena
                            if ($tem_imagens):
                                foreach ($av_json_data['imagens_cena'] as $img_id => $prompt):
                                    $url_atual = $wpdb->get_var($wpdb->prepare(
                                        "SELECT url FROM {$wpdb->prefix}dnd_solo_assets
                                         WHERE aventura_id=%d AND tipo='imagem' AND asset_id=%s",
                                        $av->id, $img_id
                                    ));
                            ?>
                            <div style="background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:10px;margin-bottom:8px;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                    <span style="font-size:16px;">🖼</span>
                                    <strong style="font-size:12px;"><?=esc_html($img_id)?></strong>
                                    <?php if ($url_atual): ?>
                                        <span style="font-size:10px;color:#16a34a;">✅ Gerada</span>
                                    <?php else: ?>
                                        <span style="font-size:10px;color:#d97706;">⏳ Pendente</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:10px;color:#888;margin-bottom:6px;font-style:italic;">
                                    <?=esc_html(mb_substr($prompt, 0, 80))?>...
                                </div>
                                <?php if ($url_atual): ?>
                                    <img src="<?=esc_url($url_atual)?>" style="width:100%;height:80px;object-fit:cover;border-radius:4px;margin-bottom:6px;">
                                <?php endif; ?>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button type="button"
                                        onclick="dndmGerarAsset(<?=$av->id?>, 'imagem', '<?=esc_js($img_id)?>', this)"
                                        style="font-size:10px;padding:3px 8px;border:1px solid #2563eb;background:none;color:#2563eb;border-radius:4px;cursor:pointer;">
                                        🔄 <?=$url_atual ? 'Regerar' : 'Gerar'?>
                                    </button>
                                    <?php if ($url_atual): ?>
                                    <a href="<?=esc_url($url_atual)?>" target="_blank"
                                        style="font-size:10px;padding:3px 8px;border:1px solid #666;background:none;color:#666;border-radius:4px;text-decoration:none;">
                                        🔍 Ver
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </details>
                    <?php endif; ?>

                    <!-- Capa -->
                    <details style="margin-bottom:10px;">
                        <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#555;">🖼 Gerenciar Capa</summary>
                        <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
                            <form method="post" style="display:flex;gap:6px;">
                                <?php wp_nonce_field('dndm_goblin_solo'); ?>
                                <input type="hidden" name="aventura_id" value="<?=$av->id?>">
                                <input type="submit" name="dndm_gerar_capa_solo" class="button" value="🎨 Gerar com IA" style="font-size:11px;">
                            </form>
                            <form method="post" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <?php wp_nonce_field('dndm_goblin_solo'); ?>
                                <input type="hidden" name="aventura_id" value="<?=$av->id?>">
                                <input type="file" name="solo_capa" accept="image/*" style="font-size:11px;flex:1;min-width:140px;">
                                <input type="submit" name="dndm_upload_capa_solo" class="button" value="📁 Upload" style="font-size:11px;">
                            </form>
                        </div>
                    </details>

                    <!-- Ações -->
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('dndm_goblin_solo'); ?>
                            <input type="hidden" name="aventura_id" value="<?=$av->id?>">
                            <input type="hidden" name="novo_status" value="<?=$av->status==='ativa'?'inativa':'ativa'?>">
                            <input type="submit" name="dndm_toggle_solo" class="button" style="font-size:11px;"
                                value="<?=$av->status==='ativa'?'⏸ Desativar':'▶ Ativar'?>">
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Excluir esta aventura e todas as sessões dos jogadores?');">
                            <?php wp_nonce_field('dndm_goblin_solo'); ?>
                            <input type="hidden" name="aventura_id" value="<?=$av->id?>">
                            <input type="submit" name="dndm_excluir_solo" class="button" style="font-size:11px;color:#dc3545;border-color:#dc3545;" value="🗑 Excluir">
                        </form>
                        <?php
                        // Estatísticas de sessões
                        $total    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dnd_solo_sessoes WHERE aventura_id=%d", $av->id));
                        $concluidas = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dnd_solo_sessoes WHERE aventura_id=%d AND status='concluida'", $av->id));
                        if ($total > 0):
                        ?>
                        <span style="font-size:11px;color:#888;padding:4px 6px;background:#f5f5f5;border-radius:4px;">
                            👥 <?=$total?> sessões · ✅ <?=$concluidas?> concluídas
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script>
    var dndmNonce = '<?=wp_create_nonce("wp_rest")?>';
    var dndmApiBase = '<?=rest_url("dnd-master/v1")?>';

    function dndmGerarAsset(aventuraId, tipo, assetId, btn) {
        var orig = btn.textContent;
        btn.textContent = '⏳ Gerando...';
        btn.disabled = true;
        fetch(dndmApiBase + '/solo/assets/' + aventuraId + '/gerar-um', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': dndmNonce },
            body: JSON.stringify({ tipo: tipo, asset_id: assetId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.sucesso) {
                btn.textContent = '✅ Gerado!';
                setTimeout(function() { location.reload(); }, 800);
            } else {
                btn.textContent = '❌ Erro';
                alert('Erro: ' + (data.erro || 'desconhecido'));
                btn.disabled = false;
            }
        }).catch(function() {
            btn.textContent = orig;
            btn.disabled = false;
            alert('Erro de conexão.');
        });
    }

    function dndmGerarTodosAssets(aventuraId, btn) {
        if (!confirm('Gerar TODOS os mapas e imagens desta aventura? Pode demorar alguns minutos.')) return;
        var orig = btn.textContent;
        btn.textContent = '⏳ Gerando...';
        btn.disabled = true;
        var status = document.getElementById('assets-status-' + aventuraId);
        if (status) status.textContent = 'Aguarde, gerando imagens...';
        fetch(dndmApiBase + '/solo/assets/' + aventuraId + '/gerar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': dndmNonce },
            body: JSON.stringify({})
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.sucesso) {
                if (status) status.textContent = '✅ ' + data.gerado + ' gerados' + (data.falhou > 0 ? ', ' + data.falhou + ' falharam' : '');
                btn.textContent = '✅ Concluído';
                setTimeout(function() { location.reload(); }, 1200);
            } else {
                btn.textContent = orig;
                btn.disabled = false;
                if (status) status.textContent = '❌ Erro: ' + (data.erro || 'desconhecido');
            }
        }).catch(function() {
            btn.textContent = orig;
            btn.disabled = false;
        });
    }
    </script>

<?php
}

// ── Aba: Expressões do Goblin ─────────────────────────────────────────────────
function dndm_goblin_tab_expressoes() {
    $emocoes = array(
        'neutro'       => array('label' => '😐 Neutro',        'desc' => 'Ouvindo o jogador, aguardando input, transições entre cenas.'),
        'entusiasmado' => array('label' => '😄 Entusiasmado',  'desc' => 'Revelando uma fofoca, apresentando NPC novo, chegada em local importante.'),
        'suspense'     => array('label' => '👀 Suspense',       'desc' => 'Pista importante aparece, antes de uma revelação, perigo se aproximando.'),
        'assustado'    => array('label' => '😱 Assustado',      'desc' => 'Capangas aparecem, Calla é mencionada, perigo iminente.'),
        'debochado'    => array('label' => '😏 Debochado',      'desc' => 'Jogador falha numa rolagem, momentos de humor, situações constrangedoras.'),
        'satisfeito'   => array('label' => '😌 Satisfeito',     'desc' => 'Jogador resolve algo bem, conecta pistas, convence NPC difícil.'),
        'comemorando'  => array('label' => '🎉 Comemorando',    'desc' => 'Exclusivo para o final da aventura ao desbloquear o badge.'),
    );

    $goblin_nome   = get_option('dndm_goblin_nome',   'Dockside Extortionist');
    $goblin_titulo = get_option('dndm_goblin_titulo', 'Mestre das Fofocas');

    $total_configuradas = 0;
    foreach ( array_keys($emocoes) as $emocao ) {
        if ( get_option('dndm_goblin_'.$emocao, '') ) $total_configuradas++;
    }
    ?>
    <style>
        .goblin-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; margin:20px 0; }
        .goblin-card { background:#fff; border:1px solid #ddd; border-radius:10px; overflow:hidden; }
        .goblin-card-header { padding:14px 18px; background:#f8f8f8; border-bottom:1px solid #eee; display:flex; align-items:center; gap:10px; }
        .goblin-card-header strong { font-size:15px; }
        .goblin-card-header span { font-size:12px; color:#666; }
        .goblin-card-body { padding:16px 18px; }
        .goblin-preview { width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px; display:block; background:#f0f0f0; }
        .goblin-preview-empty { width:100%; height:180px; border-radius:6px; background:linear-gradient(135deg,#1a1208,#2a1e0a); display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; margin-bottom:10px; }
        .goblin-preview-empty span { font-size:40px; opacity:0.4; }
        .goblin-preview-empty p { color:#5a4828; font-size:12px; margin:0; font-style:italic; }
        .goblin-status { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .goblin-status.ok { background:#d4edda; color:#155724; }
        .goblin-status.missing { background:#fff3cd; color:#856404; }
        .goblin-progress { background:#f0f0f0; border-radius:20px; height:8px; margin:8px 0 16px; overflow:hidden; }
        .goblin-progress-bar { height:100%; background:linear-gradient(90deg,#c9a84c,#f0d080); border-radius:20px; transition:width .4s; }
        .goblin-top-card { background:#fff; border:1px solid #ddd; border-radius:10px; padding:20px; margin-bottom:20px; }
        .goblin-identity { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    </style>

    <?php
    // Status geral
    $pct = round(($total_configuradas / count($emocoes)) * 100);
    ?>
    <div class="goblin-top-card">
        <div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <h3 style="margin:0 0 6px;">🎭 Status das Expressões</h3>
                <p style="color:#666;font-size:13px;margin:0 0 10px;"><?=$total_configuradas?>/<?=count($emocoes)?> expressões configuradas</p>
                <div class="goblin-progress">
                    <div class="goblin-progress-bar" style="width:<?=$pct?>%"></div>
                </div>
                <?php if ($total_configuradas === count($emocoes)): ?>
                    <p style="color:#155724;font-size:13px;margin:0;">✅ Todas as expressões configuradas! O Mestre Goblin está pronto.</p>
                <?php else: ?>
                    <p style="color:#856404;font-size:13px;margin:0;">⚠ Faça upload das imagens geradas no Midjourney para cada expressão.</p>
                <?php endif; ?>
            </div>
            <div style="min-width:200px;">
                <h3 style="margin:0 0 6px;">📋 Dica de Prompt</h3>
                <p style="font-size:12px;color:#666;margin:0;line-height:1.6;">Use o mesmo prompt base no Midjourney para todas as expressões, mudando apenas a emoção. Mantenha o mesmo fundo e iluminação para transições consistentes.</p>
            </div>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('dndm_goblin'); ?>

        <div class="goblin-top-card">
            <h3 style="margin:0 0 14px;">🧌 Identidade do Mestre</h3>
            <div class="goblin-identity">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Nome</label>
                    <input type="text" name="dndm_goblin_nome" value="<?=esc_attr($goblin_nome)?>" class="regular-text" placeholder="Dockside Extortionist">
                    <p class="description">Como o goblin se apresenta aos jogadores.</p>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Título</label>
                    <input type="text" name="dndm_goblin_titulo" value="<?=esc_attr($goblin_titulo)?>" class="regular-text" placeholder="Mestre das Fofocas">
                    <p class="description">Subtítulo exibido abaixo do nome na tela da aventura.</p>
                </div>
            </div>
        </div>

        <div class="goblin-grid">
        <?php foreach ( $emocoes as $emocao => $info ):
            $url_atual = get_option('dndm_goblin_'.$emocao, '');
            $tem_img   = ! empty($url_atual);
        ?>
            <div class="goblin-card">
                <div class="goblin-card-header">
                    <div style="flex:1">
                        <strong><?=$info['label']?></strong><br>
                        <span><?=$info['desc']?></span>
                    </div>
                    <span class="goblin-status <?=$tem_img?'ok':'missing'?>">
                        <?=$tem_img?'✅ Ok':'⚠ Falta'?>
                    </span>
                </div>
                <div class="goblin-card-body">
                    <?php if ($tem_img): ?>
                        <img src="<?=esc_url($url_atual)?>" class="goblin-preview" alt="<?=$emocao?>">
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
                            <span style="font-size:11px;color:#888;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=basename($url_atual)?></span>
                            <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:#dc3545;cursor:pointer;">
                                <input type="checkbox" name="dndm_remover_goblin_<?=$emocao?>" value="1"> Remover
                            </label>
                        </div>
                        <label style="display:block;font-size:12px;color:#555;margin-bottom:6px;font-weight:600;">🔄 Substituir imagem:</label>
                    <?php else: ?>
                        <div class="goblin-preview-empty">
                            <span>🎭</span>
                            <p>Nenhuma imagem</p>
                        </div>
                        <label style="display:block;font-size:12px;color:#555;margin-bottom:6px;font-weight:600;">📤 Fazer upload:</label>
                    <?php endif; ?>
                    <input type="file" name="goblin_img_<?=$emocao?>" accept="image/jpeg,image/png,image/gif,image/webp"
                        style="display:block;width:100%;font-size:12px;padding:6px;border:1px solid #ddd;border-radius:4px;background:#fafafa;">
                    <p style="font-size:11px;color:#999;margin:6px 0 0;">JPG, PNG, GIF ou WEBP. Recomendado: 400×500px.</p>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <p class="submit" style="margin-top:10px;">
            <input type="submit" name="dndm_salvar_goblin" class="button-primary" value="🎭 Salvar Expressões">
        </p>
    </form>
    </div>
    <?php
}


function dndm_page_config() {
    dndm_admin_header('Configurações');
    $groq_key = get_option('dndm_groq_key','');
    $poll_key = get_option('dndm_pollinations_key','');
    ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;">
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">📊 Status</h3>
            <ul style="line-height:2.2;">
                <li><?=!empty($groq_key)?'✅':'❌'?> Groq API Key <?=!empty($groq_key)?'<strong>(configurada)</strong>':'<strong style="color:#dc3545;">(não configurada — obrigatória)</strong>'?></li>
                <li><?=!empty($poll_key)?'✅':'⚠️'?> Pollinations Key <?=!empty($poll_key)?'<strong>(configurada)</strong>':'(opcional)'?></li>
                <li>✅ Versão: <strong><?=DNDM_VERSION?></strong></li>
            </ul>
        </div>
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;">📖 Setup</h3>
            <ol style="line-height:2;font-size:13px;">
                <li>Configure a Groq API Key abaixo</li>
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
                        <p class="description">Obrigatória para IA narrativa e aventuras solo. Gratuita em <a href="https://console.groq.com" target="_blank">console.groq.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="dndm_pollinations_key">🎨 Pollinations Key</label></th>
                    <td>
                        <input type="password" id="dndm_pollinations_key" name="dndm_pollinations_key" value="<?=esc_attr($poll_key)?>" class="regular-text" autocomplete="new-password">
                        <p class="description">Opcional. Aumenta o rate limit de geração de imagens.</p>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="dndm_salvar_config" class="button-primary" value="⚔ Salvar"></p>
        </form>
    </div>
    </div>
    <?php
}

// ── Editor de Aventura Solo (v0.9.6RC) ───────────────────────────────────────
function dndm_page_editor_aventura() {
    global $wpdb;
    dndm_admin_header('✏ Editor de Aventura');

    $aventuras = $wpdb->get_results(
        "SELECT id, nome FROM {$wpdb->prefix}dnd_solo_aventuras WHERE status='ativa' ORDER BY criado_em DESC"
    );

    if (empty($aventuras)) {
        echo '<p>Nenhuma aventura ativa. <a href="'.admin_url('admin.php?page=dnd-master-goblin').'">Importe uma aventura primeiro.</a></p>';
        echo '</div>'; return;
    }

    $av_id_sel = intval($_GET['aventura'] ?? $aventuras[0]->id);
    $av_sel    = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $av_id_sel
    ));
    $av_json   = json_decode($av_sel->json_content ?? '{}', true) ?: array();
    $atos_json = $av_json['atos'] ?? $av_json['chapters'] ?? array();
    $mapa      = $av_json['mapa_config'] ?? array();
    $locais_mapa = $mapa['locais'] ?? array();
    $npcs_json = is_array($av_json['npcs'] ?? null) ? $av_json['npcs'] : array();
    $nonce_api = wp_create_nonce('wp_rest');
    $api_url   = rest_url('dnd-master/v1');
    $upload_url= admin_url('admin-ajax.php');
    // NPCs conhecidos da aventura — definidos aqui para estar disponível em todo escopo
    $npcs_detectados = array(
        'toblen'  => 'Toblen Stonehill',
        'linene'  => 'Linene Graywind',
        'garaele' => 'Sister Garaele',
        'harbin'  => 'Harbin Wester',
        'calla'   => 'Calla Duskmantle',
        'zarck'   => 'Zarck, o Coletor',
    );
    ?>
    <style>
        .editor-wrap{display:grid;grid-template-columns:220px 1fr;gap:0;height:calc(100vh - 120px);overflow:hidden;}
        .editor-sidebar{background:#1a1208;border-right:1px solid #2a1e0a;overflow-y:auto;padding:12px 0;}
        .editor-sidebar-title{padding:8px 16px;font-size:10px;letter-spacing:3px;color:#4a3a2a;font-family:'Cinzel',serif;}
        .editor-nav-item{display:block;padding:10px 16px;color:#8a7a5a;font-size:12px;cursor:pointer;border:none;background:none;width:100%;text-align:left;border-left:3px solid transparent;transition:all .15s;}
        .editor-nav-item:hover{background:rgba(201,168,76,.05);color:#c9a84c;}
        .editor-nav-item.active{background:rgba(201,168,76,.1);color:#c9a84c;border-left-color:#c9a84c;}
        .editor-main{background:#0d0b07;overflow-y:auto;padding:24px;}
        .editor-card{background:#120e04;border:1px solid #2a1e0a;border-radius:12px;padding:20px;margin-bottom:20px;}
        .editor-card h3{color:#c9a84c;font-family:'Cinzel',serif;font-size:13px;margin:0 0 16px;letter-spacing:1px;}
        .editor-field{margin-bottom:14px;}
        .editor-label{display:block;font-size:10px;letter-spacing:2px;color:#6a5a3a;font-family:'Cinzel',serif;margin-bottom:6px;}
        .editor-input{width:100%;background:#0d0b07;border:1px solid #2a1e0a;border-radius:6px;color:#d4b896;font-size:13px;padding:8px 12px;font-family:'Crimson Text',Georgia,serif;resize:vertical;}
        .editor-input:focus{outline:none;border-color:#c9a84c44;}
        .editor-btn{padding:8px 16px;border:none;border-radius:6px;font-family:'Cinzel',serif;font-size:11px;cursor:pointer;transition:all .2s;}
        .editor-btn-gold{background:linear-gradient(135deg,#6b4f10,#c9a84c);color:#0a0704;font-weight:700;}
        .editor-btn-ghost{background:rgba(201,168,76,.08);border:1px solid #c9a84c33;color:#c9a84c;}
        .editor-btn-danger{background:rgba(220,38,38,.1);border:1px solid #dc262633;color:#f87171;}
        .editor-img-preview{width:100%;max-height:200px;object-fit:cover;border-radius:8px;margin-bottom:8px;border:1px solid #2a1e0a;}
        .editor-img-placeholder{width:100%;height:120px;background:linear-gradient(135deg,#1a1208,#2a1e0a);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:32px;opacity:.4;margin-bottom:8px;}
        .editor-imgs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin:12px 0;}
        .editor-img-slot{background:#1a1208;border:1px solid #2a1e0a;border-radius:8px;padding:12px;}
        .editor-break-btn{background:rgba(99,102,241,.1);border:1px solid #4338ca33;color:#a5b4fc;padding:4px 10px;border-radius:4px;font-size:11px;cursor:pointer;font-family:'Cinzel',serif;}
        .editor-save-bar{position:sticky;bottom:0;background:#0d0b07;border-top:1px solid #2a1e0a;padding:12px 24px;display:flex;gap:10px;align-items:center;}
        .editor-status{font-size:11px;color:#4a3a2a;font-family:'Cinzel',serif;}
        .revisita-item{background:#1a1208;border:1px solid #2a1e0a;border-radius:8px;padding:12px;margin-bottom:8px;}
        .aventura-selector{display:flex;gap:8px;align-items:center;margin-bottom:20px;flex-wrap:wrap;}
        .aventura-selector select{background:#1a1208;border:1px solid #2a1e0a;color:#c9a84c;padding:6px 12px;border-radius:6px;font-family:'Cinzel',serif;font-size:12px;}
    </style>

    <!-- Seletor de aventura -->
    <div class="aventura-selector">
        <span style="color:#6a5a3a;font-size:11px;font-family:'Cinzel',serif;letter-spacing:2px;">AVENTURA:</span>
        <select onchange="location.href='?page=dnd-master-editor&aventura='+this.value">
            <?php foreach ($aventuras as $av): ?>
                <option value="<?=$av->id?>" <?=selected($av->id,$av_id_sel,false)?>><?=esc_html($av->nome)?></option>
            <?php endforeach; ?>
        </select>
        <span style="color:#4a3a2a;font-size:11px;"><?=count($atos_json)?> atos · <?=count($locais_mapa)?> locais</span>
    </div>

    <div class="editor-wrap">
        <!-- Sidebar de navegação -->
        <div class="editor-sidebar">
            <div class="editor-sidebar-title">SEÇÕES</div>
            <button class="editor-nav-item active" onclick="editorNav('intro-lore',this)">📖 Lore da Cidade</button>
            <div class="editor-sidebar-title" style="margin-top:12px;">ATOS</div>
            <?php foreach ($atos_json as $ato): ?>
                <button class="editor-nav-item" onclick="editorNav('ato-<?=$ato['id']?>',this)">
                    <?=$ato['id']?>. <?=esc_html(mb_substr($ato['titulo']??'Ato '.$ato['id'],0,22))?>
                </button>
            <?php endforeach; ?>
            <div class="editor-sidebar-title" style="margin-top:12px;">LOCAIS</div>
            <?php foreach ($locais_mapa as $lid => $ldados): ?>
                <button class="editor-nav-item" onclick="editorNav('local-<?=$lid?>',this)">
                    📍 <?=esc_html($ldados['nome']??$lid)?>
                </button>
            <?php endforeach; ?>
            <?php if (!empty($npcs_json)): ?>
            <div class="editor-sidebar-title" style="margin-top:12px;">NPCS</div>
            <?php foreach ($npcs_json as $npc): ?>
                <button class="editor-nav-item" onclick="editorNav('npc-<?=esc_attr(sanitize_title($npc['nome']??'npc'))?>',this)">
                    👤 <?=esc_html($npc['nome']??'NPC')?>
                </button>
            <?php endforeach; ?>
            <?php endif; ?>
            <div class="editor-sidebar-title" style="margin-top:12px;">DIÁLOGOS CACHE</div>
            <button class="editor-nav-item" onclick="editorNav('revisita-cache',this)">💬 Revisitas Cacheadas</button>
        </div>

        <!-- Main content -->
        <div class="editor-main" id="editor-main">

            <!-- INTRO LORE -->
            <div class="editor-section" id="section-intro-lore">
                <div class="editor-card">
                    <h3>📖 LORE DA CIDADE — Aparece na primeira entrada do jogador</h3>
                    <div class="editor-field">
                        <label class="editor-label">TÍTULO</label>
                        <input type="text" class="editor-input" id="il-titulo" placeholder="Ex: Bem-vindo a Phandalin" style="resize:none;">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">TEXTO DA CIDADE</label>
                        <textarea class="editor-input" id="il-texto" rows="6" placeholder="Texto de boas-vindas e lore da cidade..."></textarea>
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">IMAGENS (até 5) — cada uma com título e lore ao clicar</label>
                        <div id="il-imagens-wrap"></div>
                        <button class="editor-btn editor-btn-ghost" onclick="ilAdicionarImagem()" style="margin-top:8px;">+ Adicionar Imagem</button>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <button class="editor-btn editor-btn-gold" onclick="ilSalvar()">💾 Salvar Lore da Cidade</button>
                        <span class="editor-status" id="il-status"></span>
                    </div>
                </div>
            </div>

            <!-- ATO sections (geradas dinamicamente) -->
            <?php foreach ($atos_json as $ato):
                $ato_id  = $ato['id'];
                $titulo  = esc_attr($ato['titulo']??'');
                $dialogo = esc_attr($ato['dialogo']??$ato['content']??'');
            ?>
            <div class="editor-section" id="section-ato-<?=$ato_id?>" style="display:none;">
                <div class="editor-card">
                    <h3>ATO <?=$ato_id?> — <?=esc_html($ato['titulo']??'')?></h3>

                    <!-- Imagem do ato -->
                    <div class="editor-field">
                        <label class="editor-label">IMAGEM DO ATO</label>
                        <div id="ato-<?=$ato_id?>-img-preview" class="editor-img-placeholder">🖼</div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                            <label class="editor-btn editor-btn-ghost" style="cursor:pointer;">
                                📁 Upload
                                <input type="file" accept="image/*" style="display:none;" onchange="atoUploadImagem(<?=$ato_id?>,this)">
                            </label>
                            <button class="editor-btn editor-btn-ghost" onclick="atoGerarImagem(<?=$ato_id?>)">🎨 Gerar com IA</button>
                        </div>
                        <div class="editor-field">
                            <label class="editor-label">LORE/CURIOSIDADE DA IMAGEM (aparece ao clicar)</label>
                            <textarea class="editor-input" id="ato-<?=$ato_id?>-img-lore" rows="2" placeholder="Descreva algo curioso sobre esta cena..."></textarea>
                            <div style="display:flex;gap:6px;margin-top:4px;">
                                <button class="editor-btn editor-btn-ghost" style="font-size:10px;" onclick="gerarLore('ato-<?=$ato_id?>-img-lore','imagem','<?=esc_js($titulo)?>')">✨ Gerar Lore</button>
                            </div>
                        </div>
                        <div class="editor-field">
                            <label class="editor-label">PROMPT PARA GERAÇÃO (em português)</label>
                            <textarea class="editor-input" id="ato-<?=$ato_id?>-img-prompt" rows="2" placeholder="Ex: Beco escuro de Phandalin à noite, corpo no chão..."><?=esc_textarea($ato['image_prompt']??'')?></textarea>
                        </div>
                    </div>

                    <!-- NPC Ativo -->
                    <div class="editor-field">
                        <label class="editor-label">NPC ATIVO NESTA CENA (aparece no lugar do narrador no chat)</label>
                        <select class="editor-input" id="ato-<?=$ato_id?>-npc" style="resize:none;">
                            <option value="">— Narrador (padrão) —</option>
                            <?php foreach ($npcs_json as $npc):
                                $npc_id = sanitize_title($npc['nome']??'npc');
                            ?>
                            <option value="<?=esc_attr($npc_id)?>"><?=esc_html($npc['nome']??'')?></option>
                            <?php endforeach; ?>
                            <?php foreach ($npcs_detectados as $nid => $nnome): ?>
                            <option value="<?=$nid?>"><?=esc_html($nnome)?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Diálogo com quebras -->
                    <div class="editor-field">
                        <label class="editor-label">DIÁLOGO / NARRATIVA</label>
                        <div style="display:flex;gap:6px;margin-bottom:6px;">
                            <button class="editor-break-btn" onclick="inserirQuebra('ato-<?=$ato_id?>-dialogo')">✂ Inserir [BREAK]</button>
                            <span style="font-size:10px;color:#4a3a2a;">Use [BREAK] para dividir em telas. O jogador clica "Continuar" entre cada trecho.</span>
                        </div>
                        <textarea class="editor-input" id="ato-<?=$ato_id?>-dialogo" rows="8"
                            placeholder="[RA] Texto da cena... [BREAK] Segundo trecho..."><?=esc_textarea($ato['dialogo']??$ato['content']??'')?></textarea>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="editor-btn editor-btn-gold" onclick="atoSalvar(<?=$ato_id?>)">💾 Salvar Ato</button>
                        <button class="editor-btn editor-btn-ghost" onclick="gerarLore('ato-<?=$ato_id?>-img-lore','imagem','<?=esc_js($titulo)?>')">✨ Gerar Lore IA</button>
                        <span class="editor-status" id="ato-<?=$ato_id?>-status"></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- LOCAL sections -->
            <?php foreach ($locais_mapa as $lid => $ldados): ?>
            <div class="editor-section" id="section-local-<?=$lid?>" style="display:none;">
                <div class="editor-card">
                    <h3>📍 <?=esc_html($ldados['nome']??$lid)?></h3>
                    <div class="editor-field">
                        <label class="editor-label">NOME DO LOCAL</label>
                        <input type="text" class="editor-input" id="local-<?=$lid?>-nome"
                            value="<?=esc_attr($ldados['nome']??'')?>" style="resize:none;">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">LORE DO LOCAL (aparece ao clicar no pin do mapa)</label>
                        <textarea class="editor-input" id="local-<?=$lid?>-lore" rows="4"
                            placeholder="Historia, curiosidades, segredos deste local..."></textarea>
                        <button class="editor-btn editor-btn-ghost" style="margin-top:4px;font-size:10px;"
                            onclick="gerarLore('local-<?=$lid?>-lore','local','<?=esc_js($ldados['nome']??$lid)?>')">✨ Gerar Lore IA</button>
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">IMAGENS DO LOCAL (até 5) — cada uma com lore ao clicar</label>
                        <div id="local-<?=$lid?>-imgs-wrap"></div>
                        <button class="editor-btn editor-btn-ghost" onclick="localAdicionarImagem('<?=$lid?>')" style="margin-top:8px;">+ Adicionar Imagem</button>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <button class="editor-btn editor-btn-gold" onclick="localSalvar('<?=$lid?>')">💾 Salvar Local</button>
                        <span class="editor-status" id="local-<?=$lid?>-status"></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- NPC sections -->
            <?php
            $todos_npcs = array_merge(
                (array)$npcs_json,
                array_map(
                    function($id, $nome){ return array('id'=>$id,'nome'=>$nome); },
                    array_keys($npcs_detectados),
                    array_values($npcs_detectados)
                )
            );
            foreach ($todos_npcs as $npc):
                $npc_id  = sanitize_title($npc['nome']??'npc');
                $npc_nome= esc_html($npc['nome']??'');
            ?>
            <div class="editor-section" id="section-npc-<?=$npc_id?>" style="display:none;">
                <div class="editor-card">
                    <h3>👤 <?=$npc_nome?></h3>
                    <div style="display:grid;grid-template-columns:160px 1fr;gap:16px;">
                        <div>
                            <div id="npc-<?=$npc_id?>-preview" class="editor-img-placeholder" style="height:160px;">👤</div>
                            <label class="editor-btn editor-btn-ghost" style="display:block;text-align:center;cursor:pointer;margin-top:4px;font-size:10px;">
                                📁 Upload Foto
                                <input type="file" accept="image/*" style="display:none;" onchange="npcUploadImagem('<?=$npc_id?>',this)">
                            </label>
                        </div>
                        <div>
                            <div class="editor-field">
                                <label class="editor-label">LORE / CURIOSIDADES / DETALHES</label>
                                <textarea class="editor-input" id="npc-<?=$npc_id?>-lore" rows="6"
                                    placeholder="Personalidade, segredos, ferimentos, histórico..."></textarea>
                                <button class="editor-btn editor-btn-ghost" style="margin-top:4px;font-size:10px;"
                                    onclick="gerarLore('npc-<?=$npc_id?>-lore','npc','<?=esc_js($npc['nome']??'')?>')">✨ Gerar Lore IA</button>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <button class="editor-btn editor-btn-gold" onclick="npcSalvar('<?=$npc_id?>','<?=$npc_nome?>')">💾 Salvar NPC</button>
                        <span class="editor-status" id="npc-<?=$npc_id?>-status"></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Revisita Cache -->
            <div class="editor-section" id="section-revisita-cache" style="display:none;">
                <div class="editor-card">
                    <h3>💬 DIÁLOGOS DE REVISITA CACHEADOS</h3>
                    <p style="color:#6a5a3a;font-size:12px;margin-bottom:16px;">Diálogos gerados pela IA quando o jogador revisita um local. Você pode editar cada um.</p>
                    <div id="revisita-lista">
                        <?php
                        $revisitas = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}dnd_solo_revisita WHERE aventura_id=%d ORDER BY gerado_em DESC", $av_id_sel
                        ));
                        if (empty($revisitas)): ?>
                            <p style="color:#4a3a2a;font-size:12px;">Nenhum diálogo cacheado ainda. Eles aparecem quando os jogadores visitam locais pela segunda vez.</p>
                        <?php else: foreach ($revisitas as $rev): ?>
                        <div class="revisita-item">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <span style="color:#c9a84c;font-size:11px;font-family:'Cinzel',serif;">📍 <?=esc_html($rev->local_id)?> <?=$rev->editado?'✏':'🤖'?></span>
                                <span style="color:#4a3a2a;font-size:10px;"><?=date('d/m H:i',strtotime($rev->gerado_em))?></span>
                            </div>
                            <textarea class="editor-input" id="rev-<?=$rev->id?>" rows="3" style="margin-bottom:8px;"><?=esc_textarea($rev->dialogo)?></textarea>
                            <button class="editor-btn editor-btn-ghost" style="font-size:10px;" onclick="revisitaSalvar(<?=$rev->id?>)">💾 Salvar edição</button>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /editor-main -->
    </div><!-- /editor-wrap -->
    </div><!-- /wrap -->

    <script>
    var EDITOR_AV_ID = <?=$av_id_sel?>;
    var EDITOR_NONCE = '<?=$nonce_api?>';
    var EDITOR_API   = '<?=$api_url?>';
    var editorData   = {};

    // Carrega todos os dados do BD ao iniciar
    fetch(EDITOR_API+'/solo/editor/todos/'+EDITOR_AV_ID, {
        headers:{'X-WP-Nonce':EDITOR_NONCE}
    }).then(r=>r.json()).then(function(d){
        editorData = d;
        // Popula atos
        (d.atos||[]).forEach(function(a){
            var el = document.getElementById('ato-'+a.ato_id+'-dialogo');
            if(el && a.dialogo) el.value = a.dialogo;
            var lore = document.getElementById('ato-'+a.ato_id+'-img-lore');
            if(lore && a.imagem_lore) lore.value = a.imagem_lore;
            var prompt = document.getElementById('ato-'+a.ato_id+'-img-prompt');
            if(prompt && a.imagem_prompt) prompt.value = a.imagem_prompt;
            var npcSel = document.getElementById('ato-'+a.ato_id+'-npc');
            if(npcSel && a.npc_ativo) npcSel.value = a.npc_ativo;
            var imgPrev = document.getElementById('ato-'+a.ato_id+'-img-preview');
            if(imgPrev && a.imagem_url) {
                imgPrev.outerHTML = '<img src="'+a.imagem_url+'" class="editor-img-preview" id="ato-'+a.ato_id+'-img-preview">';
            }
        });
        // Popula locais
        (d.locais||[]).forEach(function(l){
            var lore = document.getElementById('local-'+l.local_id+'-lore');
            if(lore && l.lore_texto) lore.value = l.lore_texto;
            var nome = document.getElementById('local-'+l.local_id+'-nome');
            if(nome && l.nome) nome.value = l.nome;
            var imgs = l.imagens || [];
            var wrap = document.getElementById('local-'+l.local_id+'-imgs-wrap');
            if(wrap) { wrap.innerHTML=''; imgs.forEach(function(img,i){ localAdicionarImagemSlot(l.local_id, img, i); }); }
        });
        // Popula NPCs
        (d.npcs||[]).forEach(function(n){
            var lore = document.getElementById('npc-'+n.npc_id+'-lore');
            if(lore && n.lore) lore.value = n.lore;
            var prev = document.getElementById('npc-'+n.npc_id+'-preview');
            if(prev && n.imagem_url) prev.outerHTML = '<img src="'+n.imagem_url+'" class="editor-img-preview" id="npc-'+n.npc_id+'-preview" style="height:160px;">';
        });
        // Popula intro lore
        if(d.intro){
            var t = document.getElementById('il-titulo'); if(t) t.value = d.intro.titulo||'';
            var tx = document.getElementById('il-texto'); if(tx) tx.value = d.intro.texto||'';
            var imgs = d.intro.imagens||[];
            imgs.forEach(function(img,i){ ilAdicionarImagemSlot(img,i); });
        }
    });

    function editorNav(id, btn){
        document.querySelectorAll('.editor-section').forEach(s=>s.style.display='none');
        document.querySelectorAll('.editor-nav-item').forEach(b=>b.classList.remove('active'));
        var sec = document.getElementById('section-'+id);
        if(sec) sec.style.display='block';
        if(btn) btn.classList.add('active');
    }

    function editorFetch(url, body, statusId){
        var st = document.getElementById(statusId);
        if(st) st.textContent = '⏳ Salvando...';
        return fetch(EDITOR_API+url, {
            method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':EDITOR_NONCE},
            body: JSON.stringify(body)
        }).then(r=>r.json()).then(function(d){
            if(st) st.textContent = d.sucesso ? '✅ Salvo!' : '❌ '+( d.erro||'Erro');
            setTimeout(function(){ if(st) st.textContent=''; }, 3000);
            return d;
        }).catch(function(){ if(st) st.textContent='❌ Erro'; });
    }

    // ── Atos ────────────────────────────────────────────────────────────────
    function atoSalvar(id){
        editorFetch('/solo/editor/ato/'+EDITOR_AV_ID+'/'+id, {
            aventura_id:   EDITOR_AV_ID,
            ato_id:        String(id),
            dialogo:       document.getElementById('ato-'+id+'-dialogo')?.value||'',
            imagem_lore:   document.getElementById('ato-'+id+'-img-lore')?.value||'',
            imagem_prompt: document.getElementById('ato-'+id+'-img-prompt')?.value||'',
            npc_ativo:     document.getElementById('ato-'+id+'-npc')?.value||'',
        }, 'ato-'+id+'-status');
    }

    function atoGerarImagem(id){
        var prompt = document.getElementById('ato-'+id+'-img-prompt')?.value;
        if(!prompt){ alert('Preencha o prompt primeiro.'); return; }
        var st = document.getElementById('ato-'+id+'-status');
        if(st) st.textContent = '🎨 Gerando...';
        fetch(EDITOR_API+'/solo/editor/gerar-imagem-ato', {
            method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':EDITOR_NONCE},
            body: JSON.stringify({aventura_id:EDITOR_AV_ID, ato_id:String(id), prompt:prompt})
        }).then(r=>r.json()).then(function(d){
            if(d.sucesso){
                var prev = document.getElementById('ato-'+id+'-img-preview');
                if(prev) prev.outerHTML='<img src="'+d.url+'" class="editor-img-preview" id="ato-'+id+'-img-preview">';
                if(st) st.textContent='✅ Gerada!';
            } else { if(st) st.textContent='❌ '+d.erro; }
            setTimeout(function(){ if(st) st.textContent=''; },3000);
        });
    }

    function atoUploadImagem(id, input){
        if(!input.files[0]) return;
        var fd = new FormData();
        fd.append('action','dndm_upload_solo_asset');
        fd.append('file', input.files[0]);
        fd.append('tipo','ato');
        fd.append('ref_id', EDITOR_AV_ID+'_'+id);
        fd.append('_ajax_nonce','<?=wp_create_nonce("dndm_upload")?>');
        fetch('<?=admin_url("admin-ajax.php")?>', {method:'POST',body:fd})
        .then(r=>r.json()).then(function(d){
            if(d.url){
                var prev = document.getElementById('ato-'+id+'-img-preview');
                if(prev) prev.outerHTML='<img src="'+d.url+'" class="editor-img-preview" id="ato-'+id+'-img-preview">';
                // Salva URL
                editorFetch('/solo/editor/ato/'+EDITOR_AV_ID+'/'+id,{aventura_id:EDITOR_AV_ID,ato_id:String(id),imagem_url:d.url},'ato-'+id+'-status');
            }
        });
    }

    function inserirQuebra(textareaId){
        var ta = document.getElementById(textareaId);
        if(!ta) return;
        var pos = ta.selectionStart;
        ta.value = ta.value.slice(0,pos) + '\n[BREAK]\n' + ta.value.slice(pos);
        ta.focus();
        ta.setSelectionRange(pos+9, pos+9);
    }

    // ── Locais ───────────────────────────────────────────────────────────────
    function localAdicionarImagem(lid){
        var wrap = document.getElementById('local-'+lid+'-imgs-wrap');
        if(!wrap) return;
        var atual = wrap.querySelectorAll('.editor-img-slot').length;
        if(atual >= 5){ alert('Máximo 5 imagens por local.'); return; }
        localAdicionarImagemSlot(lid, {}, atual);
    }

    function localAdicionarImagemSlot(lid, dados, idx){
        var wrap = document.getElementById('local-'+lid+'-imgs-wrap');
        if(!wrap) return;
        var div = document.createElement('div');
        div.className = 'editor-img-slot';
        div.dataset.idx = idx;
        div.innerHTML = `
            ${dados.url ? '<img src="'+dados.url+'" class="editor-img-preview">' : '<div class="editor-img-placeholder" style="height:80px;">🖼</div>'}
            <label class="editor-btn editor-btn-ghost" style="display:block;text-align:center;font-size:10px;cursor:pointer;margin-bottom:6px;">
                📁 Upload <input type="file" accept="image/*" style="display:none;" onchange="localUploadImagem('${lid}',${idx},this)">
            </label>
            <input type="text" class="editor-input" placeholder="Título da imagem" value="${dados.titulo||''}" style="margin-bottom:4px;font-size:11px;" data-field="titulo">
            <textarea class="editor-input" rows="2" placeholder="Lore/curiosidade ao clicar..." style="font-size:11px;" data-field="lore">${dados.lore||''}</textarea>
            <input type="hidden" data-field="url" value="${dados.url||''}">
        `;
        wrap.appendChild(div);
    }

    function localUploadImagem(lid, idx, input){
        if(!input.files[0]) return;
        var fd = new FormData();
        fd.append('action','dndm_upload_solo_asset');
        fd.append('file',input.files[0]);
        fd.append('tipo','local');
        fd.append('ref_id',EDITOR_AV_ID+'_'+lid+'_'+idx);
        fd.append('_ajax_nonce','<?=wp_create_nonce("dndm_upload")?>');
        fetch('<?=admin_url("admin-ajax.php")?>', {method:'POST',body:fd})
        .then(r=>r.json()).then(function(d){
            if(d.url){
                var slot = document.querySelector('#local-'+lid+'-imgs-wrap .editor-img-slot[data-idx="'+idx+'"]');
                if(slot){
                    slot.querySelector('[data-field="url"]').value = d.url;
                    var old = slot.querySelector('.editor-img-placeholder')||slot.querySelector('.editor-img-preview');
                    if(old) old.outerHTML='<img src="'+d.url+'" class="editor-img-preview">';
                }
            }
        });
    }

    function localSalvar(lid){
        var wrap = document.getElementById('local-'+lid+'-imgs-wrap');
        var slots = wrap ? wrap.querySelectorAll('.editor-img-slot') : [];
        var imagens = [];
        slots.forEach(function(s){
            var url   = s.querySelector('[data-field="url"]')?.value||'';
            var titulo= s.querySelector('[data-field="titulo"]')?.value||'';
            var lore  = s.querySelector('[data-field="lore"]')?.value||'';
            if(url||titulo) imagens.push({url,titulo,lore});
        });
        editorFetch('/solo/editor/local/'+EDITOR_AV_ID+'/'+lid, {
            aventura_id: EDITOR_AV_ID,
            local_id:    lid,
            nome:        document.getElementById('local-'+lid+'-nome')?.value||'',
            lore_texto:  document.getElementById('local-'+lid+'-lore')?.value||'',
            imagens:     imagens,
        }, 'local-'+lid+'-status');
    }

    // ── NPCs ─────────────────────────────────────────────────────────────────
    function npcUploadImagem(npcId, input){
        if(!input.files[0]) return;
        var fd = new FormData();
        fd.append('action','dndm_upload_solo_asset');
        fd.append('file',input.files[0]);
        fd.append('tipo','npc');
        fd.append('ref_id',EDITOR_AV_ID+'_'+npcId);
        fd.append('_ajax_nonce','<?=wp_create_nonce("dndm_upload")?>');
        fetch('<?=admin_url("admin-ajax.php")?>', {method:'POST',body:fd})
        .then(r=>r.json()).then(function(d){
            if(d.url){
                var prev = document.getElementById('npc-'+npcId+'-preview');
                if(prev) prev.outerHTML='<img src="'+d.url+'" class="editor-img-preview" id="npc-'+npcId+'-preview" style="height:160px;">';
                editorFetch('/solo/editor/npc/'+EDITOR_AV_ID+'/'+npcId,{aventura_id:EDITOR_AV_ID,npc_id:npcId,imagem_url:d.url},'npc-'+npcId+'-status');
            }
        });
    }

    function npcSalvar(npcId, nome){
        editorFetch('/solo/editor/npc/'+EDITOR_AV_ID+'/'+npcId, {
            aventura_id: EDITOR_AV_ID,
            npc_id:      npcId,
            nome:        nome,
            lore:        document.getElementById('npc-'+npcId+'-lore')?.value||'',
        }, 'npc-'+npcId+'-status');
    }

    // ── Intro Lore ───────────────────────────────────────────────────────────
    var ilImagens = [];
    function ilAdicionarImagem(){
        if(ilImagens.length >= 5){ alert('Máximo 5 imagens.'); return; }
        ilAdicionarImagemSlot({}, ilImagens.length);
        ilImagens.push({});
    }
    function ilAdicionarImagemSlot(dados, idx){
        var wrap = document.getElementById('il-imagens-wrap');
        if(!wrap) return;
        var div = document.createElement('div');
        div.className = 'editor-img-slot';
        div.dataset.idx = idx;
        div.innerHTML = `
            ${dados.url?'<img src="'+dados.url+'" class="editor-img-preview">':'<div class="editor-img-placeholder" style="height:80px;">🖼</div>'}
            <label class="editor-btn editor-btn-ghost" style="display:block;text-align:center;font-size:10px;cursor:pointer;margin-bottom:6px;">
                📁 Upload <input type="file" accept="image/*" style="display:none;" onchange="ilUploadImagem(${idx},this)">
            </label>
            <input type="text" class="editor-input" placeholder="Título" value="${dados.titulo||''}" style="margin-bottom:4px;font-size:11px;" data-field="titulo">
            <textarea class="editor-input" rows="2" placeholder="Texto/lore ao clicar..." style="font-size:11px;" data-field="lore">${dados.lore||''}</textarea>
            <input type="hidden" data-field="url" value="${dados.url||''}">
        `;
        wrap.appendChild(div);
    }
    function ilUploadImagem(idx, input){
        if(!input.files[0]) return;
        var fd = new FormData();
        fd.append('action','dndm_upload_solo_asset');
        fd.append('file',input.files[0]);
        fd.append('tipo','lore');
        fd.append('ref_id',EDITOR_AV_ID+'_lore_'+idx);
        fd.append('_ajax_nonce','<?=wp_create_nonce("dndm_upload")?>');
        fetch('<?=admin_url("admin-ajax.php")?>', {method:'POST',body:fd})
        .then(r=>r.json()).then(function(d){
            if(d.url){
                var slot = document.querySelector('#il-imagens-wrap .editor-img-slot[data-idx="'+idx+'"]');
                if(slot){
                    slot.querySelector('[data-field="url"]').value = d.url;
                    var old = slot.querySelector('.editor-img-placeholder')||slot.querySelector('.editor-img-preview');
                    if(old) old.outerHTML='<img src="'+d.url+'" class="editor-img-preview">';
                }
            }
        });
    }
    function ilSalvar(){
        var wrap = document.getElementById('il-imagens-wrap');
        var slots = wrap ? wrap.querySelectorAll('.editor-img-slot') : [];
        var imgs = [];
        slots.forEach(function(s){
            var url=s.querySelector('[data-field="url"]')?.value||'';
            var titulo=s.querySelector('[data-field="titulo"]')?.value||'';
            var lore=s.querySelector('[data-field="lore"]')?.value||'';
            if(url||titulo) imgs.push({url,titulo,lore});
        });
        editorFetch('/solo/editor/intro-lore/'+EDITOR_AV_ID, {
            aventura_id:EDITOR_AV_ID,
            titulo: document.getElementById('il-titulo')?.value||'',
            texto:  document.getElementById('il-texto')?.value||'',
            imagens:imgs,
        }, 'il-status');
    }

    // ── Lore IA ───────────────────────────────────────────────────────────────
    function gerarLore(targetId, tipo, nome){
        var el = document.getElementById(targetId);
        if(!el) return;
        var old = el.value;
        el.value = '⏳ Gerando...';
        fetch(EDITOR_API+'/solo/editor/gerar-lore', {
            method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':EDITOR_NONCE},
            body: JSON.stringify({tipo:tipo, nome:nome, contexto:old})
        }).then(r=>r.json()).then(function(d){
            el.value = d.lore || old;
        }).catch(function(){ el.value = old; });
    }

    // ── Revisita ───────────────────────────────────────────────────────────────
    function revisitaSalvar(id){
        var dialogo = document.getElementById('rev-'+id)?.value||'';
        fetch(EDITOR_API+'/solo/revisita/'+EDITOR_AV_ID+'/cache', {
            method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':EDITOR_NONCE},
            body: JSON.stringify({id:id, dialogo:dialogo})
        }).then(r=>r.json()).then(function(d){
            if(d.sucesso) alert('✅ Diálogo salvo!');
        });
    }
    </script>
    <?php
}

// Ajax handler para upload de assets solo
add_action('wp_ajax_dndm_upload_solo_asset', function() {
    check_ajax_referer('dndm_upload');
    if (!current_user_can('manage_options')) wp_die('Sem permissão');
    if (empty($_FILES['file'])) wp_send_json_error('Sem arquivo');

    $tipo   = sanitize_text_field($_POST['tipo']  ?? 'misc');
    $ref_id = sanitize_text_field($_POST['ref_id'] ?? 'asset');
    $dir    = DNDM_UPLOAD_DIR . '/solo/' . $tipo;
    wp_mkdir_p($dir);

    $ext  = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg','jpeg','png','gif','webp');
    if (!in_array($ext, $allowed)) wp_send_json_error('Tipo não permitido');

    $fname = sanitize_file_name($ref_id . '-' . time() . '.' . $ext);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $fname)) {
        $url = DNDM_UPLOAD_URL . '/solo/' . $tipo . '/' . $fname;
        wp_send_json_success(array('url' => $url));
    }
    wp_send_json_error('Falha ao salvar');
});

// ── Aventuras em Destaque ─────────────────────────────────────────────────────
function dndm_page_destaque() {
    global $wpdb;
    dndm_admin_header('⭐ Aventuras em Destaque');

    $config    = DNDM_LP_Editor::get_config();
    $secao     = $config['modulos_secao'] ?? array();
    $destaques = $secao['destaques'] ?? array();
    $titulo    = $secao['titulo']    ?? 'Aventuras em Destaque';
    $subtitulo = $secao['subtitulo'] ?? 'MÓDULOS DISPONÍVEIS';
    $modo      = $secao['modo']      ?? 'dinamico';
    $nonce_api = wp_create_nonce('wp_rest');
    $api_url   = rest_url('dnd-master/v1');

    $msg = $_GET['msg'] ?? '';
    if ($msg==='saved') echo '<div class="notice notice-success"><p>✅ Destaques salvos!</p></div>';

    // Busca aventuras disponíveis
    $solos   = $wpdb->get_results("SELECT id, nome, capa_url FROM {$wpdb->prefix}dnd_solo_aventuras WHERE status='ativa' ORDER BY nome");
    $modulos = $wpdb->get_results("SELECT id, nome FROM {$wpdb->prefix}dnd_modulos ORDER BY nome");
    ?>
    <style>
        .dest-wrap{display:grid;grid-template-columns:340px 1fr;gap:24px;margin:20px 0;}
        .dest-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;}
        .dest-card h3{margin-top:0;color:#1e1e1e;}
        .dest-item{background:#f9f9f9;border:1px solid #e0e0e0;border-radius:8px;padding:14px;margin-bottom:12px;position:relative;}
        .dest-item-header{display:flex;gap:10px;align-items:center;margin-bottom:10px;}
        .dest-img-prev{width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #ddd;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
        .dest-field label{display:block;font-size:11px;color:#666;margin-bottom:3px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
        .dest-field{margin-bottom:8px;}
        .dest-field input,.dest-field textarea,.dest-field select{width:100%;border:1px solid #ddd;border-radius:4px;padding:6px 8px;font-size:13px;}
        .dest-field textarea{resize:vertical;min-height:60px;}
        .dest-remove{position:absolute;top:10px;right:10px;background:#dc3545;border:none;border-radius:4px;color:#fff;padding:3px 8px;cursor:pointer;font-size:11px;}
        .dest-add-btn{background:#0073aa;border:1px solid #006799;border-radius:4px;color:#fff;padding:8px 16px;cursor:pointer;font-size:13px;width:100%;margin-top:8px;}
        .dest-preview{background:#0a0704;border-radius:12px;overflow:hidden;min-height:200px;display:flex;align-items:center;justify-content:center;color:#4a3a2a;font-family:'Cinzel',serif;font-size:12px;letter-spacing:2px;}
        .dest-preview-card{background:linear-gradient(135deg,#1a0c04,#2a1008);border:1px solid #2a1e0a;border-radius:10px;padding:20px;margin:10px;flex:1;min-width:200px;max-width:280px;}
    </style>

    <div class="dest-wrap">
        <!-- Config geral -->
        <div>
            <div class="dest-card" style="margin-bottom:20px;">
                <h3>📋 Configurações</h3>
                <form method="post">
                    <?php wp_nonce_field('dndm_destaque'); ?>

                    <div class="dest-field">
                        <label>Título da seção</label>
                        <input type="text" name="destaque_titulo" value="<?=esc_attr($titulo)?>">
                    </div>
                    <div class="dest-field">
                        <label>Subtítulo (tag acima)</label>
                        <input type="text" name="destaque_subtitulo" value="<?=esc_attr($subtitulo)?>">
                    </div>
                    <div class="dest-field">
                        <label>Modo de exibição</label>
                        <select name="destaque_modo">
                            <option value="dinamico" <?=selected($modo,'dinamico',false)?>>Dinâmico — todas as aventuras ativas</option>
                            <option value="manual"   <?=selected($modo,'manual',false)  ?>>Manual — apenas os selecionados abaixo</option>
                        </select>
                        <p style="font-size:11px;color:#666;margin:4px 0 0;">No modo dinâmico, os cards abaixo ainda customizam apresentação de cada aventura.</p>
                    </div>

                    <hr style="margin:16px 0;">
                    <h4 style="margin:0 0 12px;">🃏 Aventuras em Destaque</h4>
                    <p style="font-size:12px;color:#666;margin-bottom:12px;">Configure até 6 aventuras. Edite nome, tagline, descrição e imagem de cada uma.</p>

                    <div id="dest-lista">
                    <?php foreach ($destaques as $idx => $d): ?>
                    <div class="dest-item" id="dest-<?=$idx?>">
                        <button type="button" class="dest-remove" onclick="this.closest('.dest-item').remove()">✕</button>
                        <input type="hidden" name="dest_id[]"   value="<?=esc_attr($d['id']??'')?>">
                        <input type="hidden" name="dest_tipo[]" value="<?=esc_attr($d['tipo']??'solo')?>">
                        <div class="dest-item-header">
                            <?php if (!empty($d['capa_url'])): ?>
                                <img src="<?=esc_url($d['capa_url'])?>" class="dest-img-prev" id="dest-prev-<?=$idx?>">
                            <?php else: ?>
                                <div class="dest-img-prev" id="dest-prev-<?=$idx?>">🎭</div>
                            <?php endif; ?>
                            <div style="flex:1;">
                                <strong style="font-size:13px;"><?=esc_html($d['nome_custom']??($d['tipo']??'solo')==='solo' ? 'Aventura Solo #'.($d['id']??'') : 'Módulo #'.($d['id']??''))?></strong>
                                <div style="font-size:11px;color:#666;"><?=esc_html(ucfirst($d['tipo']??'solo'))?> · ID <?=esc_html($d['id']??'')?></div>
                            </div>
                        </div>
                        <div class="dest-field">
                            <label>Nome customizado</label>
                            <input type="text" name="dest_nome[]" value="<?=esc_attr($d['nome_custom']??')?>" placeholder="Deixe vazio para usar o original">
                        </div>
                        <div class="dest-field">
                            <label>Tagline (frase curta)</label>
                            <input type="text" name="dest_tag[]" value="<?=esc_attr($d['tagline']??'')?>" placeholder="Ex: Uma investigação sombria em Phandalin">
                        </div>
                        <div class="dest-field">
                            <label>Descrição do card</label>
                            <textarea name="dest_desc[]" placeholder="Texto abaixo do título no card..."><?=esc_textarea($d['descricao']??'')?></textarea>
                        </div>
                        <div class="dest-field">
                            <label>Imagem de capa</label>
                            <div style="display:flex;gap:6px;">
                                <input type="text" name="dest_capa[]" id="dest-capa-<?=$idx?>" value="<?=esc_url($d['capa_url']??'')?>" placeholder="URL da imagem" style="flex:1;">
                                <label style="background:#666;border:none;border-radius:4px;color:#fff;padding:6px 10px;cursor:pointer;font-size:12px;white-space:nowrap;">
                                    📁 Upload
                                    <input type="file" accept="image/*" style="display:none;" onchange="destUpload(<?=$idx?>,this)">
                                </label>
                                <button type="button" onclick="destGerarImagem(<?=$idx?>,<?=$d['id']?>,<?=json_encode($d['tipo']??'solo')?>)" style="background:#6b4f10;border:none;border-radius:4px;color:#c9a84c;padding:6px 10px;cursor:pointer;font-size:12px;">🎨 IA</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Adicionar nova aventura -->
                    <div style="background:#f0f0f0;border:1px dashed #ccc;border-radius:8px;padding:12px;margin-top:12px;">
                        <h4 style="margin:0 0 8px;font-size:13px;">+ Adicionar Aventura</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                            <div>
                                <label style="font-size:11px;color:#666;font-weight:600;">TIPO</label>
                                <select id="new-dest-tipo" style="width:100%;border:1px solid #ddd;border-radius:4px;padding:6px;">
                                    <option value="solo">🎭 Aventura Solo</option>
                                    <option value="modulo">📜 Módulo Campanha</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px;color:#666;font-weight:600;">AVENTURA</label>
                                <select id="new-dest-id" style="width:100%;border:1px solid #ddd;border-radius:4px;padding:6px;">
                                    <optgroup label="Aventuras Solo" id="opts-solo">
                                        <?php foreach ($solos as $s): ?>
                                        <option value="<?=$s->id?>" data-tipo="solo" data-nome="<?=esc_attr($s->nome)?>" data-capa="<?=esc_attr($s->capa_url??'')?>">
                                            <?=esc_html($s->nome)?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Módulos de Campanha" id="opts-modulo">
                                        <?php foreach ($modulos as $m): ?>
                                        <option value="<?=$m->id?>" data-tipo="modulo" data-nome="<?=esc_attr($m->nome)?>" data-capa="">
                                            <?=esc_html($m->nome)?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="dest-add-btn" onclick="destAdicionar()">+ Adicionar ao Destaque</button>
                    </div>

                    <p style="margin-top:16px;">
                        <input type="submit" name="dndm_salvar_destaque" class="button-primary" value="⭐ Salvar Destaques">
                    </p>
                </form>
            </div>
        </div>

        <!-- Preview -->
        <div class="dest-card">
            <h3>👁 Preview da Seção</h3>
            <p style="font-size:12px;color:#666;margin-bottom:16px;">Prévia visual dos cards na landing page.</p>
            <div style="background:#0a0704;border-radius:12px;padding:24px;">
                <div style="text-align:center;margin-bottom:24px;">
                    <div style="font-family:'Cinzel',serif;font-size:10px;letter-spacing:4px;color:#4a3a2a;margin-bottom:8px;"><?=esc_html($subtitulo)?></div>
                    <div style="font-family:'Cinzel Decorative',serif;color:#c9a84c;font-size:24px;"><?=esc_html($titulo)?></div>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;" id="preview-cards">
                <?php
                $todos = DNDM_LP_Editor::get_modulos_lp();
                foreach ($todos as $item):
                    $capa = $item['capa_url'] ?? '';
                ?>
                <div style="background:linear-gradient(135deg,#1a0c04,#2a1008);border:1px solid #2a1e0a;border-radius:10px;overflow:hidden;flex:1;min-width:180px;max-width:220px;position:relative;">
                    <?php if($capa): ?>
                    <img src="<?=esc_url($capa)?>" style="width:100%;height:120px;object-fit:cover;opacity:.5;display:block;">
                    <?php else: ?>
                    <div style="width:100%;height:120px;background:#1a1208;display:flex;align-items:center;justify-content:center;font-size:32px;opacity:.3;">🎭</div>
                    <?php endif; ?>
                    <div style="padding:12px;">
                        <div style="font-family:'Cinzel',serif;font-size:9px;color:#4a3a2a;letter-spacing:2px;margin-bottom:4px;"><?=esc_html($item['sistema'])?></div>
                        <div style="font-family:'Cinzel',serif;color:#c9a84c;font-size:12px;font-weight:700;margin-bottom:4px;"><?=esc_html($item['nome'])?></div>
                        <?php if($item['tagline']): ?>
                        <div style="color:#6a5a3a;font-size:11px;line-height:1.4;"><?=esc_html(wp_trim_words($item['tagline'],12))?></div>
                        <?php endif; ?>
                        <div style="margin-top:8px;padding:5px 10px;background:rgba(201,168,76,.1);border:1px solid #c9a84c33;border-radius:4px;color:#c9a84c;font-size:10px;font-family:'Cinzel',serif;text-align:center;">⚔ Explorar</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($todos)): ?>
                <div style="color:#4a3a2a;font-family:'Cinzel',serif;font-size:11px;letter-spacing:2px;text-align:center;width:100%;padding:32px;">NENHUMA AVENTURA CADASTRADA</div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
    var DEST_NONCE  = '<?=$nonce_api?>';
    var DEST_API    = '<?=$api_url?>';
    var DEST_UPLOAD = '<?=admin_url("admin-ajax.php")?>';
    var DEST_COUNT  = <?=count($destaques)?>;

    function destAdicionar() {
        var sel   = document.getElementById('new-dest-id');
        var opt   = sel.options[sel.selectedIndex];
        var id    = opt.value;
        var tipo  = opt.dataset.tipo || 'solo';
        var nome  = opt.dataset.nome || '';
        var capa  = opt.dataset.capa || '';
        var idx   = DEST_COUNT++;

        var div = document.createElement('div');
        div.className = 'dest-item';
        div.id = 'dest-'+idx;
        div.innerHTML = `
            <button type="button" class="dest-remove" onclick="this.closest('.dest-item').remove()">✕</button>
            <input type="hidden" name="dest_id[]"   value="${id}">
            <input type="hidden" name="dest_tipo[]" value="${tipo}">
            <div class="dest-item-header">
                ${capa ? '<img src="'+capa+'" class="dest-img-prev" id="dest-prev-'+idx+'">' : '<div class="dest-img-prev" id="dest-prev-'+idx+'">🎭</div>'}
                <div style="flex:1;"><strong style="font-size:13px;">${nome}</strong><div style="font-size:11px;color:#666;">${tipo.charAt(0).toUpperCase()+tipo.slice(1)} · ID ${id}</div></div>
            </div>
            <div class="dest-field"><label>Nome customizado</label><input type="text" name="dest_nome[]" placeholder="Deixe vazio para usar o original"></div>
            <div class="dest-field"><label>Tagline</label><input type="text" name="dest_tag[]" placeholder="Frase curta de impacto"></div>
            <div class="dest-field"><label>Descrição</label><textarea name="dest_desc[]" placeholder="Texto do card..."></textarea></div>
            <div class="dest-field"><label>Imagem de capa</label>
                <div style="display:flex;gap:6px;">
                    <input type="text" name="dest_capa[]" id="dest-capa-${idx}" value="${capa}" placeholder="URL" style="flex:1;">
                    <label style="background:#666;border:none;border-radius:4px;color:#fff;padding:6px 10px;cursor:pointer;font-size:12px;white-space:nowrap;">
                        📁 Upload <input type="file" accept="image/*" style="display:none;" onchange="destUpload(${idx},this)">
                    </label>
                    <button type="button" onclick="destGerarImagem(${idx},${id},'${tipo}')" style="background:#6b4f10;border:none;border-radius:4px;color:#c9a84c;padding:6px 10px;cursor:pointer;font-size:12px;">🎨 IA</button>
                </div>
            </div>`;
        document.getElementById('dest-lista').appendChild(div);
    }

    function destUpload(idx, input) {
        if (!input.files[0]) return;
        var fd = new FormData();
        fd.append('action','dndm_upload_solo_asset');
        fd.append('file', input.files[0]);
        fd.append('tipo','destaque');
        fd.append('ref_id','destaque_'+idx);
        fd.append('_ajax_nonce','<?=wp_create_nonce("dndm_upload")?>');
        fetch(DEST_UPLOAD,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
            if (d.url) {
                document.getElementById('dest-capa-'+idx).value = d.url;
                var prev = document.getElementById('dest-prev-'+idx);
                if (prev) prev.outerHTML = '<img src="'+d.url+'" class="dest-img-prev" id="dest-prev-'+idx+'">';
            }
        });
    }

    function destGerarImagem(idx, avId, tipo) {
        var nome = document.querySelector('#dest-'+idx+' [name="dest_nome[]"]')?.value
                || document.querySelector('#dest-'+idx+' strong')?.textContent || 'aventura';
        var btn = event.target;
        btn.textContent = '⏳';
        btn.disabled = true;
        fetch(DEST_API+'/solo/editor/gerar-imagem-ato', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-WP-Nonce':DEST_NONCE},
            body: JSON.stringify({
                aventura_id: avId,
                ato_id: 'destaque',
                prompt: 'Capa épica de aventura D&D para "'+nome+'", fantasia noir, cinematográfico, detalhado'
            })
        }).then(r=>r.json()).then(function(d){
            if (d.url) {
                document.getElementById('dest-capa-'+idx).value = d.url;
                var prev = document.getElementById('dest-prev-'+idx);
                if (prev) prev.outerHTML = '<img src="'+d.url+'" class="dest-img-prev" id="dest-prev-'+idx+'">';
            }
            btn.textContent = '🎨 IA';
            btn.disabled = false;
        }).catch(function(){ btn.textContent='🎨 IA'; btn.disabled=false; });
    }
    </script>
    <?php
}
