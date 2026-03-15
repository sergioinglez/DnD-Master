<?php
/**
 * DNDM_Auth — Autenticação e sistema de tiers.
 *
 * Tiers:
 *   admin  → Admin WP — poder pleno (único com acesso ao wp-admin)
 *   tier1  → Pode mestrar, jogar, criar usuários (t2/t3), gerenciar módulos
 *   tier2  → Pode mestrar, jogar, criar usuários (t3 apenas)
 *   tier3  → Pode jogar
 *
 * Regra de ouro: admin WP = tier 'admin' automaticamente.
 * Tier é armazenado em dnd_usuarios.tier.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Auth {

    private static $saudacoes = array(
        'Bárbaro'    => 'Que os deuses da guerra olhem por você, Bárbaro!',
        'Bardo'      => 'Que suas histórias ecoem pelos séculos, Bardo!',
        'Bruxo'      => 'Seu patrono aguarda, Bruxo. Que o pacto seja cumprido.',
        'Clérigo'    => 'A luz divina guia seus passos, Clérigo.',
        'Druida'     => 'A natureza sussurra seu nome, Druida.',
        'Feiticeiro' => 'A magia corre em seu sangue, Feiticeiro(a).',
        'Guerreiro'  => 'Honra e aço. Bem-vindo(a) de volta, Guerreiro(a).',
        'Ladino'     => 'Silêncio e sombras. A jornada continua, Ladino(a).',
        'Mago'       => 'Os arcanos revelam novos segredos, Mago(a).',
        'Monge'      => 'O equilíbrio é sua força, Monge.',
        'Paladino'   => 'Seu juramento ecoa nas pedras, Paladino(a).',
        'Patrulheiro'=> 'A floresta conhece seu rastro, Patrulheiro(a).',
    );

    public static function init() {
        add_action( 'user_register', array( __CLASS__, 'registrar_usuario_dnd' ) );
    }

    // ── TIER ─────────────────────────────────────────────────────────────────

    /**
     * Retorna o tier do usuário: 'admin'|'tier1'|'tier2'|'tier3'
     * Admin WP sempre retorna 'admin' independente do banco.
     */
    public static function get_tier( $wp_user_id = null ) {
        if ( ! $wp_user_id ) $wp_user_id = get_current_user_id();
        if ( ! $wp_user_id ) return null;

        if ( user_can( $wp_user_id, 'manage_options' ) ) return 'admin';

        $dnd = self::get_usuario_dnd( $wp_user_id );
        return $dnd->tier ?? 'tier3';
    }

    public static function can_mestrar( $wp_user_id = null ) {
        $t = self::get_tier( $wp_user_id );
        return in_array( $t, array('admin','tier1','tier2') );
    }

    public static function can_jogar( $wp_user_id = null ) {
        return self::get_tier( $wp_user_id ) !== null; // todos
    }

    public static function can_gerenciar_modulos( $wp_user_id = null ) {
        $t = self::get_tier( $wp_user_id );
        return in_array( $t, array('admin','tier1') );
    }

    /**
     * Verifica se $criador pode criar usuário com tier $target_tier.
     */
    public static function can_criar_usuario( $criador_wp_id, $target_tier ) {
        $t = self::get_tier( $criador_wp_id );
        if ( $t === 'admin'  ) return true;
        if ( $t === 'tier1'  ) return in_array( $target_tier, array('tier1','tier2','tier3') );
        if ( $t === 'tier2'  ) return $target_tier === 'tier3';
        return false;
    }

    // ── REGISTRO ─────────────────────────────────────────────────────────────

    public static function registrar_usuario_dnd( $wp_user_id ) {
        global $wpdb;

        $existe = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_usuarios WHERE wp_user_id = %d", $wp_user_id
        ));
        if ( $existe ) return;

        $tier  = user_can( $wp_user_id, 'manage_options' ) ? 'admin' : 'tier3';
        $papel = $tier === 'admin' ? 'mestre' : 'jogador';

        $wpdb->insert( $wpdb->prefix . 'dnd_usuarios', array(
            'wp_user_id' => $wp_user_id,
            'papel'      => $papel,
            'tier'       => $tier,
        ));
    }

    // ── GET USUÁRIO DnD ───────────────────────────────────────────────────────

    public static function get_usuario_dnd( $wp_user_id = null ) {
        global $wpdb;
        if ( ! $wp_user_id ) $wp_user_id = get_current_user_id();
        if ( ! $wp_user_id ) return null;

        $u = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_usuarios WHERE wp_user_id = %d", $wp_user_id
        ));

        if ( ! $u ) {
            self::registrar_usuario_dnd( $wp_user_id );
            return self::get_usuario_dnd( $wp_user_id );
        }

        // Garante que admin WP sempre tem tier 'admin' no objeto
        if ( user_can( $wp_user_id, 'manage_options' ) && $u->tier !== 'admin' ) {
            $wpdb->update( $wpdb->prefix . 'dnd_usuarios', array('tier'=>'admin','papel'=>'mestre'), array('wp_user_id'=>$wp_user_id) );
            $u->tier  = 'admin';
            $u->papel = 'mestre';
        }

        return $u;
    }

    // ── CRIAR USUÁRIO ─────────────────────────────────────────────────────────

    /**
     * Cria usuário com tier. Valida hierarquia de criação.
     *
     * @param string $nome
     * @param string $email
     * @param string $senha  Gerada automaticamente se vazia.
     * @param string $tier   'tier1'|'tier2'|'tier3'
     * @param int    $criado_por_wp_id  WP user ID de quem está criando.
     */
    public static function criar_usuario( $nome, $email, $senha = '', $tier = 'tier3', $criado_por_wp_id = null ) {
        if ( ! $criado_por_wp_id ) $criado_por_wp_id = get_current_user_id();

        if ( ! self::can_criar_usuario( $criado_por_wp_id, $tier ) ) {
            return new WP_Error('sem_permissao', 'Você não tem permissão para criar usuários com este tier.');
        }

        if ( email_exists( $email ) ) {
            return new WP_Error('email_existe', 'Já existe uma conta com este e-mail.');
        }

        if ( empty($senha) ) $senha = wp_generate_password(12, false);

        $username = sanitize_user( explode('@', $email)[0] ) . rand(10,99);
        while ( username_exists($username) ) $username = sanitize_user( explode('@',$email)[0] ) . rand(100,999);

        $wp_user_id = wp_create_user( $username, $senha, sanitize_email($email) );
        if ( is_wp_error($wp_user_id) ) return $wp_user_id;

        wp_update_user( array(
            'ID'           => $wp_user_id,
            'display_name' => sanitize_text_field($nome),
            'first_name'   => sanitize_text_field($nome),
            'role'         => 'subscriber',
        ));

        // Salva tier no banco
        $papel = in_array($tier, array('tier1','tier2')) ? 'mestre' : 'jogador';
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'dnd_usuarios', array(
            'wp_user_id' => $wp_user_id,
            'papel'      => $papel,
            'tier'       => $tier,
        ));

        self::enviar_boas_vindas( $email, $nome, $senha, $tier );

        return array( 'wp_user_id' => $wp_user_id, 'senha_gerada' => $senha );
    }

    // Compat com código legado
    public static function criar_jogador( $nome, $email, $senha = '' ) {
        return self::criar_usuario( $nome, $email, $senha, 'tier3' );
    }

    // ── SAUDAÇÃO ─────────────────────────────────────────────────────────────

    public static function get_saudacao( $wp_user_id = null, $classe = '' ) {
        if ( ! $wp_user_id ) $wp_user_id = get_current_user_id();
        if ( ! empty($classe) && isset(self::$saudacoes[$classe]) ) {
            return self::$saudacoes[$classe];
        }
        $wp_user = get_user_by('ID', $wp_user_id);
        $nome = $wp_user ? $wp_user->display_name : 'Aventureiro(a)';
        return "Bem-vindo(a) de volta, {$nome}!";
    }

    // ── IS_MESTRE (compat) ────────────────────────────────────────────────────

    public static function is_mestre( $wp_user_id = null ) {
        return self::can_mestrar( $wp_user_id );
    }

    // ── LISTAR USUÁRIOS ───────────────────────────────────────────────────────

    public static function listar_usuarios( $para_tier = null ) {
        global $wpdb;

        $users = get_users( array( 'role' => 'subscriber', 'number' => -1, 'orderby' => 'display_name' ) );

        $resultado = array();
        foreach ( $users as $user ) {
            $dnd = self::get_usuario_dnd( $user->ID );
            $tier = $dnd->tier ?? 'tier3';

            // Filtro por tier visível ao solicitante (se informado)
            if ( $para_tier ) {
                if ( $para_tier === 'tier2' && ! in_array($tier, array('tier3')) ) continue;
                if ( $para_tier === 'tier1' && ! in_array($tier, array('tier2','tier3')) ) continue;
            }

            $personagem = $wpdb->get_row( $wpdb->prepare(
                "SELECT nome AS personagem_nome, classe AS personagem_classe, nivel, imagem_url AS imagem
                 FROM {$wpdb->prefix}dnd_personagens
                 WHERE usuario_id = %d AND status = 'ativo' LIMIT 1",
                $dnd->id ?? 0
            ));

            $resultado[] = array(
                'wp_id'           => $user->ID,
                'id'              => $dnd->id ?? null,
                'display_name'    => $user->display_name,   // frontend lê display_name
                'nome'            => $user->display_name,   // alias para compat
                'email'           => $user->user_email,
                'tier'            => $tier,
                'personagem_nome' => $personagem ? $personagem->personagem_nome  : null, // frontend lê personagem_nome
                'personagem'      => $personagem ? $personagem->personagem_nome  : null, // alias
                'personagem_classe'=> $personagem ? $personagem->personagem_classe : null,
                'classe'          => $personagem ? $personagem->personagem_classe : null,
                'nivel'           => $personagem ? $personagem->nivel            : null,
                'imagem'          => $personagem ? $personagem->imagem           : null,
            );
        }

        return $resultado;
    }

    // Compat
    public static function listar_jogadores() { return self::listar_usuarios(); }

    // ── E-MAIL BOAS-VINDAS ────────────────────────────────────────────────────

    private static function enviar_boas_vindas( $email, $nome, $senha, $tier = 'tier3' ) {
        $site = get_bloginfo('name') ?: 'DnD Master';
        $url  = home_url('/');
        $tier_label = array('tier1'=>'Mestre & Jogador (Tier 1)','tier2'=>'Mestre & Jogador (Tier 2)','tier3'=>'Jogador (Tier 3)');

        $assunto  = "[{$site}] Sua conta foi criada — Bem-vindo(a) à aventura!";
        $mensagem = "Saudações, {$nome}!\n\n";
        $mensagem .= "Sua conta foi criada na plataforma {$site}.\n";
        $mensagem .= "Nível de acesso: " . ($tier_label[$tier] ?? $tier) . "\n\n";
        $mensagem .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $mensagem .= "  E-mail: {$email}\n";
        $mensagem .= "  Senha:  {$senha}\n";
        $mensagem .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $mensagem .= "Acesse em: {$url}\n\n";
        $mensagem .= "Que seu destino seja épico! ⚔\n\n— O Mestre de {$site}\n";

        wp_mail( $email, $assunto, $mensagem );
    }
}
