<?php
// Este tema não renderiza páginas WordPress normais.
// Toda a renderização é feita pelo plugin DnD Master Platform
// via o filtro 'dndm_template_path' registrado em functions.php.
// Esta página só aparece se alguém acessar uma URL que não foi
// interceptada pelo plugin.
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body style="margin:0;background:#0a0704;color:#c9a84c;font-family:Cinzel,serif;display:flex;align-items:center;justify-content:center;height:100vh;">
    <div style="text-align:center;">
        <div style="font-size:48px;margin-bottom:16px;">⚔</div>
        <div style="font-size:13px;letter-spacing:4px;">DND MASTER</div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
