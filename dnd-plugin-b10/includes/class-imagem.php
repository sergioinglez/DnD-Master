<?php
/**
 * DNDM_Imagem — Geração de imagens via Pollinations.ai
 *
 * - Recebe descrições em PT-BR
 * - Traduz internamente para inglês otimizado
 * - Adiciona sufixo de estilo épico obrigatório
 * - Salva permanentemente em /wp-content/uploads/dnd-master/
 *
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Imagem {

    const SUFIXO_ESTILO = ', oil painting, dungeons and dragons style, detailed, 8k, fantasy art, cinematic lighting';
    const SUFIXO_FERIDO = ', wounded, exhausted, battle scars, blood, torn clothes';

    private static $traducao_racas = array(
        'Humano'=>'human','Elfo'=>'elf','Elfa'=>'elf','Anão'=>'dwarf',
        'Halfling'=>'halfling','Gnomo'=>'gnome','Meio-Elfo'=>'half-elf',
        'Meio-Orc'=>'half-orc','Tiefling'=>'tiefling','Draconato'=>'dragonborn',
        'Aasimar'=>'aasimar','Kenku'=>'kenku','Tabaxi'=>'tabaxi',
    );
    private static $traducao_classes = array(
        'Bárbaro'=>'barbarian warrior','Bardo'=>'bard musician','Bruxo'=>'warlock sorcerer',
        'Clérigo'=>'cleric priest','Druida'=>'druid nature mage','Feiticeiro'=>'sorcerer mage',
        'Guerreiro'=>'fighter warrior','Ladino'=>'rogue thief','Mago'=>'wizard mage',
        'Monge'=>'monk martial artist','Paladino'=>'paladin holy warrior','Patrulheiro'=>'ranger hunter',
    );
    private static $traducao_genero  = array('Masculino'=>'male','Feminino'=>'female','Não-binário'=>'androgynous','Neutro'=>'androgynous');
    private static $traducao_cabelo  = array('Preto'=>'black hair','Castanho'=>'brown hair','Loiro'=>'blonde hair','Ruivo'=>'red hair','Branco'=>'white hair','Cinza'=>'gray hair','Azul'=>'blue hair','Roxo'=>'purple hair','Prata'=>'silver hair','Esverdeado'=>'green tinted hair');
    private static $traducao_olhos   = array('Castanhos'=>'brown eyes','Azuis'=>'blue eyes','Verdes'=>'green eyes','Cinzas'=>'gray eyes','Negros'=>'black eyes','Âmbar'=>'amber eyes','Violeta'=>'violet eyes','Dourados'=>'golden eyes','Vermelhos'=>'red eyes','Heterocromia'=>'heterochromia eyes');
    private static $traducao_porte   = array('Magro'=>'slim build','Atlético'=>'athletic build','Robusto'=>'muscular build','Corpulento'=>'heavy build','Esguio'=>'slender build','Mediano'=>'average build');
    private static $traducao_tom_pele= array('Claro'=>'fair skin','Moreno'=>'tanned skin','Escuro'=>'dark skin','Azulado'=>'blue-tinted skin','Esverdeado'=>'green-tinted skin','Cinzento'=>'grey skin','Avermelhado'=>'reddish skin');

    public static function traduzir_prompt( $descricao_ptbr ) {
        if ( empty($descricao_ptbr) ) return '';
        $texto = $descricao_ptbr;
        $todos = array_merge(
            self::$traducao_racas, self::$traducao_classes, self::$traducao_genero,
            self::$traducao_cabelo, self::$traducao_olhos, self::$traducao_porte, self::$traducao_tom_pele
        );
        foreach ( $todos as $ptbr => $en ) {
            $texto = preg_replace('/\b'.preg_quote($ptbr,'/').'\\b/ui', $en, $texto);
        }
        $cenario = array(
            'floresta'=>'forest','masmorra'=>'dungeon','castelo'=>'castle','taverna'=>'tavern',
            'montanha'=>'mountain','caverna'=>'cave','templo'=>'ancient temple','cidade'=>'city',
            'aldeia'=>'village','dragão'=>'dragon','goblin'=>'goblin','orc'=>'orc','demônio'=>'demon',
            'espada'=>'sword','escudo'=>'shield','cajado'=>'staff','arco'=>'bow','adaga'=>'dagger',
            'armadura'=>'armor','fogo'=>'fire','gelo'=>'ice','magia'=>'magic','sombrio'=>'dark',
            'épico'=>'epic','misterioso'=>'mysterious','antigo'=>'ancient','sagrado'=>'sacred',
        );
        foreach ( $cenario as $ptbr => $en ) {
            $texto = preg_replace('/\b'.preg_quote($ptbr,'/').'\\b/ui', $en, $texto);
        }
        return preg_replace('/\s{2,}/', ' ', trim($texto));
    }

    private static function get_api_key() {
        return get_option('dndm_pollinations_key', '');
    }

    public static function gerar_e_salvar( $prompt_en, $nome_arquivo, $subpasta = 'misc' ) {
        $api_key    = self::get_api_key();
        $seed       = rand(1, 999999);
        $key_param  = $api_key ? '&key='.urlencode($api_key) : '';
        $url        = 'https://gen.pollinations.ai/image/'.urlencode($prompt_en).'?model=flux&width=512&height=512&seed='.$seed.'&nologo=true'.$key_param;

        $response = wp_remote_get($url, array('timeout'=>90,'headers'=>array('Accept'=>'image/*')));
        if ( is_wp_error($response) ) { error_log('[DnD Master] Imagem erro: '.$response->get_error_message()); return ''; }

        $http_code = wp_remote_retrieve_response_code($response);
        if ( $http_code !== 200 ) { error_log("[DnD Master] Imagem HTTP {$http_code}"); return ''; }

        $image_data = wp_remote_retrieve_body($response);
        if ( empty($image_data) ) return '';

        return self::salvar_imagem($image_data, $nome_arquivo, $subpasta, 'jpg');
    }

    private static function salvar_imagem( $image_data, $nome_arquivo, $subpasta, $ext = 'jpg' ) {
        $pasta = DNDM_UPLOAD_DIR."/{$subpasta}";
        wp_mkdir_p($pasta);
        $filename = sanitize_file_name($nome_arquivo).'-'.time().'.'.$ext;
        $filepath = $pasta.'/'.$filename;
        if ( false === file_put_contents($filepath, $image_data) ) {
            error_log("[DnD Master] Falha ao salvar imagem em: {$filepath}");
            return '';
        }
        return DNDM_UPLOAD_URL."/{$subpasta}/{$filename}";
    }

    public static function gerar_de_descricao( $descricao_ptbr, $nome_arquivo, $subpasta = 'misc', $ferido = false ) {
        $prompt_en = self::traduzir_prompt($descricao_ptbr);
        if ( empty($prompt_en) ) return '';
        $sufixo = self::SUFIXO_ESTILO;
        if ( $ferido ) $sufixo .= self::SUFIXO_FERIDO;
        return self::gerar_e_salvar($prompt_en.$sufixo, $nome_arquivo, $subpasta);
    }

    public static function gerar_retrato_personagem( $personagem_data, $ferido = false ) {
        $genero    = self::$traducao_genero[$personagem_data['genero'] ?? 'Masculino'] ?? 'male';
        $raca_en   = self::$traducao_racas[$personagem_data['raca']   ?? 'Humano']    ?? 'human';
        $classe_en = self::$traducao_classes[$personagem_data['classe'] ?? 'Guerreiro'] ?? 'warrior';
        $aparencia = $personagem_data['aparencia'] ?? array();
        $parts = array_filter(array(
            "{$genero} {$raca_en} {$classe_en}",
            self::$traducao_cabelo[$aparencia['cabelo'] ?? ''] ?? '',
            self::$traducao_olhos[$aparencia['olhos']   ?? ''] ?? '',
            self::$traducao_porte[$aparencia['porte']   ?? ''] ?? '',
            self::$traducao_tom_pele[$aparencia['pele'] ?? ''] ?? '',
            self::traduzir_prompt($aparencia['traco']   ?? ''),
            'fantasy portrait character','dramatic lighting','highly detailed face',
        ));
        $prompt = implode(', ', $parts).self::SUFIXO_ESTILO;
        if ( $ferido ) $prompt .= self::SUFIXO_FERIDO;
        return self::gerar_e_salvar($prompt, 'personagem-'.sanitize_title($personagem_data['nome'] ?? 'heroi'), 'personagens');
    }

    public static function gerar_imagem_npc( $npc_data ) {
        if ( !empty($npc_data['prompt_imagem']) ) {
            $prompt = $npc_data['prompt_imagem'].self::SUFIXO_ESTILO;
        } else {
            $raca_en = self::$traducao_racas[$npc_data['raca'] ?? ''] ?? 'human';
            $desc_en = self::traduzir_prompt($npc_data['aparencia'] ?? $npc_data['nome']);
            $prompt  = "npc character, {$raca_en}, {$desc_en}, face portrait".self::SUFIXO_ESTILO;
        }
        return self::gerar_e_salvar($prompt, 'npc-'.sanitize_title($npc_data['nome'] ?? 'npc'), 'npcs');
    }

    public static function gerar_imagem_cena( $descricao_ptbr ) {
        $desc_en = self::traduzir_prompt($descricao_ptbr);
        $prompt  = "fantasy scene environment, {$desc_en}, wide establishing shot, atmospheric, detailed".self::SUFIXO_ESTILO;
        return self::gerar_e_salvar($prompt, 'cena-'.time(), 'cenas');
    }

    public static function gerar_retrato_ferido( $personagem_data ) {
        return self::gerar_retrato_personagem($personagem_data, true);
    }
}
