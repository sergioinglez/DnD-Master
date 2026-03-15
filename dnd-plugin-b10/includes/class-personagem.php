<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Personagem {

    // Dados D&D 5e por classe
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

    // ── HABILIDADES POR CLASSE E NÍVEL (do JSON dnd_classes) ─────────────────
    // Resumo das principais habilidades por classe, nível 1-12, em PT-BR
    private static $habilidades_por_classe = array(

        'guerreiro' => array(
            1  => array( array('nome'=>'Estilo de Combate','resumo'=>'Escolha uma especialidade: Arquearia, Arma e Escudo, Armas de Duas Mãos, Duelo, etc.'), array('nome'=>'Segunda Fôlego','resumo'=>'Use ação bônus para recuperar 1d10 + nível em PV uma vez por descanso.') ),
            2  => array( array('nome'=>'Surto de Ação','resumo'=>'Uma vez por descanso curto, tome uma ação extra no seu turno.') ),
            3  => array( array('nome'=>'Arquétipo Marcial','resumo'=>'Escolha uma especialização: Campeão, Mestre de Batalha ou Cavaleiro Élfico.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Ataque Extra','resumo'=>'Ataque duas vezes ao usar a ação Atacar.') ),
            6  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            7  => array( array('nome'=>'Característica de Arquétipo','resumo'=>'Nova habilidade do arquétipo marcial escolhido no nível 3.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Indomável','resumo'=>'Refaça uma jogada de resistência com falha uma vez por descanso longo.') ),
            10 => array( array('nome'=>'Característica de Arquétipo','resumo'=>'Nova habilidade do arquétipo marcial.') ),
            11 => array( array('nome'=>'Ataque Extra (2)','resumo'=>'Ataque três vezes ao usar a ação Atacar.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'barbaro' => array(
            1  => array( array('nome'=>'Fúria','resumo'=>'Entre em fúria como ação bônus: vantagem em testes de Força, resistência a dano físico, bônus de dano.'), array('nome'=>'Defesa Sem Armadura','resumo'=>'CA = 10 + mod. Destreza + mod. Constituição sem armadura.') ),
            2  => array( array('nome'=>'Ataque Imprudente','resumo'=>'Ataque com vantagem mas os inimigos também têm vantagem contra você.'), array('nome'=>'Sentido de Perigo','resumo'=>'Vantagem em resistências contra efeitos que você pode ver.') ),
            3  => array( array('nome'=>'Caminho Primitivo','resumo'=>'Escolha: Caminho do Berserker ou Caminho do Totem.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Ataque Extra','resumo'=>'Ataque duas vezes ao usar a ação Atacar.'), array('nome'=>'Movimento Rápido','resumo'=>'+10 pés de deslocamento sem armadura.') ),
            6  => array( array('nome'=>'Característica do Caminho','resumo'=>'Nova habilidade do Caminho Primitivo escolhido no nível 3.') ),
            7  => array( array('nome'=>'Instinto Selvagem','resumo'=>'Vantagem em iniciativa. Pode entrar em fúria durante a surpresa.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Crítico Brutal (1)','resumo'=>'Role 1 dado de dano extra ao acertar um crítico.') ),
            10 => array( array('nome'=>'Característica do Caminho','resumo'=>'Nova habilidade do Caminho Primitivo.') ),
            11 => array( array('nome'=>'Fúria Implacável','resumo'=>'Se chegar a 0 PV durante a fúria, role CD 10 de Constituição para ficar em 1 PV.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'bardo' => array(
            1  => array( array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias usando Carisma como atributo de conjuração.'), array('nome'=>'Inspiração de Bardo','resumo'=>'Conceda 1d6 de inspiração para um aliado por ação bônus.') ),
            2  => array( array('nome'=>'Multiclasse','resumo'=>'Obtenha proficiência em qualquer perícia ou ferramenta.'), array('nome'=>'Canção de Repouso','resumo'=>'Aliados recuperam PV extras durante descanso curto.') ),
            3  => array( array('nome'=>'Colégio de Bardo','resumo'=>'Escolha um colégio: Conhecimento ou Valor.'), array('nome'=>'Conhecimento dos Especialistas','resumo'=>'Domine quatro perícias e adicione o dobro do bônus em duas delas.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Inspiração de Bardo (1d8)','resumo'=>'O dado de inspiração aumenta para d8.'), array('nome'=>'Fonte de Inspiração','resumo'=>'Recupere Inspiração de Bardo no descanso curto também.') ),
            6  => array( array('nome'=>'Característica do Colégio','resumo'=>'Nova habilidade do Colégio de Bardo escolhido.') ),
            7  => array( array('nome'=>'Segredo da Magia','resumo'=>'Aprenda duas magias de qualquer classe.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Canção de Repouso (2d6)','resumo'=>'O dado de cura durante descanso aumenta.') ),
            10 => array( array('nome'=>'Inspiração de Bardo (1d10)','resumo'=>'O dado de inspiração aumenta para d10.'), array('nome'=>'Segredo da Magia','resumo'=>'Aprenda mais duas magias de qualquer classe.'), array('nome'=>'Maestria','resumo'=>'Escolha mais duas perícias para adicionar o dobro do bônus.') ),
            11 => array( array('nome'=>'Magias de 6º Círculo','resumo'=>'Acesso a magias de 6º nível.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'clerigo' => array(
            1  => array( array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias divinas usando Sabedoria.'), array('nome'=>'Domínio Divino','resumo'=>'Escolha um domínio (Vida, Luz, Trickery, etc.) que concede magias e habilidades extras.') ),
            2  => array( array('nome'=>'Canalizar Divindade','resumo'=>'Use o poder da sua divindade: Afastar Mortos-Vivos ou o efeito do domínio.'), array('nome'=>'Característica do Domínio','resumo'=>'Nova habilidade do domínio escolhido.') ),
            3  => array( array('nome'=>'Magias de 2º Círculo','resumo'=>'Acesso a magias de 2º nível.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Destruir Mortos-Vivos','resumo'=>'Mortos-vivos afastados com CR baixo são destruídos instantaneamente.'), array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            6  => array( array('nome'=>'Canalizar Divindade (2x)','resumo'=>'Use Canalizar Divindade duas vezes por descanso.'), array('nome'=>'Característica do Domínio','resumo'=>'Nova habilidade do domínio.') ),
            7  => array( array('nome'=>'Magias de 4º Círculo','resumo'=>'Acesso a magias de 4º nível.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.'), array('nome'=>'Golpe Divino','resumo'=>'Uma vez por turno, cause 1d8 extra de dano (tipo do domínio) em ataque com arma.') ),
            9  => array( array('nome'=>'Magias de 5º Círculo','resumo'=>'Acesso a magias de 5º nível.') ),
            10 => array( array('nome'=>'Intervenção Divina','resumo'=>'Implore o auxílio da sua divindade (chance = nível %).') ),
            11 => array( array('nome'=>'Destruir Mortos-Vivos (CR 2)','resumo'=>'Mortos-vivos com CR 2 ou menor são destruídos.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'druida' => array(
            1  => array( array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias da natureza usando Sabedoria.'), array('nome'=>'Idioma Druídico','resumo'=>'Conheça o idioma secreto dos druidas.') ),
            2  => array( array('nome'=>'Forma Selvagem','resumo'=>'Transforme-se em beasts com CR até 1/4 por 1 hora. 2x por descanso.'), array('nome'=>'Círculo Druídico','resumo'=>'Escolha: Círculo da Terra ou Círculo da Lua.') ),
            3  => array( array('nome'=>'Magias de 2º Círculo','resumo'=>'Acesso a magias de 2º nível.') ),
            4  => array( array('nome'=>'Forma Selvagem Aprimorada','resumo'=>'Transforme-se em beasts com CR até 1/2 (sem velocidade de natação).'), array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            6  => array( array('nome'=>'Característica do Círculo','resumo'=>'Nova habilidade do Círculo Druídico escolhido.') ),
            7  => array( array('nome'=>'Magias de 4º Círculo','resumo'=>'Acesso a magias de 4º nível.') ),
            8  => array( array('nome'=>'Forma Selvagem Aprimorada (CR 1)','resumo'=>'Transforme-se em beasts com CR até 1.'), array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Magias de 5º Círculo','resumo'=>'Acesso a magias de 5º nível.') ),
            10 => array( array('nome'=>'Característica do Círculo','resumo'=>'Nova habilidade do Círculo Druídico.') ),
            11 => array( array('nome'=>'Magias de 6º Círculo','resumo'=>'Acesso a magias de 6º nível.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'feiticeiro' => array(
            1  => array( array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias usando Carisma. A magia é inata ao seu sangue.'), array('nome'=>'Origem de Feiticeiro','resumo'=>'Escolha: Linhagem Dracônica ou Alma Selvagem.') ),
            2  => array( array('nome'=>'Fonte de Magia','resumo'=>'Acumule pontos de feitiçaria (= nível) para metamagia e slots extras.') ),
            3  => array( array('nome'=>'Metamagia','resumo'=>'Modifique suas magias: Cuidadosa, Distante, Fortalecida, Rápida, Silenciosa, Gêmea, etc.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            6  => array( array('nome'=>'Característica da Origem','resumo'=>'Nova habilidade da Origem de Feiticeiro escolhida.') ),
            7  => array( array('nome'=>'Magias de 4º Círculo','resumo'=>'Acesso a magias de 4º nível.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Magias de 5º Círculo','resumo'=>'Acesso a magias de 5º nível.') ),
            10 => array( array('nome'=>'Metamagia','resumo'=>'Aprenda mais uma opção de Metamagia.') ),
            11 => array( array('nome'=>'Magias de 6º Círculo','resumo'=>'Acesso a magias de 6º nível.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'mago' => array(
            1  => array( array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias poderosas usando Inteligência e seu grimório.'), array('nome'=>'Recuperar Magia','resumo'=>'Recupere slots de magia gastos uma vez por descanso curto.') ),
            2  => array( array('nome'=>'Tradição Arcana','resumo'=>'Escolha uma escola de magia: Abjuração, Conjuração, Adivinhação, Encantamento, etc.') ),
            3  => array( array('nome'=>'Magias de 2º Círculo','resumo'=>'Acesso a magias de 2º nível.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            6  => array( array('nome'=>'Característica da Tradição','resumo'=>'Nova habilidade da Tradição Arcana escolhida.') ),
            7  => array( array('nome'=>'Magias de 4º Círculo','resumo'=>'Acesso a magias de 4º nível.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Magias de 5º Círculo','resumo'=>'Acesso a magias de 5º nível.') ),
            10 => array( array('nome'=>'Característica da Tradição','resumo'=>'Nova habilidade da Tradição Arcana.') ),
            11 => array( array('nome'=>'Magias de 6º Círculo','resumo'=>'Acesso a magias de 6º nível.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'ladino' => array(
            1  => array( array('nome'=>'Ataque Furtivo','resumo'=>'1d6 extra de dano quando tem vantagem ou aliado adjacente ao alvo.'), array('nome'=>'Linguagem dos Ladrões','resumo'=>'Idioma secreto de comunicação entre ladinos.'), array('nome'=>'Especialização','resumo'=>'Domine duas perícias com o dobro do bônus de proficiência.') ),
            2  => array( array('nome'=>'Ação Astuciosa','resumo'=>'Use ação bônus para Disparada, Desengajar ou Esconder a cada turno.') ),
            3  => array( array('nome'=>'Arquétipo de Ladino','resumo'=>'Escolha: Ladrão, Assassino ou Trapasseiro Arcano.'), array('nome'=>'Reflexo de Proteção','resumo'=>'Metade do dano em jogadas de resistência de Destreza com sucesso.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Ataque Furtivo (3d6)','resumo'=>'O dano furtivo aumenta para 3d6.'), array('nome'=>'Esquiva Sobrenatural','resumo'=>'Use reação para reduzir pela metade o dano de um ataque.') ),
            6  => array( array('nome'=>'Especialização','resumo'=>'Domine mais duas perícias com o dobro do bônus.') ),
            7  => array( array('nome'=>'Ataque Furtivo (4d6)','resumo'=>'O dano furtivo aumenta para 4d6.'), array('nome'=>'Evasão','resumo'=>'Nenhum dano em resistências de Destreza bem-sucedidas (metade se falhar).') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Ataque Furtivo (5d6)','resumo'=>'O dano furtivo aumenta para 5d6.'), array('nome'=>'Característica do Arquétipo','resumo'=>'Nova habilidade do Arquétipo de Ladino.') ),
            10 => array( array('nome'=>'Ataque Furtivo (5d6)','resumo'=>'Melhoria de atributo este nível.'), array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            11 => array( array('nome'=>'Ataque Furtivo (6d6)','resumo'=>'O dano furtivo aumenta para 6d6.'), array('nome'=>'Talento Confiável','resumo'=>'Trate qualquer resultado 9 ou menor como 10 em testes com proficiência.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'monge' => array(
            1  => array( array('nome'=>'Artes Marciais','resumo'=>'Use Destreza em ataques desarmados. Dado de dano desarmado = 1d4.'), array('nome'=>'Defesa Sem Armadura','resumo'=>'CA = 10 + mod. Destreza + mod. Sabedoria sem armadura.') ),
            2  => array( array('nome'=>'Ki','resumo'=>'Pontos de ki (= nível) para habilidades especiais.'), array('nome'=>'Movimento Não-Armado','resumo'=>'+10 pés de deslocamento sem armadura.'), array('nome'=>'Rajada de Golpes','resumo'=>'Gaste 1 ki para atacar duas vezes como ação bônus.') ),
            3  => array( array('nome'=>'Tradição Monástica','resumo'=>'Escolha: Caminho da Mão Aberta, Sombra ou Elementos.'), array('nome'=>'Desvio','resumo'=>'Gaste 1 ki como reação para esquivar de projéteis.') ),
            4  => array( array('nome'=>'Queda Lenta','resumo'=>'Use reação para reduzir dano de queda em 5x o nível.'), array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Ataque Extra','resumo'=>'Ataque duas vezes ao usar a ação Atacar.'), array('nome'=>'Golpe Atordoante','resumo'=>'Gaste 1 ki ao acertar um ataque para forçar teste de Constituição ou atordoar.') ),
            6  => array( array('nome'=>'Golpes ki Empoderados','resumo'=>'Seus ataques desarmados contam como mágicos.'), array('nome'=>'Característica da Tradição','resumo'=>'Nova habilidade da Tradição Monástica.') ),
            7  => array( array('nome'=>'Tranquilidade','resumo'=>'Vantagem em resistências contra ser encantado ou amedrontado.'), array('nome'=>'Esquiva Sobrenatural','resumo'=>'Use reação para reduzir pela metade o dano de um ataque.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Movimento Não-Armado Aprimorado','resumo'=>'Corra por paredes e superfícies verticais sem cair.') ),
            10 => array( array('nome'=>'Pureza do Corpo','resumo'=>'Imunidade a doenças e venenos.') ),
            11 => array( array('nome'=>'Característica da Tradição','resumo'=>'Nova habilidade da Tradição Monástica.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'paladino' => array(
            1  => array( array('nome'=>'Sentido Divino','resumo'=>'Detecte celestiais, demônios e mortos-vivos a 60 pés. (1 + mod. Carisma) vezes por descanso.'), array('nome'=>'Imposição de Mãos','resumo'=>'Cura total de PV = nível × 5 por descanso longo.') ),
            2  => array( array('nome'=>'Estilo de Combate','resumo'=>'Escolha uma especialidade de combate.'), array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias sagradas usando Carisma.'), array('nome'=>'Golpe Divino','resumo'=>'Gaste slots de magia para adicionar dano radiante extra a ataques.') ),
            3  => array( array('nome'=>'Juramento Sagrado','resumo'=>'Escolha: Devoção, Ancestrais ou Vingança.'), array('nome'=>'Saúde Divina','resumo'=>'Imunidade a doenças.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Ataque Extra','resumo'=>'Ataque duas vezes ao usar a ação Atacar.') ),
            6  => array( array('nome'=>'Aura de Proteção','resumo'=>'Aliados a 10 pés adicionam seu mod. de Carisma em resistências.') ),
            7  => array( array('nome'=>'Característica do Juramento','resumo'=>'Nova habilidade do Juramento Sagrado escolhido.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            10 => array( array('nome'=>'Aura de Coragem','resumo'=>'Aliados a 10 pés são imunes ao medo.') ),
            11 => array( array('nome'=>'Golpe Divino Aprimorado','resumo'=>'O bônus de dano do Golpe Divino aumenta para 2d8 radiante.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'ranger' => array(
            1  => array( array('nome'=>'Inimigo Favorito','resumo'=>'Escolha um tipo de criatura: vantagem em rastreamento e memória, bônus em dano.'), array('nome'=>'Explorador Natural','resumo'=>'Escolha um terreno favorito onde você se destaca em rastreamento, forrageio e viagem.') ),
            2  => array( array('nome'=>'Estilo de Combate','resumo'=>'Escolha: Arquearia, Defesa ou Combate com Duas Armas.'), array('nome'=>'Conjuração de Magias','resumo'=>'Conjure magias da natureza usando Sabedoria.') ),
            3  => array( array('nome'=>'Arquétipo de Patrulheiro','resumo'=>'Escolha: Caçador ou Mestre das Bestas.'), array('nome'=>'Consciência Primitiva','resumo'=>'Expenda slots de magia para detectar criaturas ao redor.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Ataque Extra','resumo'=>'Ataque duas vezes ao usar a ação Atacar.') ),
            6  => array( array('nome'=>'Inimigo Favorito Aprimorado','resumo'=>'Escolha um segundo tipo de criatura como inimigo favorito.'), array('nome'=>'Explorador Natural Aprimorado','resumo'=>'Escolha um segundo terreno favorito.') ),
            7  => array( array('nome'=>'Característica do Arquétipo','resumo'=>'Nova habilidade do Arquétipo de Patrulheiro.') ),
            8  => array( array('nome'=>'Passada da Terra','resumo'=>'Ignore terreno difícil não-mágico. Não sofre dano de plantas com espinhos.'), array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            10 => array( array('nome'=>'Esconder-se à Vista','resumo'=>'Gaste 1 minuto para se camuflar e ganhar +10 em testes de Furtividade.') ),
            11 => array( array('nome'=>'Característica do Arquétipo','resumo'=>'Nova habilidade do Arquétipo de Patrulheiro.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),

        'bruxo' => array(
            1  => array( array('nome'=>'Patrono Sobrenatural','resumo'=>'Escolha um patrono: O Arquilich, O Grão-Antigo ou A Corte Feérica.'), array('nome'=>'Magia de Pacto','resumo'=>'Conjure magias via Carisma com slots que se recuperam no descanso curto.') ),
            2  => array( array('nome'=>'Dádivas Eldritch','resumo'=>'Escolha duas Dádivas Eldritch para customizar seus poderes.') ),
            3  => array( array('nome'=>'Bênção do Pacto','resumo'=>'Escolha: Lâmina do Pacto, Corrente do Pacto ou Tomo do Pacto.') ),
            4  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            5  => array( array('nome'=>'Magias de 3º Círculo','resumo'=>'Acesso a magias de 3º nível.') ),
            6  => array( array('nome'=>'Característica do Patrono','resumo'=>'Nova habilidade do Patrono Sobrenatural escolhido.') ),
            7  => array( array('nome'=>'Magias de 4º Círculo','resumo'=>'Acesso a magias de 4º nível.') ),
            8  => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
            9  => array( array('nome'=>'Magias de 5º Círculo','resumo'=>'Acesso a magias de 5º nível.') ),
            10 => array( array('nome'=>'Característica do Patrono','resumo'=>'Nova habilidade do Patrono Sobrenatural.') ),
            11 => array( array('nome'=>'Arcanum Místico (6º nível)','resumo'=>'Conjure uma magia de 6º nível sem gastar slot, uma vez por descanso longo.') ),
            12 => array( array('nome'=>'Melhoria de Atributo','resumo'=>'+2 em um atributo ou +1 em dois atributos diferentes.') ),
        ),
    );

    /**
     * Retorna habilidades desbloqueadas para uma classe até determinado nível.
     * Usado pela API e pela ficha do jogador.
     *
     * @param  string $classe  Nome da classe em PT-BR minúsculo (ex: 'guerreiro')
     * @param  int    $nivel   Nível atual do personagem
     * @return array           Lista de ['nome' => ..., 'resumo' => ...]
     */
    public static function get_habilidades_classe( $classe, $nivel ) {
        $classe_key = mb_strtolower( trim( $classe ), 'UTF-8' );

        // Normaliza variações de nome
        $map = array(
            'bárbaro'     => 'barbaro',
            'patrulheiro' => 'ranger',
            'feiticeiro'  => 'feiticeiro',
        );
        if ( isset( $map[ $classe_key ] ) ) {
            $classe_key = $map[ $classe_key ];
        }

        if ( ! isset( self::$habilidades_por_classe[ $classe_key ] ) ) {
            return array();
        }

        $habilidades = array();
        $tabela      = self::$habilidades_por_classe[ $classe_key ];

        for ( $n = 1; $n <= min( (int) $nivel, 12 ); $n++ ) {
            if ( isset( $tabela[ $n ] ) ) {
                foreach ( $tabela[ $n ] as $hab ) {
                    $habilidades[] = array(
                        'nivel'  => $n,
                        'nome'   => $hab['nome'],
                        'resumo' => $hab['resumo'],
                    );
                }
            }
        }

        return $habilidades;
    }

    public static function calcular_modificador( $valor ) {
        return floor( ($valor - 10) / 2 );
    }

    public static function calcular_hp( $classe, $constituicao ) {
        $dado = isset(self::$dados_vida[$classe]) ? self::$dados_vida[$classe] : 8;
        $mod_con = self::calcular_modificador($constituicao);
        return max(1, $dado + $mod_con);
    }

    public static function get_proficiencias( $classe ) {
        return isset(self::$proficiencias_por_classe[$classe])
            ? self::$proficiencias_por_classe[$classe]
            : array();
    }

    public static function get_equipamento( $classe ) {
        return isset(self::$equipamento_por_classe[$classe])
            ? self::$equipamento_por_classe[$classe]
            : array();
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

        // Adiciona equipamento inicial ao inventário
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
        $wpdb->update(
            $wpdb->prefix . 'dnd_personagens',
            array('hp_atual' => $novo_hp),
            array('id' => $personagem_id)
        );

        // Log
        $wpdb->insert( $wpdb->prefix . 'dnd_acoes_log', array(
            'personagem_id' => $personagem_id,
            'tipo'          => 'dano',
            'texto'         => "Sofreu {$dano} pontos de dano. HP: {$novo_hp}/{$p->hp_max}",
        ));

        if ($novo_hp === 0) {
            self::adicionar_condicao($personagem_id, 'inconsciente');
        }

        // Dispara gatilhos de conquista (inimigo = alvo externo)
        do_action( 'dndm_dano_aplicado', $personagem_id, $dano, $novo_hp, (int)$p->hp_max, 'inimigo' );

        return $novo_hp;
    }

    public static function aplicar_cura( $personagem_id, $cura ) {
        global $wpdb;
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return false;

        $novo_hp = min($p->hp_max, $p->hp_atual + $cura);
        $wpdb->update(
            $wpdb->prefix . 'dnd_personagens',
            array('hp_atual' => $novo_hp),
            array('id' => $personagem_id)
        );

        // Remove inconsciente se curou
        if ($novo_hp > 0) self::remover_condicao($personagem_id, 'inconsciente');

        return $novo_hp;
    }

    public static function adicionar_condicao( $personagem_id, $tipo ) {
        global $wpdb;
        // Evita duplicata
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dnd_condicoes WHERE personagem_id=%d AND tipo=%s",
            $personagem_id, $tipo
        ));
        if ($existe) return;

        $wpdb->insert($wpdb->prefix . 'dnd_condicoes', array(
            'personagem_id' => $personagem_id,
            'tipo'          => sanitize_text_field($tipo),
        ));
    }

    public static function remover_condicao( $personagem_id, $tipo ) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'dnd_condicoes', array(
            'personagem_id' => $personagem_id,
            'tipo'          => $tipo,
        ));
    }

    public static function adicionar_item( $personagem_id, $item ) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'dnd_inventario', array(
            'personagem_id' => $personagem_id,
            'nome'          => sanitize_text_field($item['nome']),
            'descricao'     => sanitize_textarea_field($item['descricao'] ?? ''),
            'tipo'          => sanitize_text_field($item['tipo'] ?? 'item'),
            'quantidade'    => intval($item['quantidade'] ?? 1),
            'imagem_url'    => sanitize_url($item['imagem_url'] ?? ''),
        ));
    }

    public static function ganhar_xp( $personagem_id, $xp ) {
        global $wpdb;
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return false;

        $novo_xp    = $p->xp + $xp;
        $novo_nivel = self::calcular_nivel($novo_xp);

        $wpdb->update(
            $wpdb->prefix . 'dnd_personagens',
            array('xp' => $novo_xp, 'nivel' => $novo_nivel),
            array('id' => $personagem_id)
        );

        return array('xp' => $novo_xp, 'nivel' => $novo_nivel, 'subiu' => $novo_nivel > $p->nivel);
    }

    private static function calcular_nivel( $xp ) {
        $tabela = array(0,300,900,2700,6500,14000,23000,34000,48000,64000,85000,100000,120000,140000,165000,195000,225000,265000,305000,355000);
        $nivel  = 1;
        foreach ($tabela as $i => $req) {
            if ($xp >= $req) $nivel = $i + 1;
        }
        return min(20, $nivel);
    }

    public static function get_ficha_completa( $personagem_id ) {
        $p = DNDM_Database::get_personagem($personagem_id);
        if (!$p) return null;

        $p->atributos           = json_decode($p->atributos, true) ?: array();
        $p->aparencia           = json_decode($p->aparencia, true) ?: array();
        $p->proficiencias       = json_decode($p->proficiencias, true) ?: array();
        $p->equipamento_inicial = json_decode($p->equipamento_inicial, true) ?: array();
        $p->condicoes           = DNDM_Database::get_condicoes($personagem_id);
        $p->inventario          = DNDM_Database::get_inventario($personagem_id);
        $p->modificadores       = array();
        $p->habilidades_classe  = self::get_habilidades_classe( $p->classe, $p->nivel );

        foreach ($p->atributos as $attr => $val) {
            $p->modificadores[$attr] = self::calcular_modificador($val);
        }

        return $p;
    }
}
