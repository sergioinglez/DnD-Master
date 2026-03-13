<?php
/**
 * DnD Master — plataforma.php
 * Integração dinâmica: Carrega dados do JSON de classes para o Front-end
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Captura o personagem selecionado via URL (ex: ?personagem_id=10)
$personagem_id = $_GET['personagem_id'] ?? null;
$ficha_dados = null;

if ( $personagem_id && class_exists('DNDM_Personagem') ) {
    // get_ficha_completa já foi atualizado para ler o dnd.classes.json
    $ficha_dados = DNDM_Personagem::get_ficha_completa( (int)$personagem_id );
}

// 2. Injeta a ficha dentro do objeto de configuração global
if ( isset($config) ) {
    $config['personagem_ativo'] = $ficha_dados;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚔ DnD Master</title>

    <?php
    // Fontes Google — carregadas diretamente
    $lp_cfg       = $config['lp'] ?? [];
    $fonte_titulo = $lp_cfg['tipografia']['fonte_titulo'] ?? 'Cinzel Decorative';
    $fonte_corpo  = $lp_cfg['tipografia']['fonte_corpo']  ?? 'Crimson Text';
    $fonte_ui     = $lp_cfg['tipografia']['fonte_ui']     ?? 'Cinzel';

    $fontes_google = [
        'Cinzel Decorative' => 'Cinzel+Decorative:wght@400;700;900',
        'Cinzel'            => 'Cinzel:wght@400;600;700',
        'Crimson Text'      => 'Crimson+Text:ital,wght@0,400;0,600;1,400;1,600',
        'MedievalSharp'     => 'MedievalSharp',
        'Uncial Antiqua'    => 'Uncial+Antiqua',
        'IM Fell English'   => 'IM+Fell+English:ital@0;1',
        'Pirata One'        => 'Pirata+One',
    ];

    $carregar = array_unique([$fonte_titulo, $fonte_corpo, $fonte_ui]);
    $families = [];
    foreach ($carregar as $f) {
        if (isset($fontes_google[$f])) $families[] = 'family=' . $fontes_google[$f];
    }
    if (!empty($families)):
        $gurl = 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap';
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= esc_url($gurl) ?>" rel="stylesheet">
    <?php endif;
    $fav = get_site_icon_url(32);
    if ($fav): ?>
    <link rel="icon" href="<?= esc_url($fav) ?>">
    <?php endif; ?>

    <script>window.DNDM = <?php echo wp_json_encode( $config ); ?>;</script>

    <?php // CSS do tema — edite assets/css/platform.css ?>
    <link rel="stylesheet" href="<?= esc_url(DNDMT_URL . 'assets/css/platform.css') ?>?v=<?= DNDMT_VERSION ?>">

    <?php do_action('dndm_head'); ?>
</head>
<body style="margin:0; background:#0a0704; overflow:hidden;">
    <div id="dnd-root">
        <div id="dnd-preload" style="position:fixed;inset:0;background:#0a0704;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;font-family:'Cinzel',serif;color:#4a3a2a;font-size:12px;letter-spacing:4px;">
            <div style="font-size:36px;animation:spin 2s linear infinite;">⚔</div>
            CARREGANDO...
        </div>
        <style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js" crossorigin="anonymous"
        onerror="document.getElementById('dnd-preload').innerHTML='<div style=color:#dc2626;padding:32px;font-family:monospace>❌ Falha ao carregar React. Verifique sua conexão.</div>'">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js" crossorigin="anonymous"></script>

    <?php // App React — edite assets/js/app.js ?>
    <script src="<?= esc_url(DNDMT_URL . 'assets/js/app.js') ?>?v=<?= DNDMT_VERSION ?>"></script>

    <script>
    (function(){
        var p = document.getElementById('dnd-preload');
        if (!p) return;
        var o = new MutationObserver(function(ms){
            ms.forEach(function(m){
                m.addedNodes.forEach(function(n){ if(n!==p){ p.remove(); o.disconnect(); } });
            });
        });
        o.observe(document.getElementById('dnd-root'), { childList: true });
        setTimeout(function(){
            if (document.getElementById('dnd-preload'))
                p.innerHTML = '<div style="color:#dc2626;font-family:monospace;padding:32px;text-align:center">'
                    +'❌ O app não carregou.<br><small style="color:#6a5a3a">Abra o DevTools (F12) → Console para detalhes.</small></div>';
        }, 8000);
    })();
    </script>

    <?php do_action('dndm_footer'); ?>
</body>
</html>