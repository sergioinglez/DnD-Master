<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_API {

    public static function registrar_rotas() {
        $ns = 'dnd-master/v1';

        // Usuário atual
        register_rest_route($ns, '/usuario', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_usuario'), 'permission_callback' => '__return_true'),
        ));

        // Registro público (tier3)
        register_rest_route($ns, '/registro', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'registro_publico'), 'permission_callback' => '__return_true'),
        ));

        // Logout
        register_rest_route($ns, '/logout', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'fazer_logout'), 'permission_callback' => '__return_true'),
        ));

        // Módulos públicos para Landing Page — sem autenticação
        register_rest_route($ns, '/modulos/lp', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_modulos_lp_public'), 'permission_callback' => '__return_true'),
        ));

        // Gestão de jogadores (mestre)
        register_rest_route($ns, '/mestre/jogadores', array(
            array('methods' => 'GET',  'callback' => array(__CLASS__, 'listar_jogadores'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'criar_jogador'),   'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));

        // Módulos
        register_rest_route($ns, '/modulos', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'listar_modulos'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));
        register_rest_route($ns, '/modulos/importar', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'importar_modulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));
        // Upload de PDF vinculado ao módulo
        register_rest_route($ns, '/modulos/(?P<id>\d+)/pdf', array(
            array('methods' => 'POST',   'callback' => array(__CLASS__, 'upload_pdf_modulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
            array('methods' => 'GET',    'callback' => array(__CLASS__, 'get_pdf_modulo'),    'permission_callback' => array(__CLASS__, 'check_mestre')),
            array('methods' => 'DELETE', 'callback' => array(__CLASS__, 'delete_pdf_modulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));
        // Módulo único com chapters[] parseados — CRÍTICO para o HUD carregar
        register_rest_route($ns, '/modulo/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_modulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));
        // Excluir módulo (+ checklist e NPCs vinculados)
        register_rest_route($ns, '/modulos/(?P<id>\d+)', array(
            array('methods' => 'DELETE', 'callback' => array(__CLASS__, 'excluir_modulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));
        // Gerar imagem de capítulo on-the-fly (quando não foi gerada na importação)
        register_rest_route($ns, '/mestre/gerar-imagem-capitulo', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'gerar_imagem_capitulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));
        // Upload manual de imagem de capítulo
        register_rest_route($ns, '/mestre/upload-imagem-capitulo', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'upload_imagem_capitulo'), 'permission_callback' => array(__CLASS__, 'check_mestre')),
        ));

        // Personagens
        register_rest_route($ns, '/personagem', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'criar_personagem'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/personagem/lore-opcoes', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'gerar_lore_opcoes'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/personagem/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_personagem'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/personagem/(?P<id>\d+)/ativar', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'ativar_personagem'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/personagem/(?P<id>\d+)/habilidades', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_habilidades_personagem'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        // Expressões do Mestre — carrega por aventura ou mestre_id
        register_rest_route($ns, '/goblin/expressoes', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_goblin_expressoes'), 'permission_callback' => '__return_true'),
        ));
        register_rest_route($ns, '/solo/mestre/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'solo_get_mestre'), 'permission_callback' => '__return_true'),
        ));
        // ── Assets visuais (mapas + imagens de cena) ─────────────────────────
        register_rest_route($ns, '/solo/assets/(?P<aventura_id>\d+)', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'solo_get_assets'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/solo/assets/(?P<aventura_id>\d+)/gerar', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_gerar_assets'), 'permission_callback' => array(__CLASS__, 'check_admin')),
        ));
        register_rest_route($ns, '/solo/assets/(?P<aventura_id>\d+)/gerar-um', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_gerar_asset_unico'), 'permission_callback' => array(__CLASS__, 'check_admin')),
        ));

        // ── v0.9.6RC: Editor de aventura ─────────────────────────────────────
        register_rest_route($ns, '/solo/editor/ato/(?P<aventura_id>\d+)/(?P<ato_id>[^/]+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'editor_get_ato'),    'permission_callback'=>array(__CLASS__,'check_login')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'editor_salvar_ato'), 'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/editor/local/(?P<aventura_id>\d+)/(?P<local_id>[^/]+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'editor_get_local'),    'permission_callback'=>array(__CLASS__,'check_login')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'editor_salvar_local'), 'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/editor/npc/(?P<aventura_id>\d+)/(?P<npc_id>[^/]+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'editor_get_npc'),    'permission_callback'=>array(__CLASS__,'check_login')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'editor_salvar_npc'), 'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/editor/intro-lore/(?P<aventura_id>\d+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'editor_get_intro_lore'),    'permission_callback'=>array(__CLASS__,'check_login')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'editor_salvar_intro_lore'), 'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/editor/gerar-lore', array(
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'editor_gerar_lore'), 'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/editor/gerar-imagem-ato', array(
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'editor_gerar_imagem_ato'), 'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/revisita/(?P<aventura_id>\d+)/(?P<local_id>[^/]+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'solo_get_revisita'),   'permission_callback'=>array(__CLASS__,'check_login')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'solo_salvar_revisita'),'permission_callback'=>array(__CLASS__,'check_admin')),
        ));
        register_rest_route($ns, '/solo/editor/todos/(?P<aventura_id>\d+)', array(
            array('methods'=>'GET', 'callback'=>array(__CLASS__,'editor_get_todos'), 'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        register_rest_route($ns, '/solo/aventuras', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'solo_listar_aventuras'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/solo/aventura/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'solo_get_aventura'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        // Progresso da sessão
        register_rest_route($ns, '/solo/sessao/(?P<aventura_id>\d+)', array(
            array('methods' => 'GET',  'callback' => array(__CLASS__, 'solo_get_sessao'),    'permission_callback' => array(__CLASS__, 'check_login')),
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_salvar_sessao'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/solo/sessao/(?P<aventura_id>\d+)/concluir', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_concluir'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/solo/chat-npc', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_chat_npc'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        // Geração de texto personalizado (Groq só em intro e conclusão)
        register_rest_route($ns, '/solo/intro/(?P<aventura_id>\d+)', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_gerar_intro'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/solo/conclusao/(?P<aventura_id>\d+)', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_gerar_conclusao'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        // Coleção de cartas
        register_rest_route($ns, '/solo/colecao', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'solo_get_colecao'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/solo/colecao/adicionar', array(
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'solo_adicionar_colecao'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/meus-personagens', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'meus_personagens'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));

        // Conquistas / Achievements
        register_rest_route($ns, '/achievements', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_achievements'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/achievements/poll', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'poll_achievements'), 'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        // Inventário por personagem
        register_rest_route($ns, '/inventario/(?P<personagem_id>\d+)', array(
            array('methods' => 'GET',    'callback' => array(__CLASS__, 'get_inventario'),    'permission_callback' => array(__CLASS__, 'check_login')),
            array('methods' => 'POST',   'callback' => array(__CLASS__, 'add_inventario'),    'permission_callback' => array(__CLASS__, 'check_login')),
        ));
        register_rest_route($ns, '/inventario/(?P<personagem_id>\d+)/(?P<item_id>\d+)', array(
            array('methods' => 'DELETE', 'callback' => array(__CLASS__, 'del_inventario'),    'permission_callback' => array(__CLASS__, 'check_login')),
            array('methods' => 'POST',   'callback' => array(__CLASS__, 'upd_inventario'),    'permission_callback' => array(__CLASS__, 'check_login')),
        ));

        // Controle de sessão (mestre)
        register_rest_route($ns, '/mestre/dano',      array(array('methods'=>'POST','callback'=>array(__CLASS__,'aplicar_dano'),     'permission_callback'=>array(__CLASS__,'check_mestre'))));
        register_rest_route($ns, '/mestre/cura',      array(array('methods'=>'POST','callback'=>array(__CLASS__,'aplicar_cura'),     'permission_callback'=>array(__CLASS__,'check_mestre'))));
        register_rest_route($ns, '/mestre/condicao',  array(
            array('methods'=>'POST',  'callback'=>array(__CLASS__,'add_condicao'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
            array('methods'=>'DELETE','callback'=>array(__CLASS__,'rem_condicao'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        register_rest_route($ns, '/mestre/item',      array(array('methods'=>'POST','callback'=>array(__CLASS__,'dar_item'),          'permission_callback'=>array(__CLASS__,'check_mestre'))));
        register_rest_route($ns, '/mestre/xp',        array(array('methods'=>'POST','callback'=>array(__CLASS__,'dar_xp'),            'permission_callback'=>array(__CLASS__,'check_mestre'))));
        register_rest_route($ns, '/mestre/painel/(?P<campanha_id>\d+)', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'painel_mestre'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        register_rest_route($ns, '/mestre/gancho', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'gerar_gancho'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        register_rest_route($ns, '/mestre/npc', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'gerar_npc'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        register_rest_route($ns, '/mestre/checklist/(?P<id>\d+)/concluir', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'concluir_checklist'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));

        // Mapa de batalha
        register_rest_route($ns, '/mestre/mapa/(?P<campanha_id>\d+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'get_mapa'),    'permission_callback'=>array(__CLASS__,'check_mestre')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'upload_mapa'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));

        // Posições dos tokens
        register_rest_route($ns, '/mestre/tokens/(?P<campanha_id>\d+)', array(
            array('methods'=>'GET',  'callback'=>array(__CLASS__,'get_tokens'),   'permission_callback'=>array(__CLASS__,'is_logged')),
            array('methods'=>'POST', 'callback'=>array(__CLASS__,'save_tokens'),  'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));

        // Campanhas
        register_rest_route($ns, '/campanha', array(
            array('methods'=>'GET', 'callback'=>array(__CLASS__,'listar_campanhas'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
            array('methods'=>'POST','callback'=>array(__CLASS__,'criar_campanha'),   'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Excluir campanha individual
        register_rest_route($ns, '/campanha/(?P<id>\d+)', array(
            array('methods'=>'DELETE','callback'=>array(__CLASS__,'excluir_campanha_individual'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));

        // Campanhas abertas — qualquer usuário logado pode ver
        register_rest_route($ns, '/campanhas/abertas', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'campanhas_abertas'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        // Abrir campanha para inscrições
        register_rest_route($ns, '/campanhas/(?P<id>\d+)/abrir', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'abrir_campanha'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Iniciar partida (fecha inscrições, muda status)
        register_rest_route($ns, '/campanhas/(?P<id>\d+)/iniciar', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'iniciar_campanha'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Inscrever jogador em campanha
        register_rest_route($ns, '/campanhas/(?P<id>\d+)/inscrever', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'inscrever_campanha'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        // Listar inscrições pendentes (mestre)
        register_rest_route($ns, '/campanhas/(?P<id>\d+)/inscricoes', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'listar_inscricoes'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Aprovar/rejeitar inscrição
        register_rest_route($ns, '/inscricoes/(?P<id>\d+)/aprovar', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'aprovar_inscricao'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        register_rest_route($ns, '/inscricoes/(?P<id>\d+)/rejeitar', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'rejeitar_inscricao'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Minhas campanhas como jogador + status inscrição
        register_rest_route($ns, '/minhas-campanhas', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'minhas_campanhas'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        // Ativar campanha — persiste campanha_ativa no usermeta do Mestre
        register_rest_route($ns, '/ativar-campanha', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'ativar_campanha'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Ativar campanha diretamente pelo ID da campanha (usado ao entrar como mestre pela tela de campanhas abertas)
        register_rest_route($ns, '/mestre/ativar-por-campanha', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'ativar_por_campanha'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        // Desvincular campanha ativa (limpa usermeta + marca campanha inativa)
        register_rest_route($ns, '/desvincular-campanha', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'desvincular_campanha'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));

        // Preferência de layout do Mestre (A/B/C/D) — persiste no usermeta
        register_rest_route($ns, '/layout-preferencia', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'salvar_layout'), 'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));

        // DLCs — delega para o Registry
        register_rest_route($ns, '/dlcs', array(
            array('methods'=>'GET','callback'=>array('DNDM_DLC_Registry','endpoint_listar'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        register_rest_route($ns, '/mestre/enviar-imagem', array(
            array('methods'=>'POST','callback'=>array(__CLASS__,'enviar_imagem_jogadores'),'permission_callback'=>array(__CLASS__,'check_mestre')),
        ));
        register_rest_route($ns, '/sessao/imagem-jogador', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'get_imagem_jogador'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        register_rest_route($ns, '/sessao/status', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'get_sessao_status'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
        // Endpoint consolidado: status + imagem em uma única requisição (para polling eficiente)
        register_rest_route($ns, '/sessao/poll', array(
            array('methods'=>'GET','callback'=>array(__CLASS__,'get_sessao_poll'),'permission_callback'=>array(__CLASS__,'check_login')),
        ));
    }

    // ── PERMISSIONS ─────────────────────────────────────────────────────────
    public static function check_login()  { return is_user_logged_in(); }
    public static function check_mestre() { return is_user_logged_in() && DNDM_Auth::is_mestre(); }
    public static function check_admin()  { return current_user_can('manage_options'); }

    // ── LP PÚBLICA ───────────────────────────────────────────────────────────
    public static function get_modulos_lp_public() {
        return rest_ensure_response( DNDM_LP_Editor::get_modulos_lp() );
    }

    // ── USUÁRIO ──────────────────────────────────────────────────────────────
    public static function get_usuario() {
        if ( ! is_user_logged_in() ) return rest_ensure_response(null);
        global $wpdb;
        $wp  = wp_get_current_user();
        $dnd = DNDM_Auth::get_usuario_dnd();

        // Campanha ativa e preferência de layout — ambos no usermeta
        $campanha_ativa    = (int) get_user_meta( $wp->ID, 'dndm_campanha_ativa', true );
        $layout_preferencia = get_user_meta( $wp->ID, 'dndm_layout_preferencia', true ) ?: 'B';

        // Personagem ativo do usuário (jogador) — respeita seleção do usuário
        $personagem = null;
        if ( $dnd ) {
            $ativo_id = (int) get_user_meta( $wp->ID, 'dndm_personagem_ativo', true );
            $where_extra = $ativo_id ? $wpdb->prepare( ' AND id = %d', $ativo_id ) : '';
            $p = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dnd_personagens
                 WHERE usuario_id = %d AND status = 'ativo'" . $where_extra . "
                 ORDER BY criado_em ASC LIMIT 1",
                $dnd->id
            ));
            if ( $p ) {
                $personagem = array(
                    'id'        => (int) $p->id,
                    'nome'      => $p->nome,
                    'classe'    => $p->classe,
                    'raca'      => $p->raca,
                    'nivel'     => (int) $p->nivel,
                    'hp_atual'  => (int) $p->hp_atual,
                    'hp_max'    => (int) $p->hp_max,
                    'ca'        => (int) $p->ca,
                    'xp'        => (int) $p->xp,
                    'imagem'    => $p->imagem_url,
                    'atributos' => json_decode( $p->atributos, true ) ?: array(),
                    'aparencia' => json_decode( $p->aparencia, true ) ?: array(),
                    'backstory' => $p->backstory,
                );
            }
        }

        $tier = DNDM_Auth::get_tier( $wp->ID );
        $badge_count = class_exists('DNDM_Achievements')
            ? DNDM_Achievements::count_conquistas( $wp->ID )
            : 0;
        return rest_ensure_response(array(
            'id'                 => $dnd->id ?? 0,
            'wp_id'              => $wp->ID,
            'nome'               => $wp->display_name,
            'email'              => $wp->user_email,
            'tier'               => $tier,
            'isAdmin'            => $tier === 'admin',
            'isMestre'           => DNDM_Auth::is_mestre(),
            'canMestrar'         => DNDM_Auth::can_mestrar( $wp->ID ),
            'canModulos'         => DNDM_Auth::can_gerenciar_modulos( $wp->ID ),
            'campanha_ativa'     => $campanha_ativa ?: null,
            'layout_preferencia' => $layout_preferencia,
            'saudacao'           => DNDM_Auth::get_saudacao( $wp->ID, $personagem['classe'] ?? '' ),
            'personagem'         => $personagem,
            'badge_count'        => $badge_count,
        ));
    }

    public static function fazer_logout() {
        wp_logout();
        return rest_ensure_response( array('sucesso' => true, 'redirect' => home_url('/')) );
    }

    public static function salvar_layout( WP_REST_Request $r ) {
        $layout = sanitize_text_field( $r->get_json_params()['layout'] ?? 'B' );
        if ( ! in_array( $layout, array('A','B','C','D') ) ) $layout = 'B';
        update_user_meta( get_current_user_id(), 'dndm_layout_preferencia', $layout );
        return rest_ensure_response(array('sucesso'=>true,'layout'=>$layout));
    }

    // ── JOGADORAS ────────────────────────────────────────────────────────────
    public static function listar_jogadores() {
        return rest_ensure_response( DNDM_Auth::listar_jogadores() );
    }

    public static function criar_jogador( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        $res = DNDM_Auth::criar_jogador(
            $d['nome']  ?? '',
            $d['email'] ?? '',
            $d['senha'] ?? ''
        );
        if ( is_wp_error($res) ) return new WP_REST_Response(array('sucesso'=>false,'erro'=>$res->get_error_message()), 400);
        return rest_ensure_response(array('sucesso'=>true,'senha_gerada'=>$res['senha_gerada']));
    }

    // ── MÓDULOS ──────────────────────────────────────────────────────────────
    public static function listar_modulos() {
        global $wpdb;
        $modulos = $wpdb->get_results(
            "SELECT m.*, 
             (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_checklist WHERE modulo_id=m.id) as total_cenas,
             (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id=m.id) as total_npcs
             FROM {$wpdb->prefix}dnd_modulos m ORDER BY m.criado_em DESC"
        );
        return rest_ensure_response($modulos);
    }

    // Módulo único com chapters[] parseados do JSON salvo em conteudo
    public static function get_modulo( WP_REST_Request $r ) {
        global $wpdb;
        $id     = intval( $r->get_param('id') );
        $modulo = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_modulos WHERE id = %d", $id
        ));
        if ( ! $modulo ) {
            return new WP_Error( 'nao_encontrado', 'Módulo não encontrado', array('status' => 404) );
        }

        $conteudo = json_decode( $modulo->conteudo, true );
        $chapters = array();
        if ( is_array($conteudo) && ! empty($conteudo['chapters']) ) {
            foreach ( $conteudo['chapters'] as $i => $cap ) {
                $chapters[] = array(
                    'id'          => isset($cap['id']) ? $cap['id'] : ($i + 1),
                    'title'       => isset($cap['title']) ? $cap['title'] : (isset($cap['titulo']) ? $cap['titulo'] : 'Capítulo ' . ($i + 1)),
                    'sub'         => isset($cap['sub']) ? $cap['sub'] : (isset($cap['subtitulo']) ? $cap['subtitulo'] : ''),
                    'icon'        => isset($cap['icon']) ? $cap['icon'] : (isset($cap['icone']) ? $cap['icone'] : '📖'),
                    'content'     => isset($cap['content']) ? $cap['content'] : (isset($cap['conteudo']) ? $cap['conteudo'] : ''),
                    'blocks'      => isset($cap['blocks']) ? $cap['blocks'] : array(),
                    'image_prompt'=> isset($cap['image_prompt']) ? $cap['image_prompt'] : '',
                    'imagem_url'  => isset($cap['imagem_url']) ? $cap['imagem_url'] : '',
                );
            }
        }

        $npcs = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, nome, raca, papel, personalidade, ganchos, segredo, imagem_url FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id = %d", $id
        ));

        // Expõe monsters e loot direto do JSON do módulo
        $monsters = $conteudo['monsters'] ?? $conteudo['monstros'] ?? array();
        $loot     = $conteudo['loot']     ?? $conteudo['tesouros'] ?? array();

        return rest_ensure_response( array(
            'id'         => (int) $modulo->id,
            'nome'       => $modulo->nome,
            'descricao'  => $modulo->descricao,
            'sistema'    => $modulo->sistema,
            'synopsis'   => isset($conteudo['synopsis']) ? $conteudo['synopsis'] : $modulo->descricao,
            'chapters'   => $chapters,
            'npcs'       => $npcs,
            'monsters'   => $monsters,
            'loot'       => $loot,
            // objectives: suporta tanto "objectives" quanto "checklist" no JSON do módulo
            'objectives' => array_map( function($obj) {
                return array(
                    'titulo'   => $obj['titulo']   ?? $obj['title']       ?? '',
                    'descricao'=> $obj['descricao'] ?? $obj['description'] ?? '',
                    'tipo'     => $obj['tipo']      ?? $obj['type']        ?? 'secundaria',
                );
            }, $conteudo['objectives'] ?? $conteudo['checklist'] ?? array() ),
        ));
    }

    public static function excluir_modulo( WP_REST_Request $r ) {
        global $wpdb;
        $id = intval( $r->get_param('id') );

        // Proteção: bloqueia se houver campanha ativa usando este módulo
        $ativas = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_campanhas
             WHERE modulo_id = %d AND status = 'ativa'",
            $id
        ));
        if ( $ativas > 0 ) {
            return new WP_REST_Response( array(
                'sucesso' => false,
                'erro'    => 'Este módulo está vinculado a uma campanha ativa. Desvincule antes de excluir.',
            ), 400 );
        }

        // Remove dados filhos e o módulo
        $wpdb->delete( $wpdb->prefix . 'dnd_checklist', array( 'modulo_id' => $id ) );
        $wpdb->delete( $wpdb->prefix . 'dnd_npcs',      array( 'modulo_id' => $id ) );
        $wpdb->delete( $wpdb->prefix . 'dnd_modulos',   array( 'id'        => $id ) );

        return rest_ensure_response( array( 'sucesso' => true ) );
    }

    // ── PDF DO MÓDULO ────────────────────────────────────────────────────────

    public static function upload_pdf_modulo( WP_REST_Request $r ) {
        $modulo_id = intval( $r->get_param('id') );
        $files     = $r->get_file_params();
        $file      = $files['pdf'] ?? null;

        if ( ! $file || empty($file['tmp_name']) ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Nenhum arquivo enviado.'), 400 );
        }
        if ( $file['type'] !== 'application/pdf' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'pdf' ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Apenas arquivos PDF são aceitos.'), 400 );
        }
        if ( $file['size'] > 50 * 1024 * 1024 ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'PDF muito grande (máx 50MB).'), 400 );
        }

        $dir = DNDM_UPLOAD_DIR . '/pdfs';
        wp_mkdir_p( $dir );

        $filename = 'modulo-' . $modulo_id . '-' . sanitize_file_name( $file['name'] );
        $destino  = $dir . '/' . $filename;

        // Remove PDF anterior do mesmo módulo
        $pdf_anterior = get_option( 'dndm_pdf_modulo_' . $modulo_id, '' );
        if ( $pdf_anterior && file_exists( DNDM_UPLOAD_DIR . '/pdfs/' . basename($pdf_anterior) ) ) {
            @unlink( DNDM_UPLOAD_DIR . '/pdfs/' . basename($pdf_anterior) );
        }

        if ( ! move_uploaded_file( $file['tmp_name'], $destino ) ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Falha ao salvar o arquivo.'), 500 );
        }

        $url = DNDM_UPLOAD_URL . '/pdfs/' . $filename;
        update_option( 'dndm_pdf_modulo_' . $modulo_id, $url );

        return rest_ensure_response( array('sucesso'=>true, 'pdf_url'=>$url, 'nome'=>$file['name']) );
    }

    public static function get_pdf_modulo( WP_REST_Request $r ) {
        $modulo_id = intval( $r->get_param('id') );
        $url = get_option( 'dndm_pdf_modulo_' . $modulo_id, '' );
        return rest_ensure_response( array('pdf_url'=>$url ?: null) );
    }

    public static function delete_pdf_modulo( WP_REST_Request $r ) {
        $modulo_id = intval( $r->get_param('id') );
        $url = get_option( 'dndm_pdf_modulo_' . $modulo_id, '' );
        if ( $url ) {
            @unlink( DNDM_UPLOAD_DIR . '/pdfs/' . basename($url) );
            delete_option( 'dndm_pdf_modulo_' . $modulo_id );
        }
        return rest_ensure_response( array('sucesso'=>true) );
    }

    public static function gerar_imagem_capitulo( WP_REST_Request $r ) {
        $d         = $r->get_json_params();
        $prompt    = sanitize_textarea_field( $d['image_prompt'] ?? '' );
        $modulo_id = intval( $d['modulo_id'] ?? 0 );
        $cap_index = intval( $d['cap_index']  ?? 0 );

        if ( ! $prompt ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => 'image_prompt ausente' ), 400 );
        }
        if ( ! class_exists('DNDM_Imagem') ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => 'Módulo de imagem indisponível' ), 500 );
        }

        $url = DNDM_Imagem::gerar_imagem_cena( $prompt );
        if ( ! $url ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => 'Falha ao gerar imagem (Pollinations indisponível?)' ), 500 );
        }

        // Persiste a URL no JSON do módulo para não precisar gerar de novo
        if ( $modulo_id ) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT conteudo FROM {$wpdb->prefix}dnd_modulos WHERE id = %d", $modulo_id
            ));
            if ( $row ) {
                $conteudo = json_decode( $row->conteudo, true );
                if ( isset( $conteudo['chapters'][ $cap_index ] ) ) {
                    $conteudo['chapters'][ $cap_index ]['imagem_url'] = $url;
                    $wpdb->update(
                        $wpdb->prefix . 'dnd_modulos',
                        array( 'conteudo' => wp_json_encode( $conteudo, JSON_UNESCAPED_UNICODE ) ),
                        array( 'id' => $modulo_id )
                    );
                }
            }
        }

        return rest_ensure_response( array( 'sucesso' => true, 'imagem_url' => $url ) );
    }

    public static function upload_imagem_capitulo( WP_REST_Request $r ) {
        $modulo_id = intval( $r->get_param('modulo_id') ?? 0 );
        $cap_index = intval( $r->get_param('cap_index')  ?? 0 );

        if ( empty( $_FILES['imagem'] ) ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => 'Nenhum arquivo enviado.' ), 400 );
        }

        // Usa a API de mídia do WordPress
        if ( ! function_exists('wp_handle_upload') ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists('wp_generate_attachment_metadata') ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        if ( ! function_exists('media_handle_upload') ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $attachment_id = media_handle_upload( 'imagem', 0 );
        if ( is_wp_error( $attachment_id ) ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => $attachment_id->get_error_message() ), 500 );
        }

        $url = wp_get_attachment_url( $attachment_id );

        // Persiste no JSON do módulo
        if ( $modulo_id && $url ) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT conteudo FROM {$wpdb->prefix}dnd_modulos WHERE id = %d", $modulo_id
            ));
            if ( $row ) {
                $conteudo = json_decode( $row->conteudo, true );
                if ( isset( $conteudo['chapters'][ $cap_index ] ) ) {
                    $conteudo['chapters'][ $cap_index ]['imagem_url'] = $url;
                    $wpdb->update(
                        $wpdb->prefix . 'dnd_modulos',
                        array( 'conteudo' => wp_json_encode( $conteudo, JSON_UNESCAPED_UNICODE ) ),
                        array( 'id' => $modulo_id )
                    );
                }
            }
        }

        return rest_ensure_response( array( 'sucesso' => true, 'imagem_url' => $url ) );
    }

    public static function importar_modulo( WP_REST_Request $r ) {
        $d    = $r->get_json_params();
        $json = $d['json'] ?? array();
        $nome = sanitize_text_field($d['nome'] ?? ($json['nome'] ?? 'Módulo sem nome'));

        if ( empty($json) ) return new WP_REST_Response(array('sucesso'=>false,'erro'=>'JSON vazio'), 400);

        $modulo_id = DNDM_Campanha::importar_modulo_json($json, $nome);
        if ( is_wp_error($modulo_id) ) return new WP_REST_Response(array('sucesso'=>false,'erro'=>$modulo_id->get_error_message()), 500);

        // Conta imagens geradas
        global $wpdb;
        $imgs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id=%d AND imagem_url!=''", $modulo_id
        ));

        return rest_ensure_response(array('sucesso'=>true,'nome'=>$nome,'imagens_geradas'=>(int)$imgs));
    }

    // ── PERSONAGENS ──────────────────────────────────────────────────────────
    public static function gerar_lore_opcoes( WP_REST_Request $r ) {
        $d = $r->get_json_params();

        if ( ! class_exists('DNDM_Groq') ) {
            return new WP_REST_Response(array('erro' => 'Groq não disponível'), 500);
        }
        $groq_key = get_option('dndm_groq_key', '');
        if ( empty($groq_key) ) {
            return new WP_REST_Response(array('erro' => 'Chave Groq não configurada'), 500);
        }

        $nome      = sanitize_text_field($d['nome'] ?? 'Aventureiro');
        $raca      = sanitize_text_field($d['raca'] ?? '');
        $classe    = sanitize_text_field($d['classe'] ?? '');
        $antecedente = sanitize_text_field($d['antecedente'] ?? '');
        $alinhamento = sanitize_text_field($d['alinhamento'] ?? '');

        $system = "Você é um escritor criativo especialista em D&D 5e. Crie histórias de origem únicas, evocativas e imersivas em português brasileiro. Responda APENAS em JSON válido, sem markdown.";

        $user = "Gere 3 opções de história de origem (lore) distintas e criativas para este personagem de D&D 5e:

Nome: {$nome}
Raça: {$raca}
Classe: {$classe}
Antecedente: {$antecedente}
Alinhamento: {$alinhamento}

Cada opção deve ser um parágrafo único de 3-5 frases, escrito em primeira pessoa, revelando motivação, evento marcante e objetivo.
Faça cada opção ter um TOM diferente: uma mais sombria, uma mais heroica, uma mais misteriosa.

Responda APENAS em JSON:
{\"opcoes\": [\"opção 1...\", \"opção 2...\", \"opção 3...\"]}";

        $resultado = DNDM_Groq::chamar($system, $user, 800, 30);
        if (is_wp_error($resultado)) {
            return new WP_REST_Response(array('erro' => $resultado->get_error_message()), 500);
        }

        $json = json_decode($resultado, true);
        if (!$json || !isset($json['opcoes']) || !is_array($json['opcoes'])) {
            // fallback: tenta extrair como texto puro
            if (is_string($resultado) && strlen($resultado) > 20) {
                return rest_ensure_response(array('sucesso' => true, 'opcoes' => array($resultado)));
            }
            return new WP_REST_Response(array('erro' => 'Resposta inválida do Groq'), 500);
        }

        return rest_ensure_response(array('sucesso' => true, 'opcoes' => array_values($json['opcoes'])));
    }

    public static function criar_personagem( WP_REST_Request $r ) {
        global $wpdb;
        $d = $r->get_json_params();

        if ( empty($d) ) {
            return new WP_REST_Response( array('erro' => 'Payload vazio ou JSON inválido.'), 400 );
        }

        // Usa o 'lore' digitado pelo jogador como backstory base
        if ( !empty($d['lore']) && empty($d['backstory']) ) {
            $d['backstory'] = $d['lore'];
        }

        // 1. Gera backstory com Groq (timeout 25s — servidores lentos toleram)
        if ( class_exists('DNDM_Groq') ) {
            $groq_key = get_option('dndm_groq_key', '');
            if ( !empty($groq_key) ) {
                $backstory = DNDM_Groq::gerar_backstory($d, 25);
                if ( is_array($backstory) && !is_wp_error($backstory) ) {
                    $d = array_merge($d, $backstory);
                } else {
                    $erro_msg = is_wp_error($backstory) ? $backstory->get_error_message() : 'resposta inválida';
                    error_log('[DnD Master] Groq falhou: ' . $erro_msg);
                }
            } else {
                error_log('[DnD Master] Groq key não configurada — pulando backstory.');
            }
        }

        // 2. Gera imagem com Pollinations (timeout 90s)
        $imagem = '';
        if ( class_exists('DNDM_Imagem') ) {
            $imagem = DNDM_Imagem::gerar_retrato_personagem($d);
            if ( empty($imagem) ) {
                error_log('[DnD Master] Pollinations falhou — personagem será salvo sem imagem.');
            }
        }
        $d['imagem_url'] = $imagem;

        // 3. Salva no banco
        $id = DNDM_Personagem::criar($d);
        if ( is_wp_error($id) ) {
            error_log('[DnD Master] Erro criar personagem: ' . $id->get_error_message() . ' | DB: ' . $wpdb->last_error);
            return new WP_REST_Response( array(
                'erro'    => $id->get_error_message(),
                'db_erro' => $wpdb->last_error,
            ), 500 );
        }

        // Dispara gatilhos de conquista
        do_action( 'dndm_personagem_criado', $id, get_current_user_id(), $d['classe'] ?? '' );

        return rest_ensure_response( array('sucesso'=>true, 'personagem_id'=>$id, 'imagem_url'=>$imagem) );
    }

    public static function get_personagem( WP_REST_Request $r ) {
        $ficha = DNDM_Personagem::get_ficha_completa(intval($r->get_param('id')));
        if (!$ficha) return new WP_Error('nao_encontrado','Personagem não encontrado',array('status'=>404));
        return rest_ensure_response($ficha);
    }

    /**
     * GET /goblin/expressoes — compatibilidade: retorna o primeiro mestre ativo
     */
    public static function get_goblin_expressoes() {
        global $wpdb;
        $mestre = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE status='ativo' ORDER BY id ASC LIMIT 1"
        );
        if ( !$mestre ) {
            // Fallback: sem mestre cadastrado, retorna estrutura vazia
            return rest_ensure_response(array(
                'id'         => null,
                'nome'       => get_option('dndm_goblin_nome',   'Dockside Extortionist'),
                'titulo'     => get_option('dndm_goblin_titulo', 'Mestre das Fofocas'),
                'persona'    => '',
                'expressoes' => self::_get_expressoes_mestre(null),
            ));
        }
        return rest_ensure_response(array(
            'id'         => (int)$mestre->id,
            'nome'       => $mestre->nome,
            'titulo'     => $mestre->titulo,
            'persona'    => $mestre->persona,
            'expressoes' => self::_get_expressoes_mestre($mestre->id),
        ));
    }

    /**
     * GET /solo/mestre/{id} — carrega mestre específico
     */
    public static function solo_get_mestre( WP_REST_Request $r ) {
        global $wpdb;
        $id = intval($r->get_param('id'));
        $mestre = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE id=%d", $id
        ));
        if ( !$mestre ) return new WP_Error('nao_encontrado', 'Mestre não encontrado', array('status'=>404));
        return rest_ensure_response(array(
            'id'         => (int)$mestre->id,
            'nome'       => $mestre->nome,
            'titulo'     => $mestre->titulo,
            'persona'    => $mestre->persona,
            'expressoes' => self::_get_expressoes_mestre($mestre->id),
        ));
    }

    /** Retorna URLs das expressões de um mestre (por ID) ou do legado (por wp_option) */
    private static function _get_expressoes_mestre( $mestre_id ) {
        $emocoes = array('neutro','entusiasmado','suspense','assustado','debochado','satisfeito','comemorando');
        $expressoes = array();
        foreach ( $emocoes as $em ) {
            if ( $mestre_id ) {
                $expressoes[$em] = get_option('dndm_mestre_'.$mestre_id.'_'.$em, null) ?: null;
            } else {
                // Fallback legado (wp_options sem mestre_id)
                $expressoes[$em] = get_option('dndm_goblin_'.$em, null) ?: null;
            }
        }
        return $expressoes;
    }

    /**
     * GET /personagem/{id}/habilidades
     * Retorna habilidades desbloqueadas até o nível atual do personagem.
     */
    public static function get_habilidades_personagem( WP_REST_Request $r ) {
        global $wpdb;
        $id = intval( $r->get_param('id') );
        $p  = $wpdb->get_row( $wpdb->prepare(
            "SELECT classe, nivel FROM {$wpdb->prefix}dnd_personagens WHERE id=%d", $id
        ));
        if ( ! $p ) return new WP_Error('nao_encontrado','Personagem não encontrado',array('status'=>404));

        $habilidades = DNDM_Personagem::get_habilidades_classe( $p->classe, (int) $p->nivel );
        return rest_ensure_response( array( 'sucesso' => true, 'habilidades' => $habilidades ) );
    }

    public static function meus_personagens() {
        global $wpdb;
        $usuario = DNDM_Auth::get_usuario_dnd();
        $ativo_id = (int) get_user_meta( get_current_user_id(), 'dndm_personagem_ativo', true );
        $lista = $wpdb->get_results($wpdb->prepare(
            "SELECT id,nome,raca,classe,nivel,hp_atual,hp_max,imagem_url FROM {$wpdb->prefix}dnd_personagens
             WHERE usuario_id=%d AND status='ativo' ORDER BY criado_em ASC LIMIT 5", $usuario->id
        ));
        $badge_count = class_exists('DNDM_Achievements')
            ? DNDM_Achievements::count_conquistas( get_current_user_id() )
            : 0;
        $result = array();
        foreach ($lista as $i => $p) {
            $p->ativo = $ativo_id ? ( $ativo_id === (int)$p->id ) : ( $i === 0 );
            $result[] = $p;
        }
        return rest_ensure_response(array('personagens' => $result, 'badge_count' => $badge_count));
    }

    public static function get_achievements() {
        if ( ! class_exists('DNDM_Achievements') ) return rest_ensure_response(array('catalogo'=>array(),'conquistadas'=>array()));
        $wp_user_id = get_current_user_id();
        $catalogo   = DNDM_Achievements::catalogo();
        $conquistadas = DNDM_Achievements::get_conquistas( $wp_user_id );
        $mapa = array();
        foreach ($conquistadas as $c) {
            $mapa[$c->badge_slug] = array(
                'conquistado_em' => $c->conquistado_em,
                'aventura_nome'  => $c->aventura_nome,
                'char_id'        => $c->char_id,
            );
        }
        return rest_ensure_response(array(
            'catalogo'    => $catalogo,
            'conquistadas' => $mapa,
            'total'        => count($mapa),
        ));
    }

    // Usado pelo polling — retorna só o total e as últimas conquistas (leve)
    public static function poll_achievements() {
        if ( ! class_exists('DNDM_Achievements') ) return rest_ensure_response(array('total'=>0,'recentes'=>array()));
        $wp_user_id  = get_current_user_id();
        $since       = sanitize_text_field($_GET['since'] ?? '');
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT badge_slug, conquistado_em, aventura_nome
             FROM {$wpdb->prefix}dnd_achievements
             WHERE user_id=%d", $wp_user_id
        );
        if ($since) $query .= $wpdb->prepare(" AND conquistado_em > %s", $since);
        $query .= " ORDER BY conquistado_em DESC LIMIT 10";
        $recentes = $wpdb->get_results($query);
        return rest_ensure_response(array(
            'total'   => DNDM_Achievements::count_conquistas($wp_user_id),
            'recentes' => $recentes,
        ));
    }

    public static function ativar_personagem( WP_REST_Request $r ) {
        global $wpdb;
        $usuario = DNDM_Auth::get_usuario_dnd();
        $id = (int) $r->get_param('id');
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_personagens WHERE id=%d AND usuario_id=%d AND status='ativo'",
            $id, $usuario->id
        ));
        if (!$existe) return new WP_Error('nao_encontrado', 'Personagem não encontrado', array('status'=>404));
        update_user_meta( get_current_user_id(), 'dndm_personagem_ativo', $id );
        return rest_ensure_response(array('sucesso'=>true, 'personagem_id'=>$id));
    }

    // ── INVENTÁRIO ────────────────────────────────────────────────────────────
    public static function get_inventario( WP_REST_Request $r ) {
        global $wpdb;
        $usuario = DNDM_Auth::get_usuario_dnd();
        $pid = (int) $r->get_param('personagem_id');
        $dono = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_personagens WHERE id=%d AND usuario_id=%d", $pid, $usuario->id
        ));
        if (!$dono && !DNDM_Auth::is_mestre()) return new WP_Error('proibido','Sem permissão',array('status'=>403));
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_inventario WHERE personagem_id=%d ORDER BY tipo, nome", $pid
        ));
        return rest_ensure_response($items ?: array());
    }

    public static function add_inventario( WP_REST_Request $r ) {
        global $wpdb;
        $pid = (int) $r->get_param('personagem_id');
        $d   = $r->get_json_params();
        if (empty($d['nome'])) return new WP_Error('invalido','Nome é obrigatório',array('status'=>400));
        $wpdb->insert($wpdb->prefix.'dnd_inventario', array(
            'personagem_id' => $pid,
            'nome'          => sanitize_text_field($d['nome']),
            'descricao'     => sanitize_textarea_field($d['descricao'] ?? ''),
            'tipo'          => sanitize_text_field($d['tipo'] ?? 'misc'),
            'quantidade'    => max(1, intval($d['quantidade'] ?? 1)),
            'peso'          => floatval($d['peso'] ?? 0),
            'valor'         => sanitize_text_field($d['valor'] ?? ''),
            'equipado'      => 0,
        ));
        return rest_ensure_response(array('sucesso'=>true,'id'=>$wpdb->insert_id));
    }

    public static function del_inventario( WP_REST_Request $r ) {
        global $wpdb;
        $pid = (int) $r->get_param('personagem_id');
        $iid = (int) $r->get_param('item_id');
        $wpdb->delete($wpdb->prefix.'dnd_inventario', array('id'=>$iid,'personagem_id'=>$pid));
        return rest_ensure_response(array('sucesso'=>true));
    }

    public static function upd_inventario( WP_REST_Request $r ) {
        global $wpdb;
        $pid = (int) $r->get_param('personagem_id');
        $iid = (int) $r->get_param('item_id');
        $d   = $r->get_json_params();
        $up  = array();
        if (isset($d['equipado']))   $up['equipado']   = $d['equipado'] ? 1 : 0;
        if (isset($d['quantidade'])) $up['quantidade'] = max(1, intval($d['quantidade']));
        if (!empty($up)) $wpdb->update($wpdb->prefix.'dnd_inventario', $up, array('id'=>$iid,'personagem_id'=>$pid));
        return rest_ensure_response(array('sucesso'=>true));
    }

    // ── CONTROLE DE SESSÃO ───────────────────────────────────────────────────
    public static function aplicar_dano( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        $hp = DNDM_Personagem::aplicar_dano(intval($d['personagem_id']), intval($d['dano']));
        return rest_ensure_response(array('hp_atual'=>$hp));
    }
    public static function aplicar_cura( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        $hp = DNDM_Personagem::aplicar_cura(intval($d['personagem_id']), intval($d['cura']));
        return rest_ensure_response(array('hp_atual'=>$hp));
    }
    public static function add_condicao( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        DNDM_Personagem::adicionar_condicao(intval($d['personagem_id']), $d['tipo']);
        return rest_ensure_response(array('sucesso'=>true));
    }
    public static function rem_condicao( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        DNDM_Personagem::remover_condicao(intval($d['personagem_id']), $d['tipo']);
        return rest_ensure_response(array('sucesso'=>true));
    }
    public static function dar_item( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        if ( class_exists('DNDM_Imagem') ) $d['imagem_url'] = DNDM_Imagem::gerar_imagem_item($d['nome'], $d['descricao']??'');
        DNDM_Personagem::adicionar_item(intval($d['personagem_id']), $d);
        return rest_ensure_response(array('sucesso'=>true));
    }
    public static function dar_xp( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        $res = DNDM_Personagem::ganhar_xp(intval($d['personagem_id']), intval($d['xp']));
        return rest_ensure_response($res);
    }
    public static function painel_mestre( WP_REST_Request $r ) {
        return rest_ensure_response( DNDM_Campanha::get_painel_mestre(intval($r->get_param('campanha_id'))) );
    }
    public static function gerar_gancho( WP_REST_Request $r ) {
        $d = $r->get_json_params();
        $campanha = DNDM_Campanha::get_campanha($d['campanha_id']);
        $nomes = implode(', ', array_map(function($p){ return $p->nome; }, DNDM_Database::get_personagens_campanha($d['campanha_id'])));
        $res = DNDM_Groq::gerar_gancho($d['modelo'], array(
            'nome'               => $campanha->nome,
            'modulo_nome'        => $campanha->modulo_nome ?? 'Campanha personalizada',
            'personagens'        => $nomes,
            'situacao'           => $d['situacao'] ?? '',
            'objetivo_ignorado'  => $d['objetivo'] ?? '',
        ));
        if (is_wp_error($res)) return new WP_REST_Response(array('erro'=>$res->get_error_message()),500);
        return rest_ensure_response($res);
    }
    public static function gerar_npc( WP_REST_Request $r ) {
        $d   = $r->get_json_params();
        $npc = DNDM_Groq::gerar_npc($d);
        if (is_wp_error($npc)) return new WP_REST_Response(array('erro'=>$npc->get_error_message()),500);
        if (class_exists('DNDM_Imagem')) $npc['imagem_url'] = DNDM_Imagem::gerar_imagem_npc($npc);
        return rest_ensure_response($npc);
    }
    public static function concluir_checklist( WP_REST_Request $r ) {
        $id    = intval( $r->get_param('id') );
        $d     = $r->get_json_params();
        // Se o JS passar "concluida": true/false, usa esse valor.
        // Se não passar, faz toggle automático no banco.
        if ( isset( $d['concluida'] ) ) {
            $novo = $d['concluida'] ? 1 : 0;
        } else {
            global $wpdb;
            $atual = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT concluida FROM {$wpdb->prefix}dnd_checklist WHERE id = %d", $id
            ) );
            $novo = $atual ? 0 : 1;
        }
        DNDM_Campanha::set_checklist( $id, $novo );
        return rest_ensure_response( array( 'sucesso' => true, 'concluida' => (bool) $novo ) );
    }

    // ── MAPA DE BATALHA ──────────────────────────────────────────────────────
    public static function get_mapa( WP_REST_Request $r ) {
        $camp_id = intval($r->get_param('campanha_id'));
        $mapa_url = get_post_meta($camp_id, '_dndm_mapa_url', true);
        return rest_ensure_response(array('mapa_url' => $mapa_url ?: null));
    }

    public static function upload_mapa( WP_REST_Request $r ) {
        if (!function_exists('wp_handle_upload')) require_once ABSPATH . 'wp-admin/includes/file.php';
        if (!function_exists('wp_generate_attachment_metadata')) require_once ABSPATH . 'wp-admin/includes/image.php';
        if (!function_exists('wp_insert_attachment')) require_once ABSPATH . 'wp-admin/includes/media.php';

        $camp_id = intval($r->get_param('campanha_id'));
        $files   = $r->get_file_params();

        if (empty($files['mapa'])) {
            return new WP_Error('sem_arquivo', 'Nenhum arquivo enviado.', array('status' => 400));
        }

        $upload = wp_handle_upload($files['mapa'], array('test_form' => false));
        if (isset($upload['error'])) {
            return new WP_Error('upload_falhou', $upload['error'], array('status' => 500));
        }

        update_post_meta($camp_id, '_dndm_mapa_url', esc_url_raw($upload['url']));
        return rest_ensure_response(array('sucesso' => true, 'mapa_url' => $upload['url']));
    }

    public static function get_tokens( WP_REST_Request $r ) {
        $camp_id = intval($r->get_param('campanha_id'));
        $tokens  = get_post_meta($camp_id, '_dndm_tokens', true);
        return rest_ensure_response(array('tokens' => $tokens ? json_decode($tokens, true) : array()));
    }

    public static function save_tokens( WP_REST_Request $r ) {
        $camp_id = intval($r->get_param('campanha_id'));
        $tokens  = $r->get_param('tokens');
        if (!is_array($tokens)) {
            return new WP_Error('invalido', 'tokens deve ser um array.', array('status' => 400));
        }
        update_post_meta($camp_id, '_dndm_tokens', wp_json_encode($tokens));
        return rest_ensure_response(array('sucesso' => true));
    }

    // ── CAMPANHAS ────────────────────────────────────────────────────────────
    public static function listar_campanhas() {
        return rest_ensure_response( DNDM_Campanha::get_campanhas_mestre() );
    }

    public static function criar_campanha( WP_REST_Request $r ) {
        $id = DNDM_Campanha::criar($r->get_json_params());
        if (is_wp_error($id)) return new WP_REST_Response(array('erro'=>$id->get_error_message()),500);
        return rest_ensure_response(array('sucesso'=>true,'campanha_id'=>$id));
    }

    /**
     * DELETE /campanha/{id}
     * Exclui uma campanha do mestre (limpa usermeta, inscrições e registro).
     */
    public static function excluir_campanha_individual( WP_REST_Request $r ) {
        global $wpdb;
        $id         = intval( $r->get_param('id') );
        $mestre_dnd = DNDM_Auth::get_usuario_dnd();
        $camp = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_campanhas WHERE id=%d AND mestre_id=%d",
            $id, $mestre_dnd->id
        ));
        if ( ! $camp ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Campanha não encontrada'), 404 );
        }
        $wpdb->delete( $wpdb->prefix.'usermeta',      array('meta_key'=>'dndm_campanha_ativa','meta_value'=>$id) );
        $wpdb->delete( $wpdb->prefix.'dnd_inscricoes', array('campanha_id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'dnd_campanhas',  array('id'=>$id) );
        return rest_ensure_response( array('sucesso'=>true) );
    }

    /**
     * POST /mestre/ativar-por-campanha
     * Body: { "campanha_id": 3 }
     * Persiste campanha_ativa via campanha_id (usado ao entrar como Mestre pela tela de campanhas abertas).
     */
    public static function ativar_por_campanha( WP_REST_Request $r ) {
        global $wpdb;

        $d           = $r->get_json_params();
        $campanha_id = intval( $d['campanha_id'] ?? 0 );

        if ( ! $campanha_id ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => 'campanha_id ausente' ), 400 );
        }

        $mestre_dnd = DNDM_Auth::get_usuario_dnd();
        $campanha   = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_campanhas WHERE id = %d AND mestre_id = %d",
            $campanha_id, $mestre_dnd->id
        ) );

        if ( ! $campanha ) {
            return new WP_REST_Response( array( 'sucesso' => false, 'erro' => 'Campanha não encontrada ou sem permissão' ), 404 );
        }

        update_user_meta( get_current_user_id(), 'dndm_campanha_ativa', $campanha_id );

        return rest_ensure_response( array(
            'sucesso'     => true,
            'campanha_id' => $campanha_id,
        ) );
    }

    /**
     * POST /ativar-campanha
     * Body: { "modulo_id": 5 }
     *
     * Fluxo:
     *  1. Valida que o módulo existe e pertence a este Mestre (via tabela dnd_modulos).
     *  2. Cria ou reutiliza uma campanha vinculada ao módulo.
     *  3. Persiste campanha_id em usermeta do WP do Mestre.
     *  4. Retorna os dados completos da campanha para o React redirecionar.
     */
    public static function ativar_campanha( WP_REST_Request $r ) {
        global $wpdb;

        $d         = $r->get_json_params();
        $modulo_id = intval( $d['modulo_id'] ?? 0 );

        if ( ! $modulo_id ) {
            return new WP_REST_Response(
                array('sucesso' => false, 'erro' => 'modulo_id ausente ou inválido'),
                400
            );
        }

        // 1. Confirma que o módulo existe
        $modulo = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}dnd_modulos WHERE id = %d", $modulo_id)
        );
        if ( ! $modulo ) {
            return new WP_REST_Response(
                array('sucesso' => false, 'erro' => 'Módulo não encontrado'),
                404
            );
        }

        $mestre_wp_id = get_current_user_id();
        $mestre_dnd   = DNDM_Auth::get_usuario_dnd();

        // 2. Busca campanha já existente para este módulo e mestre, ou cria uma nova
        $campanha_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}dnd_campanhas
                 WHERE modulo_id = %d AND mestre_id = %d AND status = 'ativa'
                 ORDER BY criado_em DESC LIMIT 1",
                $modulo_id,
                $mestre_dnd->id
            )
        );

        if ( ! $campanha_id ) {
            $wpdb->insert(
                $wpdb->prefix . 'dnd_campanhas',
                array(
                    'nome'      => sanitize_text_field( $modulo->nome ),
                    'modulo_id' => $modulo_id,
                    'mestre_id' => $mestre_dnd->id,
                    'status'    => 'ativa',
                )
            );
            $campanha_id = $wpdb->insert_id;

            if ( ! $campanha_id ) {
                return new WP_REST_Response(
                    array('sucesso' => false, 'erro' => 'Erro ao criar campanha: ' . $wpdb->last_error),
                    500
                );
            }
        }

        // 3. Persiste no usermeta — é o que get_usuario() lê na próxima requisição
        update_user_meta( $mestre_wp_id, 'dndm_campanha_ativa', $campanha_id );

        // 4. Retorna dados completos para o React
        $campanha = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT c.*, m.nome as modulo_nome, m.sistema, m.descricao as modulo_descricao
                 FROM {$wpdb->prefix}dnd_campanhas c
                 LEFT JOIN {$wpdb->prefix}dnd_modulos m ON c.modulo_id = m.id
                 WHERE c.id = %d",
                $campanha_id
            )
        );

        $checklist_total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_checklist WHERE modulo_id = %d",
                $modulo_id
            )
        );

        $npcs_total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id = %d",
                $modulo_id
            )
        );

        return rest_ensure_response(array(
            'sucesso'         => true,
            'campanha_id'     => (int) $campanha_id,
            'campanha_nome'   => $campanha->nome,
            'modulo_nome'     => $campanha->modulo_nome,
            'modulo_sistema'  => $campanha->sistema,
            'checklist_total' => $checklist_total,
            'npcs_total'      => $npcs_total,
            'redirect'        => home_url('/dnd-mestre'),
        ));
    }

    /**
     * POST /desvincular-campanha
     * Limpa o usermeta dndm_campanha_ativa E marca a campanha como inativa,
     * liberando o módulo para ser excluído.
     */
    public static function desvincular_campanha() {
        global $wpdb;
        $wp_id = get_current_user_id();

        // Pega a campanha ativa antes de limpar
        $campanha_id = (int) get_user_meta( $wp_id, 'dndm_campanha_ativa', true );

        // Marca campanha como inativa no banco
        if ( $campanha_id ) {
            $wpdb->update(
                $wpdb->prefix . 'dnd_campanhas',
                array( 'status' => 'inativa' ),
                array( 'id' => $campanha_id )
            );
        }

        // Limpa o usermeta
        delete_user_meta( $wp_id, 'dndm_campanha_ativa' );

        return rest_ensure_response( array(
            'sucesso'  => true,
            'redirect' => home_url('/dnd-mestre'),
        ));
    }
    // ── CAMPANHAS ABERTAS ────────────────────────────────────────────────────
    public static function campanhas_abertas() {
        global $wpdb;
        $dnd = DNDM_Auth::get_usuario_dnd();

        $rows = $wpdb->get_results(
            "SELECT c.*, m.nome AS modulo_nome, m.descricao AS modulo_desc, m.sistema
             FROM {$wpdb->prefix}dnd_campanhas c
             LEFT JOIN {$wpdb->prefix}dnd_modulos m ON c.modulo_id = m.id
             WHERE c.status IN ('aberta','ativa','em_andamento')
             ORDER BY c.criado_em DESC"
        );

        $resultado = array();
        foreach ($rows as $c) {
            $is_mestre = $dnd && ( (int)$c->mestre_id === (int)$dnd->id );

            // Status de inscrição deste usuário
            $inscricao = null;
            if ($dnd && !$is_mestre) {
                $insc = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}dnd_inscricoes WHERE campanha_id=%d AND usuario_id=%d",
                    $c->id, $dnd->id
                ));
                if ($insc) $inscricao = $insc->status;
            }
            if ($is_mestre) $inscricao = 'mestre';

            // Capa do módulo
            $capa = '';
            if ($c->modulo_id) {
                $mapas = get_option('dndm_mapas_modulo_' . $c->modulo_id, array());
                if (!empty($mapas[0]['url'])) $capa = $mapas[0]['url'];
            }
            $resultado[] = array(
                'id'           => (int) $c->id,
                'nome'         => $c->nome,
                'modulo_nome'  => $c->modulo_nome,
                'modulo_desc'  => $c->modulo_desc,
                'sistema'      => $c->sistema ?? 'D&D 5E',
                'max_jogadores'=> (int) $c->max_jogadores,
                'status'       => $c->status,
                'inscricao'    => $inscricao,
                'capa_url'     => $capa,
                'is_mestre'    => $is_mestre,
                'mestre_id'    => (int) $c->mestre_id,
            );
        }
        return rest_ensure_response($resultado);
    }

    public static function abrir_campanha(WP_REST_Request $r) {
        global $wpdb;
        $id = (int) $r->get_param('id');
        $wpdb->update($wpdb->prefix.'dnd_campanhas', array('status'=>'aberta'), array('id'=>$id));
        return rest_ensure_response(array('sucesso'=>true));
    }

    public static function iniciar_campanha(WP_REST_Request $r) {
        global $wpdb;
        $id = (int) $r->get_param('id');
        $wpdb->update($wpdb->prefix.'dnd_campanhas', array('status'=>'em_andamento'), array('id'=>$id));
        // Ativa campanha para o mestre
        update_user_meta(get_current_user_id(), 'dndm_campanha_ativa', $id);
        // Reseta checklist — sempre começa nova sessão com itens desmarcados
        $campanha = $wpdb->get_row( $wpdb->prepare(
            "SELECT modulo_id FROM {$wpdb->prefix}dnd_campanhas WHERE id = %d", $id
        ) );
        if ( $campanha && $campanha->modulo_id ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}dnd_checklist
                 SET concluida = 0, concluida_em = NULL
                 WHERE (campanha_id = %d OR modulo_id = %d)",
                $id, $campanha->modulo_id
            ) );
        }
        return rest_ensure_response(array('sucesso'=>true, 'campanha_id'=>$id));
    }

    // Mestre envia imagem para os jogadores da campanha ativa
    public static function enviar_imagem_jogadores(WP_REST_Request $r) {
        $campanha_id = (int) get_user_meta(get_current_user_id(), 'dndm_campanha_ativa', true);
        if (!$campanha_id) return new WP_Error('sem_campanha','Nenhuma campanha ativa',array('status'=>400));
        $url = sanitize_url($r->get_param('imagem_url'));
        if (!$url) return new WP_Error('sem_url','URL de imagem obrigatória',array('status'=>400));
        update_option('dndm_imagem_sessao_'.$campanha_id, array(
            'url' => $url,
            'ts'  => time(),
        ));
        return rest_ensure_response(array('sucesso'=>true));
    }

    // Jogador consulta imagem atual da sessão
    public static function get_imagem_jogador(WP_REST_Request $r) {
        $wp_user_id = get_current_user_id();
        $campanha_id = (int) get_user_meta($wp_user_id, 'dndm_campanha_ativa', true);
        if (!$campanha_id) {
            // Tenta via inscrição aprovada
            global $wpdb;
            $dnd = DNDM_Auth::get_usuario_dnd();
            if ($dnd) {
                $campanha_id = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT campanha_id FROM {$wpdb->prefix}dnd_inscricoes WHERE usuario_id=%d AND status='aprovado' ORDER BY id DESC LIMIT 1",
                    $dnd->id
                ));
            }
        }
        if (!$campanha_id) return rest_ensure_response(array('imagem_url'=>null,'ts'=>0));
        $data = get_option('dndm_imagem_sessao_'.$campanha_id, array('url'=>'','ts'=>0));
        return rest_ensure_response(array('imagem_url'=>$data['url']??'','ts'=>(int)($data['ts']??0)));
    }

    // Jogador consulta status da sessão para detectar início
    public static function get_sessao_status(WP_REST_Request $r) {
        global $wpdb;
        $dnd = DNDM_Auth::get_usuario_dnd();
        if (!$dnd) return rest_ensure_response(array('status'=>'sem_usuario'));
        $insc = $wpdb->get_row($wpdb->prepare(
            "SELECT i.status as insc_status, c.status as camp_status, c.id as camp_id, c.nome as camp_nome
             FROM {$wpdb->prefix}dnd_inscricoes i
             JOIN {$wpdb->prefix}dnd_campanhas c ON i.campanha_id = c.id
             WHERE i.usuario_id=%d AND i.status='aprovado'
             ORDER BY i.id DESC LIMIT 1",
            $dnd->id
        ));
        if (!$insc) return rest_ensure_response(array('status'=>'sem_inscricao'));
        return rest_ensure_response(array(
            'status'       => $insc->camp_status,
            'campanha_id'  => (int)$insc->camp_id,
            'campanha_nome'=> $insc->camp_nome,
        ));
    }

    /**
     * GET /sessao/poll
     * Endpoint consolidado para o jogador: retorna status da sessão + imagem atual
     * em uma única requisição, reduzindo pela metade o número de chamadas de polling.
     */
    public static function get_sessao_poll(WP_REST_Request $r) {
        global $wpdb;
        $dnd = DNDM_Auth::get_usuario_dnd();
        if (!$dnd) return rest_ensure_response(array('status'=>'sem_usuario','url'=>'','ts'=>0));

        // Busca inscrição aprovada mais recente
        $insc = $wpdb->get_row($wpdb->prepare(
            "SELECT i.status as insc_status, c.status as camp_status, c.id as camp_id, c.nome as camp_nome
             FROM {$wpdb->prefix}dnd_inscricoes i
             JOIN {$wpdb->prefix}dnd_campanhas c ON i.campanha_id = c.id
             WHERE i.usuario_id=%d AND i.status='aprovado'
             ORDER BY i.id DESC LIMIT 1",
            $dnd->id
        ));

        $camp_id = $insc ? (int)$insc->camp_id : 0;

        // Fallback: campanha ativa via usermeta
        if (!$camp_id) {
            $camp_id = (int) get_user_meta(get_current_user_id(), 'dndm_campanha_ativa', true);
        }

        // Busca imagem da sessão
        $imagem_data = $camp_id
            ? get_option('dndm_imagem_sessao_'.$camp_id, array('url'=>'','ts'=>0))
            : array('url'=>'','ts'=>0);

        return rest_ensure_response(array(
            'status'        => $insc ? $insc->camp_status : 'sem_inscricao',
            'campanha_id'   => $camp_id,
            'campanha_nome' => $insc ? $insc->camp_nome : '',
            'imagem_url'    => $imagem_data['url'] ?? '',
            'ts'            => (int)($imagem_data['ts'] ?? 0),
        ));
    }

    public static function inscrever_campanha(WP_REST_Request $r) {
        global $wpdb;
        $campanha_id = (int) $r->get_param('id');
        $dnd = DNDM_Auth::get_usuario_dnd();
        if (!$dnd) return new WP_Error('sem_usuario','Usuário não encontrado',array('status'=>401));

        // Verifica se já inscrito
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_inscricoes WHERE campanha_id=%d AND usuario_id=%d",
            $campanha_id, $dnd->id
        ));
        if ($existe) return rest_ensure_response(array('sucesso'=>false,'erro'=>'Já inscrito nesta campanha.'));

        $wpdb->insert($wpdb->prefix.'dnd_inscricoes', array(
            'campanha_id' => $campanha_id,
            'usuario_id'  => $dnd->id,
            'status'      => 'pendente',
        ));
        return rest_ensure_response(array('sucesso'=>true,'status'=>'pendente'));
    }

    public static function listar_inscricoes(WP_REST_Request $r) {
        global $wpdb;
        $campanha_id = (int) $r->get_param('id');
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, u.wp_user_id FROM {$wpdb->prefix}dnd_inscricoes i
             LEFT JOIN {$wpdb->prefix}dnd_usuarios u ON i.usuario_id = u.id
             WHERE i.campanha_id = %d ORDER BY i.criado_em ASC",
            $campanha_id
        ));
        $resultado = array();
        foreach ($rows as $row) {
            $wp_user = get_user_by('ID', $row->wp_user_id);
            $personagem = $wpdb->get_row($wpdb->prepare(
                "SELECT nome, classe, nivel, imagem_url FROM {$wpdb->prefix}dnd_personagens
                 WHERE usuario_id=%d AND status='ativo' LIMIT 1",
                $row->usuario_id
            ));
            $resultado[] = array(
                'id'          => (int) $row->id,
                'usuario_id'  => (int) $row->usuario_id,
                'nome'        => $wp_user ? $wp_user->display_name : '?',
                'email'       => $wp_user ? $wp_user->user_email : '',
                'status'      => $row->status,
                'personagem'  => $personagem ? $personagem->nome : null,
                'classe'      => $personagem ? $personagem->classe : null,
                'nivel'       => $personagem ? (int)$personagem->nivel : null,
                'imagem'      => $personagem ? $personagem->imagem_url : null,
                'criado_em'   => $row->criado_em,
            );
        }
        return rest_ensure_response($resultado);
    }

    public static function aprovar_inscricao(WP_REST_Request $r) {
        global $wpdb;
        $insc_id = (int) $r->get_param('id');
        $inscricao = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_inscricoes WHERE id=%d", $insc_id
        ));
        if (!$inscricao) return new WP_Error('nao_encontrado','Inscrição não encontrada',array('status'=>404));

        $wpdb->update($wpdb->prefix.'dnd_inscricoes', array('status'=>'aprovado'), array('id'=>$insc_id));

        // Determina o personagem ativo do jogador (via user meta ou o mais recente)
        $dnd_usuario = $wpdb->get_row($wpdb->prepare(
            "SELECT wp_user_id FROM {$wpdb->prefix}dnd_usuarios WHERE id=%d", $inscricao->usuario_id
        ));
        $personagem_ativo_id = $dnd_usuario
            ? (int) get_user_meta($dnd_usuario->wp_user_id, 'dndm_personagem_ativo', true)
            : 0;

        if ($personagem_ativo_id) {
            // Vincula só o personagem ativo
            $wpdb->update(
                $wpdb->prefix.'dnd_personagens',
                array('campanha_id' => $inscricao->campanha_id),
                array('id' => $personagem_ativo_id, 'usuario_id' => $inscricao->usuario_id)
            );
        } else {
            // Fallback: vincula o personagem mais recente com status ativo
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}dnd_personagens SET campanha_id=%d
                 WHERE usuario_id=%d AND status='ativo'
                 ORDER BY criado_em DESC LIMIT 1",
                $inscricao->campanha_id, $inscricao->usuario_id
            ));
        }

        return rest_ensure_response(array('sucesso'=>true));
    }

    public static function rejeitar_inscricao(WP_REST_Request $r) {
        global $wpdb;
        $insc_id = (int) $r->get_param('id');
        $wpdb->update($wpdb->prefix.'dnd_inscricoes', array('status'=>'rejeitado'), array('id'=>$insc_id));
        return rest_ensure_response(array('sucesso'=>true));
    }

    public static function minhas_campanhas() {
        global $wpdb;
        $dnd = DNDM_Auth::get_usuario_dnd();
        if (!$dnd) return rest_ensure_response(array());

        // Campanhas onde foi aprovado
        $aprovadas = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.nome AS modulo_nome FROM {$wpdb->prefix}dnd_campanhas c
             LEFT JOIN {$wpdb->prefix}dnd_modulos m ON c.modulo_id = m.id
             INNER JOIN {$wpdb->prefix}dnd_inscricoes i ON i.campanha_id = c.id
             WHERE i.usuario_id=%d AND i.status='aprovado'",
            $dnd->id
        ));

        // Inscrições pendentes
        $pendentes = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.nome, c.status AS campanha_status, 'pendente' AS inscricao_status
             FROM {$wpdb->prefix}dnd_campanhas c
             INNER JOIN {$wpdb->prefix}dnd_inscricoes i ON i.campanha_id = c.id
             WHERE i.usuario_id=%d AND i.status='pendente'",
            $dnd->id
        ));

        return rest_ensure_response(array(
            'aprovadas' => $aprovadas,
            'pendentes' => $pendentes,
        ));
    }

    // ════════════════════════════════════════════════════════════════════════
    // SOLO ADVENTURES — Arquitetura por Cenas com Opções
    // ════════════════════════════════════════════════════════════════════════

    /** Lista aventuras solo ativas com status de sessão e bloqueio por nível */
    public static function solo_listar_aventuras() {
        global $wpdb;
        $dnd = DNDM_Auth::get_usuario_dnd();
        if (!$dnd) return new WP_Error('sem_usuario', 'Não autenticado', array('status'=>401));

        $ativo_id    = (int) get_user_meta( get_current_user_id(), 'dndm_personagem_ativo', true );
        $nivel_ativo = 1;
        if ( $ativo_id ) {
            $nivel_ativo = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT nivel FROM {$wpdb->prefix}dnd_personagens WHERE id=%d", $ativo_id
            )) ?: 1;
        }

        $aventuras = $wpdb->get_results(
            "SELECT id, nome, synopsis, duracao, nivel, nivel_minimo, mestre_id, capa_url, criado_em
             FROM {$wpdb->prefix}dnd_solo_aventuras
             WHERE status='ativa' ORDER BY nivel_minimo ASC, criado_em ASC"
        );

        $resultado = array();
        foreach ($aventuras as $av) {
            // Se nome está vazio, tenta ler do json_content
            if ( empty($av->nome) ) {
                $json_raw  = $wpdb->get_var($wpdb->prepare(
                    "SELECT json_content FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $av->id
                ));
                $json_data = json_decode($json_raw, true);
                $av->nome  = $json_data['campanha']['titulo']
                          ?? $json_data['aventura']['nome']
                          ?? $json_data['nome']
                          ?? 'Aventura Solo';
            }

            $sessao = $wpdb->get_row($wpdb->prepare(
                "SELECT status, cena_atual, iniciada_em, concluida_em
                 FROM {$wpdb->prefix}dnd_solo_sessoes
                 WHERE aventura_id=%d AND usuario_id=%d",
                $av->id, $dnd->id
            ));
            $av->nivel_minimo     = (int)($av->nivel_minimo ?: 1);
            $av->nivel_bloqueado  = $nivel_ativo < $av->nivel_minimo;
            $av->sessao_status    = $sessao ? $sessao->status       : null;
            $av->sessao_cena      = $sessao ? $sessao->cena_atual   : null;
            $av->sessao_iniciada  = $sessao ? $sessao->iniciada_em  : null;
            $av->sessao_concluida = $sessao ? $sessao->concluida_em : null;
            $resultado[] = $av;
        }
        return rest_ensure_response($resultado);
    }

    /** Retorna dados completos de uma aventura incluindo cenas, mapa e colecionáveis */
    public static function solo_get_aventura( WP_REST_Request $r ) {
        global $wpdb;
        $id = intval($r->get_param('id'));
        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d AND status='ativa'", $id
        ));
        if (!$av) return new WP_Error('nao_encontrado', 'Aventura não encontrada', array('status'=>404));
        $av->json_content = json_decode($av->json_content, true);
        return rest_ensure_response($av);
    }

    /** Busca progresso da sessão do usuário */
    public static function solo_get_sessao( WP_REST_Request $r ) {
        global $wpdb;
        $dnd         = DNDM_Auth::get_usuario_dnd();
        $aventura_id = intval($r->get_param('aventura_id'));

        $sessao = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_sessoes
             WHERE aventura_id=%d AND usuario_id=%d",
            $aventura_id, $dnd->id
        ));

        if (!$sessao) return rest_ensure_response(array('sessao' => null));

        return rest_ensure_response(array('sessao' => array(
            'id'               => (int)$sessao->id,
            'cena_atual'       => $sessao->cena_atual ?: '1',
            'cenas_visitadas'  => json_decode($sessao->cenas_visitadas  ?: '[]', true),
            'itens_coletados'  => json_decode($sessao->itens_coletados  ?: '[]', true),
            'bonus_ativos'     => json_decode($sessao->bonus_ativos     ?: '[]', true),
            'tentativas_falhas'=> json_decode($sessao->tentativas_falhas?: '{}', true),
            'opcoes_clicadas'  => json_decode($sessao->opcoes_clicadas  ?: '{}', true),
            'flags'            => json_decode($sessao->flags            ?: '{}', true),
            'memorias'         => json_decode($sessao->memorias         ?: '[]', true),
            'relacionamentos'  => json_decode($sessao->relacionamentos  ?: '{}', true),
            'texto_intro'      => $sessao->texto_intro,
            'status'           => $sessao->status,
        )));
    }

    /** Salva progresso da sessão — inclui flags, memórias, relacionamentos */
    public static function solo_salvar_sessao( WP_REST_Request $r ) {
        global $wpdb;
        $dnd              = DNDM_Auth::get_usuario_dnd();
        $aventura_id      = intval($r->get_param('aventura_id'));
        $personagem_id    = intval($r->get_param('personagem_id'));
        $cena_atual       = sanitize_text_field($r->get_param('cena_atual') ?? '1');
        $cenas_visitadas  = $r->get_param('cenas_visitadas')   ?: array();
        $itens_coletados  = $r->get_param('itens_coletados')   ?: array();
        $bonus_ativos     = $r->get_param('bonus_ativos')      ?: array();
        $tentativas_falhas= $r->get_param('tentativas_falhas') ?: array();
        $opcoes_clicadas  = $r->get_param('opcoes_clicadas')   ?: array();
        $flags            = $r->get_param('flags')             ?: array();
        $memorias         = $r->get_param('memorias')          ?: array();
        $relacionamentos  = $r->get_param('relacionamentos')   ?: array();
        $texto_intro      = sanitize_textarea_field($r->get_param('texto_intro') ?? '');

        $dados = array(
            'personagem_id'    => $personagem_id,
            'cena_atual'       => $cena_atual,
            'cenas_visitadas'  => wp_json_encode($cenas_visitadas),
            'itens_coletados'  => wp_json_encode($itens_coletados),
            'bonus_ativos'     => wp_json_encode($bonus_ativos),
            'tentativas_falhas'=> wp_json_encode($tentativas_falhas),
            'opcoes_clicadas'  => wp_json_encode($opcoes_clicadas),
            'flags'            => wp_json_encode($flags),
            'memorias'         => wp_json_encode($memorias),
            'relacionamentos'  => wp_json_encode($relacionamentos),
            'status'           => 'em_andamento',
        );
        if ($texto_intro) $dados['texto_intro'] = $texto_intro;

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_sessoes
             WHERE aventura_id=%d AND usuario_id=%d",
            $aventura_id, $dnd->id
        ));

        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_sessoes', $dados, array('id'=>$existe));
        } else {
            $dados['aventura_id'] = $aventura_id;
            $dados['usuario_id']  = $dnd->id;
            $wpdb->insert($wpdb->prefix.'dnd_solo_sessoes', $dados);
        }
        return rest_ensure_response(array('sucesso' => true));
    }

    /** Conclui aventura e concede badge */
    public static function solo_concluir( WP_REST_Request $r ) {
        global $wpdb;
        $dnd         = DNDM_Auth::get_usuario_dnd();
        $aventura_id = intval($r->get_param('aventura_id'));
        $final_tipo  = sanitize_text_field($r->get_param('final_tipo') ?? 'comum');

        $wpdb->update(
            $wpdb->prefix.'dnd_solo_sessoes',
            array('status'=>'concluida','concluida_em'=>current_time('mysql')),
            array('aventura_id'=>$aventura_id,'usuario_id'=>$dnd->id)
        );

        // Badge fofoqueiro
        $badge_slug = 'fofoqueiro_phandalin';
        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT nome FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $aventura_id
        ));
        $ja_tem = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_achievements WHERE user_id=%d AND badge_slug=%s",
            get_current_user_id(), $badge_slug
        ));
        if (!$ja_tem) {
            $wpdb->insert($wpdb->prefix.'dnd_achievements', array(
                'user_id'       => get_current_user_id(),
                'badge_slug'    => $badge_slug,
                'aventura_nome' => $av ? $av->nome : '',
            ));
        }

        return rest_ensure_response(array(
            'sucesso'    => true,
            'badge'      => $badge_slug,
            'final_tipo' => $final_tipo,
        ));
    }

    /**
     * POST /solo/intro/{aventura_id}
     * Gera introdução personalizada via Groq para o personagem do jogador.
     * Chamado apenas uma vez por sessão.
     */
    public static function solo_gerar_intro( WP_REST_Request $r ) {
        $groq_key = get_option('dndm_groq_key','');
        if (empty($groq_key)) return new WP_REST_Response(array('erro'=>'Groq não configurado.'),500);

        global $wpdb;
        $dnd           = DNDM_Auth::get_usuario_dnd();
        $aventura_id   = intval($r->get_param('aventura_id'));
        $personagem_id = intval($r->get_param('personagem_id'));

        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT nome, synopsis, json_content, mestre_id FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $aventura_id
        ));
        if (!$av) return new WP_REST_Response(array('erro'=>'Aventura não encontrada.'),404);
        $p = DNDM_Personagem::get_ficha_completa($personagem_id);
        if (!$p) return new WP_REST_Response(array('erro'=>'Personagem não encontrado.'),404);

        $mestre = $av->mestre_id
            ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE id=%d", $av->mestre_id))
            : null;
        if (!$mestre) $mestre = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE status='ativo' ORDER BY id ASC LIMIT 1");
        $persona = $mestre ? $mestre->persona : "Você é um narrador de D&D teatral e carismático.";
        $nome_mestre = $mestre ? $mestre->nome : 'Narrador';

        $av_json   = json_decode($av->json_content, true);
        $meta      = $av_json['campanha'] ?? $av_json['aventura'] ?? $av_json;
        $intro_ctx = $meta['introducao_ia_contexto'] ?? '';

        $system = $persona . "\n\nCrie uma introdução personalizada para o jogador em 2-3 parágrafos. " .
                  "Faça uma fofoca sobre o personagem baseada na sua backstory. " .
                  "Apresente o contexto da aventura de forma envolvente e noir. " .
                  ($intro_ctx ? "Diretrizes: {$intro_ctx}\n" : "") .
                  "Responda APENAS com o texto da introdução, sem tags.";

        $user = "Personagem: {$p->nome} ({$p->raca} {$p->classe} Nível {$p->nivel})\n" .
                "Backstory: {$p->backstory}\nAlinhamento: {$p->alinhamento}\n\n" .
                "Aventura: {$av->nome}\nSinopse: {$av->synopsis}";

        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array('Authorization'=>'Bearer '.$groq_key,'Content-Type'=>'application/json'),
            'body' => wp_json_encode(array(
                'model'=>'llama-3.3-70b-versatile','max_tokens'=>600,'temperature'=>0.9,
                'messages'=>array(array('role'=>'system','content'=>$system),array('role'=>'user','content'=>$user)),
            )),
        ));
        if (is_wp_error($response)) return new WP_REST_Response(array('erro'=>$response->get_error_message()),500);
        $json  = json_decode(wp_remote_retrieve_body($response),true);
        $texto = $json['choices'][0]['message']['content'] ?? '';
        if (empty($texto)) return new WP_REST_Response(array('erro'=>'Resposta vazia.'),500);
        return rest_ensure_response(array('sucesso'=>true,'intro'=>$texto,'narrador'=>$nome_mestre));
    }

        public static function solo_gerar_conclusao( WP_REST_Request $r ) {
        $groq_key = get_option('dndm_groq_key','');
        if (empty($groq_key)) return new WP_REST_Response(array('erro'=>'Groq não configurado.'),500);

        global $wpdb;
        $aventura_id   = intval($r->get_param('aventura_id'));
        $personagem_id = intval($r->get_param('personagem_id'));
        $final_tipo    = sanitize_text_field($r->get_param('final_tipo') ?? 'comum');
        $itens         = $r->get_param('itens_coletados') ?: array();

        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT nome, json_content, mestre_id FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $aventura_id
        ));
        $p  = DNDM_Personagem::get_ficha_completa($personagem_id);
        if (!$av || !$p) return new WP_REST_Response(array('erro'=>'Não encontrado.'),404);

        $mestre = null;
        if ($av->mestre_id) $mestre = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE id=%d", $av->mestre_id
        ));
        if (!$mestre) $mestre = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}dnd_solo_mestres WHERE status='ativo' ORDER BY id ASC LIMIT 1");

        $persona     = $mestre ? $mestre->persona : "Você é um narrador teatral de D&D.";
        $nome_mestre = $mestre ? $mestre->nome : 'Narrador';

        $aventura_json = json_decode($av->json_content, true);
        $texto_final   = $aventura_json['finais'][$final_tipo]['texto'] ?? $aventura_json['finais']['comum'] ?? '';

        $itens_str = !empty($itens) ? implode(', ', $itens) : 'nenhum item especial';

        $system = $persona . "\n\nCrie uma conclusão épica e personalizada para o jogador, em 2-3 parágrafos. " .
                  "O narrador comenta sobre a jornada, menciona os itens coletados e encerra com o texto do final. " .
                  "Tom dramático, com personalidade marcante. Responda APENAS com o texto da conclusão.";

        $user = "Personagem: {$p->nome} ({$p->raca} {$p->classe})\n" .
                "Aventura concluída: {$av->nome}\n" .
                "Final alcançado: {$final_tipo}\n" .
                "Texto do final: {$texto_final}\n" .
                "Itens coletados: {$itens_str}";

        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array('Authorization'=>'Bearer '.$groq_key,'Content-Type'=>'application/json'),
            'body' => wp_json_encode(array(
                'model'      => 'llama-3.3-70b-versatile',
                'max_tokens' => 600,
                'temperature'=> 0.9,
                'messages'   => array(
                    array('role'=>'system','content'=>$system),
                    array('role'=>'user',  'content'=>$user),
                ),
            )),
        ));

        if (is_wp_error($response)) return new WP_REST_Response(array('erro'=>$response->get_error_message()),500);
        $json  = json_decode(wp_remote_retrieve_body($response), true);
        $texto = $json['choices'][0]['message']['content'] ?? '';
        if (empty($texto)) return new WP_REST_Response(array('erro'=>'Resposta vazia.'),500);

        return rest_ensure_response(array('sucesso'=>true,'conclusao'=>$texto,'narrador'=>$nome_mestre));
    }

    /** GET /solo/colecao — cartas colecionáveis do usuário */
    public static function solo_get_colecao() {
        global $wpdb;
        $user_id = get_current_user_id();
        $cartas  = $wpdb->get_results($wpdb->prepare(
            "SELECT card_id, aventura_id, coletado_em FROM {$wpdb->prefix}dnd_solo_colecao
             WHERE user_id=%d ORDER BY coletado_em DESC",
            $user_id
        ));
        return rest_ensure_response($cartas);
    }

    /** POST /solo/colecao/adicionar — registra carta coletada */
    public static function solo_adicionar_colecao( WP_REST_Request $r ) {
        global $wpdb;
        $user_id     = get_current_user_id();
        $aventura_id = intval($r->get_param('aventura_id'));
        $card_id     = sanitize_text_field($r->get_param('card_id') ?? '');

        if (empty($card_id)) return new WP_REST_Response(array('erro'=>'card_id obrigatório.'),400);

        $ja_tem = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_colecao WHERE user_id=%d AND card_id=%s",
            $user_id, $card_id
        ));
        if (!$ja_tem) {
            $wpdb->insert($wpdb->prefix.'dnd_solo_colecao', array(
                'user_id'     => $user_id,
                'aventura_id' => $aventura_id,
                'card_id'     => $card_id,
            ));
        }
        return rest_ensure_response(array('sucesso'=>true,'nova'=>!$ja_tem));
    }



        // ════════════════════════════════════════════════════════════════════════
    // SOLO ASSETS — Mapas e Imagens de Cena
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /solo/assets/{aventura_id}
     * Retorna todos os assets gerados (mapas + imagens) de uma aventura.
     */
    public static function solo_get_assets( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, asset_id, url FROM {$wpdb->prefix}dnd_solo_assets
             WHERE aventura_id=%d", $aventura_id
        ));

        $assets = array('mapas' => array(), 'imagens' => array());
        foreach ($rows as $row) {
            if ($row->tipo === 'mapa') {
                $assets['mapas'][$row->asset_id] = $row->url;
            } else {
                $assets['imagens'][$row->asset_id] = $row->url;
            }
        }

        // Inclui também dados dos locais do mapa (do JSON da aventura)
        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT json_content FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $aventura_id
        ));
        $locais_mapa = array();
        if ($av) {
            $json = json_decode($av->json_content, true);
            if (!empty($json['mapas'])) {
                foreach ($json['mapas'] as $mapa_id => $mapa_data) {
                    if (!empty($mapa_data['locais'])) {
                        $locais_mapa[$mapa_id] = $mapa_data['locais'];
                    }
                }
            }
        }

        return rest_ensure_response(array(
            'assets'      => $assets,
            'locais_mapa' => $locais_mapa,
        ));
    }

    /**
     * POST /solo/assets/{aventura_id}/gerar
     * Gera TODOS os assets de uma aventura via Pollinations (admin only).
     * Retorna contagem de gerados/falhos.
     */
    public static function solo_gerar_assets( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));

        $av = $wpdb->get_row($wpdb->prepare(
            "SELECT json_content FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $aventura_id
        ));
        if (!$av) return new WP_REST_Response(array('erro' => 'Aventura não encontrada.'), 404);

        $json   = json_decode($av->json_content, true);
        $gerado = 0; $falhou = 0;

        // Gera mapas
        if (!empty($json['mapas'])) {
            foreach ($json['mapas'] as $mapa_id => $mapa_data) {
                $prompt = is_array($mapa_data) ? ($mapa_data['prompt'] ?? '') : $mapa_data;
                if (empty($prompt)) continue;
                $prompt_en = $prompt . ', top-down fantasy map, hand-drawn style, warm colors, detailed, parchment texture';
                $url = DNDM_Imagem::gerar_e_salvar($prompt_en, 'solo-mapa-'.$aventura_id.'-'.$mapa_id, 'solo/mapas');
                if ($url) {
                    self::_upsert_asset($aventura_id, 'mapa', $mapa_id, $url, $prompt);
                    $gerado++;
                } else { $falhou++; }
            }
        }

        // Gera imagens de cena
        if (!empty($json['imagens_cena'])) {
            foreach ($json['imagens_cena'] as $img_id => $prompt) {
                if (empty($prompt)) continue;
                $prompt_en = DNDM_Imagem::traduzir_prompt($prompt) . ', D&D fantasy illustration, cinematic, detailed, dark atmosphere';
                $url = DNDM_Imagem::gerar_e_salvar($prompt_en, 'solo-cena-'.$aventura_id.'-'.$img_id, 'solo/cenas');
                if ($url) {
                    self::_upsert_asset($aventura_id, 'imagem', $img_id, $url, $prompt);
                    $gerado++;
                } else { $falhou++; }
            }
        }

        return rest_ensure_response(array(
            'sucesso' => true,
            'gerado'  => $gerado,
            'falhou'  => $falhou,
        ));
    }

    /**
     * POST /solo/assets/{aventura_id}/gerar-um
     * Gera ou regenera um asset específico.
     * Body: { tipo: 'mapa'|'imagem', asset_id: 'xxx', prompt: 'opcional override' }
     */
    public static function solo_gerar_asset_unico( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $tipo        = sanitize_text_field($r->get_param('tipo') ?? 'imagem');
        $asset_id    = sanitize_text_field($r->get_param('asset_id') ?? '');
        $prompt_ovr  = sanitize_textarea_field($r->get_param('prompt') ?? '');

        if (empty($asset_id)) return new WP_REST_Response(array('erro' => 'asset_id obrigatório.'), 400);

        // Busca prompt no JSON se não foi passado override
        if (empty($prompt_ovr)) {
            $av = $wpdb->get_row($wpdb->prepare(
                "SELECT json_content FROM {$wpdb->prefix}dnd_solo_aventuras WHERE id=%d", $aventura_id
            ));
            if ($av) {
                $json = json_decode($av->json_content, true);
                if ($tipo === 'mapa' && !empty($json['mapas'][$asset_id])) {
                    $mapa_data = $json['mapas'][$asset_id];
                    $prompt_ovr = is_array($mapa_data) ? ($mapa_data['prompt'] ?? '') : $mapa_data;
                } elseif ($tipo === 'imagem' && !empty($json['imagens_cena'][$asset_id])) {
                    $prompt_ovr = $json['imagens_cena'][$asset_id];
                }
            }
        }

        if (empty($prompt_ovr)) return new WP_REST_Response(array('erro' => 'Prompt não encontrado.'), 400);

        if ($tipo === 'mapa') {
            $prompt_en = $prompt_ovr . ', top-down fantasy map, hand-drawn style, warm colors, detailed, parchment texture';
            $subpasta  = 'solo/mapas';
            $nome      = 'solo-mapa-'.$aventura_id.'-'.$asset_id;
        } else {
            $prompt_en = DNDM_Imagem::traduzir_prompt($prompt_ovr) . ', D&D fantasy illustration, cinematic, detailed, dark atmosphere';
            $subpasta  = 'solo/cenas';
            $nome      = 'solo-cena-'.$aventura_id.'-'.$asset_id;
        }

        $url = DNDM_Imagem::gerar_e_salvar($prompt_en, $nome, $subpasta);
        if (!$url) return new WP_REST_Response(array('erro' => 'Falha ao gerar imagem.'), 500);

        self::_upsert_asset($aventura_id, $tipo, $asset_id, $url, $prompt_ovr);

        return rest_ensure_response(array('sucesso' => true, 'url' => $url));
    }

    /** Helper — upsert na tabela de assets */
    private static function _upsert_asset($aventura_id, $tipo, $asset_id, $url, $prompt = '') {
        global $wpdb;
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_assets
             WHERE aventura_id=%d AND tipo=%s AND asset_id=%s",
            $aventura_id, $tipo, $asset_id
        ));
        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_assets',
                array('url'=>$url, 'prompt'=>$prompt),
                array('id'=>$existe)
            );
        } else {
            $wpdb->insert($wpdb->prefix.'dnd_solo_assets', array(
                'aventura_id' => $aventura_id,
                'tipo'        => $tipo,
                'asset_id'    => $asset_id,
                'url'         => $url,
                'prompt'      => $prompt,
            ));
        }
    }

    /**
     * POST /solo/chat-npc
     * Chat livre com o NPC/narrador do ato atual. Contexto vem do campo chat_contexto do ato.
     */
    public static function solo_chat_npc( WP_REST_Request $r ) {
        $groq_key = get_option('dndm_groq_key','');
        if (empty($groq_key)) return new WP_REST_Response(array('erro'=>'Groq não configurado.'),500);

        global $wpdb;
        $aventura_id   = intval($r->get_param('aventura_id'));
        $personagem_id = intval($r->get_param('personagem_id'));
        $contexto      = sanitize_textarea_field($r->get_param('contexto') ?? '');
        $historico     = $r->get_param('historico') ?: array();
        $mensagem      = sanitize_textarea_field($r->get_param('mensagem') ?? '');
        $memorias      = $r->get_param('memorias')      ?: array();
        $flags         = $r->get_param('flags')         ?: array();
        $relacionamentos = $r->get_param('relacionamentos') ?: array();

        if (empty($mensagem)) return new WP_REST_Response(array('erro'=>'Mensagem vazia.'),400);

        $p = DNDM_Personagem::get_ficha_completa($personagem_id);

        // Monta contexto de memória
        $ctx_memoria = '';
        if (!empty($memorias)) {
            $ctx_memoria .= "\n\nMEMÓRIAS DO JOGADOR NESTA SESSÃO:\n";
            foreach (array_slice($memorias, -5) as $mem) { // máx 5 memórias recentes
                $ctx_memoria .= "- " . (is_array($mem) ? $mem['texto'] : $mem) . "\n";
            }
        }
        $ctx_flags = '';
        if (!empty($flags)) {
            $flags_ativos = array_keys(array_filter($flags));
            if (!empty($flags_ativos)) {
                $ctx_flags = "\nFLAGS ATIVAS: " . implode(', ', $flags_ativos) . "\n";
            }
        }
        $ctx_rel = '';
        if (!empty($relacionamentos)) {
            $ctx_rel = "\nRELACIONAMENTOS:\n";
            foreach ($relacionamentos as $npc => $score) {
                $nivel = $score >= 50 ? 'amigável' : ($score >= 0 ? 'neutro' : ($score >= -50 ? 'desconfiante' : 'hostil'));
                $ctx_rel .= "- {$npc}: {$score} ({$nivel})\n";
            }
        }

        $system = !empty($contexto) ? $contexto
            : "Você é um personagem de D&D 5e numa aventura noir. Responda em português brasileiro.";

        if ($p) $system .= "\n\nO jogador é {$p->nome} ({$p->raca} {$p->classe} Nível {$p->nivel}).";
        $system .= $ctx_memoria . $ctx_flags . $ctx_rel;
        $system .= "\n\nResponda em português brasileiro. Mantenha-se no personagem. 1 a 3 parágrafos.";

        $messages = array(array('role'=>'system','content'=>$system));
        foreach ($historico as $msg) {
            if (!empty($msg['role'])&&!empty($msg['content'])) {
                $messages[] = array('role'=>$msg['role']=== 'assistant'?'assistant':'user','content'=>$msg['content']);
            }
        }
        $messages[] = array('role'=>'user','content'=>$mensagem);

        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array('Authorization'=>'Bearer '.$groq_key,'Content-Type'=>'application/json'),
            'body' => wp_json_encode(array(
                'model'=>'llama-3.3-70b-versatile','messages'=>$messages,'max_tokens'=>400,'temperature'=>0.85,
            )),
        ));
        if (is_wp_error($response)) return new WP_REST_Response(array('erro'=>$response->get_error_message()),500);
        $json  = json_decode(wp_remote_retrieve_body($response),true);
        $texto = $json['choices'][0]['message']['content'] ?? '';
        if (empty($texto)) return new WP_REST_Response(array('erro'=>'Resposta vazia.'),500);
        return rest_ensure_response(array('sucesso'=>true,'resposta'=>$texto));
    }

        /**
     * POST /registro — Cadastro público de novos aventureiros (tier3)
     */
    public static function registro_publico( WP_REST_Request $r ) {
        $nome  = sanitize_text_field( $r->get_param('nome')  ?? '' );
        $email = sanitize_email(      $r->get_param('email') ?? '' );
        $senha = $r->get_param('senha') ?? '';

        if ( empty($nome) || empty($email) || empty($senha) )
            return new WP_REST_Response(array('erro' => 'Preencha todos os campos.'), 400);
        if ( ! is_email($email) )
            return new WP_REST_Response(array('erro' => 'E-mail inválido.'), 400);
        if ( strlen($senha) < 6 )
            return new WP_REST_Response(array('erro' => 'A senha deve ter pelo menos 6 caracteres.'), 400);
        if ( email_exists($email) )
            return new WP_REST_Response(array('erro' => 'Este e-mail já está cadastrado.'), 400);

        // Username único baseado no nome
        $username_base = sanitize_user( strtolower( str_replace(' ', '', $nome) ), true );
        if ( empty($username_base) ) $username_base = 'aventureiro';
        $username = $username_base; $suffix = 1;
        while ( username_exists($username) ) { $username = $username_base . $suffix; $suffix++; }

        // Cria usuário WP
        $wp_user_id = wp_create_user( $username, $senha, $email );
        if ( is_wp_error($wp_user_id) )
            return new WP_REST_Response(array('erro' => $wp_user_id->get_error_message()), 400);

        wp_update_user(array('ID' => $wp_user_id, 'display_name' => $nome, 'first_name' => $nome));

        // Cria usuário DnD tier3
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'dnd_usuarios', array(
            'wp_user_id' => $wp_user_id, 'papel' => 'jogador', 'tier' => 'tier3',
        ));

        // Login automático
        wp_set_current_user( $wp_user_id );
        wp_set_auth_cookie( $wp_user_id, true );

        // E-mail de boas-vindas
        $site = get_bloginfo('name');
        wp_mail( $email, '⚔ Bem-vindo(a) ao ' . $site . '!',
            "Olá, {$nome}!

Sua conta de aventureiro foi criada com sucesso.

" .
            "📧 E-mail: {$email}
🔑 Senha: a que você escolheu no cadastro

" .
            "Acesse: " . home_url('/dnd-mestre') . "

Que os dados rolem a seu favor!

— " . $site
        );

        return rest_ensure_response(array('sucesso' => true, 'redirect' => home_url('/dnd-mestre')));
    }


// ════════════════════════════════════════════════════════════════════════
    // v0.9.6RC — EDITOR DE AVENTURA
    // ════════════════════════════════════════════════════════════════════════

    /** Retorna todos os dados editáveis de uma aventura de uma vez (atos + locais + npcs + intro) */
    public static function editor_get_todos( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));

        $atos    = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_atos WHERE aventura_id=%d ORDER BY ato_id+0 ASC", $aventura_id
        ));
        $locais  = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_locais WHERE aventura_id=%d", $aventura_id
        ));
        $npcs    = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_npcs WHERE aventura_id=%d", $aventura_id
        ));
        $intro   = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_intro_lore WHERE aventura_id=%d", $aventura_id
        ));

        // Normaliza imagens JSON
        foreach ($locais as $l) $l->imagens = json_decode($l->imagens ?: '[]', true);
        if ($intro) $intro->imagens = json_decode($intro->imagens ?: '[]', true);
        foreach ($atos as $a) $a->quebras = json_decode($a->quebras ?: '[]', true);

        return rest_ensure_response(array(
            'atos'   => $atos,
            'locais' => $locais,
            'npcs'   => $npcs,
            'intro'  => $intro,
        ));
    }

    /** GET ato editável */
    public static function editor_get_ato( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $ato_id      = sanitize_text_field($r->get_param('ato_id'));
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_atos WHERE aventura_id=%d AND ato_id=%s",
            $aventura_id, $ato_id
        ));
        if ($row) $row->quebras = json_decode($row->quebras ?: '[]', true);
        return rest_ensure_response($row ?: array());
    }

    /** POST salvar ato editável */
    public static function editor_salvar_ato( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id   = intval($r->get_param('aventura_id'));
        $ato_id        = sanitize_text_field($r->get_param('ato_id'));
        $titulo        = sanitize_text_field($r->get_param('titulo') ?? '');
        $dialogo       = sanitize_textarea_field($r->get_param('dialogo') ?? '');
        $imagem_url    = esc_url_raw($r->get_param('imagem_url') ?? '');
        $imagem_lore   = sanitize_textarea_field($r->get_param('imagem_lore') ?? '');
        $imagem_prompt = sanitize_textarea_field($r->get_param('imagem_prompt') ?? '');
        $npc_ativo     = sanitize_text_field($r->get_param('npc_ativo') ?? '');
        $quebras       = $r->get_param('quebras') ?: array();

        $dados = array(
            'titulo'        => $titulo,
            'dialogo'       => $dialogo,
            'imagem_url'    => $imagem_url,
            'imagem_lore'   => $imagem_lore,
            'imagem_prompt' => $imagem_prompt,
            'npc_ativo'     => $npc_ativo,
            'quebras'       => wp_json_encode($quebras),
        );

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_atos WHERE aventura_id=%d AND ato_id=%s",
            $aventura_id, $ato_id
        ));
        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_atos', $dados, array('id'=>$existe));
        } else {
            $dados['aventura_id'] = $aventura_id;
            $dados['ato_id']      = $ato_id;
            $wpdb->insert($wpdb->prefix.'dnd_solo_atos', $dados);
        }
        return rest_ensure_response(array('sucesso'=>true));
    }

    /** GET local (lore + imagens) */
    public static function editor_get_local( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $local_id    = sanitize_text_field($r->get_param('local_id'));
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_locais WHERE aventura_id=%d AND local_id=%s",
            $aventura_id, $local_id
        ));
        if ($row) $row->imagens = json_decode($row->imagens ?: '[]', true);
        return rest_ensure_response($row ?: array());
    }

    /** POST salvar local */
    public static function editor_salvar_local( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $local_id    = sanitize_text_field($r->get_param('local_id'));
        $nome        = sanitize_text_field($r->get_param('nome') ?? '');
        $lore_texto  = sanitize_textarea_field($r->get_param('lore_texto') ?? '');
        $imagens     = $r->get_param('imagens') ?: array(); // [{url, lore, titulo}]

        // Máx 5 imagens
        $imagens = array_slice($imagens, 0, 5);

        $dados = array('nome'=>$nome, 'lore_texto'=>$lore_texto, 'imagens'=>wp_json_encode($imagens));
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_locais WHERE aventura_id=%d AND local_id=%s",
            $aventura_id, $local_id
        ));
        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_locais', $dados, array('id'=>$existe));
        } else {
            $dados['aventura_id'] = $aventura_id;
            $dados['local_id']    = $local_id;
            $wpdb->insert($wpdb->prefix.'dnd_solo_locais', $dados);
        }
        return rest_ensure_response(array('sucesso'=>true));
    }

    /** GET NPC */
    public static function editor_get_npc( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $npc_id      = sanitize_text_field($r->get_param('npc_id'));
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_npcs WHERE aventura_id=%d AND npc_id=%s",
            $aventura_id, $npc_id
        ));
        return rest_ensure_response($row ?: array());
    }

    /** POST salvar NPC */
    public static function editor_salvar_npc( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $npc_id      = sanitize_text_field($r->get_param('npc_id'));
        $nome        = sanitize_text_field($r->get_param('nome') ?? '');
        $imagem_url  = esc_url_raw($r->get_param('imagem_url') ?? '');
        $lore        = sanitize_textarea_field($r->get_param('lore') ?? '');

        $dados = array('nome'=>$nome, 'imagem_url'=>$imagem_url, 'lore'=>$lore);
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_npcs WHERE aventura_id=%d AND npc_id=%s",
            $aventura_id, $npc_id
        ));
        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_npcs', $dados, array('id'=>$existe));
        } else {
            $dados['aventura_id'] = $aventura_id;
            $dados['npc_id']      = $npc_id;
            $wpdb->insert($wpdb->prefix.'dnd_solo_npcs', $dados);
        }
        return rest_ensure_response(array('sucesso'=>true));
    }

    /** GET intro lore */
    public static function editor_get_intro_lore( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_intro_lore WHERE aventura_id=%d", $aventura_id
        ));
        if ($row) $row->imagens = json_decode($row->imagens ?: '[]', true);
        return rest_ensure_response($row ?: array());
    }

    /** POST salvar intro lore */
    public static function editor_salvar_intro_lore( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $titulo      = sanitize_text_field($r->get_param('titulo') ?? '');
        $texto       = sanitize_textarea_field($r->get_param('texto') ?? '');
        $imagens     = array_slice($r->get_param('imagens') ?: array(), 0, 5);

        $dados = array('titulo'=>$titulo, 'texto'=>$texto, 'imagens'=>wp_json_encode($imagens));
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_intro_lore WHERE aventura_id=%d", $aventura_id
        ));
        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_intro_lore', $dados, array('id'=>$existe));
        } else {
            $dados['aventura_id'] = $aventura_id;
            $wpdb->insert($wpdb->prefix.'dnd_solo_intro_lore', $dados);
        }
        return rest_ensure_response(array('sucesso'=>true));
    }

    /** POST gerar lore via Groq */
    public static function editor_gerar_lore( WP_REST_Request $r ) {
        $groq_key = get_option('dndm_groq_key','');
        if (empty($groq_key)) return new WP_REST_Response(array('erro'=>'Groq não configurado.'),500);

        $contexto = sanitize_textarea_field($r->get_param('contexto') ?? '');
        $tipo     = sanitize_text_field($r->get_param('tipo') ?? 'local'); // local, npc, imagem
        $nome     = sanitize_text_field($r->get_param('nome') ?? '');

        $prompts = array(
            'local'  => "Escreva uma lore curta e imersiva (2-3 frases) sobre o local '{$nome}' na cidade de Phandalin, D&D 5e. Contexto: {$contexto}. Tom noir e misterioso. Em português brasileiro.",
            'npc'    => "Escreva uma curiosidade/lore (2-3 frases) sobre o NPC '{$nome}' em Phandalin, D&D 5e. Contexto: {$contexto}. Revele algo sobre personalidade, segredo ou história. Em português.",
            'imagem' => "Escreva uma legenda imersiva e curiosa (1-2 frases) para uma imagem mostrando: '{$nome}'. Contexto: {$contexto}. Em português, tom épico-noir.",
        );

        $prompt = $prompts[$tipo] ?? $prompts['local'];

        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array('Authorization'=>'Bearer '.$groq_key,'Content-Type'=>'application/json'),
            'body' => wp_json_encode(array(
                'model'=>'llama-3.3-70b-versatile','max_tokens'=>200,'temperature'=>0.85,
                'messages'=>array(array('role'=>'user','content'=>$prompt)),
            )),
        ));
        if (is_wp_error($response)) return new WP_REST_Response(array('erro'=>$response->get_error_message()),500);
        $json  = json_decode(wp_remote_retrieve_body($response),true);
        $texto = $json['choices'][0]['message']['content'] ?? '';
        if (empty($texto)) return new WP_REST_Response(array('erro'=>'Resposta vazia.'),500);
        return rest_ensure_response(array('sucesso'=>true,'lore'=>trim($texto)));
    }

    /** POST gerar imagem de ato via Pollinations */
    public static function editor_gerar_imagem_ato( WP_REST_Request $r ) {
        $aventura_id = intval($r->get_param('aventura_id'));
        $ato_id      = sanitize_text_field($r->get_param('ato_id'));
        $prompt      = sanitize_textarea_field($r->get_param('prompt') ?? '');

        if (empty($prompt)) return new WP_REST_Response(array('erro'=>'Prompt obrigatório.'),400);

        $prompt_en = DNDM_Imagem::traduzir_prompt($prompt);
        $prompt_en .= ', D&D 5e fantasy illustration, cinematic, detailed, dark atmosphere, noir';
        $url = DNDM_Imagem::gerar_e_salvar($prompt_en, 'ato-'.$aventura_id.'-'.$ato_id, 'solo/atos');

        if (!$url) return new WP_REST_Response(array('erro'=>'Falha ao gerar imagem.'),500);

        // Salva URL no ato
        global $wpdb;
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_solo_atos WHERE aventura_id=%d AND ato_id=%s",
            $aventura_id, $ato_id
        ));
        if ($existe) {
            $wpdb->update($wpdb->prefix.'dnd_solo_atos', array('imagem_url'=>$url), array('id'=>$existe));
        } else {
            $wpdb->insert($wpdb->prefix.'dnd_solo_atos', array(
                'aventura_id'=>$aventura_id,'ato_id'=>$ato_id,'imagem_url'=>$url
            ));
        }
        return rest_ensure_response(array('sucesso'=>true,'url'=>$url));
    }

    /** GET diálogo de revisita (com cache) */
    public static function solo_get_revisita( WP_REST_Request $r ) {
        global $wpdb;
        $aventura_id = intval($r->get_param('aventura_id'));
        $local_id    = sanitize_text_field($r->get_param('local_id'));
        $flags       = $r->get_param('flags') ?: array();
        $flags_hash  = md5(serialize($flags));

        // Busca cache
        $cached = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_revisita
             WHERE aventura_id=%d AND local_id=%s AND flags_hash=%s",
            $aventura_id, $local_id, $flags_hash
        ));
        if ($cached) return rest_ensure_response(array('sucesso'=>true,'dialogo'=>$cached->dialogo,'cached'=>true));

        // Gera via Groq
        $groq_key = get_option('dndm_groq_key','');
        if (empty($groq_key)) return new WP_REST_Response(array('erro'=>'Groq não configurado.'),500);

        // Busca contexto do local
        $local_dados = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_solo_locais WHERE aventura_id=%d AND local_id=%s",
            $aventura_id, $local_id
        ));
        $nome_local = $local_dados ? $local_dados->nome : str_replace('_',' ',ucfirst($local_id));

        $flags_ativos = array_keys(array_filter((array)$flags));
        $ctx_flags    = !empty($flags_ativos) ? implode(', ', $flags_ativos) : 'nenhuma';

        $personagem_id = intval($r->get_param('personagem_id'));
        $p = $personagem_id ? DNDM_Personagem::get_ficha_completa($personagem_id) : null;
        $nome_personagem = $p ? $p->nome : 'Aventureiro';

        $prompt = "Você é um NPC de Phandalin em {$nome_local}. O jogador {$nome_personagem} está voltando a este local.\n" .
                  "Flags ativas da sessão: {$ctx_flags}.\n" .
                  "Reaja de forma natural à volta do jogador, mencionando sutilmente o que aconteceu (baseado nas flags). " .
                  "Seja curto (2-3 frases), natural e imersivo. Em português brasileiro. Não quebre o personagem.";

        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array('Authorization'=>'Bearer '.$groq_key,'Content-Type'=>'application/json'),
            'body' => wp_json_encode(array(
                'model'=>'llama-3.3-70b-versatile','max_tokens'=>150,'temperature'=>0.9,
                'messages'=>array(array('role'=>'user','content'=>$prompt)),
            )),
        ));
        if (is_wp_error($response)) return new WP_REST_Response(array('erro'=>$response->get_error_message()),500);
        $json   = json_decode(wp_remote_retrieve_body($response),true);
        $dialogo = trim($json['choices'][0]['message']['content'] ?? '');
        if (empty($dialogo)) return new WP_REST_Response(array('erro'=>'Resposta vazia.'),500);

        // Cacheia no banco
        $wpdb->insert($wpdb->prefix.'dnd_solo_revisita', array(
            'aventura_id'=>$aventura_id,'local_id'=>$local_id,
            'flags_hash'=>$flags_hash,'dialogo'=>$dialogo,
        ));

        return rest_ensure_response(array('sucesso'=>true,'dialogo'=>$dialogo,'cached'=>false));
    }

    /** POST editar diálogo de revisita cacheado */
    public static function solo_salvar_revisita( WP_REST_Request $r ) {
        global $wpdb;
        $id      = intval($r->get_param('id') ?? 0);
        $dialogo = sanitize_textarea_field($r->get_param('dialogo') ?? '');
        if (!$id || empty($dialogo)) return new WP_REST_Response(array('erro'=>'Dados inválidos.'),400);
        $wpdb->update($wpdb->prefix.'dnd_solo_revisita',
            array('dialogo'=>$dialogo,'editado'=>1), array('id'=>$id));
        return rest_ensure_response(array('sucesso'=>true));
    }

}