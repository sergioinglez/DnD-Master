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
        $config    = self::get_config();
        $modo      = $config['modulos_secao']['modo'] ?? 'dinamico';
        $destaques = $config['modulos_secao']['destaques'] ?? array(); // IDs manuais

        $result = array();

        // ── Solo Adventures ──────────────────────────────────────────────────
        $solos = $wpdb->get_results(
            "SELECT id, nome, synopsis, nivel, capa_url, 'solo' as tipo FROM {$wpdb->prefix}dnd_solo_aventuras
             WHERE status='ativa' ORDER BY criado_em DESC LIMIT 6"
        );
        foreach ( $solos as $s ) {
            // Verifica se tem destaque manual configurado
            $dest = null;
            foreach ( $destaques as $d ) {
                if ( isset($d['tipo']) && $d['tipo']==='solo' && (int)$d['id']===(int)$s->id ) { $dest=$d; break; }
            }
            $result[] = array(
                'id'       => (int)$s->id,
                'tipo'     => 'solo',
                'nome'     => $dest['nome_custom'] ?? $s->nome,
                'sistema'  => $s->nivel ?: 'D&D 5E Solo',
                'tagline'  => $dest['tagline'] ?? wp_trim_words($s->synopsis??'',18),
                'descricao'=> $dest['descricao'] ?? ($s->synopsis??''),
                'capa_url' => $dest['capa_url'] ?? $s->capa_url ?? '',
            );
        }

        // ── Módulos de Campanha ───────────────────────────────────────────────
        $rows = $wpdb->get_results(
            "SELECT id, nome, descricao, sistema, dados_json FROM {$wpdb->prefix}dnd_modulos ORDER BY criado_em DESC LIMIT 6"
        );
        foreach ( $rows as $m ) {
            $dest = null;
            foreach ( $destaques as $d ) {
                if ( isset($d['tipo']) && $d['tipo']==='modulo' && (int)$d['id']===(int)$m->id ) { $dest=$d; break; }
            }
            $mapas = get_option( 'dndm_mapas_modulo_' . $m->id, array() );
            $capa  = $dest['capa_url'] ?? (! empty($mapas[0]['url']) ? $mapas[0]['url'] : '');
            if ( ! $capa && ! empty($m->dados_json) ) {
                $dados = json_decode( $m->dados_json, true );
                if ( is_array($dados) ) {
                    $capa = $dados['capa_url'] ?? '';
                }
            }
            $dados_arr = ! empty($m->dados_json) ? ( json_decode($m->dados_json, true) ?: array() ) : array();
            $tagline   = $dest['tagline'] ?? ($m->descricao ? wp_trim_words($m->descricao,18) : wp_trim_words($dados_arr['synopsis']??'',18));
            $result[] = array(
                'id'       => (int)$m->id,
                'tipo'     => 'modulo',
                'nome'     => $dest['nome_custom'] ?? $m->nome,
                'sistema'  => $m->sistema ?: 'D&D 5E',
                'tagline'  => $tagline,
                'descricao'=> $dest['descricao'] ?? ($m->descricao??''),
                'capa_url' => $capa,
            );
        }

        // Se tem destaques manuais configurados, ordena por eles
        if ( !empty($destaques) && $modo === 'manual' ) {
            $ordenado = array();
            foreach ( $destaques as $d ) {
                foreach ( $result as $item ) {
                    if ( $item['tipo']===$d['tipo'] && $item['id']===(int)$d['id'] ) {
                        $ordenado[] = $item; break;
                    }
                }
            }
            if ( !empty($ordenado) ) return $ordenado;
        }

        return $result;
    }
}
