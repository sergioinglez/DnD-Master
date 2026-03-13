<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * DNDM_LP_Editor — Editor completo da Landing Page
 * Config salva como JSON em dndm_lp_config
 */
class DNDM_LP_Editor {

    // Config padrão completa
    public static function get_defaults() {
        return array(
            'hero' => array(
                'titulo'        => 'DnD Master',
                'subtitulo'     => 'DUNGEONS & DRAGONS 5E — PLATAFORMA FAMILIAR',
                'desc'          => 'Uma plataforma épica para mestres e jogadores de D&D. O Mestre narra, os jogadores decidem, e a IA cuida do resto.',
                'cta_texto'     => '⚔ ENTRAR NA AVENTURA',
                'cta_subtexto'  => 'Use suas credenciais fornecidas pelo Mestre',
                'bg_tipo'       => 'cor',        // 'cor' | 'imagem'
                'bg_cor'        => '#0a0704',
                'bg_imagem'     => '',
                'bg_overlay'    => 70,           // 0–100
            ),
            'features' => array(
                array( 'icone' => '🎭', 'titulo' => 'Narrativa Viva',       'desc' => 'O Mestre conduz. Os jogadores escolhem. Cada decisão, item e batalha fica registrado em tempo real.' ),
                array( 'icone' => '⚔',  'titulo' => 'D&D 5e Completo',     'desc' => 'Fichas com atributos, HP, condições, inventário, XP e level up — tudo calculado automaticamente.' ),
                array( 'icone' => '🎨', 'titulo' => 'Arte Gerada por IA',   'desc' => 'Personagens, monstros, itens e cenas ganham vida visual ao importar o módulo.' ),
                array( 'icone' => '🧙', 'titulo' => 'Ferramentas do Mestre','desc' => 'Ganchos narrativos, checklist de objetivos, alertas de desvio e gerador de NPCs com IA.' ),
                array( 'icone' => '📜', 'titulo' => 'Módulos de Aventura',  'desc' => 'Importe aventuras em JSON. Mapas, monstros e missões carregados em um clique.' ),
                array( 'icone' => '👨‍👩‍👧', 'titulo' => 'Feito para Família', 'desc' => 'Interface em português, fluxo simples e visual épico. Mesmo iniciantes conseguem jogar.' ),
            ),
            'faq' => array(
                array( 'p' => 'Preciso saber D&D para jogar?',           'r' => 'Não! O sistema foi pensado para iniciantes. O Mestre conduz e o sistema cuida das regras.' ),
                array( 'p' => 'Como funciona o sistema de imagens?',     'r' => 'Cada personagem e cena tem imagem gerada automaticamente pela IA na importação do módulo.' ),
                array( 'p' => 'Posso usar aventuras oficiais de D&D?',   'r' => 'Sim! Envie o PDF ao Claude, que processa e gera um JSON compatível.' ),
                array( 'p' => 'Quantos jogadores simultâneos?',          'r' => 'Até 6 jogadores + o Mestre. Cada um acessa com login próprio.' ),
                array( 'p' => 'O sistema consome créditos do Claude?',   'r' => 'Apenas ganchos, backstory e NPCs usam o Groq (plano gratuito). A sessão em si não consome nada.' ),
            ),
            'modulos_secao' => array(
                'titulo'    => 'Aventuras em Destaque',
                'subtitulo' => 'MÓDULOS DISPONÍVEIS',
                'mostrar'   => true,
                'modo'      => 'dinamico', // 'dinamico' = pega do banco | 'manual' = lista manual
            ),
            'efeitos' => array(
                'chamas_ativo'      => true,
                'chamas_quantidade' => 35,      // 5–100
                'chamas_velocidade' => 16,      // 8–40 segundos
                'chamas_cor'        => '#c9a84c',
                'chamas_tamanho'    => 2,       // 1–5 px
                'chamas_opacidade'  => 70,      // 10–100
            ),
            'tipografia' => array(
                'fonte_titulo' => 'Cinzel Decorative',
                'fonte_corpo'  => 'Crimson Text',
                'fonte_ui'     => 'Cinzel',
                'escala'       => 100,  // 80–130
            ),
            'cores' => array(
                'ouro'      => '#c9a84c',
                'ouro_dim'  => '#8b6914',
                'fundo'     => '#0a0704',
                'texto'     => '#907060',
                'borda'     => '#1e1608',
            ),
            'rodape' => array(
                'cta_titulo'  => 'Pronto(a) para a Aventura?',
                'cta_desc'    => 'Entre com suas credenciais e comece a sua jornada.',
                'mostrar_rodape' => true,
            ),
        );
    }

    public static function get_config() {
        $saved    = get_option( 'dndm_lp_config', '' );
        $defaults = self::get_defaults();
        if ( empty($saved) ) return $defaults;
        $parsed = json_decode( $saved, true );
        if ( ! $parsed ) return $defaults;
        // Merge recursivo para não perder defaults de novas versões
        return array_replace_recursive( $defaults, $parsed );
    }

    public static function save_config( $data ) {
        update_option( 'dndm_lp_config', json_encode( $data ) );
    }

    // Módulos reais do banco para a LP
    public static function get_modulos_lp() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT id, nome, descricao, sistema, dados_json FROM {$wpdb->prefix}dnd_modulos ORDER BY criado_em DESC LIMIT 12"
        );
        $result = array();
        foreach ( $rows as $m ) {
            // 1ª prioridade: mapa/capa enviada manualmente no admin
            $mapas = get_option( 'dndm_mapas_modulo_' . $m->id, array() );
            $capa  = ! empty($mapas[0]['url']) ? $mapas[0]['url'] : '';

            // 2ª prioridade: dados do JSON importado
            if ( ! $capa && ! empty($m->dados_json) ) {
                $dados = json_decode( $m->dados_json, true );
                if ( is_array($dados) ) {
                    if ( ! empty($dados['capa_url']) )
                        $capa = $dados['capa_url'];
                    elseif ( ! empty($dados['chapters']) ) {
                        foreach ( $dados['chapters'] as $cap ) {
                            if ( ! empty($cap['imagem_url']) ) { $capa = $cap['imagem_url']; break; }
                        }
                    }
                    if ( ! $capa && ! empty($dados['npcs']) ) {
                        foreach ( $dados['npcs'] as $npc ) {
                            if ( ! empty($npc['imagem_url']) ) { $capa = $npc['imagem_url']; break; }
                        }
                    }
                    if ( ! $capa && ! empty($dados['monsters']) ) {
                        foreach ( $dados['monsters'] as $mon ) {
                            if ( ! empty($mon['imagem_url']) ) { $capa = $mon['imagem_url']; break; }
                        }
                    }
                }
            }

            // 3ª prioridade: NPC com retrato na tabela dnd_npcs
            if ( ! $capa ) {
                $npc_img = $wpdb->get_var( $wpdb->prepare(
                    "SELECT imagem_url FROM {$wpdb->prefix}dnd_npcs WHERE modulo_id=%d AND imagem_url != '' LIMIT 1",
                    $m->id
                ));
                if ( $npc_img ) $capa = $npc_img;
            }

            $dados_arr = ! empty($m->dados_json) ? ( json_decode($m->dados_json, true) ?: array() ) : array();
            $tagline   = $m->descricao
                ? wp_trim_words( $m->descricao, 20 )
                : ( ! empty($dados_arr['synopsis']) ? wp_trim_words($dados_arr['synopsis'], 20) : '' );

            $result[] = array(
                'id'       => (int) $m->id,
                'nome'     => $m->nome,
                'sistema'  => $m->sistema ?: 'D&D 5E',
                'tagline'  => $tagline,
                'descricao'=> $m->descricao ?: '',
                'synopsis' => ! empty($dados_arr['synopsis']) ? $dados_arr['synopsis'] : '',
                'capa_url' => $capa,
            );
        }
        return $result;
    }
}
