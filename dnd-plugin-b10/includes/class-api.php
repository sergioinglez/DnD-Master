<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_API {

    public static function registrar_rotas() {
        $ns = 'dnd-master/v1';

        // Usuário atual
        register_rest_route($ns, '/usuario', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'get_usuario'), 'permission_callback' => '__return_true'),
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
    public static function check_login() { return is_user_logged_in(); }
    public static function check_mestre() { return is_user_logged_in() && DNDM_Auth::is_mestre(); }

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


}
