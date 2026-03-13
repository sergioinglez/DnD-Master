<?php
/**
 * DnD Master Theme — Admin
 * Painel de visual / tema separado do sistema (plugin dnd-v6).
 *
 * Registra o menu "🎨 DnD Tema" no wp-admin com:
 *  - Editor da Landing Page (LP) — com campo eyebrow editável
 *  - (Futuro: presets de cor, uploads de assets, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Menu do tema no wp-admin ─────────────────────────────────────────────────

add_action( 'admin_menu', 'dndmt_menu_admin' );
function dndmt_menu_admin() {
    add_menu_page(
        'DnD Master — Tema',
        '🎨 DnD Tema',
        'manage_options',
        'dnd-master-tema',
        'dndmt_page_lp',
        'dashicons-art',
        31   // aparece logo abaixo do menu do plugin (posição 30)
    );
    add_submenu_page( 'dnd-master-tema', 'Landing Page', '🏠 Landing Page', 'manage_options', 'dnd-master-tema',    'dndmt_page_lp' );
}

// ── Salvar config LP (POST) ──────────────────────────────────────────────────

add_action( 'admin_init', 'dndmt_admin_acoes' );
function dndmt_admin_acoes() {
    if ( ! current_user_can('manage_options') ) return;

    if ( isset($_POST['dndmt_salvar_lp']) && check_admin_referer('dndmt_lp_v2') ) {
        $raw    = stripslashes( $_POST['lp_config_json'] ?? '{}' );
        $parsed = json_decode( $raw, true );
        if ( $parsed ) {
            DNDM_LP_Editor::save_config( $parsed );
            wp_redirect( admin_url('admin.php?page=dnd-master-tema&msg=saved') );
            exit;
        }
        wp_redirect( admin_url('admin.php?page=dnd-master-tema&msg=json_erro') );
        exit;
    }

    // Gerar imagem de capa de módulo via IA
    if ( isset($_POST['dndmt_gerar_capa']) && check_admin_referer('dndmt_gerar_capa') ) {
        $modulo_id  = intval($_POST['modulo_id'] ?? 0);
        $descricao  = sanitize_textarea_field($_POST['descricao_cena'] ?? '');
        if ( $modulo_id && $descricao && class_exists('DNDM_Imagem') ) {
            $url = DNDM_Imagem::gerar_imagem_cena( $descricao );
            if ( $url ) {
                $mapas   = get_option('dndm_mapas_modulo_' . $modulo_id, array());
                // Insere como primeiro mapa (capa)
                array_unshift($mapas, array('url' => $url, 'nome' => 'Capa (IA)'));
                update_option('dndm_mapas_modulo_' . $modulo_id, $mapas);
                wp_redirect( admin_url('admin.php?page=dnd-master-tema&msg=capa_ok') );
            } else {
                wp_redirect( admin_url('admin.php?page=dnd-master-tema&msg=capa_erro') );
            }
        } else {
            wp_redirect( admin_url('admin.php?page=dnd-master-tema&msg=capa_dados') );
        }
        exit;
    }
}

// ── Página: Editor da Landing Page ───────────────────────────────────────────

function dndmt_page_lp() {
    // Depende do plugin estar ativo
    if ( ! class_exists('DNDM_LP_Editor') ) {
        echo '<div class="wrap"><div class="notice notice-error"><p>⚠ Plugin <strong>DnD Master Platform (dnd-v6)</strong> não está ativo.</p></div></div>';
        return;
    }

    $cfg  = DNDM_LP_Editor::get_config();
    // Garante que eyebrow existe (pode ser instalação anterior sem esse campo)
    $cfg['hero']['eyebrow'] = $cfg['hero']['eyebrow'] ?? '✦  SISTEMA DE RPG FAMILIAR  ✦';
    $json = json_encode( $cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    $msg  = $_GET['msg'] ?? '';
    ?>
    <div class="wrap">
    <h1 style="display:flex;align-items:center;gap:10px;">
        🎨 DnD Tema
        <span style="font-size:13px;color:#999;font-weight:400;">Editor Visual da Landing Page</span>
        <?php if ($msg === 'saved'): ?><span style="font-size:13px;color:#16a34a;font-weight:600;">✅ Salvo!</span><?php endif; ?>
        <?php if ($msg === 'json_erro'): ?><span style="font-size:13px;color:#dc2626;font-weight:600;">❌ JSON inválido</span><?php endif; ?>
    </h1>
    <p style="color:#666;margin-bottom:4px;">
        Aqui você edita o <strong>visual</strong>: textos da LP, cores, fontes, efeitos e layout.
        As configurações de sistema (API keys, campanhas, usuários) ficam em <a href="<?=admin_url('admin.php?page=dnd-master')?>">⚔ DnD Master</a>.
    </p>
    </div>

    <style>
    .lp-editor { display:grid; grid-template-columns:420px 1fr; gap:0; height:calc(100vh - 120px); margin-top:4px; border:1px solid #ddd; border-radius:8px; overflow:hidden; }
    .lp-painel { background:#fff; border-right:1px solid #e0e0e0; display:flex; flex-direction:column; overflow:hidden; }
    .lp-abas   { display:flex; background:#f8f8f8; border-bottom:1px solid #e0e0e0; overflow-x:auto; flex-shrink:0; }
    .lp-aba    { padding:10px 14px; font-size:12px; font-weight:600; cursor:pointer; border:none; background:none; white-space:nowrap; border-bottom:3px solid transparent; color:#666; }
    .lp-aba.ativo { color:#7c3aed; border-bottom-color:#7c3aed; background:#fff; }
    .lp-body   { flex:1; overflow-y:auto; padding:20px; }
    .lp-grupo  { display:none; }
    .lp-grupo.ativo { display:block; }
    .lp-preview { flex:1; position:relative; display:flex; flex-direction:column; }
    .lp-preview iframe { flex:1; border:none; }
    .lp-section-title { font-size:11px; letter-spacing:2px; color:#888; text-transform:uppercase; margin:20px 0 12px; padding-bottom:6px; border-bottom:1px solid #eee; }
    .lp-row    { display:flex; gap:10px; margin-bottom:12px; align-items:flex-start; }
    .lp-row label { font-size:12px; font-weight:600; color:#555; width:130px; flex-shrink:0; padding-top:8px; }
    .lp-row input[type=text],.lp-row input[type=color],.lp-row input[type=range],.lp-row textarea,.lp-row select { flex:1; }
    .lp-row textarea { min-height:70px; resize:vertical; }
    .lp-card   { background:#f8f8ff; border:1px solid #e0e0f0; border-radius:8px; padding:14px; margin-bottom:12px; }
    .lp-card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .lp-card-head strong { font-size:13px; }
    .lp-toggle { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
    .lp-toggle input[type=checkbox] { width:18px; height:18px; }
    .range-val { font-size:12px; color:#7c3aed; font-weight:700; min-width:36px; text-align:right; }
    .btn-add   { background:#7c3aed; color:#fff; border:none; border-radius:6px; padding:7px 14px; font-size:12px; cursor:pointer; margin-top:8px; }
    .btn-del   { background:#dc2626; color:#fff; border:none; border-radius:4px; padding:3px 9px; font-size:11px; cursor:pointer; }
    .preview-bar { padding:8px 16px; background:#f0f0f0; border-bottom:1px solid #ddd; display:flex; gap:8px; align-items:center; font-size:12px; color:#666; flex-shrink:0; }
    .save-bar  { padding:12px 16px; background:#f8f8f8; border-top:1px solid #e0e0e0; display:flex; gap:10px; align-items:center; flex-shrink:0; }
    </style>

    <div class="lp-editor">
        <!-- Painel esquerdo -->
        <div class="lp-painel">
            <div class="lp-abas">
                <?php foreach([
                    'hero'       => '🦸 Hero',
                    'features'   => '⭐ Features',
                    'modulos'    => '📜 Módulos',
                    'faq'        => '❓ FAQ',
                    'efeitos'    => '🔥 Efeitos',
                    'tipografia' => '🔤 Tipografia',
                    'cores'      => '🎨 Cores',
                    'rodape'     => '🏁 Rodapé',
                ] as $id => $label): ?>
                <button class="lp-aba <?=$id==='hero'?'ativo':''?>" onclick="lpAba('<?=$id?>')" id="aba-<?=$id?>"><?=$label?></button>
                <?php endforeach; ?>
            </div>
            <div class="lp-body">

                <!-- HERO -->
                <div class="lp-grupo ativo" id="g-hero">
                    <p class="lp-section-title">Textos da Hero Section</p>
                    <?php dndmt_lpRow('hero.eyebrow',    'Eyebrow',      $cfg['hero']['eyebrow'],      'text', 'ex: ✦ SISTEMA DE RPG FAMILIAR ✦'); ?>
                    <?php dndmt_lpRow('hero.titulo',     'Título',       $cfg['hero']['titulo'],       'text', 'ex: DnD Master'); ?>
                    <?php dndmt_lpRow('hero.subtitulo',  'Subtítulo',    $cfg['hero']['subtitulo'],    'text'); ?>
                    <?php dndmt_lpRow('hero.desc',       'Descrição',    $cfg['hero']['desc'],         'textarea'); ?>
                    <?php dndmt_lpRow('hero.cta_texto',  'Botão CTA',    $cfg['hero']['cta_texto'],    'text', 'ex: ⚔ ENTRAR'); ?>
                    <?php dndmt_lpRow('hero.cta_subtexto','Rodapé CTA', $cfg['hero']['cta_subtexto'], 'text'); ?>
                    <p class="lp-section-title">Fundo</p>
                    <div class="lp-row">
                        <label>Tipo de Fundo</label>
                        <select data-key="hero.bg_tipo" onchange="lpUpdate('hero.bg_tipo',this.value);document.getElementById('bg-cor-row').style.display=this.value==='cor'?'flex':'none';document.getElementById('bg-img-row').style.display=this.value==='imagem'?'flex':'none'">
                            <option value="cor"    <?=dndmt_selected_val($cfg['hero']['bg_tipo'],'cor')?>>Cor Sólida</option>
                            <option value="imagem" <?=dndmt_selected_val($cfg['hero']['bg_tipo'],'imagem')?>>Imagem</option>
                        </select>
                    </div>
                    <div class="lp-row" id="bg-cor-row" style="display:<?=$cfg['hero']['bg_tipo']==='cor'?'flex':'none'?>">
                        <label>Cor de Fundo</label>
                        <input type="color" value="<?=esc_attr($cfg['hero']['bg_cor'])?>" data-key="hero.bg_cor" oninput="lpUpdate('hero.bg_cor',this.value)" style="height:38px;width:80px">
                    </div>
                    <div class="lp-row" id="bg-img-row" style="display:<?=$cfg['hero']['bg_tipo']==='imagem'?'flex':'none'?>">
                        <label>URL da Imagem</label>
                        <input type="text" class="regular-text" value="<?=esc_attr($cfg['hero']['bg_imagem'])?>" data-key="hero.bg_imagem" oninput="lpUpdate('hero.bg_imagem',this.value)" placeholder="https://...">
                    </div>
                    <?php dndmt_lpRange('hero.bg_overlay','Overlay Escuro',$cfg['hero']['bg_overlay'],0,100,'%'); ?>
                </div>

                <!-- FEATURES -->
                <div class="lp-grupo" id="g-features">
                    <p class="lp-section-title">Cards de Features (máx 9)</p>
                    <div id="features-list">
                    <?php foreach($cfg['features'] as $i=>$f): ?>
                    <div class="lp-card" id="feat-<?=$i?>">
                        <div class="lp-card-head">
                            <strong>Feature <?=$i+1?></strong>
                            <button class="btn-del" onclick="lpDelFeature(<?=$i?>)">✕ Remover</button>
                        </div>
                        <div class="lp-row"><label>Ícone</label><input type="text" value="<?=esc_attr($f['icone'])?>" data-key="features.<?=$i?>.icone" oninput="lpUpdate('features.<?=$i?>.icone',this.value)" style="width:60px"></div>
                        <div class="lp-row"><label>Título</label><input type="text" class="regular-text" value="<?=esc_attr($f['titulo'])?>" data-key="features.<?=$i?>.titulo" oninput="lpUpdate('features.<?=$i?>.titulo',this.value)"></div>
                        <div class="lp-row"><label>Descrição</label><textarea data-key="features.<?=$i?>.desc" oninput="lpUpdate('features.<?=$i?>.desc',this.value)"><?=esc_textarea($f['desc'])?></textarea></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <button class="btn-add" onclick="lpAddFeature()">+ Adicionar Feature</button>
                </div>

                <!-- MÓDULOS -->
                <div class="lp-grupo" id="g-modulos">
                    <p class="lp-section-title">Seção de Aventuras na LP</p>
                    <div class="lp-toggle">
                        <input type="checkbox" <?=$cfg['modulos_secao']['mostrar']?'checked':''?> data-key="modulos_secao.mostrar" onchange="lpUpdate('modulos_secao.mostrar',this.checked)">
                        <label><strong>Mostrar seção de módulos na LP</strong></label>
                    </div>
                    <?php dndmt_lpRow('modulos_secao.titulo',    'Título',  $cfg['modulos_secao']['titulo'],    'text'); ?>
                    <?php dndmt_lpRow('modulos_secao.subtitulo', 'Label',   $cfg['modulos_secao']['subtitulo'], 'text'); ?>
                    <div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:6px;padding:10px 14px;font-size:12px;color:#2e7d32;margin-top:8px;">
                        ✅ Exibe automaticamente os módulos importados em <a href="<?=admin_url('admin.php?page=dnd-master-modulos')?>">⚔ DnD Master → Módulos</a>.
                    </div>

                    <!-- Gerador de imagem de capa via IA -->
                    <?php
                    global $wpdb;
                    $modulos_lista = $wpdb->get_results("SELECT id, nome FROM {$wpdb->prefix}dnd_modulos ORDER BY criado_em DESC");
                    $msg_capa = $_GET['msg'] ?? '';
                    if ($msg_capa === 'capa_ok'): ?>
                    <div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:6px;padding:8px 12px;font-size:12px;color:#2e7d32;margin-top:12px;">✅ Imagem gerada e definida como capa!</div>
                    <?php elseif (in_array($msg_capa,['capa_erro','capa_dados'])): ?>
                    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:8px 12px;font-size:12px;color:#dc2626;margin-top:12px;">❌ Erro ao gerar imagem. Verifique a chave Pollinations e os dados.</div>
                    <?php endif; ?>

                    <?php if (!empty($modulos_lista)): ?>
                    <div style="background:#f9f5ff;border:1px solid #ddd6fe;border-radius:8px;padding:14px;margin-top:14px;">
                        <p style="font-weight:700;font-size:13px;color:#7c3aed;margin:0 0 10px;">🎨 Gerar Capa de Módulo com IA</p>
                        <form method="post" id="form-gerar-capa">
                            <?php wp_nonce_field('dndmt_gerar_capa'); ?>
                            <table class="form-table" style="margin:0;">
                                <tr>
                                    <th style="padding:6px 0;font-size:12px;">Módulo</th>
                                    <td>
                                        <select name="modulo_id" style="min-width:220px;font-size:13px;">
                                            <?php foreach($modulos_lista as $m): ?>
                                            <option value="<?=(int)$m->id?>"><?=esc_html($m->nome)?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="padding:6px 0;font-size:12px;">Cena</th>
                                    <td>
                                        <textarea name="descricao_cena" rows="2" style="width:100%;font-size:13px;" placeholder="Ex: floresta mágica sombria, neblina dourada, ruínas antigas, altar de pedra..."></textarea>
                                        <p style="color:#888;font-size:11px;margin:4px 0 0;">Descreva o ambiente em português. A IA traduz e gera.</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin-top:10px;">
                                <button type="submit" name="dndmt_gerar_capa" class="button button-primary" id="btn-gerar-capa" onclick="this.disabled=true;this.textContent='⏳ Gerando (~30s)...';this.form.submit();">🎨 Gerar Imagem com IA</button>
                                <span style="color:#888;font-size:11px;margin-left:10px;">Usa Pollinations — leva ~30 segundos</span>
                            </p>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- FAQ -->
                <div class="lp-grupo" id="g-faq">
                    <p class="lp-section-title">Perguntas Frequentes</p>
                    <div id="faq-list">
                    <?php foreach($cfg['faq'] as $i=>$f): ?>
                    <div class="lp-card" id="faq-<?=$i?>">
                        <div class="lp-card-head">
                            <strong>FAQ <?=$i+1?></strong>
                            <button class="btn-del" onclick="lpDelFaq(<?=$i?>)">✕</button>
                        </div>
                        <div class="lp-row"><label>Pergunta</label><input type="text" class="large-text" value="<?=esc_attr($f['p'])?>" data-key="faq.<?=$i?>.p" oninput="lpUpdate('faq.<?=$i?>.p',this.value)"></div>
                        <div class="lp-row"><label>Resposta</label><textarea data-key="faq.<?=$i?>.r" oninput="lpUpdate('faq.<?=$i?>.r',this.value)"><?=esc_textarea($f['r'])?></textarea></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <button class="btn-add" onclick="lpAddFaq()">+ Adicionar Pergunta</button>
                </div>

                <!-- EFEITOS -->
                <div class="lp-grupo" id="g-efeitos">
                    <!-- PARTÍCULAS / CHAMAS -->
                    <p class="lp-section-title">🔥 Partículas / Chamas</p>
                    <div class="lp-toggle">
                        <input type="checkbox" <?=$cfg['efeitos']['chamas_ativo']?'checked':''?> data-key="efeitos.chamas_ativo" onchange="lpUpdate('efeitos.chamas_ativo',this.checked)">
                        <label><strong>Ativar efeito de partículas</strong></label>
                    </div>
                    <?php dndmt_lpRange('efeitos.chamas_quantidade','Quantidade',   $cfg['efeitos']['chamas_quantidade'],5,120,' partículas'); ?>
                    <?php dndmt_lpRange('efeitos.chamas_velocidade','Velocidade',   $cfg['efeitos']['chamas_velocidade'],5,50,'s (maior = mais lento)'); ?>
                    <?php dndmt_lpRange('efeitos.chamas_tamanho',   'Tamanho',      $cfg['efeitos']['chamas_tamanho'],1,8,'px'); ?>
                    <?php dndmt_lpRange('efeitos.chamas_opacidade', 'Opacidade',    $cfg['efeitos']['chamas_opacidade'],10,100,'%'); ?>
                    <div class="lp-row">
                        <label>Cor</label>
                        <input type="color" value="<?=esc_attr($cfg['efeitos']['chamas_cor'])?>" data-key="efeitos.chamas_cor" oninput="lpUpdate('efeitos.chamas_cor',this.value)" style="height:38px;width:80px">
                    </div>

                    <!-- NÉVOA -->
                    <p class="lp-section-title" style="margin-top:20px;">🌫 Névoa Rastejante</p>
                    <div class="lp-toggle">
                        <input type="checkbox" <?=!empty($cfg['efeitos']['nevoa_ativo'])?'checked':''?> data-key="efeitos.nevoa_ativo" onchange="lpUpdate('efeitos.nevoa_ativo',this.checked)">
                        <label><strong>Ativar névoa na base do hero</strong></label>
                    </div>
                    <?php dndmt_lpRange('efeitos.nevoa_opacidade','Opacidade', $cfg['efeitos']['nevoa_opacidade'] ?? 40, 5, 100, '%'); ?>
                    <div class="lp-row">
                        <label>Cor</label>
                        <input type="color" value="<?=esc_attr($cfg['efeitos']['nevoa_cor'] ?? '#0a0704')?>" data-key="efeitos.nevoa_cor" oninput="lpUpdate('efeitos.nevoa_cor',this.value)" style="height:38px;width:80px">
                    </div>

                    <!-- RUNAS PULSANTES -->
                    <p class="lp-section-title" style="margin-top:20px;">✦ Runas Pulsantes</p>
                    <div class="lp-toggle">
                        <input type="checkbox" <?=!empty($cfg['efeitos']['runas_ativo'])?'checked':''?> data-key="efeitos.runas_ativo" onchange="lpUpdate('efeitos.runas_ativo',this.checked)">
                        <label><strong>Ativar runas flutuantes no fundo</strong></label>
                    </div>
                    <?php dndmt_lpRange('efeitos.runas_quantidade','Quantidade', $cfg['efeitos']['runas_quantidade'] ?? 8, 2, 20, ' runas'); ?>
                    <?php dndmt_lpRange('efeitos.runas_opacidade', 'Opacidade',  $cfg['efeitos']['runas_opacidade']  ?? 8, 2, 40, '%'); ?>
                    <div class="lp-row">
                        <label>Cor</label>
                        <input type="color" value="<?=esc_attr($cfg['efeitos']['runas_cor'] ?? '#c9a84c')?>" data-key="efeitos.runas_cor" oninput="lpUpdate('efeitos.runas_cor',this.value)" style="height:38px;width:80px">
                    </div>

                    <!-- BORDAS BG3 -->
                    <p class="lp-section-title" style="margin-top:20px;">🏰 Molduras Estilo Baldur's Gate</p>
                    <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:8px 12px;font-size:12px;color:#7a5a00;margin-bottom:10px;">
                        Aplica bordas ornamentais com cantos gravados nos cards de módulo e features da LP.
                    </div>
                    <div class="lp-toggle">
                        <input type="checkbox" <?=!empty($cfg['efeitos']['borda_bg3_ativo'])?'checked':''?> data-key="efeitos.borda_bg3_ativo" onchange="lpUpdate('efeitos.borda_bg3_ativo',this.checked)">
                        <label><strong>Ativar molduras ornamentais</strong></label>
                    </div>
                    <div class="lp-row">
                        <label>Estilo</label>
                        <select data-key="efeitos.borda_bg3_estilo" onchange="lpUpdate('efeitos.borda_bg3_estilo',this.value)" style="font-size:13px;">
                            <?php
                            $estilos = ['ouro'=>'Ouro Clássico','prata'=>'Prata Arcana','esmeralda'=>'Esmeralda Druida','sangue'=>'Sangue Demônio','gelo'=>'Gelo Nórdico'];
                            $sel = $cfg['efeitos']['borda_bg3_estilo'] ?? 'ouro';
                            foreach($estilos as $v=>$l): ?>
                            <option value="<?=$v?>" <?=$sel===$v?'selected':''?>><?=$l?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php dndmt_lpRange('efeitos.borda_bg3_espessura','Espessura', $cfg['efeitos']['borda_bg3_espessura'] ?? 2, 1, 4, 'px'); ?>
                    <?php dndmt_lpRange('efeitos.borda_bg3_brilho',   'Brilho',    $cfg['efeitos']['borda_bg3_brilho']    ?? 30, 0, 100, '%'); ?>
                </div>

                <!-- TIPOGRAFIA -->
                <div class="lp-grupo" id="g-tipografia">
                    <p class="lp-section-title">Fontes</p>
                    <?php
                    $font_opts = ['Cinzel Decorative','Cinzel','Crimson Text','MedievalSharp','Uncial Antiqua','IM Fell English','Pirata One','Palatino Linotype','Georgia','system-ui'];
                    foreach(['tipografia.fonte_titulo'=>'Fonte Títulos','tipografia.fonte_corpo'=>'Fonte Corpo','tipografia.fonte_ui'=>'Fonte UI'] as $key=>$label):
                        $parts = explode('.',$key); $val = $cfg[$parts[0]][$parts[1]];
                    ?>
                    <div class="lp-row">
                        <label><?=$label?></label>
                        <select data-key="<?=$key?>" onchange="lpUpdate('<?=$key?>',this.value)">
                            <?php foreach($font_opts as $f): ?>
                            <option value="<?=esc_attr($f)?>" <?=$val===$f?'selected':''?>><?=$f?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    <?php dndmt_lpRange('tipografia.escala','Escala de Texto',$cfg['tipografia']['escala'],80,130,'%'); ?>
                    <p style="font-size:12px;color:#795548;background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:10px;margin-top:8px;">
                        💡 Fontes medievais recomendadas: <strong>Cinzel Decorative, MedievalSharp, Pirata One</strong>
                    </p>
                </div>

                <!-- CORES -->
                <div class="lp-grupo" id="g-cores">
                    <p class="lp-section-title">Paleta de Cores</p>
                    <?php foreach([
                        'cores.ouro'     => 'Ouro principal',
                        'cores.ouro_dim' => 'Ouro escurecido',
                        'cores.fundo'    => 'Fundo geral',
                        'cores.texto'    => 'Texto principal',
                        'cores.borda'    => 'Bordas e divisores',
                    ] as $key => $label):
                        $parts = explode('.',$key); $val = $cfg[$parts[0]][$parts[1]];
                    ?>
                    <div class="lp-row">
                        <label><?=$label?></label>
                        <input type="color" value="<?=esc_attr($val)?>" data-key="<?=$key?>" oninput="lpUpdate('<?=$key?>',this.value)" style="height:38px;width:80px">
                        <span style="font-size:11px;color:#888;padding-top:10px;" id="clr-<?=str_replace('.','-',$key)?>"><?=$val?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- RODAPÉ -->
                <div class="lp-grupo" id="g-rodape">
                    <p class="lp-section-title">CTA Final</p>
                    <div class="lp-toggle">
                        <input type="checkbox" <?=$cfg['rodape']['mostrar_rodape']?'checked':''?> data-key="rodape.mostrar_rodape" onchange="lpUpdate('rodape.mostrar_rodape',this.checked)">
                        <label><strong>Mostrar seção de CTA no rodapé</strong></label>
                    </div>
                    <?php dndmt_lpRow('rodape.cta_titulo','Título',    $cfg['rodape']['cta_titulo'],'text'); ?>
                    <?php dndmt_lpRow('rodape.cta_desc',  'Descrição', $cfg['rodape']['cta_desc'],  'textarea'); ?>
                </div>

            </div><!-- end lp-body -->

            <div class="save-bar">
                <form method="post">
                    <?php wp_nonce_field('dndmt_lp_v2'); ?>
                    <input type="hidden" name="lp_config_json" id="lp-config-json" value="">
                    <button type="submit" name="dndmt_salvar_lp" class="button button-primary button-large" onclick="lpPrepareSave()">💾 Salvar</button>
                </form>
                <a href="<?=esc_url(home_url('/'))?>" target="_blank" class="button button-large">🔎 Ver ao Vivo</a>
                <span style="font-size:11px;color:#999;">Salvar aplica imediatamente na LP.</span>
            </div>
        </div>

        <!-- Preview -->
        <div class="lp-preview">
            <div class="preview-bar">
                <strong>Preview</strong>
                <button onclick="document.getElementById('lp-frame').src+='';" class="button button-small">↺ Recarregar</button>
                <span style="margin-left:auto;color:#aaa;font-size:11px;">Atualiza após salvar</span>
            </div>
            <iframe id="lp-frame" src="<?=esc_url(home_url('/'))?>"></iframe>
        </div>
    </div>

    <script>
    var lpConfig = <?=$json?>;

    function lpAba(id) {
        document.querySelectorAll('.lp-aba').forEach(b => b.classList.remove('ativo'));
        document.querySelectorAll('.lp-grupo').forEach(g => g.classList.remove('ativo'));
        document.getElementById('aba-'+id).classList.add('ativo');
        document.getElementById('g-'+id).classList.add('ativo');
    }

    function lpUpdate(key, val) {
        var parts = key.split('.'), obj = lpConfig;
        for (var i = 0; i < parts.length - 1; i++) {
            var p = isNaN(parts[i]) ? parts[i] : parseInt(parts[i]);
            if (obj[p] === undefined) obj[p] = {};
            obj = obj[p];
        }
        var last = isNaN(parts[parts.length-1]) ? parts[parts.length-1] : parseInt(parts[parts.length-1]);
        if (val === 'true') val = true;
        if (val === 'false') val = false;
        obj[last] = val;
        var clrEl = document.getElementById('clr-' + key.replace(/\./g,'-'));
        if (clrEl) clrEl.textContent = val;
    }

    document.querySelectorAll('input[type=range]').forEach(function(r) {
        var id = 'rv-' + r.dataset.key.replace(/\./g,'_');
        r.addEventListener('input', function() {
            var el = document.getElementById(id);
            if (el) el.textContent = r.value;
            lpUpdate(r.dataset.key, parseInt(r.value));
        });
    });

    function lpAddFeature() {
        lpConfig.features.push({ icone:'⭐', titulo:'Nova Feature', desc:'Descrição.' });
        var i = lpConfig.features.length - 1;
        var div = document.createElement('div');
        div.className='lp-card'; div.id='feat-'+i;
        div.innerHTML='<div class="lp-card-head"><strong>Feature '+(i+1)+'</strong><button class="btn-del" onclick="lpDelFeature('+i+')">✕ Remover</button></div>'
            +'<div class="lp-row"><label>Ícone</label><input type="text" value="⭐" data-key="features.'+i+'.icone" oninput="lpUpdate(\'features.'+i+'.icone\',this.value)" style="width:60px"></div>'
            +'<div class="lp-row"><label>Título</label><input type="text" class="regular-text" value="Nova Feature" data-key="features.'+i+'.titulo" oninput="lpUpdate(\'features.'+i+'.titulo\',this.value)"></div>'
            +'<div class="lp-row"><label>Descrição</label><textarea data-key="features.'+i+'.desc" oninput="lpUpdate(\'features.'+i+'.desc\',this.value)">Descrição.</textarea></div>';
        document.getElementById('features-list').appendChild(div);
    }
    function lpDelFeature(i) {
        lpConfig.features.splice(i,1);
        document.getElementById('feat-'+i).remove();
        document.querySelectorAll('#features-list .lp-card').forEach(function(c,idx){ c.id='feat-'+idx; });
    }
    function lpAddFaq() {
        lpConfig.faq.push({ p:'Nova Pergunta', r:'Resposta aqui.' });
        var i = lpConfig.faq.length - 1;
        var div = document.createElement('div');
        div.className='lp-card'; div.id='faq-'+i;
        div.innerHTML='<div class="lp-card-head"><strong>FAQ '+(i+1)+'</strong><button class="btn-del" onclick="lpDelFaq('+i+')">✕</button></div>'
            +'<div class="lp-row"><label>Pergunta</label><input type="text" class="large-text" value="Nova Pergunta" data-key="faq.'+i+'.p" oninput="lpUpdate(\'faq.'+i+'.p\',this.value)"></div>'
            +'<div class="lp-row"><label>Resposta</label><textarea data-key="faq.'+i+'.r" oninput="lpUpdate(\'faq.'+i+'.r\',this.value)">Resposta.</textarea></div>';
        document.getElementById('faq-list').appendChild(div);
    }
    function lpDelFaq(i) { lpConfig.faq.splice(i,1); document.getElementById('faq-'+i).remove(); }
    function lpPrepareSave() { document.getElementById('lp-config-json').value = JSON.stringify(lpConfig); }
    </script>
    <?php
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function dndmt_lpRow($key, $label, $val, $type='text', $placeholder='') {
    $id = 'lp-'.str_replace('.','_',$key);
    echo '<div class="lp-row"><label>'.esc_html($label).'</label>';
    if ($type==='textarea')
        echo '<textarea id="'.esc_attr($id).'" data-key="'.esc_attr($key).'" oninput="lpUpdate(\''.esc_js($key).'\',this.value)">'.esc_textarea($val).'</textarea>';
    else
        echo '<input type="text" id="'.esc_attr($id).'" class="regular-text" value="'.esc_attr($val).'" data-key="'.esc_attr($key).'" oninput="lpUpdate(\''.esc_js($key).'\',this.value)" placeholder="'.esc_attr($placeholder).'">';
    echo '</div>';
}

function dndmt_lpRange($key, $label, $val, $min, $max, $unit='') {
    $id = 'rv-'.str_replace('.','_',$key);
    echo '<div class="lp-row"><label>'.esc_html($label).'</label>';
    echo '<input type="range" data-key="'.esc_attr($key).'" min="'.(int)$min.'" max="'.(int)$max.'" value="'.(int)$val.'" style="flex:1">';
    echo '<span class="range-val" id="'.esc_attr($id).'">'.(int)$val.'</span>';
    echo '<span style="font-size:11px;color:#888;white-space:nowrap;padding-top:6px;">'.esc_html($unit).'</span>';
    echo '</div>';
}

function dndmt_selected_val($a,$b) { return $a===$b?'selected':''; }
