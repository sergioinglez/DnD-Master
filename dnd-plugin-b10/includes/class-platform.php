<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Platform {

    private static $serving = false;

    public static function init() {
        add_action( 'after_setup_theme', array( __CLASS__, 'suprimir_barra_admin' ) );
        add_action( 'init',              array( __CLASS__, 'bloquear_wp_admin'    ) );
        add_filter( 'login_redirect',    array( __CLASS__, 'redirecionar_login'   ), 10, 3 );
        add_action( 'template_redirect', array( __CLASS__, 'servir_paginas'       ) );
        add_filter( 'query_vars',        array( __CLASS__, 'query_vars'           ) );

        // FIX: chamada direta — não aninhada em add_action('init') de dentro do init
        self::registrar_rewrite();

        add_action( 'rest_api_init', function() {
            register_rest_route( 'dnd-master/v1', '/login', array(
                'methods'             => 'POST',
                'callback'            => array( 'DNDM_Platform', 'rest_login' ),
                'permission_callback' => '__return_true',
            ));
            register_rest_route( 'dnd-master/v1', '/logout', array(
                'methods'             => 'POST',
                'callback'            => array( 'DNDM_Platform', 'rest_logout' ),
                'permission_callback' => '__return_true',
            ));
        });
    }

    public static function rest_login( WP_REST_Request $request ) {
        $usuario = sanitize_text_field( $request->get_param('usuario') );
        $senha   = $request->get_param('senha');

        if ( empty($usuario) || empty($senha) ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Preencha usuário e senha.'), 400 );
        }

        $wp_user = is_email($usuario)
            ? get_user_by('email', $usuario)
            : get_user_by('login', $usuario);

        if ( ! $wp_user ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Usuário não encontrado.'), 401 );
        }

        $resultado = wp_signon( array(
            'user_login'    => $wp_user->user_login,
            'user_password' => $senha,
            'remember'      => true,
        ), is_ssl() );

        if ( is_wp_error($resultado) ) {
            return new WP_REST_Response( array('sucesso'=>false,'erro'=>'Senha incorreta.'), 401 );
        }

        return new WP_REST_Response( array(
            'sucesso'  => true,
            'redirect' => home_url('/dnd-painel'),
            'tier'     => DNDM_Auth::get_tier( $resultado->ID ),
            'nome'     => $resultado->display_name,
        ), 200 );
    }

    public static function rest_logout() {
        wp_logout();
        return new WP_REST_Response( array('sucesso'=>true,'redirect'=>home_url()), 200 );
    }

    public static function suprimir_barra_admin() {
        if ( is_user_logged_in() && ! current_user_can('manage_options') ) {
            show_admin_bar(false);
        }
    }

    public static function bloquear_wp_admin() {
        if ( is_admin() && ! defined('DOING_AJAX') && is_user_logged_in() && ! current_user_can('manage_options') ) {
            wp_redirect( home_url('/dnd-painel') );
            exit;
        }
    }

    public static function redirecionar_login( $redirect_to, $request, $user ) {
        if ( is_wp_error($user) ) return $redirect_to;
        return home_url('/dnd-painel');
    }

    public static function registrar_rewrite() {
        add_rewrite_rule( '^dnd-painel/?$',   'index.php?dnd_pagina=painel', 'top' );
        add_rewrite_rule( '^dnd-mestre/?$',   'index.php?dnd_pagina=painel', 'top' );
        add_rewrite_rule( '^dnd-aventura/?$', 'index.php?dnd_pagina=painel', 'top' );
    }

    public static function query_vars( $vars ) {
        $vars[] = 'dnd_pagina';
        return $vars;
    }

    public static function servir_paginas() {
        $pagina = get_query_var('dnd_pagina');

        if ( ! $pagina ) {
            if ( is_front_page() || is_home() ) {
                self::$serving = true;
                self::servir_template('home');
                exit;
            }
            return;
        }

        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url() );
            exit;
        }

        self::$serving = true;
        self::servir_template('painel');
    }

    private static function servir_template( $pagina ) {
        // FIX: null em vez de array() — [] é truthy em JS e quebra lógica do React
        $usuario_data = null;

        if ( is_user_logged_in() ) {
            $wp_user  = wp_get_current_user();
            $dnd_user = DNDM_Auth::get_usuario_dnd();
            $tier     = DNDM_Auth::get_tier( $wp_user->ID );

            global $wpdb;
            $personagem = $dnd_user ? $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dnd_personagens
                 WHERE usuario_id = %d AND status = 'ativo'
                 ORDER BY atualizado_em DESC LIMIT 1",
                $dnd_user->id
            )) : null;

            $campanha_ativa = null;
            if ( $tier === 'admin' || DNDM_Auth::can_mestrar( $wp_user->ID ) ) {
                $campanha_ativa = (int) get_user_meta( $wp_user->ID, 'dndm_campanha_ativa', true ) ?: null;
            }
            if ( ! $campanha_ativa && $personagem && $personagem->campanha_id ) {
                $campanha_ativa = (int) $personagem->campanha_id;
            }

            $usuario_data = array(
                'id'             => $dnd_user->id ?? 0,
                'wp_id'          => $wp_user->ID,
                'nome'           => $wp_user->display_name,
                'email'          => $wp_user->user_email,
                'tier'           => $tier,
                'isAdmin'        => $tier === 'admin',
                'canMestrar'     => DNDM_Auth::can_mestrar( $wp_user->ID ),
                'canModulos'     => DNDM_Auth::can_gerenciar_modulos( $wp_user->ID ),
                'papel'          => $dnd_user->papel ?? 'jogador',
                'isMestre'       => DNDM_Auth::can_mestrar( $wp_user->ID ),
                'campanha_ativa' => $campanha_ativa,
                'adminUrl'       => $tier === 'admin' ? admin_url() : null,
                'saudacao'       => DNDM_Auth::get_saudacao( $wp_user->ID, $personagem->classe ?? '' ),
                'personagem'     => $personagem ? array(
                    'id'       => $personagem->id,
                    'nome'     => $personagem->nome,
                    'classe'   => $personagem->classe,
                    'raca'     => $personagem->raca,
                    'nivel'    => $personagem->nivel,
                    'hp_atual' => $personagem->hp_atual,
                    'hp_max'   => $personagem->hp_max,
                    'imagem'   => $personagem->imagem_url,
                    'xp'       => $personagem->xp,
                    'atributos'=> $personagem->atributos ? json_decode($personagem->atributos, true) : null,
                ) : null,
            );
        }

        $config = array(
            'pagina'    => $pagina,
            'apiUrl'    => rest_url('dnd-master/v1'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'homeUrl'   => home_url(),
            'painelUrl' => home_url('/dnd-painel'),
            'adminUrl'  => admin_url(),
            'loginUrl'  => wp_login_url(),
            'uploadUrl' => wp_upload_dir()['baseurl'],
            'loggedIn'  => is_user_logged_in(),
            'usuario'   => $usuario_data,
            'lp'        => array_merge(
                DNDM_LP_Editor::get_config(),
                array('modulos' => DNDM_LP_Editor::get_modulos_lp())
            ),
            'dlcs'      => array(), // DLCs injetam dados aqui via filtro dndm_config_data
        );

        // Permite que DLCs adicionem dados ao config JSON do frontend
        $config = apply_filters( 'dndm_config_data', $config );

        // Tema ativo pode registrar 'dndm_template_path' no functions.php
        // e fornecer seu próprio template. O plugin usa seu fallback mínimo.
        $template_path = apply_filters(
            'dndm_template_path',
            DNDM_PLUGIN_DIR . 'templates/plataforma.php'
        );

        include $template_path;
        exit;
    }

    public static function ativar() {
        self::registrar_rewrite();
        flush_rewrite_rules();
    }
}
