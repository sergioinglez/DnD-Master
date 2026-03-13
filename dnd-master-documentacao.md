# DnD Master Platform — Documentação do Projeto

> Última atualização: Sessão 2 — Sistema base + DLC1 Roll Tables
> Versão do plugin: `0.0.4` | Tema: `0.0.3` | DLC1: `1.0.0`

---

## 🗺️ Visão Geral

Sistema de Tabletop (D&D 5e) construído em WordPress, separado em três camadas independentes:

| Camada | Arquivo ZIP | Responsabilidade |
|--------|-------------|-----------------|
| **Plugin** | `dnd-v6.zip` | Sistema central: banco de dados, usuários, módulos, campanhas, IA, API REST, loja de DLCs |
| **Tema** | `dnd-master-theme.zip` | Visual: Landing Page, template do painel, editor visual no admin |
| **DLC 1** | `dnd-dlc-roll-tables.zip` | Tabelas de rolagem contextuais com IA |

**Princípio de separação adotado:** sistema é sistema, tema é tema, DLC é DLC. Cada camada pode ser desenvolvida, depurada e versionada de forma independente. O sistema funciona normalmente sem nenhuma DLC ativa.

---

## 🐛 Bugs Corrigidos (Sessão 2)

### Bug 1 — Erro 500 / Fatal Error ao ativar tema + plugin juntos

**Causa raiz:** Função `selected_val()` declarada em dois arquivos ao mesmo tempo:
- `dnd-master.php` (plugin) — `function selected_val($a, $b)`
- `admin/theme-admin.php` (tema) — mesmo nome de função

Quando o WordPress carregava ambos, o PHP disparava:
`Fatal error: Cannot redeclare function selected_val()`
derrubando o site inteiro com erro 500.

**Por que o plugin sozinho funcionava:** apenas um arquivo declarava a função.

**Por que na ordem inversa (plugin primeiro, depois tema) parecia funcionar:** o WordPress completava a ativação do plugin antes de processar o tema — mas o erro ainda ocorria no próximo request completo.

**Correção aplicada:**
1. **No plugin (v0.0.4):** `selected_val()`, `lpRow()`, `lpRange()` e toda a função `dndm_page_lp()` foram **removidos completamente**. A LP já havia sido migrada para o tema — o plugin não deveria mais tê-la.
2. **No tema (v0.0.3):** `selected_val()` renomeada para `dndmt_selected_val()`, seguindo o prefixo padrão do tema. Todas as chamadas internas atualizadas.

### Bug 2 — Editor de Landing Page duplicado / conflito de responsabilidades

**Causa:** O plugin ainda registrava o submenu LP (`dnd-master-lp`) e o tema tentava removê-lo via `remove_submenu_page()`. Isso criava dependência de ordem de carregamento e era fonte latente de outros bugs.

**Correção:** O submenu LP foi removido definitivamente do plugin. O `functions.php` do tema não chama mais `remove_submenu_page()` — a linha foi removida. O tema é a única fonte de verdade para tudo que é visual.

---

## 📁 Estrutura de Arquivos

### Plugin `dnd-v6`

```
dnd-v6/
├── dnd-master.php                  # Ponto de entrada. Constantes, autoload, hooks, menus admin
│                                   # v0.0.4: removida LP, adicionada loja de DLCs
├── includes/
│   ├── class-database.php          # Criação e atualização das tabelas MySQL (dbDelta)
│   ├── class-auth.php              # Autenticação, tiers, permissões (can_mestrar etc.)
│   ├── class-personagem.php        # CRUD e lógica de personagens, level up, XP
│   ├── class-campanha.php          # CRUD de campanhas, importação de módulos JSON
│   ├── class-groq.php              # Integração Groq (IA narrativa)
│   │                               # v0.0.4: +método completar() genérico para DLCs
│   ├── class-imagem.php            # Geração de imagens via Pollinations
│   ├── class-api.php               # Endpoints REST do sistema principal
│   ├── class-platform.php          # Rotas, template, config JSON do frontend
│   │                               # v0.0.4: +filtro dndm_config_data para DLCs injetarem dados
│   ├── class-lp-editor.php         # Config da LP salva em JSON no banco
│   └── class-dlc-registry.php      # NOVO: catálogo completo + install/activate/deactivate
├── templates/
│   └── plataforma.php              # Template base do frontend (sobrescrito pelo tema)
└── assets/
    ├── css/platform.css
    └── js/app.js                   # Aplicação JS do painel
```

### Tema `dnd-master-theme`

```
dnd-master-theme/
├── functions.php                   # Dependência do plugin, override de template
│                                   # v0.0.3: REMOVIDA chamada remove_submenu_page
├── admin/
│   └── theme-admin.php             # Editor visual da LP no wp-admin
│                                   # v0.0.3: CORRIGIDO selected_val → dndmt_selected_val
│                                   #         lpRow → dndmt_lpRow, lpRange → dndmt_lpRange
├── templates/
│   └── plataforma.php              # Template completo do painel (sobrescreve o do plugin)
├── assets/
│   ├── css/platform.css
│   └── js/app.js
├── index.php                       # Obrigatório pelo WordPress
└── style.css                       # Cabeçalho do tema (Theme Name, Version etc.)
```

### DLC 1 `dnd-dlc-roll-tables`

```
dnd-dlc-roll-tables/
├── dnd-dlc-roll-tables.php         # Plugin principal. Registra com o sistema central,
│                                   # enfileira assets, injeta dados via filtro dndm_config_data
├── includes/
│   ├── class-rt-database.php       # Tabelas: dnd_rt_categorias, dnd_rt_tabelas,
│   │                               #          dnd_rt_itens, dnd_rt_historico
│   ├── class-rt-admin.php          # Painel admin: CRUD de tabelas, categorias, histórico,
│   │                               # gerador de itens com IA contextual
│   └── class-rt-api.php            # Endpoints REST: /rolar, /listar, /gerar-ia
└── assets/
    ├── css/roll-tables.css         # Estilos do painel flutuante (tema escuro medieval)
    └── js/roll-tables.js           # Lógica do painel 🎲 no frontend do Mestre
```

---

## 🗄️ Banco de Dados

### Tabelas do Sistema Principal

| Tabela | Descrição |
|--------|-----------|
| `dnd_usuarios` | Usuários com tier (tier1/2/3) |
| `dnd_modulos` | Aventuras importadas via JSON |
| `dnd_campanhas` | Campanhas vinculadas a módulos e mestres |
| `dnd_inscricoes` | Jogadores inscritos em campanhas |
| `dnd_personagens` | Fichas completas de personagens |
| `dnd_inventario` | Itens no inventário de cada personagem |
| `dnd_sessoes` | Registros de sessões de jogo |
| `dnd_acoes_log` | Log de ações durante as sessões |
| `dnd_npcs` | NPCs gerados ou importados por módulo |
| `dnd_checklist` | Etapas/objetivos de cada módulo |
| `dnd_ganchos` | Ganchos narrativos gerados pela IA |
| `dnd_condicoes` | Condições de status dos personagens |

### Tabelas da DLC 1 — Roll Tables

| Tabela | Descrição |
|--------|-----------|
| `dnd_rt_categorias` | Categorias de tabelas (Encontros, Loot, Clima...) |
| `dnd_rt_tabelas` | Tabelas de rolagem com dado e categoria |
| `dnd_rt_itens` | Itens individuais de cada tabela com peso |
| `dnd_rt_historico` | Histórico de rolagens com resultado original e enriquecido pela IA |

---

## ⚙️ Sistema de DLCs

### Filosofia de design

DLCs são plugins WordPress independentes que se "apresentam" ao sistema central ao serem ativadas. O sistema funciona 100% sem nenhuma DLC. Cada DLC é desenvolvida, testada e depurada de forma totalmente isolada — se uma DLC quebrar, o sistema principal não cai.

### Fluxo de instalação (Opção A — local, sem internet)

1. Colocar o ZIP da DLC em `/wp-content/dnd-dlcs/`
2. No painel: ⚔ DnD Master → 🧩 DLCs → botão **Instalar**
3. Após instalação: botão vira **Ativar**
4. DLC aparece como ativa na loja

**Por que Opção A:** O sistema é de uso local/presencial. Não faz sentido depender de internet para instalar funcionalidades. A Opção B (download automático) pode ser implementada futuramente se necessário.

### Estados possíveis de uma DLC na loja

| Estado | Descrição |
|--------|-----------|
| `em_breve` | Planejada, ainda não disponível |
| `nao_instalado` | ZIP não encontrado em `/wp-content/dnd-dlcs/` |
| `zip_disponivel` | ZIP encontrado, pronta para instalar |
| `instalado` | Plugin extraído, pronto para ativar |
| `ativo` | Plugin ativo e registrado no sistema |

### Contrato de integração — como uma DLC se registra

```php
// No init() da DLC — obrigatório:
do_action( 'dndm_dlc_register', array(
    'id'     => 'roll-tables',  // deve bater com o id no catálogo
    'nome'   => 'Roll Tables',
    'versao' => '1.0.0',
));

// Na desativação — obrigatório para limpar o registro:
register_deactivation_hook( __FILE__, function() {
    DNDM_DLC_Registry::desregistrar('roll-tables');
});
```

### Como uma DLC injeta dados no frontend

O sistema principal expõe um filtro na montagem do config JSON:

```php
add_filter( 'dndm_config_data', function( $config ) {
    $config['dlcs']['minha_dlc'] = array(
        'ativo'  => true,
        'versao' => '1.0.0',
        'dados'  => [...],
    );
    return $config;
});
```

O JS do frontend acessa via:
```js
const dados = window.DNDM_CONFIG.dlcs.minha_dlc;
```

### Como uma DLC usa a IA do sistema

O método `DNDM_Groq::completar()` foi adicionado na v0.0.4 especificamente para uso pelas DLCs, sem precisar conhecer os detalhes internos do Groq:

```php
$resultado = DNDM_Groq::completar( $prompt_completo, $max_tokens );
if ( is_wp_error($resultado) ) {
    // tratar erro
}
// $resultado é a string de resposta do modelo
```

### Como uma DLC adiciona submenu no admin

```php
// Dentro do init() da DLC:
add_submenu_page(
    'dnd-master',               // parent — aparece dentro de ⚔ DnD Master
    'Minha DLC',
    '🔧 Minha DLC',
    'manage_options',
    'dnd-master-minha-dlc',
    'minha_dlc_pagina'
);
```

---

## 🏪 Catálogo de DLCs — Roadmap

| # | DLC | Status | Descrição curta |
|---|-----|--------|-----------------|
| 1 | 🎲 Roll Tables | ✅ **Disponível** | Tabelas de rolagem contextuais com IA |
| 2 | ⚔️ Combat Tracker | 🔜 Em breve | Tracker de iniciativa, condições, rounds |
| 3 | 🗺️ Scenes & Mapas | 🔜 Em breve | Troca de cena em tempo real para todos |
| 4 | 🌫️ Tokens & Fog of War | 🔜 Em breve | Tokens arrastáveis + névoa de guerra |
| 5 | 📖 DM Screen Digital | 🔜 Em breve | Painel auxiliar com referências rápidas |
| 6 | 🎰 Rolagem de Dados Visual | 🔜 Em breve | Dados animados no chat |
| 7 | 🌦️ AOE & Weather | 🔜 Em breve | Templates de área de efeito + clima |

**DLC 8 (P2P Vídeo) — descartada:** O sistema é presencial/local. WebRTC exigiria servidores TURN/STUN e seria complexidade desnecessária para o caso de uso.

**DLCs 3 e 4 — decisão pendente:** Precisarão de comunicação em tempo real entre Mestre e Jogadores (sincronização de mapa/tokens). A escolha entre WebSocket nativo (mais robusto) e polling simples (mais compatível) foi postergada para quando chegarmos nessas DLCs.

---

## 🎲 DLC 1 — Roll Tables em detalhe

### Funcionalidades

**No admin (⚔ DnD Master → 🎲 Roll Tables):**
- Gerenciar categorias de tabelas (Encontros, Loot, Clima, Nomes, Efeitos Mágicos, Viagem)
- Criar e editar tabelas com itens numerados e pesos de probabilidade
- Gerador de itens com IA: Mestre descreve o contexto, Groq cria/enriquece os itens
- Histórico das últimas 50 rolagens com resultado original e versão IA

**No painel do Mestre (frontend) — botão flutuante 🎲:**
- Seletor de tabela agrupado por categoria
- Campo de contexto: Mestre descreve a cena em linguagem simples
- Toggle "Enriquecer com IA" — quando ativo, o Groq reescreve o resultado de forma narrativa
- Resultado exibido com badge do dado, texto principal e original
- Histórico rápido das últimas 5 rolagens na sessão

### Endpoints REST

| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| `POST` | `/dnd-master/v1/roll-tables/rolar` | Logado | Rola numa tabela, opcionalmente enriquece com IA |
| `GET` | `/dnd-master/v1/roll-tables/listar` | Logado | Lista tabelas disponíveis |
| `POST` | `/dnd-master/v1/roll-tables/gerar-ia` | Admin | Gera/enriquece itens com Groq |

### Fluxo de rolagem com IA

```
Mestre escreve contexto
    → clica ROLAR
        → API POST /rolar {tabela_id, contexto, usar_ia: true}
            → sorteio ponderado por peso
            → Groq::completar(prompt com contexto + resultado)
                → resposta narrativa em 2 frases
            → salva no histórico com original + versão IA
        → frontend exibe resultado enriquecido
            → original visível abaixo em cinza
```

### Categorias padrão criadas na ativação

- 🗡️ Encontros Aleatórios
- 💰 Loot & Tesouro
- 🌦️ Clima & Ambiente
- 📛 Nomes & Lugares
- 🧪 Efeitos Mágicos
- 🗺️ Eventos de Viagem

---

## 📋 Menus do Admin

### ⚔ DnD Master (plugin)
- **Dashboard** — Métricas (módulos, campanhas, jogadores, personagens, DLCs ativas), links rápidos, ferramentas
- **📜 Módulos** — Importar aventuras JSON, gerenciar materiais (PDF, mapas, NPCs com IA)
- **👥 Jogadores** — Criar usuários, alterar tiers, excluir
- **🧙 Personagens** — Visualizar fichas completas com atributos, HP, inventário
- **🧩 DLCs** — Loja visual com todas as DLCs planejadas, instalar/ativar/desativar
- **⚙ Configurações** — API keys (Groq + Pollinations) + link para editor visual do tema

### 🎨 DnD Tema (tema)
- **🏠 Landing Page** — Editor visual completo: Hero, Features, Módulos, FAQ, Efeitos, Tipografia, Cores, Rodapé + preview ao vivo

### 🎲 Roll Tables (DLC 1, submenu de ⚔ DnD Master)
- **📋 Tabelas** — Lista de tabelas + criar nova
- **🗂 Categorias** — Gerenciar categorias
- **📜 Histórico** — Últimas 50 rolagens

---

## 👥 Sistema de Tiers

| Tier | Permissões |
|------|-----------|
| **Admin** | WordPress admin — acesso total ao wp-admin |
| **Tier 1** | Mestrar, jogar, gerenciar módulos, criar usuários |
| **Tier 2** | Mestrar, jogar, criar usuários Tier 3 |
| **Tier 3** | Somente jogar — redirecionado de /wp-admin para /dnd-painel |

---

## 🏗️ Rotas do Frontend

| URL | Descrição |
|-----|-----------|
| `/` | Landing Page (home) |
| `/dnd-painel` | Painel principal (redireciona por tier) |
| `/dnd-mestre` | Alias para o painel do Mestre |
| `/dnd-aventura` | Alias para a tela do Jogador |

Todas as rotas são gerenciadas por `DNDM_Platform` via WordPress rewrite rules. Após qualquer mudança nas rotas: **Configurações → Permalinks → Salvar**.

---

## 🔑 Setup Inicial

1. Instalar o plugin `dnd-v6.zip` via wp-admin → Plugins
2. Instalar o tema `dnd-master-theme.zip` via wp-admin → Aparência → Temas
3. Ativar o **plugin primeiro**, depois o **tema** — nessa ordem (embora com a correção da v0.0.4 qualquer ordem funcione)
4. Ir em **Configurações → Permalinks → Salvar** (necessário para as rewrite rules)
5. Configurar Groq API Key em **⚔ DnD Master → ⚙ Configurações**
6. Acessar a home — LP deve aparecer
7. Login admin → `/dnd-painel`

### Para instalar a DLC 1

1. Copiar `dnd-dlc-roll-tables.zip` para `/wp-content/dnd-dlcs/`
2. No admin: **⚔ DnD Master → 🧩 DLCs → Instalar → Ativar**
3. Pronto — submenu 🎲 Roll Tables aparece em ⚔ DnD Master

---

## 📌 Decisões de Projeto e Porquês

| Decisão | Motivo |
|---------|--------|
| Plugin + Tema separados | Isolar bugs: sistema != visual. Cada um tem seu ciclo de versão. |
| DLCs como plugins WordPress | WP já tem infraestrutura de ativação/desativação. Sem reinventar a roda. |
| Instalação via ZIP local (Opção A) | Sistema presencial/local. Sem dependência de internet para instalar funcionalidades. |
| DLC8 (P2P vídeo) descartada | Uso local apenas — WebRTC seria complexidade sem retorno. |
| `DNDM_Groq::completar()` adicionado | DLCs precisam de IA sem conhecer os internos do Groq. Interface limpa e estável. |
| Filtro `dndm_config_data` | Forma segura de DLCs injetarem dados no frontend sem acoplar ao código central. |
| `dndmt_` prefix no tema | Evita qualquer colisão futura com plugin, outros temas ou plugins de terceiros. |
| WebSocket vs Polling — postergado | Decisão relevante apenas nas DLCs 3/4. Não bloqueia o desenvolvimento atual. |
| Sistema de dependência entre módulos | Planejado para depois do sistema base estar estável e testado. |

---

## 🔄 Histórico de Versões

### Plugin `dnd-v6`
- **v0.0.3** — Versão original entregue
- **v0.0.4** — Removida LP do plugin, adicionada loja de DLCs, `class-dlc-registry.php`, filtro `dndm_config_data`, método `DNDM_Groq::completar()`

### Tema `dnd-master-theme`
- **v0.0.2** — Versão original entregue
- **v0.0.3** — `selected_val` → `dndmt_selected_val`, removida chamada `remove_submenu_page`

### DLC 1 `dnd-dlc-roll-tables`
- **v1.0.0** — Versão inicial: tabelas, categorias, rolagem com pesos, enriquecimento IA, histórico, painel flutuante no frontend
