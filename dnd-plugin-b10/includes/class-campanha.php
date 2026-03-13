<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Campanha {

    public static function criar( $dados ) {
        global $wpdb;
        $mestre = DNDM_Auth::get_usuario_dnd();

        $resultado = $wpdb->insert($wpdb->prefix . 'dnd_campanhas', array(
            'nome'      => sanitize_text_field($dados['nome']),
            'modulo_id' => intval($dados['modulo_id'] ?? 0) ?: null,
            'mestre_id' => $mestre->id,
            'status'    => 'ativa',
        ));

        return $resultado ? $wpdb->insert_id : new WP_Error('db_erro', 'Erro ao criar campanha');
    }

    public static function get_campanha( $id ) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, m.nome as modulo_nome FROM {$wpdb->prefix}dnd_campanhas c
             LEFT JOIN {$wpdb->prefix}dnd_modulos m ON c.modulo_id = m.id
             WHERE c.id = %d", $id
        ));
    }

    public static function get_campanhas_mestre() {
        global $wpdb;
        $mestre = DNDM_Auth::get_usuario_dnd();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.nome as modulo_nome,
             (SELECT COUNT(*) FROM {$wpdb->prefix}dnd_personagens WHERE campanha_id = c.id) as total_jogadores
             FROM {$wpdb->prefix}dnd_campanhas c
             LEFT JOIN {$wpdb->prefix}dnd_modulos m ON c.modulo_id = m.id
             WHERE c.mestre_id = %d ORDER BY c.criado_em DESC", $mestre->id
        ));
    }

    public static function get_painel_mestre( $campanha_id ) {
        global $wpdb;

        $campanha    = self::get_campanha($campanha_id);
        $personagens = array();

        foreach (DNDM_Database::get_personagens_campanha($campanha_id) as $p) {
            $personagens[] = DNDM_Personagem::get_ficha_completa($p->id);
        }

        $checklist_obrig = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_checklist
             WHERE (campanha_id = %d OR modulo_id = %d) AND tipo = 'obrigatoria'
             ORDER BY ordem", $campanha_id, $campanha->modulo_id ?? 0
        ));

        $checklist_secund = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_checklist
             WHERE (campanha_id = %d OR modulo_id = %d) AND tipo = 'secundaria'
             ORDER BY ordem", $campanha_id, $campanha->modulo_id ?? 0
        ));

        $acoes_recentes = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.nome as personagem_nome FROM {$wpdb->prefix}dnd_acoes_log l
             LEFT JOIN {$wpdb->prefix}dnd_personagens p ON l.personagem_id = p.id
             WHERE l.campanha_id = %d ORDER BY l.criado_em DESC LIMIT 20", $campanha_id
        ));

        // Alerta de desvio
        $obrig_pendentes = array_filter($checklist_obrig, function($c){ return !$c->concluida; });
        $alerta_desvio   = count($obrig_pendentes) > 0 && count($acoes_recentes) > 5;

        return array(
            'campanha'          => $campanha,
            'modulo_id'         => $campanha ? (int) $campanha->modulo_id : null, // atalho para o JS
            'personagens'       => $personagens,
            'checklist_obrig'   => $checklist_obrig,
            'checklist_secund'  => $checklist_secund,
            'acoes_recentes'    => $acoes_recentes,
            'alerta_desvio'     => $alerta_desvio,
            'obrig_pendentes'   => array_values($obrig_pendentes),
        );
    }

    // Define explicitamente o estado de um item (1 = concluído, 0 = pendente)
    public static function set_checklist( $checklist_id, $valor ) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'dnd_checklist',
            array(
                'concluida'    => $valor ? 1 : 0,
                'concluida_em' => $valor ? current_time('mysql') : null,
            ),
            array( 'id' => $checklist_id )
        );
    }

    // Mantido por compatibilidade — sempre marca como concluído
    public static function concluir_checklist( $checklist_id ) {
        return self::set_checklist( $checklist_id, 1 );
    }

    public static function adicionar_checklist( $campanha_id, $dados ) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'dnd_checklist', array(
            'campanha_id' => $campanha_id,
            'titulo'      => sanitize_text_field($dados['titulo']),
            'descricao'   => sanitize_textarea_field($dados['descricao'] ?? ''),
            'tipo'        => in_array($dados['tipo'], array('obrigatoria','secundaria')) ? $dados['tipo'] : 'obrigatoria',
            'ordem'       => intval($dados['ordem'] ?? 0),
        ));
    }

    public static function registrar_acao( $campanha_id, $tipo, $texto, $personagem_id = null ) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'dnd_acoes_log', array(
            'campanha_id'   => $campanha_id,
            'personagem_id' => $personagem_id,
            'tipo'          => sanitize_text_field($tipo),
            'texto'         => sanitize_textarea_field($texto),
        ));
    }

    /**
     * Importa um módulo a partir de um objeto JSON estruturado.
     *
     * Chaves esperadas no JSON:
     *   nome          string   — título do módulo
     *   synopsis      string   — sinopse (→ dnd_modulos.descricao)
     *   sistema       string   — ex: "D&D 5e"  (→ dnd_modulos.sistema)
     *   chapters      array    — cada item: { id, title, content, image_prompt }
     *   npcs          array    — cada item: { nome, raca, papel, personalidade, segredo, ganchos, prompt_imagem }
     *   checklist     array    — cada item: { titulo, descricao, tipo } (tipo: obrigatoria|secundaria)
     *   transition_hook string — gancho de narrativa do final da aventura
     *
     * Tudo mais (encounters, traps, rewards…) é preservado em dnd_modulos.conteudo como JSON.
     */
    /**
     * Gera lore de monstro via Groq (se não vier no JSON).
     */
    private static function gerar_lore_monstro( $nome, $tipo ) {
        if ( ! class_exists('DNDM_Groq') ) return '';
        $groq_key = get_option( 'dndm_groq_key', '' );
        if ( empty($groq_key) ) return '';

        $system = "Você é um escritor de RPG. Crie lore conciso e atmosférico em português brasileiro. Responda APENAS com um parágrafo de 2-3 frases, sem introdução.";
        $user   = "Crie um lore curto e evocativo para este monstro/criatura de D&D 5e:\nNome: {$nome}\nTipo: {$tipo}\nEscreva 2-3 frases sobre origem, motivação ou comportamento, em tom dark fantasy.";

        $resultado = DNDM_Groq::chamar( $system, $user, 150, 15 );
        if ( is_wp_error($resultado) || ! is_string($resultado) ) return '';
        return trim( strip_tags($resultado) );
    }

    /**
     * Importa um módulo de história a partir de JSON estruturado.
     * Gera automaticamente:
     *   - Imagem de cada cena (chapters[].image_prompt → chapters[].imagem_url)
     *   - Imagem + lore de cada NPC   (npcs[].prompt_imagem, npcs[].lore)
     *   - Imagem + lore de cada monstro (monsters[].prompt_imagem, monsters[].lore)
     */
    public static function importar_modulo_json( $json, $nome ) {
        global $wpdb;

        if ( empty($json) || ! is_array($json) ) {
            return new WP_Error('json_invalido', 'JSON vazio ou estrutura incompatível');
        }

        $tem_imagem = class_exists('DNDM_Imagem');

        // ── 1. Imagens das CENAS ────────────────────────────────────────────
        $chapters = $json['chapters'] ?? array();
        foreach ( $chapters as $i => $cap ) {
            // Pula se já tem imagem salva
            if ( ! empty($cap['imagem_url']) ) continue;

            $prompt = $cap['image_prompt'] ?? $cap['prompt_imagem'] ?? '';
            if ( $prompt && $tem_imagem ) {
                $url = DNDM_Imagem::gerar_imagem_cena( $prompt );
                if ( $url ) {
                    $json['chapters'][ $i ]['imagem_url'] = $url;
                }
            }
        }

        // ── 2. Imagens + lore dos MONSTROS ─────────────────────────────────
        $monsters = $json['monsters'] ?? $json['monstros'] ?? array();
        foreach ( $monsters as $mi => $mon ) {
            $nome_mon = $mon['name'] ?? $mon['nome'] ?? 'Monstro';
            $tipo_mon = $mon['type'] ?? $mon['tipo'] ?? '';

            // Lore: gerar via Groq se ausente
            if ( empty($mon['lore']) ) {
                $lore = self::gerar_lore_monstro( $nome_mon, $tipo_mon );
                if ( $lore ) {
                    $json['monsters'][ $mi ]['lore'] = $lore;
                    if ( isset($json['monstros'][ $mi ]) ) $json['monstros'][ $mi ]['lore'] = $lore;
                }
            }

            // Imagem: gerar via Pollinations se ausente
            if ( empty($mon['imagem_url']) ) {
                $prompt = $mon['prompt_imagem'] ?? $mon['image_prompt'] ?? '';
                if ( ! $prompt ) {
                    // Constrói prompt automático
                    $prompt = "D&D 5e {$nome_mon}, {$tipo_mon}, fantasy creature portrait, dark fantasy, dramatic lighting, detailed illustration";
                }
                if ( $tem_imagem ) {
                    $url = DNDM_Imagem::gerar_de_descricao( $prompt, sanitize_title($nome_mon), 'monsters' );
                    if ( $url ) {
                        $json['monsters'][ $mi ]['imagem_url'] = $url;
                        if ( isset($json['monstros'][ $mi ]) ) $json['monstros'][ $mi ]['imagem_url'] = $url;
                    }
                }
            }
        }
        // Garante que a chave sempre seja "monsters" no JSON salvo
        if ( isset($json['monstros']) && ! isset($json['monsters']) ) {
            $json['monsters'] = $json['monstros'];
            unset($json['monstros']);
        }

        // ── 3. Salvar módulo principal ──────────────────────────────────────
        $resultado = $wpdb->insert(
            $wpdb->prefix . 'dnd_modulos',
            array(
                'nome'      => sanitize_text_field( $nome ),
                'descricao' => sanitize_textarea_field( $json['synopsis'] ?? $json['descricao'] ?? '' ),
                'sistema'   => sanitize_text_field( $json['sistema'] ?? 'D&D 5e' ),
                'conteudo'  => wp_json_encode( $json, JSON_UNESCAPED_UNICODE ),
            )
        );

        if ( ! $resultado ) {
            return new WP_Error('db_erro', 'Erro ao inserir módulo: ' . $wpdb->last_error);
        }

        $modulo_id = $wpdb->insert_id;

        // ── 4. Capítulos → checklist obrigatória ────────────────────────────
        foreach ( $chapters as $i => $cap ) {
            $titulo = sanitize_text_field( $cap['title'] ?? $cap['titulo'] ?? "Capítulo " . ($i + 1) );
            $desc   = sanitize_textarea_field( $cap['content'] ?? $cap['conteudo'] ?? '' );
            $wpdb->insert(
                $wpdb->prefix . 'dnd_checklist',
                array(
                    'modulo_id'  => $modulo_id,
                    'titulo'     => $titulo,
                    'descricao'  => wp_trim_words( $desc, 40, '…' ),
                    'tipo'       => 'obrigatoria',
                    'ordem'      => intval( $cap['id'] ?? $i ),
                )
            );
        }

        // ── 5. Checklist avulsa (opcional) ──────────────────────────────────
        $checklist = $json['checklist'] ?? array();
        foreach ( $checklist as $i => $item ) {
            $tipo = in_array( $item['tipo'] ?? '', array('obrigatoria','secundaria') )
                        ? $item['tipo'] : 'secundaria';
            $wpdb->insert(
                $wpdb->prefix . 'dnd_checklist',
                array(
                    'modulo_id'  => $modulo_id,
                    'titulo'     => sanitize_text_field( $item['titulo'] ?? '' ),
                    'descricao'  => sanitize_textarea_field( $item['descricao'] ?? '' ),
                    'tipo'       => $tipo,
                    'ordem'      => 100 + $i,
                )
            );
        }

        // ── 6. NPCs (com imagem + lore opcionais) ──────────────────────────
        $npcs = $json['npcs'] ?? array();
        foreach ( $npcs as $ni => $npc ) {
            $nome_npc = sanitize_text_field( $npc['nome'] ?? $npc['name'] ?? 'NPC' );

            // Lore: gerar via Groq se ausente
            $lore_npc = $npc['lore'] ?? '';
            if ( empty($lore_npc) && class_exists('DNDM_Groq') ) {
                $groq_key = get_option('dndm_groq_key', '');
                if ( ! empty($groq_key) ) {
                    $papel = $npc['papel'] ?? $npc['role'] ?? '';
                    $sys   = "Você é um escritor de RPG. Responda APENAS com um parágrafo curto, sem introdução.";
                    $usr   = "Crie um lore breve e evocativo em português para este NPC de D&D 5e:\nNome: {$nome_npc}\nPapel: {$papel}\nPersonalidade: ".($npc['personalidade']??'')."\n2-3 frases sobre origem e motivação.";
                    $res   = DNDM_Groq::chamar( $sys, $usr, 120, 15 );
                    if ( ! is_wp_error($res) && is_string($res) ) {
                        $lore_npc = trim( strip_tags($res) );
                        $json['npcs'][$ni]['lore'] = $lore_npc;
                    }
                }
            }

            // Imagem
            $prompt = sanitize_textarea_field( $npc['prompt_imagem'] ?? $npc['image_prompt'] ?? '' );
            $img    = $npc['imagem_url'] ?? '';
            if ( empty($img) ) {
                if ( ! $prompt ) {
                    $raca  = $npc['raca'] ?? $npc['race'] ?? '';
                    $papel = $npc['papel'] ?? $npc['role'] ?? '';
                    $prompt = "D&D 5e NPC portrait, {$nome_npc}, {$raca} {$papel}, fantasy character, detailed illustration, dark fantasy style";
                }
                if ( $prompt && $tem_imagem ) {
                    $img = DNDM_Imagem::gerar_de_descricao( $prompt, sanitize_title($nome_npc), 'npcs' );
                    if ( $img ) $json['npcs'][$ni]['imagem_url'] = $img;
                }
            }

            $wpdb->insert(
                $wpdb->prefix . 'dnd_npcs',
                array(
                    'modulo_id'     => $modulo_id,
                    'nome'          => $nome_npc,
                    'raca'          => sanitize_text_field( $npc['raca'] ?? $npc['race'] ?? '' ),
                    'papel'         => sanitize_text_field( $npc['papel'] ?? $npc['role'] ?? '' ),
                    'personalidade' => sanitize_textarea_field( $npc['personalidade'] ?? $npc['personality'] ?? '' ),
                    'segredo'       => sanitize_textarea_field( $npc['segredo'] ?? $npc['secret'] ?? '' ),
                    'ganchos'       => sanitize_textarea_field( $npc['ganchos'] ?? $npc['hooks'] ?? '' ),
                    'lore'          => sanitize_textarea_field( $lore_npc ),
                    'prompt_imagem' => $prompt,
                    'imagem_url'    => sanitize_url( $img ),
                )
            );
        }

        // Atualiza JSON salvo com imagens/lore gerados
        $wpdb->update(
            $wpdb->prefix . 'dnd_modulos',
            array( 'conteudo' => wp_json_encode($json, JSON_UNESCAPED_UNICODE) ),
            array( 'id' => $modulo_id )
        );

        // ── 7. Gancho de transição ──────────────────────────────────────────
        $hook = $json['transition_hook'] ?? $json['gancho_transicao'] ?? '';
        if ( $hook ) {
            $wpdb->insert(
                $wpdb->prefix . 'dnd_ganchos',
                array(
                    'modelo'   => 'modulo_importado',
                    'titulo'   => sanitize_text_field( 'Gancho Final — ' . $nome ),
                    'conteudo' => sanitize_textarea_field( $hook ),
                    'usado'    => 0,
                )
            );
        }

        return $modulo_id;
    }

    public static function importar_modulo_markdown( $conteudo_md, $nome_modulo ) {
        global $wpdb;

        // Salva o módulo
        $wpdb->insert($wpdb->prefix . 'dnd_modulos', array(
            'nome'      => sanitize_text_field($nome_modulo),
            'conteudo'  => $conteudo_md,
            'sistema'   => 'dnd5e',
        ));
        $modulo_id = $wpdb->insert_id;

        // Extrai checklist obrigatória (linhas com [OBRIG])
        preg_match_all('/\[OBRIG\]\s*(.+)/m', $conteudo_md, $obrig_matches);
        foreach ($obrig_matches[1] as $i => $titulo) {
            $wpdb->insert($wpdb->prefix . 'dnd_checklist', array(
                'modulo_id' => $modulo_id,
                'titulo'    => sanitize_text_field(trim($titulo)),
                'tipo'      => 'obrigatoria',
                'ordem'     => $i,
            ));
        }

        // Extrai checklist secundária (linhas com [SEC])
        preg_match_all('/\[SEC\]\s*(.+)/m', $conteudo_md, $sec_matches);
        foreach ($sec_matches[1] as $i => $titulo) {
            $wpdb->insert($wpdb->prefix . 'dnd_checklist', array(
                'modulo_id' => $modulo_id,
                'titulo'    => sanitize_text_field(trim($titulo)),
                'tipo'      => 'secundaria',
                'ordem'     => $i,
            ));
        }

        return $modulo_id;
    }
}
