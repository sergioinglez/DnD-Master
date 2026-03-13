<?php
/**
 * DNDM_Imagem — Geração de imagens via Pollinations.ai
 *
 * Ponte de linguagem:
 *   - Recebe descrições em PT-BR (da interface e do Groq)
 *   - Traduz internamente para inglês otimizado
 *   - Adiciona sufixo de estilo épico obrigatório
 *   - Salva permanentemente em /wp-content/uploads/dnd-master/
 *
 * Sufixo de estilo fixo (Pilar 2 do Handoff):
 *   ", oil painting, dungeons and dragons style, detailed, 8k,
 *      fantasy art, cinematic lighting"
 *
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Imagem {

    // Sufixo de estilo obrigatório para todas as imagens
    const SUFIXO_ESTILO = ', oil painting, dungeons and dragons style, detailed, 8k, fantasy art, cinematic lighting';

    // Sufixo para versão ferida (HP < 50%)
    const SUFIXO_FERIDO = ', wounded, exhausted, battle scars, blood, torn clothes';

    // ── DICIONÁRIOS DE TRADUÇÃO PT-BR → EN ───────────────────────────────────

    private static $traducao_racas = array(
        'Humano'         => 'human',
        'Elfo'           => 'elf',
        'Elfa'           => 'elf',
        'Anão'           => 'dwarf',
        'Halfling'       => 'halfling',
        'Gnomo'          => 'gnome',
        'Meio-Elfo'      => 'half-elf',
        'Meio-Orc'       => 'half-orc',
        'Tiefling'       => 'tiefling',
        'Draconato'      => 'dragonborn',
        'Aasimar'        => 'aasimar',
        'Kenku'          => 'kenku',
        'Tabaxi'         => 'tabaxi',
    );

    private static $traducao_classes = array(
        'Bárbaro'        => 'barbarian warrior',
        'Bardo'          => 'bard musician',
        'Bruxo'          => 'warlock sorcerer',
        'Clérigo'        => 'cleric priest',
        'Druida'         => 'druid nature mage',
        'Feiticeiro'     => 'sorcerer mage',
        'Guerreiro'      => 'fighter warrior',
        'Ladino'         => 'rogue thief',
        'Mago'           => 'wizard mage',
        'Monge'          => 'monk martial artist',
        'Paladino'       => 'paladin holy warrior',
        'Patrulheiro'    => 'ranger hunter',
    );

    private static $traducao_genero = array(
        'Masculino'      => 'male',
        'Feminino'       => 'female',
        'Não-binário'    => 'androgynous',
        'Neutro'         => 'androgynous',
    );

    private static $traducao_cabelo = array(
        'Preto'          => 'black hair',
        'Castanho'       => 'brown hair',
        'Loiro'          => 'blonde hair',
        'Ruivo'          => 'red hair',
        'Branco'         => 'white hair',
        'Cinza'          => 'gray hair',
        'Azul'           => 'blue hair',
        'Roxo'           => 'purple hair',
        'Prata'          => 'silver hair',
        'Esverdeado'     => 'green tinted hair',
    );

    private static $traducao_olhos = array(
        'Castanhos'      => 'brown eyes',
        'Azuis'          => 'blue eyes',
        'Verdes'         => 'green eyes',
        'Cinzas'         => 'gray eyes',
        'Negros'         => 'black eyes',
        'Âmbar'          => 'amber eyes',
        'Violeta'        => 'violet eyes',
        'Dourados'       => 'golden eyes',
        'Vermelhos'      => 'red eyes',
        'Heterocromia'   => 'heterochromia eyes',
    );

    private static $traducao_porte = array(
        'Magro'          => 'slim build',
        'Atlético'       => 'athletic build',
        'Robusto'        => 'muscular build',
        'Corpulento'     => 'heavy build',
        'Esguio'         => 'slender build',
        'Mediano'        => 'average build',
    );

    private static $traducao_tom_pele = array(
        'Claro'          => 'fair skin',
        'Moreno'         => 'tanned skin',
        'Escuro'         => 'dark skin',
        'Azulado'        => 'blue-tinted skin',
        'Esverdeado'     => 'green-tinted skin',
        'Cinzento'       => 'grey skin',
        'Avermelhado'    => 'reddish skin',
    );

    /**
     * Traduz termos PT-BR soltos numa string usando os dicionários acima.
     * Também processa frases mais livres, substituindo palavras-chave.
     *
     * @param  string $descricao_ptbr  Descrição em PT-BR.
     * @return string                  Prompt em inglês otimizado.
     */
    public static function traduzir_prompt( $descricao_ptbr ) {
        if ( empty( $descricao_ptbr ) ) return '';

        $texto = $descricao_ptbr;

        // Aplica todos os dicionários (ordem: do mais específico ao mais geral)
        $todos_dicts = array_merge(
            self::$traducao_racas,
            self::$traducao_classes,
            self::$traducao_genero,
            self::$traducao_cabelo,
            self::$traducao_olhos,
            self::$traducao_porte,
            self::$traducao_tom_pele
        );

        foreach ( $todos_dicts as $ptbr => $en ) {
            // Substituição case-insensitive, palavra inteira
            $texto = preg_replace(
                '/\b' . preg_quote( $ptbr, '/' ) . '\b/ui',
                $en,
                $texto
            );
        }

        // Tradução de palavras comuns de cenário
        $cenario = array(
            'floresta'    => 'forest',
            'masmorra'    => 'dungeon',
            'castelo'     => 'castle',
            'taverna'     => 'tavern',
            'campo'       => 'open field',
            'montanha'    => 'mountain',
            'caverna'     => 'cave',
            'templo'      => 'ancient temple',
            'mercado'     => 'marketplace',
            'navio'       => 'ship',
            'sala'        => 'chamber',
            'corredor'    => 'corridor',
            'torre'       => 'tower',
            'ruína'       => 'ruins',
            'pântano'     => 'swamp',
            'geleira'     => 'glacier',
            'deserto'     => 'desert',
            'cidade'      => 'city',
            'aldeia'      => 'village',
            'dragão'      => 'dragon',
            'esqueleto'   => 'skeleton warrior',
            'goblin'      => 'goblin',
            'orc'         => 'orc',
            'troll'       => 'troll',
            'vampiro'     => 'vampire',
            'lobisomem'   => 'werewolf',
            'demônio'     => 'demon',
            'anjo'        => 'angel',
            'espada'      => 'sword',
            'escudo'      => 'shield',
            'cajado'      => 'staff',
            'arco'        => 'bow',
            'adaga'       => 'dagger',
            'armadura'    => 'armor',
            'capa'        => 'cloak',
            'chapéu'      => 'hat',
            'capuz'       => 'hood',
            'luz'         => 'light',
            'sombra'      => 'shadow',
            'fogo'        => 'fire',
            'gelo'        => 'ice',
            'névoa'       => 'mist',
            'sangue'      => 'blood',
            'magia'       => 'magic',
            'arcano'      => 'arcane',
            'divino'      => 'divine',
            'sombrio'     => 'dark',
            'épico'       => 'epic',
            'misterioso'  => 'mysterious',
            'antigo'      => 'ancient',
            'maldito'     => 'cursed',
            'sagrado'     => 'sacred',
        );

        foreach ( $cenario as $ptbr => $en ) {
            $texto = preg_replace(
                '/\b' . preg_quote( $ptbr, '/' ) . '\b/ui',
                $en,
                $texto
            );
        }

        // Limpa espaços extras
        $texto = preg_replace( '/\s{2,}/', ' ', trim( $texto ) );

        return $texto;
    }

    // ── API KEY ───────────────────────────────────────────────────────────────

    private static function get_api_key() {
        return get_option( 'dndm_pollinations_key', '' );
    }

    // ── MÉTODO CORE: GERAR E SALVAR ───────────────────────────────────────────

    /**
     * Gera uma imagem via Pollinations.ai e salva permanentemente.
     *
     * @param  string $prompt_en     Prompt JÁ em inglês.
     * @param  string $nome_arquivo  Base do nome do arquivo (sem extensão).
     * @param  string $subpasta      Subpasta dentro de /dnd-master/ (ex: 'personagens').
     * @return string                URL pública da imagem, ou '' em caso de falha.
     */
    public static function gerar_e_salvar( $prompt_en, $nome_arquivo, $subpasta = 'misc' ) {
        $api_key   = self::get_api_key();
        $seed      = rand( 1, 999999 );
        $key_param = $api_key ? '&key=' . urlencode( $api_key ) : '';
        $prompt_url = urlencode( $prompt_en );

        $url = "https://gen.pollinations.ai/image/{$prompt_url}?model=flux&width=512&height=512&seed={$seed}&nologo=true{$key_param}";

        $response = wp_remote_get( $url, array(
            'timeout' => 90,  // imagens podem demorar
            'headers' => array( 'Accept' => 'image/*' ),
        ));

        if ( is_wp_error( $response ) ) {
            error_log( '[DnD Master] Imagem erro: ' . $response->get_error_message() );
            return '';
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        if ( $http_code !== 200 ) {
            error_log( "[DnD Master] Imagem HTTP {$http_code} para: {$prompt_en}" );
            return '';
        }

        $image_data = wp_remote_retrieve_body( $response );
        if ( empty( $image_data ) ) return '';

        // Garante diretório
        $pasta = DNDM_UPLOAD_DIR . "/{$subpasta}";
        wp_mkdir_p( $pasta );

        $filename   = sanitize_file_name( $nome_arquivo ) . '-' . time() . '.jpg';
        $filepath   = $pasta . '/' . $filename;

        if ( false === file_put_contents( $filepath, $image_data ) ) {
            error_log( "[DnD Master] Falha ao salvar imagem em: {$filepath}" );
            return '';
        }

        $url_publica = DNDM_UPLOAD_URL . "/{$subpasta}/{$filename}";
        return $url_publica;
    }

    /**
     * Recebe prompt em PT-BR, traduz, adiciona sufixo de estilo e gera.
     * Este é o método público principal para chamadas vindas da interface.
     *
     * @param  string $descricao_ptbr  Descrição em qualquer idioma.
     * @param  string $nome_arquivo    Base do nome do arquivo.
     * @param  string $subpasta        Subpasta de destino.
     * @param  bool   $ferido          Se true, adiciona keywords de lesão.
     * @return string                  URL pública ou ''.
     */
    public static function gerar_de_descricao( $descricao_ptbr, $nome_arquivo, $subpasta = 'misc', $ferido = false ) {
        $prompt_en = self::traduzir_prompt( $descricao_ptbr );

        if ( empty( $prompt_en ) ) return '';

        $sufixo = self::SUFIXO_ESTILO;
        if ( $ferido ) $sufixo .= self::SUFIXO_FERIDO;

        $prompt_final = $prompt_en . $sufixo;

        return self::gerar_e_salvar( $prompt_final, $nome_arquivo, $subpasta );
    }

    // ── MÉTODOS ESPECIALIZADOS ────────────────────────────────────────────────

    /**
     * Gera retrato de personagem a partir dos dados de criação.
     */
    public static function gerar_retrato_personagem( $personagem_data, $ferido = false ) {
        $genero    = self::$traducao_genero[ $personagem_data['genero'] ?? 'Masculino' ] ?? 'male';
        $raca_en   = self::$traducao_racas[ $personagem_data['raca'] ?? 'Humano' ]       ?? 'human';
        $classe_en = self::$traducao_classes[ $personagem_data['classe'] ?? 'Guerreiro' ] ?? 'warrior';

        $aparencia = $personagem_data['aparencia'] ?? array();
        $cabelo_en = self::$traducao_cabelo[ $aparencia['cabelo'] ?? '' ]      ?? '';
        $olhos_en  = self::$traducao_olhos[  $aparencia['olhos']  ?? '' ]      ?? '';
        $porte_en  = self::$traducao_porte[  $aparencia['porte']  ?? '' ]      ?? '';
        $pele_en   = self::$traducao_tom_pele[ $aparencia['pele'] ?? '' ]      ?? '';
        $traco_en  = self::traduzir_prompt( $aparencia['traco'] ?? '' );

        $parts = array_filter( array(
            "{$genero} {$raca_en} {$classe_en}",
            $cabelo_en,
            $olhos_en,
            $porte_en,
            $pele_en,
            $traco_en,
            'fantasy portrait character',
            'dramatic lighting',
            'highly detailed face',
        ));

        $prompt = implode( ', ', $parts );
        $prompt .= self::SUFIXO_ESTILO;
        if ( $ferido ) $prompt .= self::SUFIXO_FERIDO;

        $nome = 'personagem-' . sanitize_title( $personagem_data['nome'] ?? 'heroi' );
        return self::gerar_e_salvar( $prompt, $nome, 'personagens' );
    }

    /**
     * Gera imagem de NPC.
     * Aceita prompt em inglês (vindo do Groq) ou PT-BR (traduz automaticamente).
     */
    public static function gerar_imagem_npc( $npc_data ) {
        if ( ! empty( $npc_data['prompt_imagem'] ) ) {
            // Groq já gera em inglês — apenas adiciona sufixo
            $prompt = $npc_data['prompt_imagem'] . self::SUFIXO_ESTILO;
        } else {
            // Monta a partir dos dados e traduz
            $raca_en  = self::$traducao_racas[ $npc_data['raca'] ?? '' ] ?? 'human';
            $desc_en  = self::traduzir_prompt( $npc_data['aparencia'] ?? $npc_data['nome'] );
            $prompt   = "npc character, {$raca_en}, {$desc_en}, face portrait" . self::SUFIXO_ESTILO;
        }

        $nome = 'npc-' . sanitize_title( $npc_data['nome'] ?? 'npc' );
        return self::gerar_e_salvar( $prompt, $nome, 'npcs' );
    }

    /**
     * Gera imagem de item de inventário.
     * Traduz descrição PT-BR para inglês.
     */
    public static function gerar_imagem_item( $nome_item, $descricao_ptbr = '' ) {
        $nome_en = self::traduzir_prompt( $nome_item );
        $desc_en = self::traduzir_prompt( $descricao_ptbr );

        $prompt = "fantasy item, {$nome_en}";
        if ( $desc_en ) $prompt .= ", {$desc_en}";
        $prompt .= ', isolated on dark background, glowing, detailed render' . self::SUFIXO_ESTILO;

        $nome = 'item-' . sanitize_title( $nome_item );
        return self::gerar_e_salvar( $prompt, $nome, 'itens' );
    }

    /**
     * Gera imagem de cena/cenário.
     * Recebe descrição PT-BR, traduz e gera.
     */
    public static function gerar_imagem_cena( $descricao_ptbr ) {
        $desc_en = self::traduzir_prompt( $descricao_ptbr );
        $prompt  = "fantasy scene environment, {$desc_en}, wide establishing shot, atmospheric, detailed" . self::SUFIXO_ESTILO;

        $nome = 'cena-' . time();
        return self::gerar_e_salvar( $prompt, $nome, 'cenas' );
    }

    /**
     * Pré-gera a versão "ferida" do retrato durante a importação do módulo.
     * Retorna URL da imagem ferida para armazenar no banco.
     */
    public static function gerar_retrato_ferido( $personagem_data ) {
        return self::gerar_retrato_personagem( $personagem_data, true );
    }
}
