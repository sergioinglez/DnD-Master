<?php
/**
 * DNDM_Achievements — Sistema de Conquistas (Achievement Badges)
 * Catálogo, registro, gatilhos automáticos e helpers.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Achievements {

    // ── Catálogo completo de badges ───────────────────────────────────────────
    public static function catalogo() {
        return array(

            // ── Iniciação de Classe (Prata) ──────────────────────────────────
            'class_initiation_barbaro'   => array(
                'titulo'    => 'Fúria Incontrolável',
                'descricao' => 'Sua raiva é uma força da natureza que ninguém pode domar.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'barbaro',
            ),
            'class_initiation_bardo'     => array(
                'titulo'    => 'Voz das Lendas',
                'descricao' => 'Sua música ecoará pelas eras, inspirando heróis e vilões.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'bardo',
            ),
            'class_initiation_bruxo'     => array(
                'titulo'    => 'Pacto de Sangue',
                'descricao' => 'Você barganhou com o desconhecido por um poder proibido.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'bruxo',
            ),
            'class_initiation_clerigo'   => array(
                'titulo'    => 'Luz do Alvorecer',
                'descricao' => 'Um farol de fé e cura em um mundo de sombras.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'clerigo',
            ),
            'class_initiation_druida'    => array(
                'titulo'    => 'Guardião do Equilíbrio',
                'descricao' => 'A natureza fala através de você e o mundo selvagem obedece.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'druida',
            ),
            'class_initiation_feiticeiro' => array(
                'titulo'    => 'Poder Inato',
                'descricao' => 'A magia não é algo que você estuda, é o que você é.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'feiticeiro',
            ),
            'class_initiation_guerreiro' => array(
                'titulo'    => 'Mestre de Armas',
                'descricao' => 'Aço e disciplina são as únicas leis que você reconhece.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'guerreiro',
            ),
            'class_initiation_ladino'    => array(
                'titulo'    => 'Sombra Indetectável',
                'descricao' => 'Você é o sussurro no escuro e o passo que ninguém ouve.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'ladino',
            ),
            'class_initiation_mago'      => array(
                'titulo'    => 'Sábio do Arcano',
                'descricao' => 'Anos de estudo revelaram os segredos da trama da realidade.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'mago',
            ),
            'class_initiation_monge'     => array(
                'titulo'    => 'Equilíbrio Espiritual',
                'descricao' => 'Seu corpo e mente são uma única arma perfeita e letal.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'monge',
            ),
            'class_initiation_paladino'  => array(
                'titulo'    => 'Juramento Eterno',
                'descricao' => 'Sua honra é seu escudo e sua justiça é implacável.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'paladino',
            ),
            'class_initiation_ranger'    => array(
                'titulo'    => 'Rastreador Implacável',
                'descricao' => 'Nenhuma presa escapa aos seus olhos no horizonte selvagem.',
                'raridade'  => 'prata',
                'categoria' => 'classe',
                'icone'     => 'ranger',
            ),

            // ── Combate e Destino ────────────────────────────────────────────
            'first_blood'   => array(
                'titulo'    => 'Primeiro Sangue',
                'descricao' => 'A hesitação acabou. Você provou o sabor da vitória.',
                'raridade'  => 'bronze',
                'categoria' => 'combate',
                'icone'     => 'first_blood',
            ),
            'goblin_slayer' => array(
                'titulo'    => 'Exterminador de Goblins',
                'descricao' => 'O clã Dentefino aprendeu a temer seu nome. 10 abatidos.',
                'raridade'  => 'prata',
                'categoria' => 'combate',
                'icone'     => 'goblin_slayer',
            ),
            'natural_20'    => array(
                'titulo'    => 'Sorte de Herói',
                'descricao' => 'O destino sorriu. Um 20 natural em um momento crucial.',
                'raridade'  => 'ouro',
                'categoria' => 'combate',
                'icone'     => 'natural_20',
            ),
            'dice_curse'    => array(
                'titulo'    => 'Maldição dos Dados',
                'descricao' => 'O destino é cruel. Você tirou um 1 natural... e sobreviveu.',
                'raridade'  => 'bronze',
                'categoria' => 'combate',
                'icone'     => 'dice_curse',
            ),
            'survivor'      => array(
                'titulo'    => 'No Limite da Morte',
                'descricao' => 'Venceu o combate com menos de 5% de vida restante.',
                'raridade'  => 'ouro',
                'categoria' => 'combate',
                'icone'     => 'survivor',
            ),

            // ── Progressão de Conta (Ouro) ───────────────────────────────────
            'first_character' => array(
                'titulo'    => 'O Despertar',
                'descricao' => 'O primeiro passo de uma jornada lendária. Personagem criado.',
                'raridade'  => 'ouro',
                'categoria' => 'progressao',
                'icone'     => 'first_character',
            ),
            'master_of_arts'  => array(
                'titulo'    => 'Mestre de Todas as Artes',
                'descricao' => 'Versatilidade é sua força. Dominou 5 classes diferentes.',
                'raridade'  => 'ouro',
                'categoria' => 'progressao',
                'icone'     => 'master_of_arts',
            ),
        );

        /**
         * Filtro para DLCs adicionarem badges extras ao catálogo.
         *
         * Uso em um DLC cosmético:
         *   add_filter('dndm_badges_catalogo', function($catalogo) {
         *       $catalogo['meu_badge'] = [
         *           'titulo'    => 'Meu Badge',
         *           'descricao' => 'Descrição',
         *           'raridade'  => 'ouro',  // bronze | prata | ouro
         *           'categoria' => 'dlc',
         *           'icone'     => 'meu_badge',
         *       ];
         *       return $catalogo;
         *   });
         */
        return apply_filters( 'dndm_badges_catalogo', $catalogo );
    }

    // ── Registrar uma conquista ───────────────────────────────────────────────
    /**
     * @param int    $user_id      ID do usuário WordPress
     * @param string $badge_slug   Slug da conquista
     * @param int    $char_id      ID do personagem (opcional)
     * @param string $aventura     Nome da aventura/ação (opcional)
     * @return bool true se nova conquista, false se já tinha
     */
    public static function award( $user_id, $badge_slug, $char_id = null, $aventura = '' ) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'dnd_achievements';

        // Verifica se já conquistou
        $existe = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$tabela} WHERE user_id=%d AND badge_slug=%s",
            $user_id, $badge_slug
        ));

        if ( $existe ) return false;

        // Registra
        $wpdb->insert( $tabela, array(
            'user_id'        => $user_id,
            'char_id'        => $char_id ?: null,
            'badge_slug'     => $badge_slug,
            'aventura_nome'  => $aventura ?: self::get_aventura_ativa( $user_id ),
            'conquistado_em' => current_time( 'mysql' ),
        ));

        do_action( 'dndm_achievement_awarded', $user_id, $badge_slug, $char_id );
        return true;
    }

    // Alias público para uso externo
    public static function conceder( $user_id, $badge_slug, $char_id = null, $aventura = '' ) {
        return self::award( $user_id, $badge_slug, $char_id, $aventura );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private static function get_aventura_ativa( $user_id ) {
        global $wpdb;
        $camp_id = (int) get_user_meta( $user_id, 'dndm_campanha_ativa', true );
        if ( ! $camp_id ) return '';
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT nome FROM {$wpdb->prefix}dnd_campanhas WHERE id=%d", $camp_id
        )) ?: '';
    }

    public static function get_dnd_user_id( $wp_user_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_usuarios WHERE wp_user_id=%d", $wp_user_id
        ));
    }

    public static function get_conquistas( $user_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT badge_slug, char_id, conquistado_em, aventura_nome
             FROM {$wpdb->prefix}dnd_achievements
             WHERE user_id=%d ORDER BY conquistado_em ASC",
            $user_id
        ));
    }

    public static function count_conquistas( $user_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_achievements WHERE user_id=%d", $user_id
        ));
    }

    // ── Gatilhos automáticos ─────────────────────────────────────────────────

    /**
     * Disparado após criar personagem.
     * Gatilhos: first_character, class_initiation_*, master_of_arts
     */
    public static function on_personagem_criado( $personagem_id, $user_id, $classe ) {
        global $wpdb;
        $wp_user_id = $user_id; // já é wp_user_id aqui

        // Normaliza classe para slug
        $slug_map = array(
            'Bárbaro'    => 'barbaro',
            'Bardo'      => 'bardo',
            'Bruxo'      => 'bruxo',
            'Clérigo'    => 'clerigo',
            'Druida'     => 'druida',
            'Feiticeiro' => 'feiticeiro',
            'Guerreiro'  => 'guerreiro',
            'Ladino'     => 'ladino',
            'Mago'       => 'mago',
            'Monge'      => 'monge',
            'Paladino'   => 'paladino',
            'Patrulheiro'=> 'ranger',
        );
        $classe_slug = $slug_map[ $classe ] ?? strtolower( remove_accents( $classe ) );

        $aventura = self::get_aventura_ativa( $wp_user_id );

        // 1. first_character — só no primeiro personagem da conta
        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_personagens
             JOIN {$wpdb->prefix}dnd_usuarios u ON personagens.usuario_id = u.id
             WHERE u.wp_user_id=%d AND personagens.status='ativo'",
            $wp_user_id
        ));
        // Se total = 1, é o primeiro (acabou de criar)
        if ( $total <= 1 ) {
            self::award( $wp_user_id, 'first_character', $personagem_id, $aventura );
        }

        // 2. class_initiation_*
        if ( $classe_slug ) {
            self::award( $wp_user_id, 'class_initiation_' . $classe_slug, $personagem_id, $aventura );
        }

        // 3. master_of_arts — 5 personagens de classes diferentes
        $classes_distintas = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.classe)
             FROM {$wpdb->prefix}dnd_personagens p
             JOIN {$wpdb->prefix}dnd_usuarios u ON p.usuario_id = u.id
             WHERE u.wp_user_id=%d AND p.status='ativo'",
            $wp_user_id
        ));
        if ( $classes_distintas >= 5 ) {
            self::award( $wp_user_id, 'master_of_arts', null, $aventura );
        }
    }

    /**
     * Disparado ao aplicar dano via DNDM_Personagem::aplicar_dano()
     * Gatilhos: first_blood, survivor
     * Parâmetros: $personagem_id, $dano, $hp_novo, $hp_max, $tipo_alvo
     */
    public static function on_dano_aplicado( $personagem_id, $dano, $hp_novo, $hp_max, $alvo_tipo = 'inimigo' ) {
        if ( $alvo_tipo !== 'inimigo' ) return;

        // Obtém wp_user_id do mestre logado (quem aplicou o dano)
        $wp_user_id = get_current_user_id();
        if ( ! $wp_user_id ) return;

        $aventura = self::get_aventura_ativa( $wp_user_id );

        // first_blood — primeiro inimigo morto (HP chegou a 0)
        // Verificamos o log de kills do personagem
        if ( $hp_novo <= 0 ) {
            global $wpdb;
            $kills = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_acoes_log
                 WHERE personagem_id=%d AND tipo='kill_inimigo'",
                $personagem_id
            ));

            // Registra o kill no log
            $wpdb->insert( $wpdb->prefix . 'dnd_acoes_log', array(
                'personagem_id' => $personagem_id,
                'tipo'          => 'kill_inimigo',
                'texto'         => 'Inimigo abatido em combate.',
            ));

            // Obtém wp_user_id do dono do personagem
            $dono_wp_id = self::get_wp_user_id_do_personagem( $personagem_id );
            if ( $dono_wp_id ) {
                $kills_check = $kills + 1;
                // first_blood = primeiro kill
                if ( $kills_check === 1 ) {
                    self::award( $dono_wp_id, 'first_blood', $personagem_id, $aventura );
                }
            }
        }

        // survivor — HP ≤ 5% do máximo após o dano, mas > 0
        if ( $hp_max > 0 && $hp_novo > 0 && ($hp_novo / $hp_max) <= 0.05 ) {
            $dono_wp_id = self::get_wp_user_id_do_personagem( $personagem_id );
            if ( $dono_wp_id ) {
                self::award( $dono_wp_id, 'survivor', $personagem_id, $aventura );
            }
        }
    }

    /**
     * Disparado ao registrar ação no log com dado (tipo 'dado')
     * Gatilhos: natural_20, dice_curse
     */
    public static function on_rolagem_dado( $personagem_id, $resultado, $wp_user_id = null ) {
        if ( ! $wp_user_id ) $wp_user_id = get_current_user_id();
        if ( ! $wp_user_id ) return;

        global $wpdb;
        $aventura = self::get_aventura_ativa( $wp_user_id );

        if ( $resultado === 20 ) {
            self::award( $wp_user_id, 'natural_20', $personagem_id, $aventura );
        }
        if ( $resultado === 1 ) {
            self::award( $wp_user_id, 'dice_curse', $personagem_id, $aventura );
        }
    }

    /**
     * Verifica goblin_slayer — 10 goblins abatidos no log
     */
    public static function on_kill_registrado( $personagem_id, $nome_inimigo ) {
        global $wpdb;
        if ( stripos($nome_inimigo, 'goblin') === false ) return;

        $wp_user_id = self::get_wp_user_id_do_personagem( $personagem_id );
        if ( ! $wp_user_id ) return;

        $kills_goblin = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dnd_acoes_log
             WHERE personagem_id=%d AND tipo='kill_goblin'",
            $personagem_id
        ));

        $wpdb->insert( $wpdb->prefix . 'dnd_acoes_log', array(
            'personagem_id' => $personagem_id,
            'tipo'          => 'kill_goblin',
            'texto'         => "Goblin abatido: {$nome_inimigo}",
        ));

        if ( $kills_goblin + 1 >= 10 ) {
            $aventura = self::get_aventura_ativa( $wp_user_id );
            self::award( $wp_user_id, 'goblin_slayer', $personagem_id, $aventura );
        }
    }

    // ── Helpers privados ─────────────────────────────────────────────────────
    private static function get_wp_user_id_do_personagem( $personagem_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT u.wp_user_id
             FROM {$wpdb->prefix}dnd_personagens p
             JOIN {$wpdb->prefix}dnd_usuarios u ON p.usuario_id = u.id
             WHERE p.id=%d",
            $personagem_id
        )) ?: null;
    }
}

// Alias global para uso simples
function dndm_award_achievement( $user_id, $badge_slug, $char_id = null, $aventura = '' ) {
    return DNDM_Achievements::award( $user_id, $badge_slug, $char_id, $aventura );
}
