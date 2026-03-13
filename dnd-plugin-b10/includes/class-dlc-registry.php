<?php
/**
 * DNDM_DLC_Registry — Sistema de DLCs do DnD Master
 *
 * Cada DLC é um plugin WordPress mínimo que declara no cabeçalho:
 *   DLC-Type:     module | mechanic | cosmetic
 *   DLC-Slug:     kebab-case único
 *   DLC-Requires: versão mínima do core (ex: 0.0.4)
 *
 * E chama no `plugins_loaded`:
 *   do_action('dndm_register_dlc', $manifest_array);
 */

if ( ! defined('ABSPATH') ) exit;

class DNDM_DLC_Registry {

    /** Catálogo de DLCs registrados nesta requisição (em memória) */
    private static array $registry = [];

    /** Erros de validação indexados por slug */
    private static array $errors = [];

    // ──────────────────────────────────────────────────────────────────────────
    // INICIALIZAÇÃO
    // ──────────────────────────────────────────────────────────────────────────

    public static function init(): void {
        // Janela de registro: plugins carregados, antes do init do WP
        add_action('plugins_loaded', [ __CLASS__, 'abrir_janela_registro' ], 5);
        // Fechar janela e processar após todos os plugins carregarem
        add_action('plugins_loaded', [ __CLASS__, 'fechar_janela_registro' ], 99);
        // Admin: página de DLCs
        add_action('admin_menu',     [ __CLASS__, 'adicionar_submenu'       ], 20);
        // REST: endpoint /dlcs
        add_action('rest_api_init',  [ __CLASS__, 'registrar_endpoint_rest' ]);
    }

    public static function abrir_janela_registro(): void {
        // Disponibiliza o hook para os DLCs se registrarem
        add_action('dndm_register_dlc', [ __CLASS__, 'registrar' ], 10, 1);
    }

    public static function fechar_janela_registro(): void {
        // Salva no banco o estado atual (novos + removidos)
        self::sincronizar_banco();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REGISTRO
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Chamado por cada DLC via: do_action('dndm_register_dlc', $manifest)
     *
     * $manifest esperado:
     * [
     *   'slug'        => 'mina-phandelver',          // obrigatório, único
     *   'name'        => 'A Mina Perdida de Phandelver', // obrigatório
     *   'version'     => '1.0.0',                    // obrigatório
     *   'type'        => 'module',                   // module | mechanic | cosmetic
     *   'requires'    => '0.0.4',                    // versão mínima do core
     *   'author'      => 'Nome do autor',
     *   'description' => 'Descrição curta',
     *   'icon'        => '📦',                       // emoji ou URL
     *   // Callbacks (opcional por tipo):
     *   'on_activate'   => callable,  // executa ao ser ativado pela 1ª vez
     *   'on_deactivate' => callable,  // executa ao desativar
     *   // Dados do DLC (opcional por tipo):
     *   'data'          => [],        // módulos, badges extras, CSS, etc.
     * ]
     */
    public static function registrar( array $manifest ): void {
        $slug = sanitize_key( $manifest['slug'] ?? '' );

        if ( empty($slug) ) {
            error_log('[DnD DLC] Tentativa de registro sem slug.');
            return;
        }

        // Valida campos obrigatórios
        $required = [ 'name', 'version', 'type' ];
        foreach ( $required as $field ) {
            if ( empty($manifest[$field]) ) {
                self::$errors[$slug] = "Campo obrigatório ausente: {$field}";
                error_log("[DnD DLC] {$slug}: {$field} ausente.");
                return;
            }
        }

        // Valida tipo
        $tipos_validos = [ 'module', 'mechanic', 'cosmetic' ];
        if ( ! in_array($manifest['type'], $tipos_validos, true) ) {
            self::$errors[$slug] = "Tipo inválido: {$manifest['type']}";
            return;
        }

        // Verifica versão mínima do core
        $requires = $manifest['requires'] ?? '0.0.1';
        if ( version_compare( DNDM_VERSION, $requires, '<' ) ) {
            self::$errors[$slug] = "Requer DnD Master >= {$requires} (atual: " . DNDM_VERSION . ")";
            error_log("[DnD DLC] {$slug}: core insuficiente ({$requires} necessário).");
            return;
        }

        // Slug duplicado
        if ( isset(self::$registry[$slug]) ) {
            self::$errors[$slug] = "Slug duplicado — outro DLC já registrou '{$slug}'.";
            error_log("[DnD DLC] Slug duplicado: {$slug}");
            return;
        }

        // Normaliza e armazena
        self::$registry[$slug] = array_merge([
            'slug'        => $slug,
            'name'        => '',
            'version'     => '1.0.0',
            'type'        => 'module',
            'requires'    => '0.0.1',
            'author'      => '',
            'description' => '',
            'icon'        => '📦',
            'data'        => [],
            'on_activate'   => null,
            'on_deactivate' => null,
        ], $manifest, [ 'slug' => $slug ]); // slug sanitizado sobrescreve

        // Dispara hook para o DLC customizar o sistema
        do_action( "dndm_dlc_loaded_{$slug}", self::$registry[$slug] );
        do_action( 'dndm_dlc_loaded', self::$registry[$slug] );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BANCO DE DADOS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Sincroniza o registro em memória com a tabela dnd_dlcs.
     * - Novos DLCs: INSERT + chama on_activate
     * - DLCs ausentes (desinstalados): marca status = 'inativo'
     * - DLCs presentes: atualiza versão/status
     */
    private static function sincronizar_banco(): void {
        global $wpdb;
        $tabela = $wpdb->prefix . 'dnd_dlcs';

        // Busca todos os DLCs já conhecidos no banco
        $no_banco = $wpdb->get_results("SELECT * FROM {$tabela}", ARRAY_A);
        $slugs_banco = array_column($no_banco, 'slug');

        foreach ( self::$registry as $slug => $dlc ) {
            if ( in_array($slug, $slugs_banco, true) ) {
                // Atualiza versão e status
                $wpdb->update( $tabela,
                    [ 'version' => $dlc['version'], 'status' => 'ativo', 'name' => $dlc['name'], 'type' => $dlc['type'] ],
                    [ 'slug' => $slug ]
                );
            } else {
                // Novo DLC — insere e chama on_activate
                $wpdb->insert( $tabela, [
                    'slug'       => $slug,
                    'name'       => $dlc['name'],
                    'type'       => $dlc['type'],
                    'version'    => $dlc['version'],
                    'author'     => $dlc['author'],
                    'status'     => 'ativo',
                    'ativado_em' => current_time('mysql'),
                ]);
                error_log("[DnD DLC] Novo DLC ativado: {$slug} v{$dlc['version']}");

                // Executa callback de ativação do DLC
                if ( is_callable($dlc['on_activate']) ) {
                    call_user_func( $dlc['on_activate'] );
                }
            }
        }

        // Marca como inativo DLCs que não se registraram (plugin desativado/removido)
        foreach ( $no_banco as $row ) {
            if ( ! isset(self::$registry[$row['slug']]) && $row['status'] === 'ativo' ) {
                $wpdb->update( $tabela, [ 'status' => 'inativo' ], [ 'slug' => $row['slug'] ] );
                error_log("[DnD DLC] DLC desativado: {$row['slug']}");
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GETTERS PÚBLICOS
    // ──────────────────────────────────────────────────────────────────────────

    /** Lista todos os DLCs registrados nesta requisição */
    public static function get_ativos(): array {
        return self::$registry;
    }

    /** Retorna um DLC específico pelo slug, ou null */
    public static function get( string $slug ): ?array {
        return self::$registry[$slug] ?? null;
    }

    /** Retorna DLCs filtrados por tipo */
    public static function get_por_tipo( string $tipo ): array {
        return array_filter( self::$registry, fn($d) => $d['type'] === $tipo );
    }

    /** Verifica se um DLC está ativo */
    public static function ativo( string $slug ): bool {
        return isset( self::$registry[$slug] );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REST API
    // ──────────────────────────────────────────────────────────────────────────

    public static function registrar_endpoint_rest(): void {
        register_rest_route( 'dnd/v1', '/dlcs', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'endpoint_listar' ],
            'permission_callback' => fn() => is_user_logged_in(),
        ]);
    }

    public static function endpoint_listar(): WP_REST_Response {
        global $wpdb;

        $ativos = array_values( array_map(
            fn($d) => [
                'slug'        => $d['slug'],
                'name'        => $d['name'],
                'version'     => $d['version'],
                'type'        => $d['type'],
                'author'      => $d['author'],
                'description' => $d['description'],
                'icon'        => $d['icon'],
            ],
            self::$registry
        ));

        $historico = $wpdb->get_results(
            "SELECT slug, name, type, version, status, ativado_em FROM {$wpdb->prefix}dnd_dlcs ORDER BY ativado_em DESC",
            ARRAY_A
        );

        return new WP_REST_Response([
            'ativos'    => $ativos,
            'historico' => $historico,
            'total'     => count($ativos),
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ADMIN
    // ──────────────────────────────────────────────────────────────────────────

    public static function adicionar_submenu(): void {
        add_submenu_page(
            'dnd-master',
            'DLCs',
            '🧩 DLCs',
            'manage_options',
            'dnd-master-dlcs',
            [ __CLASS__, 'pagina_admin' ]
        );
    }

    public static function pagina_admin(): void {
        global $wpdb;

        $ativos    = self::$registry;
        $erros     = self::$errors;
        $historico = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dnd_dlcs ORDER BY ativado_em DESC",
            ARRAY_A
        );

        $tipo_labels = [
            'module'   => [ 'label' => 'Módulo de Aventura', 'cor' => '#7c3aed', 'icon' => '📦' ],
            'mechanic' => [ 'label' => 'Mecânica',           'cor' => '#dc2626', 'icon' => '⚔️' ],
            'cosmetic' => [ 'label' => 'Cosmético',          'cor' => '#d97706', 'icon' => '🎨' ],
        ];
        ?>
        <div class="wrap">
            <h1>⚔ DnD Master <span style="font-size:13px;color:#999;font-weight:400;">v<?= DNDM_VERSION ?></span> — 🧩 DLCs</h1>

            <?php if ( ! empty($erros) ): ?>
            <div class="notice notice-error">
                <p><strong>❌ Erros em DLCs:</strong></p>
                <ul>
                    <?php foreach ($erros as $slug => $msg): ?>
                    <li><code><?= esc_html($slug) ?></code>: <?= esc_html($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- DLCs Ativos -->
            <h2 style="margin-top:20px;">DLCs Ativos (<?= count($ativos) ?>)</h2>

            <?php if ( empty($ativos) ): ?>
            <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:32px;text-align:center;color:#999;">
                <div style="font-size:48px;margin-bottom:12px;">🧩</div>
                <p style="font-size:16px;margin:0 0 8px;">Nenhum DLC instalado ainda.</p>
                <p style="font-size:13px;">Instale um DLC como plugin WordPress: <strong>Plugins → Adicionar Novo → Enviar Plugin</strong></p>
            </div>
            <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:24px;">
                <?php foreach ($ativos as $slug => $dlc):
                    $info = $tipo_labels[$dlc['type']] ?? $tipo_labels['module'];
                ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px;border-top:4px solid <?= $info['cor'] ?>;">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                        <span style="font-size:28px;"><?= esc_html($dlc['icon']) ?></span>
                        <div>
                            <strong style="font-size:15px;"><?= esc_html($dlc['name']) ?></strong>
                            <div style="font-size:11px;color:#999;margin-top:2px;">v<?= esc_html($dlc['version']) ?> · <?= esc_html($dlc['author']) ?></div>
                        </div>
                        <span style="margin-left:auto;background:<?= $info['cor'] ?>22;color:<?= $info['cor'] ?>;border:1px solid <?= $info['cor'] ?>44;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;white-space:nowrap;">
                            <?= $info['icon'] ?> <?= $info['label'] ?>
                        </span>
                    </div>
                    <?php if ($dlc['description']): ?>
                    <p style="font-size:12px;color:#555;margin:0 0 10px;line-height:1.5;"><?= esc_html($dlc['description']) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <code style="font-size:10px;color:#888;"><?= esc_html($slug) ?></code>
                        <span style="background:#dcfce7;color:#166534;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;">✅ Ativo</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Histórico de DLCs -->
            <?php if ( ! empty($historico) ): ?>
            <h2>Histórico de DLCs</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Versão</th>
                        <th>Status</th>
                        <th>Ativado em</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historico as $row):
                    $info   = $tipo_labels[$row['type']] ?? $tipo_labels['module'];
                    $ativo  = $row['status'] === 'ativo';
                ?>
                    <tr>
                        <td><code><?= esc_html($row['slug']) ?></code></td>
                        <td><?= esc_html($row['name']) ?></td>
                        <td><span style="font-size:12px;"><?= $info['icon'] ?> <?= $info['label'] ?></span></td>
                        <td>v<?= esc_html($row['version']) ?></td>
                        <td>
                            <span style="background:<?= $ativo ? '#dcfce7' : '#f3f4f6' ?>;color:<?= $ativo ? '#166534' : '#6b7280' ?>;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;">
                                <?= $ativo ? '✅ Ativo' : '⏸ Inativo' ?>
                            </span>
                        </td>
                        <td style="font-size:12px;"><?= date('d/m/Y H:i', strtotime($row['ativado_em'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Como criar um DLC -->
            <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;padding:20px;margin-top:28px;">
                <h3 style="margin-top:0;color:#1e40af;">📖 Como criar um DLC</h3>
                <ol style="line-height:2.2;font-size:13px;margin:0;">
                    <li>Crie um plugin WordPress com o cabeçalho padrão + campos especiais: <code>DLC-Type</code>, <code>DLC-Slug</code>, <code>DLC-Requires</code></li>
                    <li>No corpo do plugin, chame <code>do_action('dndm_register_dlc', $manifest)</code> no hook <code>plugins_loaded</code></li>
                    <li>Instale o plugin .zip em <strong>Plugins → Adicionar Novo → Enviar Plugin</strong></li>
                    <li>O DLC aparece aqui automaticamente</li>
                </ol>
                <p style="margin-top:12px;font-size:12px;color:#555;">
                    Veja o DLC de exemplo <code>dnd-dlc-taverna-inicio</code> para entender a estrutura completa.
                </p>
            </div>
        </div>
        <?php
    }
}
