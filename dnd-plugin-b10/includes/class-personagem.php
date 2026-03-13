<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Personagem {

    // Cache estático para não ler o arquivo JSON múltiplas vezes na mesma execução
    private static $cache_classes = null;

    // --- DADOS DE SEGURANÇA (Fallback caso o JSON falhe) ---
    private static $dados_vida = array(
        'guerreiro' => 10, 'barbaro' => 12, 'paladino' => 10,
        'clerigo'   => 8,  'druida'  => 8,  'bardo'    => 8,
        'mago'      => 6,  'feiticeiro' => 6, 'bruxo'  => 8,
        'ladino'    => 8,  'monge'   => 8,  'ranger'   => 10,
    );

    private static $proficiencias_por_classe = array(
        'guerreiro'  => array('Armaduras leves', 'Armaduras médias', 'Armaduras pesadas', 'Escudos', 'Armas simples', 'Armas marciais'),
        'mago'       => array('Adagas', 'Dardos', 'Fundas', 'Bastões', 'Bodoques'),
        'ladino'     => array('Armaduras leves', 'Armas simples', 'Espadas longas', 'Rapieiras', 'Arco curto', 'Arma de mão'),
        'clerigo'    => array('Armaduras leves', 'Armaduras médias', 'Escudos', 'Armas simples'),
        'bardo'      => array('Armaduras leves', 'Armas simples', 'Espadas longas', 'Rapieiras', 'Arco curto'),
        'druida'     => array('Armaduras leves de couro', 'Escudos de madeira', 'Armas simples não metálicas'),
        'paladino'   => array('Armaduras leves', 'Armaduras médias', 'Armaduras pesadas', 'Escudos', 'Armas simples', 'Armas marciais'),
        'ranger'     => array('Armaduras leves', 'Armaduras médias', 'Escudos', 'Armas simples', 'Armas marciais'),
        'barbaro'    => array('Armaduras leves', 'Armaduras médias', 'Escudos', 'Armas simples', 'Armas marciais'),
        'monge'      => array('Armas simples', 'Espadas curtas'),
        'feiticeiro' => array('Adagas', 'Dardos', 'Fundas', 'Bastões', 'Bodoques'),
        'bruxo'      => array('Armaduras leves', 'Armas simples'),
    );

    private static $equipamento_por_classe = array(
        'guerreiro'  => array('Cota de malha', 'Escudo', 'Espada longa', 'Machado de mão (2)', 'Kit de explorador', 'Mochila'),
        'mago'       => array('Grimório', 'Bastão arcano', 'Kit de estudioso', 'Bolsa de componentes', 'Adaga (2)'),
        'ladino'     => array('Rapieira', 'Arco curto', 'Aljava (20 flechas)', 'Armadura de couro', 'Kit de ladrão', 'Adaga (2)'),
        'clerigo'    => array('Maça', 'Cota de escamas', 'Escudo', 'Símbolo sagrado', 'Kit de sacerdote', 'Besta leve'),
        'bardo'      => array('Rapieira', 'Armadura de couro', 'Instrumento musical', 'Kit de diplomata', 'Adaga'),
        'druida'     => array('Escudo de madeira', 'Cimitarra', 'Armadura de couro', 'Kit de explorador', 'Foco druídico'),
        'paladino'   => array('Espada longa', 'Cota de malha', 'Escudo', 'Símbolo sagrado', 'Kit de sacerdote', 'Lança (5)'),
        'ranger'     => array('Cota de escamas', 'Espada longa (2)', 'Arco longo', 'Aljava (20 flechas)', 'Kit de explorador'),
        'barbaro'    => array('Machado grande', 'Machado de mão (2)', 'Kit de explorador', 'Quatro azagaias'),
        'monge'      => array('Espada curta', 'Dardo (10)', 'Kit de explorador'),
        'feiticeiro' => array('Besta leve', 'Virotes (20)', 'Bolsa de componentes', 'Kit de explorador', 'Adaga (2)'),
        'bruxo'      => array('Besta leve', 'Virotes (20)', 'Armadura de couro', 'Kit de explorador', 'Adaga (2)'),
    );

    // --- MOTORES DE INTEGRAÇÃO JSON ---

    private static function get_json_data() {
        if ( self::$cache_classes !== null ) return self::$cache_classes;

        $path = plugin_dir_path( __FILE__ ) . 'dnd.classes.json';
        if ( ! file_exists( $path ) ) return array();

        $content = file_get_contents( $path );
        $data = json_decode( $content, true );
        
        self::$cache_classes = isset($data[0]['classes']) ? $data[0]['classes'] : array();
        return self::$cache_classes;
    }

    private static function mapear_classe( $slug ) {
        $mapa = [
            'guerreiro' => 'Fighter', 'ladino' => 'Rogue', 'mago' => 'Wizard',
            'ranger' => 'Ranger', 'barbaro' => 'Barbarian', 'paladino' => 'Paladin',
            'clerigo' => 'Cleric', 'bardo' => 'Bard', 'druida' => 'Druid',
            'monge' => 'Monk', 'feiticeiro' => 'Sorcerer', 'bruxo' => 'Warlock'
        ];
        return $mapa[strtolower($slug)] ?? ucfirst($slug);
    }

    // --- MÉTODOS PÚBLICOS ---

    public static function calcular_modificador( $valor ) {
        return floor( ($valor - 10) / 2 );
    }

    public static function calcular_hp( $classe, $constituicao ) {
        $dados_json = self::get_json_data();
        $key = self::mapear_classe($classe);
        
        $dado = $dados_json[$key]['hit_die'] ?? (self::$dados_vida[$classe] ?? 8);
        $mod_con = self::calcular_modificador($constituicao);
        return max(1, (int)$dado + $mod_con);
    }

    public static function get_proficiencias( $classe ) {
        $dados_json = self::get_json_data();
        $key = self::mapear_classe($classe);

        if ( isset($dados_json[$key]['proficiencies']) ) {
            $p = $dados_json[$key]['proficiencies'];
            return array_merge($p['armor'], $p['weapons']);
        }
        return self::$proficiencias_por_classe[$classe] ?? array();
    }

    public static function get_equipamento( $classe ) {
        return self::$equipamento_por_classe[$classe] ?? array();
    }

    public static function criar( $dados ) {
        global $wpdb;

        $usuario = DNDM_Auth::get_usuario_dnd();
        if ( ! $usuario ) return new WP_Error('sem_usuario', 'Usuário não encontrado');

        $atributos  = $dados['atributos'] ?? array();
        $constituicao = $atributos['constituicao'] ?? 10;
        $hp_max     = self::calcular_hp( $dados['classe'], $constituicao );
        $prof       = self::get_proficiencias( $dados['classe'] );
        $equip      = self::get_equipamento( $dados['classe'] );

        $resultado = $wpdb->insert(
            $wpdb->prefix . 'dnd_personagens',
            array(
                'usuario_id'          => $usuario->id,
                'campanha_id'         => $dados['campanha_id'] ?? null,
                'nome'                => sanitize_text_field($dados['nome']),
                'raca'                => sanitize_text_field($dados['raca']),
                'classe'              => sanitize_text_field($dados['classe']),
                'genero'              => sanitize_text_field($dados['genero']),
                'nivel'               => 1,
                'xp'                  => 0,
                'hp_atual'            => $hp_max,
                'hp_max'              => $hp_max,
                'ca'                  => 10 + self::calcular_modificador($atributos['destreza'] ?? 10),
                'atributos'           => json_encode($atributos),
                'aparencia'           => json_encode($dados['aparencia'] ?? array()),
                'imagem_url'          => sanitize_url($dados['imagem_url'] ?? ''),
                'backstory'           => $dados['backstory'] ?? '',
                'personalidade'       => $dados['personalidade'] ?? '',
                'ideal'               => $dados['ideal'] ?? '',
                'vinculo'             => $dados['vinculo'] ?? '',
                'fraqueza'            => $dados['fraqueza'] ?? '',
                'antecedente'         => sanitize_text_field($dados['antecedente'] ?? ''),
                'alinhamento'         => sanitize_text_field($dados['alinhamento'] ?? ''),
                'proficiencias'       => json_encode($prof),
                'equipamento_inicial' => json_encode($equip),
                'status'              => 'ativo',
            )
        );

        if ( ! $resultado ) return new WP_Error('db_erro', 'Erro ao salvar personagem');

        $personagem_id = $wpdb->insert_id;
        foreach ( $equip as $item ) {
            $wpdb->insert( $wpdb->prefix . 'dnd_inventario', array(
                'personagem_id' => $personagem_id,
                'nome'          => $item,
                'tipo'          => 'equipamento',
                'quantidade'    => 1,
            ));
        }
        return $personagem_id;
    }

    public static function aplicar_dano( $personagem_id, $dano ) {
        global $wpdb;
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return false;

        $novo_hp = max(0, $p->hp_atual - $dano);
        $wpdb->update($wpdb->prefix . 'dnd_personagens', array('hp_atual' => $novo_hp), array('id' => $personagem_id));
        
        if ($novo_hp === 0) self::adicionar_condicao($personagem_id, 'inconsciente');
        return $novo_hp;
    }

    public static function aplicar_cura( $personagem_id, $cura ) {
        global $wpdb;
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return false;

        $novo_hp = min($p->hp_max, $p->hp_atual + $cura);
        $wpdb->update($wpdb->prefix . 'dnd_personagens', array('hp_atual' => $novo_hp), array('id' => $personagem_id));
        
        if ($novo_hp > 0) self::remover_condicao($personagem_id, 'inconsciente');
        return $novo_hp;
    }

    public static function adicionar_condicao( $personagem_id, $tipo ) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'dnd_condicoes', array('personagem_id' => $personagem_id, 'tipo' => sanitize_text_field($tipo)));
    }

    public static function remover_condicao( $personagem_id, $tipo ) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'dnd_condicoes', array('personagem_id' => $personagem_id, 'tipo' => $tipo));
    }

    public static function ganhar_xp( $personagem_id, $xp ) {
        global $wpdb;
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return false;

        $novo_xp    = $p->xp + $xp;
        $novo_nivel = self::calcular_nivel($novo_xp);

        $wpdb->update($wpdb->prefix . 'dnd_personagens', array('xp' => $novo_xp, 'nivel' => $novo_nivel), array('id' => $personagem_id));
        return array('xp' => $novo_xp, 'nivel' => $novo_nivel, 'subiu' => $novo_nivel > $p->nivel);
    }

    private static function calcular_nivel( $xp ) {
        $tabela = array(0,300,900,2700,6500,14000,23000,34000,48000,64000,85000,100000,120000,140000,165000,195000,225000,265000,305000,355000);
        $nivel  = 1;
        foreach ($tabela as $i => $req) { if ($xp >= $req) $nivel = $i + 1; }
        return min(20, $nivel);
    }

    /**
     * Retorna a ficha completa injetando habilidades dinâmicas do JSON
     */
    public static function get_ficha_completa( $personagem_id ) {
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return null;

        $p->atributos           = json_decode($p->atributos, true) ?: array();
        $p->aparencia           = json_decode($p->aparencia, true) ?: array();
        $p->proficiencias       = json_decode($p->proficiencias, true) ?: array();
        $p->equipamento_inicial = json_decode($p->equipamento_inicial, true) ?: array();
        $p->condicoes           = DNDM_Database::get_condicoes($personagem_id);
        $p->inventario          = DNDM_Database::get_inventario($personagem_id);
        
        // --- INJEÇÃO DINÂMICA DO JSON ---
        $dados_json = self::get_json_data();
        $key = self::mapear_classe($p->classe);
        $p->habilidades_classe = [];

        if ( isset($dados_json[$key]) ) {
            $classe_info = $dados_json[$key];
            for ($i = 1; $i <= (int)$p->nivel; $i++) {
                if ( isset($classe_info['levels'][$i]['features']) ) {
                    foreach ($classe_info['levels'][$i]['features'] as $f_nome => $f_data) {
                        $p->habilidades_classe[] = [
                            'nome'   => $f_nome,
                            'resumo' => $f_data['summary'] ?? ''
                        ];
                    }
                }
            }
        }

        $p->modificadores = array();
        foreach ($p->atributos as $attr => $val) {
            $p->modificadores[$attr] = self::calcular_modificador($val);
        }

        return $p;
    }
}