<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DNDM_Groq {

    private static $modelo = 'llama-3.3-70b-versatile';
    private static $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

    private static function get_api_key() {
        return get_option('dndm_groq_key', '');
    }

    public static function chamar( $system_prompt, $user_prompt, $max_tokens = 800, $timeout = 30 ) {
        $api_key = self::get_api_key();
        if (empty($api_key)) return new WP_Error('sem_key', 'API key do Groq não configurada');

        $response = wp_remote_post(self::$endpoint, array(
            'timeout' => $timeout,
            'headers' => array(
                'Authorization' => "Bearer {$api_key}",
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode(array(
                'model'      => self::$modelo,
                'max_tokens' => $max_tokens,
                'messages'   => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user',   'content' => $user_prompt),
                ),
            )),
        ));

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? new WP_Error('resposta_vazia', 'Resposta vazia do Groq');
    }

    public static function gerar_backstory( $personagem, $timeout = 30 ) {
        $system = "Você é um escritor criativo especialista em D&D 5e. Gere conteúdo narrativo em português brasileiro. Seja criativo, evocativo e consistente com o universo de fantasia medieval. Responda APENAS em JSON válido, sem markdown.";

        $user = "Gere o perfil completo para este personagem de D&D:
Nome: {$personagem['nome']}
Raça: {$personagem['raca']}
Classe: {$personagem['classe']}
Gênero: {$personagem['genero']}
Antecedente: {$personagem['antecedente']}
Alinhamento: {$personagem['alinhamento']}

Responda em JSON com esta estrutura exata:
{
  \"backstory\": \"história de origem em 3-4 parágrafos\",
  \"personalidade\": \"2-3 traços de personalidade\",
  \"ideal\": \"o que motiva este personagem\",
  \"vinculo\": \"o que ou quem é mais importante para ele\",
  \"fraqueza\": \"seu maior defeito ou medo\"
}";

        $resultado = self::chamar($system, $user, 1000, $timeout);
        if (is_wp_error($resultado)) return $resultado;

        $json = json_decode($resultado, true);
        if (!$json) return new WP_Error('json_invalido', 'Resposta inválida do Groq');

        return $json;
    }

    public static function gerar_gancho( $modelo, $campanha_data ) {
        $modelos_desc = array(
            'npc_mensageiro'     => 'Um NPC aparece com uma mensagem ou notícia urgente que redireciona os jogadores',
            'encontro_forcado'   => 'Um encontro (inimigos, obstáculo, evento) bloqueia o caminho atual e força uma mudança de rota',
            'descoberta_acidental' => 'Os jogadores encontram uma pista, objeto ou informação importante sem procurar',
            'ameaca_iminente'    => 'Uma ameaça urgente surge que exige ação imediata dos jogadores',
            'mentor_aparece'     => 'Uma figura de confiança ou autoridade aparece e sutilmente guia os jogadores de volta',
        );

        $desc_modelo = $modelos_desc[$modelo] ?? 'Um evento inesperado que redireciona os jogadores';

        $system = "Você é um Mestre de D&D experiente. Crie ganchos narrativos envolventes em português brasileiro. Responda APENAS em JSON válido, sem markdown.";

        $user = "Crie um gancho narrativo para redirecionar os jogadores de volta à história principal.

Campanha: {$campanha_data['nome']}
Módulo: {$campanha_data['modulo_nome']}
Personagens: {$campanha_data['personagens']}
Situação atual: {$campanha_data['situacao']}
Objetivo principal que está sendo ignorado: {$campanha_data['objetivo_ignorado']}

Modelo de gancho: {$desc_modelo}

Responda em JSON:
{
  \"titulo\": \"título curto do gancho\",
  \"narrativa\": \"texto pronto para o mestre narrar (2-3 parágrafos)\",
  \"dica_mestre\": \"instrução em itálico para o mestre sobre como usar este gancho\"
}";

        $resultado = self::chamar($system, $user, 600);
        if (is_wp_error($resultado)) return $resultado;

        $json = json_decode($resultado, true);
        if (!$json) return new WP_Error('json_invalido', 'Resposta inválida do Groq');

        return $json;
    }

    public static function gerar_npc( $contexto ) {
        $system = "Você é um Mestre de D&D criativo. Crie NPCs interessantes e memoráveis em português brasileiro. Responda APENAS em JSON válido, sem markdown.";

        $user = "Crie um NPC para a campanha:
Contexto: {$contexto['descricao']}
Papel na história: {$contexto['papel']}
Tom da campanha: {$contexto['tom']}

Responda em JSON:
{
  \"nome\": \"nome do NPC\",
  \"raca\": \"raça\",
  \"aparencia\": \"descrição física detalhada\",
  \"personalidade\": \"como age e fala\",
  \"segredo\": \"algo que esconde\",
  \"ganchos\": \"como pode ser útil na história\",
  \"prompt_imagem\": \"prompt em inglês para gerar imagem (30 palavras)\"
}";

        $resultado = self::chamar($system, $user, 700);
        if (is_wp_error($resultado)) return $resultado;

        $json = json_decode($resultado, true);
        if (!$json) return new WP_Error('json_invalido', 'Resposta inválida do Groq');

        return $json;
    }

    public static function sugerir_proxima_cena( $campanha_data ) {
        $system = "Você é um Mestre de D&D experiente. Analise o progresso da campanha e sugira o próximo passo. Responda em português brasileiro.";

        $user = "Analise esta campanha e sugira a próxima cena:

Módulo: {$campanha_data['modulo_nome']}
Ações obrigatórias concluídas: {$campanha_data['obrigatorias_concluidas']}
Ações obrigatórias pendentes: {$campanha_data['obrigatorias_pendentes']}
Última ação registrada: {$campanha_data['ultima_acao']}

Sugira em 2-3 parágrafos como o mestre pode conduzir a próxima cena de forma natural, mantendo os jogadores engajados e avançando na história.";

        return self::chamar($system, $user, 500);
    }

    /**
     * Completação genérica para uso pelas DLCs.
     * Interface simples: prompt completo → string de resposta.
     */
    public static function completar( $prompt, $max_tokens = 500 ) {
        return self::chamar(
            'Você é um assistente especialista em D&D 5e. Responda sempre em português brasileiro.',
            $prompt,
            $max_tokens
        );
    }
}
