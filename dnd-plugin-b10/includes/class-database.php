<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Database {

    public static function instalar() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Usuários DnD — agora com tier
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_usuarios (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) NOT NULL,
            papel varchar(20) NOT NULL DEFAULT 'jogador',
            tier varchar(10) NOT NULL DEFAULT 'tier3',
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) $charset;");

        // Módulos
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_modulos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nome varchar(200) NOT NULL,
            descricao text,
            sistema varchar(50) DEFAULT 'dnd5e',
            conteudo longtext,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        // Campanhas — status: rascunho | aberta | em_andamento | encerrada
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_campanhas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nome varchar(200) NOT NULL,
            modulo_id bigint(20),
            mestre_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'rascunho',
            sessao_atual int DEFAULT 0,
            max_jogadores int DEFAULT 6,
            notas longtext,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        // Inscrições em campanhas
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_inscricoes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campanha_id bigint(20) NOT NULL,
            usuario_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'pendente',
            mensagem text,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY campanha_usuario (campanha_id, usuario_id)
        ) $charset;");

        // Personagens
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_personagens (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) NOT NULL,
            campanha_id bigint(20),
            nome varchar(100) NOT NULL,
            raca varchar(50),
            classe varchar(50),
            genero varchar(30),
            nivel int DEFAULT 1,
            xp int DEFAULT 0,
            hp_atual int DEFAULT 0,
            hp_max int DEFAULT 0,
            ca int DEFAULT 10,
            atributos longtext,
            aparencia longtext,
            imagem_url text,
            backstory longtext,
            personalidade text,
            ideal text,
            vinculo text,
            fraqueza text,
            antecedente varchar(100),
            alinhamento varchar(50),
            proficiencias longtext,
            pericias longtext,
            equipamento_inicial longtext,
            status varchar(20) DEFAULT 'ativo',
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            atualizado_em datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_condicoes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            personagem_id bigint(20) NOT NULL,
            tipo varchar(50) NOT NULL,
            descricao text,
            aplicado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_inventario (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            personagem_id bigint(20) NOT NULL,
            nome varchar(200) NOT NULL,
            descricao text,
            tipo varchar(50),
            quantidade int DEFAULT 1,
            peso decimal(5,2) DEFAULT 0,
            valor varchar(20),
            imagem_url text,
            equipado tinyint(1) DEFAULT 0,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_sessoes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campanha_id bigint(20) NOT NULL,
            numero int NOT NULL,
            titulo varchar(200),
            resumo longtext,
            data_sessao date,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_acoes_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sessao_id bigint(20),
            campanha_id bigint(20),
            personagem_id bigint(20),
            tipo varchar(50),
            texto longtext,
            dados_extras longtext,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_npcs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            modulo_id bigint(20),
            campanha_id bigint(20),
            nome varchar(200) NOT NULL,
            raca varchar(50),
            papel varchar(100),
            personalidade text,
            segredo text,
            ganchos text,
            lore text,
            imagem_url text,
            prompt_imagem text,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_checklist (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            modulo_id bigint(20) NOT NULL,
            campanha_id bigint(20),
            titulo varchar(300) NOT NULL,
            descricao text,
            tipo varchar(20) DEFAULT 'obrigatoria',
            concluida tinyint(1) DEFAULT 0,
            concluida_em datetime,
            ordem int DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_ganchos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campanha_id bigint(20),
            modelo varchar(50),
            titulo varchar(200),
            conteudo longtext,
            usado tinyint(1) DEFAULT 0,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        // Conquistas / Achievements
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_achievements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            char_id bigint(20),
            badge_slug varchar(80) NOT NULL,
            aventura_nome varchar(200),
            conquistado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_badge (user_id, badge_slug),
            KEY user_id (user_id)
        ) $charset;");

        // DLCs instalados
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_dlcs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(200) NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'module',
            version varchar(20) NOT NULL DEFAULT '1.0.0',
            author varchar(200),
            status varchar(20) NOT NULL DEFAULT 'ativo',
            ativado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status)
        ) $charset;");

        // ── Mestres Solo — personagens IA que conduzem aventuras solo ────────
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_mestres (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            nome varchar(200) NOT NULL,
            titulo varchar(200),
            persona longtext,
            status varchar(20) DEFAULT 'ativo',
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset;");

        // ── Aventuras Solo ────────────────────────────────────────────────────
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_aventuras (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nome varchar(200) NOT NULL,
            synopsis text,
            duracao varchar(100),
            nivel varchar(50),
            nivel_minimo int DEFAULT 1,
            mestre_id bigint(20) DEFAULT NULL,
            capa_url text,
            capa_prompt text,
            json_content longtext NOT NULL,
            status varchar(20) DEFAULT 'ativa',
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;");

        // Assets de aventura solo — mapas e imagens de cena geradas/uploaded
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_assets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            tipo varchar(20) NOT NULL DEFAULT 'imagem',
            asset_id varchar(100) NOT NULL,
            url text NOT NULL,
            prompt text,
            criado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_tipo_asset (aventura_id, tipo, asset_id)
        ) $charset;");

        // Sessões Solo — progresso por cena, itens, memória, relacionamentos, flags
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_sessoes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            usuario_id bigint(20) NOT NULL,
            personagem_id bigint(20) NOT NULL,
            cena_atual varchar(100) DEFAULT '1',
            cenas_visitadas longtext,
            itens_coletados longtext,
            bonus_ativos longtext,
            tentativas_falhas longtext,
            opcoes_clicadas longtext,
            flags longtext,
            memorias longtext,
            relacionamentos longtext,
            texto_intro longtext,
            status varchar(20) DEFAULT 'em_andamento',
            iniciada_em datetime DEFAULT CURRENT_TIMESTAMP,
            atualizada_em datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            concluida_em datetime,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_usuario (aventura_id, usuario_id)
        ) $charset;");

        // Coleção de cartas colecionáveis por usuário
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_colecao (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            aventura_id bigint(20) NOT NULL,
            card_id varchar(100) NOT NULL,
            coletado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_card (user_id, card_id)
        ) $charset;");

        // ── v0.9.6RC: Conteúdo editável por ato ──────────────────────────────
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_atos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            ato_id varchar(20) NOT NULL,
            titulo varchar(300),
            dialogo longtext,
            imagem_url text,
            imagem_lore text,
            imagem_prompt text,
            npc_ativo varchar(100),
            quebras longtext,
            atualizado_em datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_ato (aventura_id, ato_id)
        ) $charset;");

        // Locais do mapa — lore e imagens por local
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_locais (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            local_id varchar(100) NOT NULL,
            nome varchar(200),
            lore_texto longtext,
            imagens longtext,
            atualizado_em datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_local (aventura_id, local_id)
        ) $charset;");

        // NPCs da aventura — imagem + lore
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_npcs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            npc_id varchar(100) NOT NULL,
            nome varchar(200),
            imagem_url text,
            lore text,
            atualizado_em datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_npc (aventura_id, npc_id)
        ) $charset;");

        // Cache de diálogos de revisita gerados por IA
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_revisita (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            local_id varchar(100) NOT NULL,
            flags_hash varchar(64) NOT NULL,
            dialogo longtext NOT NULL,
            editado tinyint(1) DEFAULT 0,
            gerado_em datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_local_flags (aventura_id, local_id, flags_hash)
        ) $charset;");

        // Lore intro da aventura (aparece na primeira entrada)
        dbDelta("CREATE TABLE {$wpdb->prefix}dnd_solo_intro_lore (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aventura_id bigint(20) NOT NULL,
            titulo varchar(200),
            texto longtext,
            imagens longtext,
            atualizado_em datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY aventura_id (aventura_id)
        ) $charset;");

        update_option( 'dndm_db_version', DNDM_VERSION );
    }

    public static function get_personagem( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_personagens WHERE id = %d", $id
        ));
    }

    public static function get_personagens_campanha( $campanha_id ) {
        global $wpdb;
        // Busca todos vinculados à campanha
        $todos = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.*, u.wp_user_id FROM {$wpdb->prefix}dnd_personagens p
             LEFT JOIN {$wpdb->prefix}dnd_usuarios u ON p.usuario_id = u.id
             WHERE p.campanha_id = %d AND p.status = 'ativo'
             ORDER BY p.criado_em DESC", $campanha_id
        ));

        // Garante apenas 1 personagem por usuario_id (o ativo via meta, ou o mais recente)
        $por_usuario = array();
        foreach ($todos as $p) {
            $uid = (int) $p->usuario_id;
            if (isset($por_usuario[$uid])) continue; // já tem um, pula os extras
            $personagem_ativo_id = $p->wp_user_id
                ? (int) get_user_meta($p->wp_user_id, 'dndm_personagem_ativo', true)
                : 0;
            // Se tem preferência e ela ainda não foi adicionada, marca para priorizar
            if ($personagem_ativo_id && (int)$p->id !== $personagem_ativo_id) {
                $por_usuario[$uid . '_skip'] = true;
                continue;
            }
            $por_usuario[$uid] = $p;
        }
        // Segunda passagem: para usuários com personagem preferido ainda não encontrado,
        // pega qualquer um (pode acontecer se o ativo não estiver na campanha)
        foreach ($todos as $p) {
            $uid = (int) $p->usuario_id;
            if (!isset($por_usuario[$uid])) {
                $por_usuario[$uid] = $p;
            }
        }

        return array_values(array_filter($por_usuario, fn($v) => is_object($v)));
    }

    public static function get_condicoes( $personagem_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_condicoes WHERE personagem_id = %d", $personagem_id
        ));
    }

    public static function get_inventario( $personagem_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dnd_inventario WHERE personagem_id = %d ORDER BY tipo, nome", $personagem_id
        ));
    }
}
