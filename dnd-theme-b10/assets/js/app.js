// DnD Master Platform — app.js v0.0.4
(function () {
'use strict';

const { useState, useEffect, useCallback, useRef } = React;
const C = window.DNDM || {};
if (!C.apiUrl) { console.error('[DnD Master] window.DNDM não carregado'); }
const e = React.createElement;

// ════════════════════════════════════════════
// API
// ════════════════════════════════════════════
async function api(endpoint, method = 'GET', body = null) {
    const opts = {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': C.nonce },
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(C.apiUrl + endpoint, opts);
    return res.json();
}

// ════════════════════════════════════════════
// SPINNER
// ════════════════════════════════════════════
function Spinner({ texto = 'CARREGANDO...' }) {
    return e('div', { className: 'dnd-loading' },
        e('div', { className: 'spinner' }),
        texto
    );
}

// ════════════════════════════════════════════
// ACHIEVEMENTS — SVGs, catálogo e componentes
// ════════════════════════════════════════════
// ????????????????????????????????????????????
// COMPONENTE: HABILIDADES DE CLASSE (JSON)
// ????????????????????????????????????????????
function PainelHabilidadesClasse({ habilidades }) {
    if (!habilidades || habilidades.length === 0) {
        return e('div', { style: { padding: '20px', color: 'var(--g3)', fontStyle: 'italic', textAlign: 'center' } }, 
            'Nenhuma habilidade de classe desbloqueada para este n�vel.'
        );
    }

    return e('div', { className: 'habilidades-grid' },
        habilidades.map(function(hab, index) {
            return e('div', { className: 'card-habilidade', key: index },
                e('span', { className: 'hab-nome' }, hab.nome),
                e('p', { className: 'hab-resumo' }, hab.resumo)
            );
        })
    );
}
// SVGs inline por badge (retorna string SVG)
var BADGE_SVG = {
    // ── Classes ──────────────────────────────────────────────────────────────
    barbaro: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M20 44 L32 12 L44 44" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"/><path d="M24 34 L40 34" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M26 44 L38 44" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><circle cx="32" cy="12" r="3" fill="currentColor"/></svg>',
    bardo: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><ellipse cx="32" cy="32" rx="8" ry="14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M40 20 Q50 18 48 28" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M40 26 Q50 24 49 32" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M40 32 Q50 30 49 38" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>',
    bruxo: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M32 14 L32 50" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M20 26 L44 26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="32" cy="20" r="5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M24 38 Q32 34 40 38" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M20 44 Q32 40 44 44" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>',
    clerigo: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M32 16 L32 48" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M20 28 L44 28" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg>',
    druida: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M32 44 L32 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M32 20 Q22 16 20 26 Q28 24 32 30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M32 28 Q42 24 44 34 Q36 32 32 38" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="32" cy="44" r="3" fill="currentColor"/></svg>',
    feiticeiro: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M32 14 L38 28 L32 24 L26 28 Z" fill="currentColor"/><path d="M24 30 L32 50 L40 30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="32" cy="24" r="2" fill="currentColor"/><circle cx="20" cy="36" r="3" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="44" cy="36" r="3" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>',
    guerreiro: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><rect x="24" y="20" width="16" height="22" rx="2" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M28 20 L28 14 L36 14 L36 20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M28 42 L24 50 L32 46 L40 50 L36 42" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M27 28 L37 28 M27 33 L37 33" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
    ladino: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M18 46 L42 16 L46 20 L22 50 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M42 16 L48 14 L46 20" fill="currentColor" stroke="currentColor" stroke-width="1"/><circle cx="24" cy="44" r="3" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>',
    mago: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M32 12 L32 52" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="3 3"/><circle cx="32" cy="22" r="6" fill="none" stroke="currentColor" stroke-width="2"/><path d="M26 22 L38 22" stroke="currentColor" stroke-width="1.5"/><path d="M32 16 L32 28" stroke="currentColor" stroke-width="1.5"/><path d="M20 38 Q32 34 44 38" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M22 44 Q32 40 42 44" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>',
    monge: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="32" cy="28" r="10" fill="none" stroke="currentColor" stroke-width="2"/><path d="M32 18 L32 38 M22 28 L42 28" stroke="currentColor" stroke-width="1.5"/><path d="M32 38 L32 48" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M26 44 L38 44" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    paladino: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M20 18 L20 42 Q20 50 32 52 Q44 50 44 42 L44 18 Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M28 30 L32 22 L36 30 M28 30 L36 30" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ranger: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,4 58,18 58,46 32,60 6,46 6,18" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M22 50 L22 16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M22 22 Q38 14 44 24 Q38 22 34 30 Q42 30 42 42 Q34 38 28 44" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    // ── Combate ───────────────────────────────────────────────────────────────
    first_blood: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="27" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M32 14 L36 26 L48 26 L38 34 L42 46 L32 38 L22 46 L26 34 L16 26 L28 26 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    goblin_slayer: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="27" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M16 48 L38 14 L42 18 L20 52 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M38 14 L48 12 L46 22 L42 18 Z" fill="currentColor"/><circle cx="20" cy="46" r="3" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M36 24 L52 20 M40 30 L52 30" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
    natural_20: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><polygon points="32,2 40,22 62,22 46,36 52,56 32,44 12,56 18,36 2,22 24,22" fill="none" stroke="currentColor" stroke-width="2.5"/><text x="32" y="37" text-anchor="middle" font-size="18" font-weight="bold" fill="currentColor" font-family="serif">20</text></svg>',
    dice_curse: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="10" width="44" height="44" rx="8" fill="none" stroke="currentColor" stroke-width="2.5"/><text x="32" y="40" text-anchor="middle" font-size="28" font-weight="bold" fill="currentColor" font-family="serif">1</text><path d="M8 8 L56 56" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-dasharray="4 4" opacity="0.5"/></svg>',
    survivor: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M32 6 L38 20 L54 20 L42 30 L46 46 L32 36 L18 46 L22 30 L10 20 L26 20 Z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M24 46 L24 58 L32 54 L40 58 L40 46" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    // ── Progressão ────────────────────────────────────────────────────────────
    first_character: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M32 4 L60 20 L60 44 L32 60 L4 44 L4 20 Z" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="32" cy="28" r="8" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 50 Q20 40 32 40 Q44 40 44 50" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    master_of_arts: '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M32 4 L60 20 L60 44 L32 60 L4 44 L4 20 Z" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="32" cy="32" r="5" fill="currentColor"/><circle cx="16" cy="32" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="48" cy="32" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="24" cy="18" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="40" cy="18" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 32 L27 32 M37 32 L48 32 M26 21 L30 27 M34 27 L38 21" stroke="currentColor" stroke-width="1.5"/></svg>',
};

// Cores por raridade
var RARIDADE_COR = {
    bronze: { primary: '#cd7f32', glow: '#cd7f3255', text: '#8b4513' },
    prata:  { primary: '#a8a9ad', glow: '#a8a9ad44', text: '#6e6e70' },
    ouro:   { primary: '#ffd700', glow: '#ffd70055', text: '#b8860b' },
};

// Catálogo local (espelho do PHP para não precisar de fetch inicial)
var BADGES_CATALOGO = {
    class_initiation_barbaro:   { titulo:'Fúria Incontrolável',      raridade:'prata', categoria:'classe',     icone:'barbaro' },
    class_initiation_bardo:     { titulo:'Voz das Lendas',           raridade:'prata', categoria:'classe',     icone:'bardo' },
    class_initiation_bruxo:     { titulo:'Pacto de Sangue',          raridade:'prata', categoria:'classe',     icone:'bruxo' },
    class_initiation_clerigo:   { titulo:'Luz do Alvorecer',         raridade:'prata', categoria:'classe',     icone:'clerigo' },
    class_initiation_druida:    { titulo:'Guardião do Equilíbrio',   raridade:'prata', categoria:'classe',     icone:'druida' },
    class_initiation_feiticeiro:{ titulo:'Poder Inato',              raridade:'prata', categoria:'classe',     icone:'feiticeiro' },
    class_initiation_guerreiro: { titulo:'Mestre de Armas',          raridade:'prata', categoria:'classe',     icone:'guerreiro' },
    class_initiation_ladino:    { titulo:'Sombra Indetectável',      raridade:'prata', categoria:'classe',     icone:'ladino' },
    class_initiation_mago:      { titulo:'Sábio do Arcano',          raridade:'prata', categoria:'classe',     icone:'mago' },
    class_initiation_monge:     { titulo:'Equilíbrio Espiritual',    raridade:'prata', categoria:'classe',     icone:'monge' },
    class_initiation_paladino:  { titulo:'Juramento Eterno',         raridade:'prata', categoria:'classe',     icone:'paladino' },
    class_initiation_ranger:    { titulo:'Rastreador Implacável',    raridade:'prata', categoria:'classe',     icone:'ranger' },
    first_blood:     { titulo:'Primeiro Sangue',         raridade:'bronze', categoria:'combate',    icone:'first_blood' },
    goblin_slayer:   { titulo:'Exterminador de Goblins', raridade:'prata',  categoria:'combate',    icone:'goblin_slayer' },
    natural_20:      { titulo:'Sorte de Herói',          raridade:'ouro',   categoria:'combate',    icone:'natural_20' },
    dice_curse:      { titulo:'Maldição dos Dados',      raridade:'bronze', categoria:'combate',    icone:'dice_curse' },
    survivor:        { titulo:'No Limite da Morte',      raridade:'ouro',   categoria:'combate',    icone:'survivor' },
    first_character: { titulo:'O Despertar',             raridade:'ouro',   categoria:'progressao', icone:'first_character' },
    master_of_arts:  { titulo:'Mestre de Todas as Artes',raridade:'ouro',   categoria:'progressao', icone:'master_of_arts' },
};

// ── BadgeIcon: renderiza SVG com cor/cinza e efeito de brilho ─────────────────
function BadgeIcon({ slug, conquistada, size }) {
    var sz = size || 56;
    var info = BADGES_CATALOGO[slug] || {};
    var icone = info.icone || slug;
    var svg = BADGE_SVG[icone] || BADGE_SVG.first_character;
    var cor = conquistada ? (RARIDADE_COR[info.raridade] || RARIDADE_COR.bronze) : null;

    return e('div', {
        style: {
            width: sz, height: sz,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            position: 'relative',
            filter: conquistada
                ? 'drop-shadow(0 0 6px ' + (cor ? cor.glow : 'transparent') + ')'
                : 'grayscale(1) brightness(0.35)',
            color: conquistada ? (cor ? cor.primary : '#fff') : '#555',
            transition: 'all .3s',
        },
        dangerouslySetInnerHTML: { __html: svg.replace('<svg ', '<svg width="' + sz + '" height="' + sz + '" ') }
    });
}

// ── AchievementToast ──────────────────────────────────────────────────────────
function AchievementToast({ badge_slug, onClose }) {
    var info = BADGES_CATALOGO[badge_slug] || { titulo: badge_slug, raridade: 'ouro' };
    var cor = RARIDADE_COR[info.raridade] || RARIDADE_COR.ouro;
    var [visible, setVisible] = useState(false);

    useEffect(function() {
        // Entrada animada
        var t1 = setTimeout(function() { setVisible(true); }, 50);
        // Auto-fechar em 5s
        var t2 = setTimeout(function() { setVisible(false); }, 5000);
        var t3 = setTimeout(function() { onClose && onClose(); }, 5500);
        return function() { clearTimeout(t1); clearTimeout(t2); clearTimeout(t3); };
    }, [badge_slug]);

    return e('div', {
        onClick: function() { setVisible(false); setTimeout(function() { onClose && onClose(); }, 500); },
        style: {
            position: 'fixed', top: visible ? 80 : -120, right: 20, zIndex: 99999,
            background: 'linear-gradient(135deg, #0d0a04, #1a1208)',
            border: '1px solid ' + cor.primary,
            borderRadius: 14,
            boxShadow: '0 0 24px ' + cor.glow + ', 0 8px 32px rgba(0,0,0,.8)',
            padding: '16px 20px',
            display: 'flex', alignItems: 'center', gap: 16,
            minWidth: 280, maxWidth: 360,
            transition: 'top .5s cubic-bezier(.175,.885,.32,1.275)',
            cursor: 'pointer',
        }
    },
        // Ícone da badge
        e('div', { style: { flexShrink: 0 } },
            e(BadgeIcon, { slug: badge_slug, conquistada: true, size: 48 })
        ),
        // Texto
        e('div', { style: { flex: 1, minWidth: 0 } },
            e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 9, letterSpacing: 4, color: cor.primary, marginBottom: 4 } },
                '🏆 CONQUISTA DESBLOQUEADA!'
            ),
            e('div', { style: { fontFamily: "'Cinzel Decorative',serif", fontSize: 14, color: '#f0e6c8', fontWeight: 700 } },
                info.titulo
            ),
            e('div', { style: { fontSize: 11, color: '#8a7a5a', marginTop: 4, fontFamily: "'Cinzel',serif", letterSpacing: 1 } },
                (info.raridade ? info.raridade.toUpperCase() : '') + ' · Clique para fechar'
            )
        ),
        // Partícula brilhante no canto
        e('div', {
            style: {
                position: 'absolute', top: -4, right: 16,
                width: 8, height: 8, borderRadius: '50%',
                background: cor.primary,
                boxShadow: '0 0 12px 4px ' + cor.glow,
                animation: 'pulse 1.5s ease-in-out infinite',
            }
        })
    );
}

// ── AchievementManager — polling + toast queue ────────────────────────────────
function AchievementManager({ usuario }) {
    var [toastQueue, setToastQueue] = useState([]);
    var [lastCheck, setLastCheck] = useState(null);

    var checkNew = useCallback(function(since) {
        if (!usuario) return;
        var url = '/achievements/poll' + (since ? '?since=' + encodeURIComponent(since) : '');
        api(url).then(function(res) {
            if (!res || !res.recentes || !res.recentes.length) return;
            var novas = res.recentes.filter(function(r) { return !since || r.conquistado_em > since; });
            if (novas.length > 0) {
                setToastQueue(function(q) { return q.concat(novas.map(function(r) { return r.badge_slug; })); });
                setLastCheck(novas[0].conquistado_em);
            }
        }).catch(function(){});
    }, [usuario]);

    // Polling a cada 60s
    useEffect(function() {
        if (!usuario) return;
        var now = new Date().toISOString().replace('T', ' ').slice(0, 19);
        setLastCheck(now);
        var interval = setInterval(function() {
            setLastCheck(function(lc) {
                checkNew(lc);
                return lc;
            });
        }, 60000);
        return function() { clearInterval(interval); };
    }, [usuario]);

    if (toastQueue.length === 0) return null;

    var currentSlug = toastQueue[0];
    return e(AchievementToast, {
        key: currentSlug + '_' + Date.now(),
        badge_slug: currentSlug,
        onClose: function() { setToastQueue(function(q) { return q.slice(1); }); }
    });
}

// ── TelaConquistas ────────────────────────────────────────────────────────────
function TelaConquistas({ usuario }) {
    var [dados, setDados] = useState(null);
    var [filtro, setFiltro] = useState('todos');

    useEffect(function() {
        api('/achievements').then(function(r) { setDados(r); }).catch(function() {});
    }, []);

    if (!dados) return e('div', { style: { textAlign:'center', padding:'60px', color:'var(--t4)' } }, '⏳ Carregando conquistas...');

    var conquistadas = dados.conquistadas || {};
    var catalogo = dados.catalogo || BADGES_CATALOGO;

    var cat_labels = { todos:'Todas', progressao:'🏆 Progressão', classe:'🛡 Classe', combate:'⚔ Combate' };

    // Agrupa e filtra
    var slugs = Object.keys(BADGES_CATALOGO);
    var filtrados = slugs.filter(function(s) {
        var b = BADGES_CATALOGO[s];
        return filtro === 'todos' || b.categoria === filtro;
    });

    var total_conquistadas = Object.keys(conquistadas).length;
    var total = slugs.length;

    return e('div', { style: { maxWidth: 900, margin: '0 auto', padding: '0 0 48px' } },

        // Header
        e('div', { style: { marginBottom: 28 } },
            e('div', { style: { fontFamily:"'Cinzel',serif", fontSize: 10, letterSpacing: 5, color: 'var(--g5)', marginBottom: 8 } }, 'MURAL DE GLÓRIAS'),
            e('div', { style: { display:'flex', alignItems:'center', justifyContent:'space-between', flexWrap:'wrap', gap: 12 } },
                e('div', null,
                    e('div', { style: { fontFamily:"'Cinzel Decorative',serif", color:'var(--g2)', fontSize: 22 } }, 'Minhas Conquistas'),
                    e('div', { style: { color:'var(--t3)', fontSize: 13, marginTop: 4 } },
                        total_conquistadas + ' de ' + total + ' conquistas desbloqueadas'
                    )
                ),
                // Barra de progresso
                e('div', { style: { flex: 1, minWidth: 160, maxWidth: 300 } },
                    e('div', { style: { height: 6, background: 'var(--b3)', borderRadius: 3, overflow: 'hidden' } },
                        e('div', { style: {
                            height: '100%', borderRadius: 3,
                            width: Math.round((total_conquistadas/total)*100) + '%',
                            background: 'linear-gradient(90deg, var(--g4), var(--g2))',
                            transition: 'width 1s ease',
                        }})
                    ),
                    e('div', { style: { fontSize: 11, color: 'var(--t4)', marginTop: 4, textAlign:'right', fontFamily:"'Cinzel',serif" } },
                        Math.round((total_conquistadas/total)*100) + '%'
                    )
                )
            )
        ),

        // Filtros de categoria
        e('div', { style: { display:'flex', gap: 8, marginBottom: 24, flexWrap:'wrap' } },
            Object.keys(cat_labels).map(function(cat) {
                var ativo = filtro === cat;
                return e('button', {
                    key: cat,
                    onClick: function() { setFiltro(cat); },
                    style: {
                        fontFamily:"'Cinzel',serif", fontSize: 11, letterSpacing: 2,
                        padding: '6px 16px', borderRadius: 20,
                        background: ativo ? 'var(--g3)' : 'var(--b3)',
                        border: ativo ? '1px solid var(--g2)' : '1px solid var(--b5)',
                        color: ativo ? 'var(--g1)' : 'var(--t3)',
                        cursor: 'pointer', transition: 'all .2s',
                    }
                }, cat_labels[cat]);
            })
        ),

        // Grade de badges
        e('div', { style: { display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(150px, 1fr))', gap: 14 } },
            filtrados.map(function(slug) {
                var info = BADGES_CATALOGO[slug];
                var conq = conquistadas[slug];
                var cor = conq ? (RARIDADE_COR[info.raridade] || RARIDADE_COR.bronze) : null;

                return e('div', {
                    key: slug,
                    style: {
                        background: conq ? 'rgba(20,16,10,.95)' : 'rgba(10,8,4,.8)',
                        border: conq ? ('1px solid ' + cor.primary + '44') : '1px solid rgba(255,255,255,.06)',
                        borderRadius: 14,
                        padding: '18px 14px',
                        textAlign: 'center',
                        position: 'relative',
                        transition: 'all .3s',
                        boxShadow: conq ? ('0 0 20px ' + cor.glow) : 'none',
                    }
                },
                    // Raridade badge topo
                    conq && e('div', { style: {
                        position:'absolute', top: 8, right: 8,
                        fontSize: 8, fontFamily:"'Cinzel',serif", letterSpacing: 2,
                        color: cor.primary, fontWeight: 700,
                    }}, info.raridade.toUpperCase()),

                    // Ícone SVG
                    e('div', { style: { display:'flex', justifyContent:'center', marginBottom: 10 } },
                        e(BadgeIcon, { slug: slug, conquistada: !!conq, size: 52 })
                    ),

                    // Título
                    e('div', { style: {
                        fontFamily:"'Cinzel',serif",
                        fontSize: 11, fontWeight: 700,
                        color: conq ? '#f0e6c8' : 'var(--t5)',
                        marginBottom: 6, lineHeight: 1.4,
                    }}, info.titulo),

                    // Data / bloqueado
                    conq
                        ? e('div', { style: { fontSize: 10, color: cor ? cor.primary : 'var(--g4)' } },
                            new Date(conq.conquistado_em).toLocaleDateString('pt-BR')
                          )
                        : e('div', { style: { fontSize: 10, color: 'var(--t6)', letterSpacing: 1 } }, '🔒 BLOQUEADA'),

                    // Aventura
                    conq && conq.aventura_nome && e('div', { style: {
                        fontSize: 9, color: 'var(--t4)', marginTop: 4,
                        fontStyle: 'italic', overflow:'hidden',
                        textOverflow:'ellipsis', whiteSpace:'nowrap',
                    }}, conq.aventura_nome)
                );
            })
        )
    );
}

// ════════════════════════════════════════════
// PARTÍCULAS — configuráveis via C.lp.efeitos
// ════════════════════════════════════════════
function Particles() {
    var ef  = (C.lp && C.lp.efeitos) ? C.lp.efeitos : {};
    var qty  = ef.chamas_quantidade !== undefined ? ef.chamas_quantidade : 35;
    var vel  = ef.chamas_velocidade !== undefined ? ef.chamas_velocidade : 16;
    var sz   = ef.chamas_tamanho    !== undefined ? ef.chamas_tamanho    : 2;
    var op   = ef.chamas_opacidade  !== undefined ? ef.chamas_opacidade  : 70;
    var cor  = ef.chamas_cor || '#c9a84c';
    var ativo = ef.chamas_ativo !== false;
    if (!ativo) return null;

    var pts = Array.from({ length: qty }, function(_, i) {
        return {
            id: i,
            left: Math.random() * 100,
            delay: Math.random() * 25,
            dur: vel + Math.random() * (vel * 0.5),
        };
    });
    return e('div', { className: 'dnd-particles' },
        ...pts.map(function(p) {
            return e('div', {
                key: p.id, className: 'particle',
                style: {
                    left: p.left + '%',
                    width: sz + 'px', height: sz + 'px',
                    opacity: op / 100,
                    background: cor,
                    boxShadow: '0 0 ' + (sz*2) + 'px ' + cor,
                    animationDelay: p.delay + 's',
                    animationDuration: p.dur + 's',
                },
            });
        })
    );
}

// Névoa rastejante na base do hero
function Nevoa() {
    var ef = (C.lp && C.lp.efeitos) ? C.lp.efeitos : {};
    if (!ef.nevoa_ativo) return null;
    var op  = (ef.nevoa_opacidade !== undefined ? ef.nevoa_opacidade : 40) / 100;
    var cor = ef.nevoa_cor || '#0a0704';
    return e('div', { style: {
        position: 'absolute', bottom: 0, left: 0, right: 0, height: '35%', zIndex: 3,
        pointerEvents: 'none',
        background: 'linear-gradient(to top, ' + cor + ' 0%, ' + cor + 'cc 25%, ' + cor + '66 55%, transparent 100%)',
        opacity: op,
        animation: 'nevoa-drift 12s ease-in-out infinite alternate',
    }});
}

// Runas flutuantes
var RUNAS_CHARS = ['ᚠ','ᚢ','ᚦ','ᚨ','ᚱ','ᚲ','ᚷ','ᚹ','ᚺ','ᚾ','ᛁ','ᛃ','ᛇ','ᛈ','ᛉ','ᛊ','ᛏ','ᛒ','ᛖ','ᛗ','ᛚ','ᛜ','ᛞ','ᛟ'];
function Runas() {
    var ef = (C.lp && C.lp.efeitos) ? C.lp.efeitos : {};
    if (!ef.runas_ativo) return null;
    var qty = ef.runas_quantidade !== undefined ? parseInt(ef.runas_quantidade) : 8;
    var op  = (ef.runas_opacidade  !== undefined ? ef.runas_opacidade  : 8) / 100;
    var cor = ef.runas_cor || '#c9a84c';
    var items = Array.from({ length: qty }, function(_, i) {
        return {
            id: i,
            char:  RUNAS_CHARS[i % RUNAS_CHARS.length],
            left:  5 + (i / qty) * 90 + (Math.random() * 5 - 2.5),
            top:   10 + Math.random() * 75,
            size:  18 + Math.floor(Math.random() * 22),
            delay: Math.random() * 6,
            dur:   5 + Math.random() * 5,
        };
    });
    return e('div', { style: { position:'absolute', inset:0, zIndex:1, pointerEvents:'none', overflow:'hidden' } },
        items.map(function(r) {
            return e('div', {
                key: r.id,
                style: {
                    position: 'absolute',
                    left: r.left + '%', top: r.top + '%',
                    fontSize: r.size + 'px',
                    color: cor,
                    opacity: op,
                    animation: 'runa-pulsar ' + r.dur + 's ease-in-out ' + r.delay + 's infinite',
                    textShadow: '0 0 12px ' + cor + '66',
                    userSelect: 'none',
                }
            }, r.char);
        })
    );
}

// CSS dinâmico para bordas BG3 nos cards de módulo + features
function BordaBG3() {
    var ef = (C.lp && C.lp.efeitos) ? C.lp.efeitos : {};
    if (!ef.borda_bg3_ativo) return null;
    var estilo = ef.borda_bg3_estilo || 'ouro';
    var espessura = ef.borda_bg3_espessura !== undefined ? ef.borda_bg3_espessura : 2;
    var brilho = (ef.borda_bg3_brilho !== undefined ? ef.borda_bg3_brilho : 30) / 100;
    var paletas = {
        ouro:       { c1:'#c9a84c', c2:'#8b6914', c3:'#f0d080', shadow:'rgba(201,168,76,' },
        prata:      { c1:'#c0c8d8', c2:'#7a8898', c3:'#e8eef8', shadow:'rgba(192,200,216,' },
        esmeralda:  { c1:'#4ade80', c2:'#166534', c3:'#86efac', shadow:'rgba(74,222,128,' },
        sangue:     { c1:'#ef4444', c2:'#991b1b', c3:'#fca5a5', shadow:'rgba(239,68,68,' },
        gelo:       { c1:'#7dd3fc', c2:'#1e4a7a', c3:'#bae6fd', shadow:'rgba(125,211,252,' },
    };
    var p = paletas[estilo] || paletas.ouro;
    var glow = p.shadow + brilho + ')';
    var css = '\n' +
        '.lp-modulo-card, .lp-feature-card { position:relative; overflow:visible !important; }\n' +
        '.lp-modulo-card::before, .lp-feature-card::before,\n' +
        '.lp-modulo-card::after, .lp-feature-card::after {\n' +
        '    content:""; position:absolute; pointer-events:none;\n' +
        '    width:18px; height:18px;\n' +
        '    border-color:' + p.c1 + ';\n' +
        '    border-style:solid;\n' +
        '    z-index:10;\n' +
        '    transition:box-shadow 0.3s;\n' +
        '}\n' +
        '.lp-modulo-card::before, .lp-feature-card::before {\n' +
        '    top:-1px; left:-1px;\n' +
        '    border-width:' + espessura + 'px 0 0 ' + espessura + 'px;\n' +
        '    border-radius:' + espessura + 'px 0 0 0;\n' +
        '    box-shadow:-3px -3px 8px ' + glow + ', 3px 3px 8px transparent;\n' +
        '}\n' +
        '.lp-modulo-card::after, .lp-feature-card::after {\n' +
        '    bottom:-1px; right:-1px;\n' +
        '    border-width:0 ' + espessura + 'px ' + espessura + 'px 0;\n' +
        '    border-radius:0 0 ' + espessura + 'px 0;\n' +
        '    box-shadow:3px 3px 8px ' + glow + ', -3px -3px 8px transparent;\n' +
        '}\n' +
        '.lp-modulo-card:hover::before, .lp-feature-card:hover::before,\n' +
        '.lp-modulo-card:hover::after, .lp-feature-card:hover::after {\n' +
        '    border-color:' + p.c3 + ';\n' +
        '    box-shadow:0 0 16px ' + p.shadow + '0.6), 0 0 32px ' + p.shadow + '0.3);\n' +
        '    width:26px; height:26px;\n' +
        '}\n' +
        '.lp-modulo-card, .lp-feature-card {\n' +
        '    border: ' + espessura + 'px solid ' + p.c2 + ' !important;\n' +
        '    box-shadow: inset 0 0 20px rgba(0,0,0,0.3), 0 0 0 1px ' + p.c2 + '44 !important;\n' +
        '}\n' +
        '.lp-modulo-card:hover, .lp-feature-card:hover {\n' +
        '    border-color: ' + p.c1 + ' !important;\n' +
        '    box-shadow: inset 0 0 30px rgba(0,0,0,0.2), 0 0 20px ' + p.shadow + '0.15), 0 0 0 1px ' + p.c1 + '66 !important;\n' +
        '}\n';
    return e('style', null, css);
}

// ════════════════════════════════════════════
// MODAL DE LOGIN
// ════════════════════════════════════════════
function LoginModal({ fechar }) {
    const [usuario, setUsuario] = useState('');
    const [senha, setSenha]     = useState('');
    const [erro, setErro]       = useState('');
    const [loading, setLoading] = useState(false);

    const fazerLogin = async () => {
        if (!usuario.trim() || !senha.trim()) { setErro('Preencha usuário e senha.'); return; }
        setLoading(true); setErro('');
        try {
            const res = await fetch(C.apiUrl + '/login', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario, senha }),
            });
            const data = await res.json();
            if (data.sucesso && data.redirect) {
                window.location.href = data.redirect;
            } else {
                setErro(data.erro || 'Credenciais inválidas.');
            }
        } catch { setErro('Erro de conexão.'); }
        setLoading(false);
    };

    return e('div', { className: 'login-overlay', onClick: fechar },
        e('div', { className: 'login-modal', onClick: ev => ev.stopPropagation() },
            e('div', { className: 'login-body' },
                e('button', { className: 'login-fechar', onClick: fechar, title: 'Fechar' }, '×'),
                e('div', { className: 'login-header' },
                    e('span', { className: 'login-emblema' }, '⚔'),
                    e('div', { className: 'login-titulo' }, 'Entrar na Plataforma'),
                    e('div', { className: 'login-subtitulo' }, 'DnD Master · Área do Aventureiro')
                ),
                e('div', { className: 'login-form' },
                    erro && e('div', { className: 'login-erro' }, '⚠ ', erro),
                    e('div', { className: 'login-campo' },
                        e('label', { className: 'login-label' }, 'Usuário ou E-mail'),
                        e('input', { className: 'login-input', placeholder: 'seu@email.com', value: usuario, onChange: ev => setUsuario(ev.target.value), onKeyDown: ev => ev.key === 'Enter' && fazerLogin() })
                    ),
                    e('div', { className: 'login-campo' },
                        e('label', { className: 'login-label' }, 'Senha'),
                        e('input', { className: 'login-input', type: 'password', placeholder: '••••••••', value: senha, onChange: ev => setSenha(ev.target.value), onKeyDown: ev => ev.key === 'Enter' && fazerLogin() })
                    ),
                    e('button', { className: 'login-btn', onClick: fazerLogin, disabled: loading },
                        loading ? '⏳  ENTRANDO...' : '⚔  ENTRAR NA AVENTURA'
                    ),
                    e('div', { className: 'login-footer' }, 'Use as credenciais fornecidas pelo Mestre')
                )
            )
        )
    );
}

// ════════════════════════════════════════════
// LANDING PAGE
// ════════════════════════════════════════════

// ════════════════════════════════════════════
// LANDING PAGE — usa config de C.lp
// ════════════════════════════════════════════

function lp(key, fallback) {
    var obj = C.lp || {};
    var parts = key.split('.');
    for (var i = 0; i < parts.length; i++) {
        if (obj === undefined || obj === null) return fallback;
        obj = obj[parts[i]];
    }
    return obj !== undefined && obj !== null && obj !== '' ? obj : fallback;
}

// Fonte aplicada ao root da LP via style
function lpStyle() {
    var ft = lp('tipografia.fonte_titulo', 'Cinzel Decorative');
    var fb = lp('tipografia.fonte_corpo',  'Crimson Text');
    var fu = lp('tipografia.fonte_ui',     'Cinzel');
    var sc = lp('tipografia.escala', 100);
    var ouro = lp('cores.ouro', '#c9a84c');
    var fundo = lp('cores.fundo', '#0a0704');
    var texto = lp('cores.texto', '#907060');
    var borda = lp('cores.borda', '#1e1608');
    return {
        '--lp-ft': "'" + ft + "',serif",
        '--lp-fb': "'" + fb + "',Georgia,serif",
        '--lp-fu': "'" + fu + "',serif",
        '--lp-ouro': ouro,
        '--lp-fundo': fundo,
        '--lp-texto': texto,
        '--lp-borda': borda,
        fontSize: (sc / 100) + 'em',
    };
}

function FAQSection({ onEntrar }) {
    var items = lp('faq', [
        { p: 'Preciso saber D&D para jogar?', r: 'Não! O sistema foi pensado para iniciantes.' },
    ]);
    var [aberto, setAberto] = useState(null);
    return e('section', { style: { padding: '80px 24px', background: 'linear-gradient(180deg,#0a0704,#080503)' } },
        e('div', { style: { maxWidth: 700, margin: '0 auto' } },
            e('div', { style: { textAlign: 'center', marginBottom: 48 } },
                e('div', { style: { fontFamily: "var(--lp-fu,'Cinzel',serif)", fontSize: 11, letterSpacing: 6, color: '#4a3a2a', marginBottom: 12 } }, 'DÚVIDAS'),
                e('h2', { style: { fontFamily: "var(--lp-ft,'Cinzel Decorative',serif)", color: 'var(--lp-ouro,#c9a84c)', fontSize: 'clamp(22px,4vw,36px)' } }, 'Perguntas Frequentes')
            ),
            ...items.map(function(item, i) {
                return e('div', { key: i, style: { borderBottom: '1px solid #1a1208' } },
                    e('button', {
                        onClick: function() { setAberto(aberto === i ? null : i); },
                        style: {
                            width: '100%', textAlign: 'left', padding: '18px 0',
                            background: 'none', border: 'none', cursor: 'pointer',
                            color: aberto === i ? 'var(--lp-ouro,#c9a84c)' : '#d4b896',
                            fontFamily: "var(--lp-fb,'Crimson Text',Georgia,serif)", fontSize: 16,
                            display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 16,
                        },
                    },
                        e('span', null, item.p),
                        e('span', { style: { color: 'var(--lp-ouro,#c9a84c)', fontSize: 20, flexShrink: 0 } }, aberto === i ? '−' : '+')
                    ),
                    aberto === i && e('div', { style: { color: '#8a7a5a', fontSize: 15, lineHeight: 1.8, paddingBottom: 18 } }, item.r)
                );
            })
        )
    );
}

function AventurasSection({ onEntrar }) {
    var mostrar = lp('modulos_secao.mostrar', true);
    if (!mostrar) return null;

    var titulo    = lp('modulos_secao.titulo',    'Aventuras em Destaque');
    var subtitulo = lp('modulos_secao.subtitulo', 'MÓDULOS DISPONÍVEIS');

    // Busca via API (mais confiável que PHP injection)
    var [modulos,   setModulos]   = useState(lp('modulos', null)); // null = carregando
    var [carregando, setCarregando] = useState(modulos === null);

    useEffect(function() {
        // Se já veio do PHP injection, usa direto
        var injetados = lp('modulos', null);
        if (Array.isArray(injetados) && injetados.length > 0) {
            setModulos(injetados); setCarregando(false); return;
        }
        // Fallback: busca via REST público
        api('/modulos/lp').then(function(data) {
            setModulos(Array.isArray(data) ? data : []);
            setCarregando(false);
        }).catch(function() { setModulos([]); setCarregando(false); });
    }, []);

    if (carregando) return e('section', { className: 'aventuras-secao' },
        e('div', { className: 'secao-label' }, subtitulo),
        e('h2', { className: 'secao-titulo' }, titulo),
        e('div', { style:{ textAlign:'center', padding:'48px', color:'#4a3a2a' } },
            e('div', { style:{ fontSize:32, animation:'spin 2s linear infinite' } }, '⚔')
        )
    );

    return e('section', { className: 'aventuras-secao' },
        e('div', { className: 'secao-label' }, subtitulo),
        e('h2', { className: 'secao-titulo' }, titulo),

        (!modulos || modulos.length === 0)
            ? e('div', { style: { textAlign:'center', padding:'48px 24px', color:'#4a3a2a' } },
                e('div', { style:{ fontSize:48, marginBottom:16, opacity:.25 } }, '📜'),
                e('p', { style:{ fontFamily:"'Cinzel',serif", fontSize:13, letterSpacing:2 } },
                    'NENHUMA AVENTURA CADASTRADA')
              )
            : e('div', { className: 'aventuras-grid' },
                ...modulos.map(function(m) {
                    return e('div', {
                        key: m.id,
                        className: 'aventura-card lp-modulo-card',
                        style: { position: 'relative', overflow: 'hidden', cursor: 'pointer' },
                        onClick: onEntrar,
                    },
                        m.capa_url
                            ? e('img', { src: m.capa_url, style: { position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', opacity: 0.35 } })
                            : e('div', { className: 'aventura-bg', style: { background: 'linear-gradient(135deg,#1a0c04,#2a1008,#180808)' } }),
                        e('div', { className: 'aventura-overlay' }),
                        e('div', { className: 'aventura-conteudo' },
                            e('div', { className: 'aventura-sistema' }, m.sistema || 'D&D 5E'),
                            e('h3', { className: 'aventura-nome' }, m.nome),
                            (m.tagline || m.descricao || m.synopsis) && e('p', { className: 'aventura-tagline' },
                                m.tagline || m.descricao || m.synopsis
                            ),
                            e('button', { className: 'aventura-btn', onClick: onEntrar }, '⚔ Explorar Aventura')
                        )
                    );
                })
              )
    );
}

function LandingPage({ onEntrar }) {
    var heroBg = lp('hero.bg_tipo','cor') === 'imagem' && lp('hero.bg_imagem','')
        ? { backgroundImage: 'url(' + lp('hero.bg_imagem','') + ')', backgroundSize: 'cover', backgroundPosition: 'center' }
        : { background: lp('hero.bg_cor','#0a0704') };
    var overlayOp = lp('hero.bg_overlay',70) / 100;
    var features = lp('features', []);

    return e('div', { className: 'landing-root', style: lpStyle() },
        e(Particles),
        e(Runas),
        e(BordaBG3),

        // ── HERO ──────────────────────────────────────────────────────────────
        e('section', { style: Object.assign({}, {
            position: 'relative',
            height: '100vh', minHeight: '600px',
            display: 'flex', flexDirection: 'column',
            alignItems: 'center', justifyContent: 'center',
            textAlign: 'center', padding: '80px 24px 60px',
            overflow: 'hidden', boxSizing: 'border-box',
        }, heroBg) },
            overlayOp > 0 && e('div', { style: { position: 'absolute', inset: 0, background: 'rgba(0,0,0,' + overlayOp + ')', pointerEvents: 'none' } }),
            e('div', { style: { position: 'absolute', inset: 0, pointerEvents: 'none', backgroundImage: 'repeating-linear-gradient(0deg,transparent,transparent 80px,#ffffff03 80px,#ffffff03 81px),repeating-linear-gradient(90deg,transparent,transparent 80px,#ffffff03 80px,#ffffff03 81px)' } }),
            e(Nevoa),
            e('div', { style: { position: 'relative', zIndex: 1, width: '100%' } },
                e('div', { style: { fontFamily: "var(--lp-fu,Cinzel,serif)", fontSize: 11, letterSpacing: 8, color: '#4a3a2a', marginBottom: 24, animation: 'fade-in 1s ease both' } }, lp('hero.eyebrow', '✦  SISTEMA DE RPG FAMILIAR  ✦')),
                e('h1', { style: { fontFamily: "var(--lp-ft,'Cinzel Decorative',serif)", fontWeight: 900, fontSize: 'clamp(40px,10vw,100px)', color: 'var(--lp-ouro,#c9a84c)', lineHeight: 1.05, textShadow: '0 0 80px rgba(201,168,76,0.2), 0 4px 24px rgba(0,0,0,0.6)', animation: 'fade-up 1s ease 0.2s both', marginBottom: 10 } }, lp('hero.titulo','DnD Master')),
                e('p', { style: { fontFamily: "var(--lp-fu,Cinzel,serif)", fontSize: 'clamp(12px,2vw,16px)', color: '#6a5a3a', letterSpacing: 5, animation: 'fade-up 1s ease 0.4s both', marginBottom: 24 } }, lp('hero.subtitulo','DUNGEONS & DRAGONS 5E').toUpperCase()),
                e('div', { style: { width: 100, height: 1, margin: '0 auto 32px', background: 'linear-gradient(90deg,transparent,' + lp('cores.ouro_dim','#8b6914') + ',transparent)', animation: 'fade-in 1s ease 0.5s both' } }),
                e('p', { style: { maxWidth: 520, margin: '0 auto 48px', fontSize: 'clamp(16px,2.5vw,20px)', color: 'var(--lp-texto,#907060)', lineHeight: 1.8, animation: 'fade-up 1s ease 0.6s both', textAlign: 'center' } }, lp('hero.desc','Uma plataforma épica para mestres e jogadores de D&D.')),
                e('button', {
                    onClick: onEntrar,
                    style: { padding: '18px 52px', background: 'linear-gradient(135deg,#6b4f10,' + lp('cores.ouro','#c9a84c') + ',#8b6914)', border: '1px solid rgba(201,168,76,0.27)', borderRadius: 12, color: '#0a0704', fontFamily: "var(--lp-fu,Cinzel,serif)", fontSize: 14, fontWeight: 700, letterSpacing: 4, cursor: 'pointer', boxShadow: '0 8px 40px rgba(201,168,76,0.13), 0 2px 8px rgba(0,0,0,0.4)', animation: 'fade-up 1s ease 0.8s both', transition: 'transform 0.2s, box-shadow 0.2s' },
                    onMouseOver: function(ev) { ev.currentTarget.style.transform='translateY(-2px)'; },
                    onMouseOut:  function(ev) { ev.currentTarget.style.transform='translateY(0)'; },
                }, lp('hero.cta_texto','⚔ ENTRAR NA AVENTURA')),
                lp('hero.cta_subtexto','') && e('p', { style: { color: '#3a2a1a', fontSize: 13, marginTop: 20, animation: 'fade-in 1s ease 1.2s both' } }, lp('hero.cta_subtexto',''))
            )
        ),

        // ── FEATURES ──────────────────────────────────────────────────────────
        features.length > 0 && e('section', { style: { padding: '80px 24px', background: lp('cores.fundo','#0a0704') } },
            e('div', { style: { maxWidth: 1000, margin: '0 auto' } },
                e('div', { style: { textAlign: 'center', marginBottom: 56 } },
                    e('div', { style: { fontFamily: "var(--lp-fu,Cinzel,serif)", fontSize: 10, letterSpacing: 6, color: '#4a3a2a', marginBottom: 14 } }, 'O QUE VOCÊ TEM'),
                    e('h2', { style: { fontFamily: "var(--lp-ft,'Cinzel Decorative',serif)", color: 'var(--lp-ouro,#c9a84c)', fontSize: 'clamp(22px,4vw,40px)' } }, 'Uma Plataforma Épica')
                ),
                e('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(280px,1fr))', gap: 20 } },
                    ...features.map(function(f, i) {
                        return e('div', {
                            key: i,
                            className: 'lp-feature-card',
                            style: { background: '#120e04', border: '1px solid var(--lp-borda,#1e1608)', borderRadius: 14, padding: '28px 24px', transition: 'border-color 0.3s, transform 0.3s', cursor: 'default' },
                            onMouseOver: function(ev) { ev.currentTarget.style.borderColor = lp('cores.ouro_dim','#3a2a10'); ev.currentTarget.style.transform='translateY(-4px)'; },
                            onMouseOut:  function(ev) { ev.currentTarget.style.borderColor = 'var(--lp-borda,#1e1608)'; ev.currentTarget.style.transform='translateY(0)'; },
                        },
                            e('div', { style: { fontSize: 36, marginBottom: 14 } }, f.icone || '⭐'),
                            e('div', { style: { fontFamily: "var(--lp-fu,Cinzel,serif)", color: 'var(--lp-ouro,#c9a84c)', fontSize: 14, letterSpacing: 1, marginBottom: 10 } }, f.titulo),
                            e('p', { style: { color: '#7a6a4a', fontSize: 15, lineHeight: 1.7 } }, f.desc)
                        );
                    })
                )
            )
        ),

        e(AventurasSection, { onEntrar }),
        e(FAQSection, { onEntrar }),

        // ── CTA FINAL ─────────────────────────────────────────────────────────
        lp('rodape.mostrar_rodape', true) && e('section', { style: { padding: '80px 24px', textAlign: 'center', background: 'linear-gradient(180deg,#080503,#0a0704)' } },
            e('div', { style: { fontFamily: "var(--lp-ft,'Cinzel Decorative',serif)", color: 'var(--lp-ouro,#c9a84c)', fontSize: 'clamp(20px,4vw,36px)', marginBottom: 16 } }, lp('rodape.cta_titulo','Pronto(a) para a Aventura?')),
            e('p', { style: { color: '#6a5a3a', marginBottom: 32, fontSize: 16 } }, lp('rodape.cta_desc','Entre com suas credenciais e comece a sua jornada.')),
            e('button', { onClick: onEntrar, className: 'dnd-btn-primary', style: { maxWidth: 300, margin: '0 auto', display: 'block' } }, lp('hero.cta_texto','⚔ ENTRAR NA CAMPANHA'))
        )
    );
}

// ════════════════════════════════════════════
// UTILITÁRIO: PARSER DE CAPÍTULO
// ════════════════════════════════════════════
function parseConteudo(content) {
    if (!content) return { readAloud: [], dmNotes: [] };
    const readAloud = [];
    const dmNotes   = [];
    const blocos = content.split(/\n{2,}/).map(b => b.trim()).filter(Boolean);
    blocos.forEach(bloco => {
        const isDM = /^\[?\s*(?:DM|GM|NOTA DO MESTRE|NOTA|MESTRE)[:\s]/i.test(bloco) ||
                     /^\*{1,2}(?:DM|GM|Nota do Mestre|Nota)[:\s*]/i.test(bloco);
        if (isDM) {
            const texto = bloco
                .replace(/^\[?\s*(?:DM|GM|NOTA DO MESTRE|NOTA|MESTRE)[:\s]*/i, '')
                .replace(/^\*{1,2}[^*]+\*{1,2}:?\s*/i, '')
                .replace(/\]\s*$/, '').trim();
            dmNotes.push(texto || bloco);
        } else {
            readAloud.push(bloco);
        }
    });
    if (readAloud.length === 0 && dmNotes.length === 0) readAloud.push(content);
    return { readAloud, dmNotes };
}

// ════════════════════════════════════════════
// SELETOR DE MÓDULO (tela inicial do Mestre)
// ════════════════════════════════════════════
function SelecaoModuloLayout({ onLayoutSalvo, layoutAtual }) {
    const [layout, setLayout] = useState(layoutAtual || 'E');
    const [salvando, setSalvando] = useState(false);

    const layoutInfo = {
        E: { nome: '⚔ Tela do Mestre', desc: 'Layout completo com cenas, blocos, NPCs, Monstros, Tesouros e dados.' },
    };

    const salvar = async () => {
        setSalvando(true);
        await api('/layout-preferencia', 'POST', { layout });
        onLayoutSalvo(layout);
        setSalvando(false);
    };

    return e('div', { className: 'dnd-card', style: { maxWidth: 700, margin: '0 auto', marginBottom: 24 } },
        e('div', { className: 'dnd-card-label' }, '🎨 LAYOUT DO DASHBOARD'),
        e('p', { style: { color: 'var(--t2)', fontSize: 15, marginBottom: 20, lineHeight: 1.7 } },
            'Escolha como o painel do Mestre será exibido. Você pode mudar a qualquer momento pelos botões no topo.'
        ),
        e('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 20 } },
            ...Object.entries(layoutInfo).map(([k, v]) =>
                e('div', {
                    key: k,
                    onClick: () => setLayout(k),
                    style: {
                        padding: '14px 16px', borderRadius: 10, cursor: 'pointer',
                        border: layout === k ? '2px solid var(--g2)' : '1px solid var(--b5)',
                        background: layout === k ? 'rgba(201,168,76,.08)' : 'var(--b2)',
                        transition: 'all .2s',
                    }
                },
                    e('div', { style: { display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 } },
                        e('div', { style: {
                            width: 32, height: 32, borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center',
                            background: layout === k ? 'rgba(201,168,76,.2)' : 'var(--b3)',
                            border: layout === k ? '1px solid var(--g2)' : '1px solid var(--b5)',
                            fontFamily: "'Cinzel',serif", fontSize: 14, color: layout === k ? 'var(--g2)' : 'var(--t3)',
                        }}, k),
                        e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 12, color: layout === k ? 'var(--g1)' : 'var(--t2)', letterSpacing: 1 } }, v.nome)
                    ),
                    e('p', { style: { fontSize: 13, color: 'var(--t3)', margin: 0, lineHeight: 1.6 } }, v.desc)
                )
            )
        ),
        e('button', {
            className: 'btn btn-gold',
            style: { width: '100%' },
            onClick: salvar,
            disabled: salvando,
        }, salvando ? '⏳ Salvando...' : '✓ Confirmar Layout e Jogar')
    );
}

// ════════════════════════════════════════════
// SELEÇÃO DE MÓDULO (escolher história)
// ════════════════════════════════════════════
function SelecionarModulo({ onVinculado }) {
    const [modulos, setModulos]       = useState([]);
    const [carregando, setCarregando] = useState(true);
    const [vinculando, setVinculando] = useState(null);
    const [importando, setImportando] = useState(false);
    const [msg, setMsg]               = useState(null);
    const fileRef                     = useRef(null);

    const carregar = () => {
        api('/modulos').then(r => { setModulos(Array.isArray(r) ? r : []); setCarregando(false); });
    };
    useEffect(carregar, []);

    const importarJSON = async () => {
        const file = fileRef.current?.files?.[0];
        if (!file) { setMsg({ tipo: 'erro', texto: 'Selecione um arquivo JSON.' }); return; }
        setImportando(true); setMsg(null);
        try {
            const texto = await file.text();
            let json;
            try { json = JSON.parse(texto); }
            catch(pe) { setMsg({ tipo: 'erro', texto: '⚠ JSON inválido: ' + pe.message }); setImportando(false); return; }
            if (!json.nome && !json.title) { setMsg({ tipo: 'erro', texto: '⚠ JSON precisa ter campo "nome".' }); setImportando(false); return; }
            if (!Array.isArray(json.chapters) || json.chapters.length === 0) { setMsg({ tipo: 'erro', texto: '⚠ JSON precisa ter array "chapters" com pelo menos uma cena.' }); setImportando(false); return; }
            const res = await api('/modulos/importar', 'POST', { json: json });
            if (res.sucesso || res.id) {
                setMsg({ tipo: 'ok', texto: '✓ "' + (json.nome || json.title) + '" importado! Selecione abaixo para jogar.' });
                if (fileRef.current) fileRef.current.value = '';
                carregar();
            } else {
                setMsg({ tipo: 'erro', texto: res.erro || 'Erro ao importar.' });
            }
        } catch(err) {
            setMsg({ tipo: 'erro', texto: 'Erro inesperado: ' + err.message });
        }
        setImportando(false);
    };

    const vincular = async (m) => {
        setVinculando(m.id); setMsg(null);
        try {
            const res = await api('/ativar-campanha', 'POST', { modulo_id: m.id });
            if (res.sucesso) {
                setMsg({ tipo: 'ok', texto: `✓ "${m.nome}" ativado!` });
                setTimeout(() => onVinculado(res.campanha_id), 900);
            } else {
                setMsg({ tipo: 'erro', texto: res.erro || 'Erro ao ativar.' });
            }
        } catch { setMsg({ tipo: 'erro', texto: 'Erro de conexão.' }); }
        setVinculando(null);
    };

    if (carregando) return e(Spinner, { texto: 'CARREGANDO MÓDULOS...' });

    return e('div', { className: 'dnd-card', style: { maxWidth: 700, margin: '0 auto' } },
        e('div', { className: 'dnd-card-label' }, '📜 ESCOLHER HISTÓRIA'),
        msg && e('div', { className: `alerta alerta-${msg.tipo}`, style: { marginBottom: 16 } }, msg.texto),

        // ── Import inline ───────────────────────────────────────────────────
        e('div', { style: { marginBottom: 24, paddingBottom: 20, borderBottom: '1px solid var(--b4)' } },
            e('p', { style: { color: 'var(--t3)', fontSize: 13, marginBottom: 12 } },
                '📤 Importar nova aventura (JSON gerado pelo Claude):'
            ),
            e('div', { style: { display: 'flex', gap: 10, alignItems: 'center', flexWrap: 'wrap' } },
                e('input', {
                    ref: fileRef, type: 'file', accept: '.json,application/json',
                    style: {
                        flex: 1, minWidth: 180, background: 'var(--b2)',
                        border: '1px solid var(--b5)', borderRadius: 8,
                        padding: '9px 12px', color: 'var(--t2)', fontSize: 13, cursor: 'pointer',
                    }
                }),
                e('button', {
                    className: 'btn btn-gold',
                    onClick: importarJSON,
                    disabled: importando,
                    style: { whiteSpace: 'nowrap' },
                }, importando ? '⏳ Importando...' : '📤 Importar')
            )
        ),

        // ── Module list ─────────────────────────────────────────────────────
        modulos.length === 0
            ? e('div', { style: { textAlign: 'center', padding: '24px 0', color: 'var(--t4)' } },
                e('div', { style: { fontSize: 40, marginBottom: 10, opacity: .35 } }, '📦'),
                e('p', { style: { fontSize: 14 } }, 'Nenhum módulo importado ainda. Importe um JSON acima.')
              )
            : e('div', null,
                e('p', { style: { color: 'var(--t2)', fontSize: 14, marginBottom: 16 } },
                    'Selecione qual aventura será jogada nesta sessão:'
                ),
                ...modulos.map(m =>
                    e('div', {
                        key: m.id,
                        style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '14px 0', borderBottom: '1px solid var(--b4)', gap: 12 }
                    },
                        e('div', { style: { flex: 1 } },
                            e('div', { style: { color: 'var(--g2)', fontWeight: 700, fontSize: 16, marginBottom: 3 } }, m.nome),
                            e('div', { style: { color: 'var(--t3)', fontSize: 12, letterSpacing: 1 } },
                                (m.sistema || 'D&D 5e') + ' · ' + (m.total_cenas || 0) + ' capítulos · ' + (m.total_npcs || 0) + ' NPCs'
                            )
                        ),
                        e('button', {
                            className: 'btn btn-gold',
                            disabled: !!vinculando,
                            onClick: () => vincular(m),
                        }, vinculando === m.id ? '⏳ Ativando...' : '⚔ Jogar Esta')
                    )
                )
              )
    );
}

// ════════════════════════════════════════════
// GESTÃO DE JOGADORES (Mestre)
// ════════════════════════════════════════════
function GestaoJogadores({ campanhaId }) {
    const [jogadores, setJogadores]       = useState([]);
    const [inscricoes, setInscricoes]     = useState([]);
    const [carregando, setCarregando]     = useState(true);
    const [form, setForm]                 = useState({ nome: '', email: '', senha: '' });
    const [criando, setCriando]           = useState(false);
    const [msg, setMsg]                   = useState(null);
    const [aprovando, setAprovando]       = useState(null);

    const carregar = () => {
        setCarregando(true);
        api('/mestre/jogadores').then(r => { setJogadores(Array.isArray(r) ? r : []); setCarregando(false); });
    };

    const carregarInscricoes = () => {
        if (!campanhaId) return;
        api('/campanhas/' + campanhaId + '/inscricoes').then(r => {
            setInscricoes(Array.isArray(r) ? r : []);
        });
    };

    useEffect(() => { carregar(); carregarInscricoes(); }, []);

    const criar = async () => {
        if (!form.nome.trim() || !form.email.trim()) { setMsg({ tipo:'erro', texto:'Nome e e-mail são obrigatórios.' }); return; }
        setCriando(true); setMsg(null);
        const res = await api('/mestre/jogadores', 'POST', form);
        if (res.sucesso) { setMsg({ tipo:'ok', texto:'✓ Jogador criado! E-mail enviado.' }); setForm({ nome:'', email:'', senha:'' }); carregar(); }
        else setMsg({ tipo:'erro', texto: res.erro || 'Erro ao criar jogador.' });
        setCriando(false);
    };

    const aprovar = async (id) => {
        setAprovando(id);
        await api('/inscricoes/' + id + '/aprovar', 'POST');
        carregarInscricoes();
        carregar();
        setAprovando(null);
    };

    const rejeitar = async (id) => {
        await api('/inscricoes/' + id + '/rejeitar', 'POST');
        carregarInscricoes();
    };

    if (carregando) return e(Spinner, { texto: 'CARREGANDO JOGADORES...' });

    const pendentes = inscricoes.filter(i => i.status === 'pendente');
    const aprovados = inscricoes.filter(i => i.status === 'aprovado');

    return e('div', null,

        // ── Inscrições pendentes (destaque no topo quando há pendentes) ────────
        campanhaId && pendentes.length > 0 && e('div', { className: 'dnd-card', style: { borderColor: '#c9a84c', marginBottom: 16 } },
            e('div', { className: 'dnd-card-label', style: { color: '#c9a84c' } },
                '⏳ INSCRIÇÕES PENDENTES (' + pendentes.length + ')'
            ),
            ...pendentes.map(i =>
                e('div', { key: i.id, style: { display: 'flex', alignItems: 'center', gap: 12, padding: '10px 0', borderBottom: '1px solid var(--b4)' } },
                    e('div', { style: { width: 38, height: 38, borderRadius: '50%', background: 'var(--b3)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 } }, '🧙'),
                    e('div', { style: { flex: 1 } },
                        e('div', { style: { color: 'var(--t1)', fontWeight: 600, fontSize: 14 } }, i.nome),
                        e('div', { style: { color: 'var(--t3)', fontSize: 11 } },
                            i.personagem ? (i.personagem + ' · ' + (i.classe || '') + ' · Nv ' + (i.nivel || 1)) : 'Sem personagem ainda'
                        )
                    ),
                    e('div', { style: { display: 'flex', gap: 8 } },
                        e('button', {
                            className: 'btn btn-gold',
                            style: { padding: '5px 14px', fontSize: 11 },
                            disabled: aprovando === i.id,
                            onClick: () => aprovar(i.id),
                        }, aprovando === i.id ? '⏳' : '✅ Aprovar'),
                        e('button', {
                            className: 'btn btn-ghost',
                            style: { padding: '5px 10px', fontSize: 11, color: 'var(--r2)' },
                            onClick: () => rejeitar(i.id),
                        }, '✕')
                    )
                )
            )
        ),

        // ── Jogadores aprovados na campanha ───────────────────────────────────
        campanhaId && aprovados.length > 0 && e('div', { className: 'dnd-card', style: { marginBottom: 16 } },
            e('div', { className: 'dnd-card-label' }, '✅ NA CAMPANHA (' + aprovados.length + ')'),
            ...aprovados.map(i =>
                e('div', { key: i.id, style: { display: 'flex', alignItems: 'center', gap: 12, padding: '10px 0', borderBottom: '1px solid var(--b4)' } },
                    i.imagem
                        ? e('img', { src: i.imagem, style: { width: 38, height: 38, borderRadius: '50%', objectFit: 'cover' } })
                        : e('div', { style: { width: 38, height: 38, borderRadius: '50%', background: 'var(--b3)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 } }, '⚔'),
                    e('div', { style: { flex: 1 } },
                        e('div', { style: { color: 'var(--t1)', fontWeight: 600, fontSize: 14 } }, i.nome),
                        e('div', { style: { color: 'var(--t3)', fontSize: 11 } },
                            i.personagem ? (i.personagem + ' · ' + (i.classe || '') + ' · Nv ' + (i.nivel || 1)) : 'Sem personagem criado'
                        )
                    ),
                    e('span', { style: { fontSize: 11, padding: '3px 10px', borderRadius: 4, background: 'var(--b3)', color: 'var(--g3)', fontFamily: "'Cinzel',serif", letterSpacing: 1 } }, 'NA SESSÃO')
                )
            )
        ),

        // ── Criar novo jogador ────────────────────────────────────────────────
        e('div', { className: 'dnd-card' },
            e('div', { className: 'dnd-card-label' }, '➕ NOVO JOGADOR'),
            msg && e('div', { className: `alerta alerta-${msg.tipo}`, style: { marginBottom: 14 } }, msg.texto),
            e('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 } },
                e('input', { className: 'dnd-input', placeholder: 'Nome do jogador', value: form.nome, onChange: ev => setForm(f => ({...f, nome: ev.target.value})), style: { marginBottom: 0 } }),
                e('input', { className: 'dnd-input', type: 'email', placeholder: 'E-mail', value: form.email, onChange: ev => setForm(f => ({...f, email: ev.target.value})), style: { marginBottom: 0 } })
            ),
            e('button', { className: 'btn btn-gold', disabled: criando, onClick: criar }, criando ? '⏳ Criando...' : '✅ Criar Jogador')
        ),

        // ── Todos os jogadores da plataforma ──────────────────────────────────
        jogadores.length > 0 && e('div', { className: 'dnd-card' },
            e('div', { className: 'dnd-card-label' }, '👥 JOGADORES ATIVOS'),
            ...jogadores.map(j =>
                e('div', { key: j.wp_id || j.id, style: { display: 'flex', alignItems: 'center', gap: 12, padding: '12px 0', borderBottom: '1px solid var(--b4)' } },
                    j.imagem ? e('img', { src: j.imagem, style: { width: 40, height: 40, borderRadius: '50%', objectFit: 'cover' } })
                             : e('div', { style: { width: 40, height: 40, borderRadius: '50%', background: 'var(--b3)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18 } }, '⚔'),
                    e('div', { style: { flex: 1 } },
                        e('div', { style: { color: 'var(--t1)', fontWeight: 600, fontSize: 15 } }, j.display_name || j.nome),
                        e('div', { style: { color: 'var(--t3)', fontSize: 12 } },
                            j.personagem_nome
                                ? `${j.personagem_nome} · ${j.personagem_classe} · Nv ${j.nivel || 1}`
                                : 'Sem personagem criado'
                        )
                    ),
                    e('span', { style: { fontSize: 11, padding: '3px 10px', borderRadius: 4, background: 'var(--b3)', color: j.personagem_nome ? 'var(--g3)' : 'var(--t4)', fontFamily: "'Cinzel',serif", letterSpacing: 1 } },
                        j.personagem_nome ? 'ATIVA' : 'SEM PERSONAGEM'
                    )
                )
            )
        )
    );
}

// GESTÃO DE MÓDULOS
// ════════════════════════════════════════════
function GestaoModulos() {
    const [modulos, setModulos]             = useState([]);
    const [carregando, setCarregando]       = useState(true);
    const [desvinculando, setDesvinculando] = useState(null);
    const [excluindo, setExcluindo]         = useState(null);
    const [msg, setMsg]                     = useState(null);
    const [importando, setImportando]       = useState(false);
    const fileRef                           = useRef(null);

    const carregar = () => {
        api('/modulos').then(r => { setModulos(Array.isArray(r) ? r : []); setCarregando(false); });
    };
    useEffect(carregar, []);

    const importarJSON = async () => {
        const file = fileRef.current?.files?.[0];
        if (!file) { setMsg({ tipo: 'erro', texto: 'Selecione um arquivo JSON.' }); return; }
        setImportando(true); setMsg(null);
        try {
            const texto = await file.text();
            let json;
            try {
                json = JSON.parse(texto);
            } catch(parseErr) {
                setMsg({ tipo: 'erro', texto: '⚠ JSON inválido: ' + parseErr.message + '. Verifique o arquivo e tente novamente.' });
                setImportando(false); return;
            }
            // Validação mínima antes de enviar
            if (!json.nome && !json.title) {
                setMsg({ tipo: 'erro', texto: '⚠ O JSON precisa ter um campo "nome" com o título da aventura.' });
                setImportando(false); return;
            }
            if (!Array.isArray(json.chapters) || json.chapters.length === 0) {
                setMsg({ tipo: 'erro', texto: '⚠ O JSON precisa ter um array "chapters" com pelo menos uma cena.' });
                setImportando(false); return;
            }
            const res = await api('/modulos/importar', 'POST', { json: json });
            if (res.sucesso || res.id) {
                setMsg({ tipo: 'ok', texto: '✓ Módulo "' + (json.nome || json.title || 'Aventura') + '" importado com sucesso! (' + json.chapters.length + ' cenas)' });
                if (fileRef.current) fileRef.current.value = '';
                carregar();
            } else {
                setMsg({ tipo: 'erro', texto: res.erro || 'Erro ao importar.' });
            }
        } catch(err) {
            setMsg({ tipo: 'erro', texto: 'Erro inesperado: ' + err.message });
        }
        setImportando(false);
    };

    const desvincular = async (modulo) => {
        if (!window.confirm(`Desvincular "${modulo.nome}"?\n\nO módulo continuará disponível para vincular novamente.`)) return;
        setDesvinculando(modulo.id); setMsg(null);
        try {
            const res = await api('/desvincular-campanha', 'POST', { modulo_id: modulo.id });
            if (res.sucesso) { setMsg({ tipo: 'ok', texto: '✓ Módulo desvinculado.' }); carregar(); }
            else setMsg({ tipo: 'erro', texto: res.erro || 'Erro ao desvincular.' });
        } catch { setMsg({ tipo: 'erro', texto: 'Erro de conexão.' }); }
        setDesvinculando(null);
    };

    const excluir = async (modulo) => {
        if (!window.confirm(`⚠ Excluir "${modulo.nome}" permanentemente?\n\nRemove NPCs e checklist. Desvincule primeiro se necessário.`)) return;
        setExcluindo(modulo.id); setMsg(null);
        try {
            const res = await api('/modulos/' + modulo.id, 'DELETE');
            if (res.sucesso) { setMsg({ tipo: 'ok', texto: '✓ Módulo excluído.' }); carregar(); }
            else setMsg({ tipo: 'erro', texto: res.erro || 'Erro ao excluir.' });
        } catch { setMsg({ tipo: 'erro', texto: 'Erro de conexão.' }); }
        setExcluindo(null);
    };

    if (carregando) return e(Spinner, { texto: 'CARREGANDO MÓDULOS...' });

    return e('div', null,
        // ── Importar JSON ──────────────────────────────────────────────
        e('div', { className: 'dnd-card' },
            e('div', { className: 'dnd-card-label' }, '📦 IMPORTAR MÓDULO JSON'),
            msg && e('div', { className: `alerta alerta-${msg.tipo}`, style: { marginBottom: 14 } }, msg.texto),
            e('p', { style: { color: 'var(--t2)', fontSize: 14, lineHeight: 1.6, marginBottom: 16 } },
                'Selecione o arquivo JSON gerado pelo Claude a partir de uma aventura em PDF.'
            ),
            e('div', { style: { display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' } },
                e('input', {
                    ref: fileRef,
                    type: 'file',
                    accept: '.json,application/json',
                    style: {
                        flex: 1, minWidth: 200,
                        background: 'var(--b2)', border: '1px solid var(--b5)',
                        borderRadius: 8, padding: '10px 14px',
                        color: 'var(--t1)', fontSize: 13,
                        cursor: 'pointer',
                    }
                }),
                e('button', {
                    className: 'btn btn-gold',
                    onClick: importarJSON,
                    disabled: importando,
                    style: { whiteSpace: 'nowrap' },
                }, importando ? '⏳ Importando...' : '📤 Importar JSON')
            )
        ),
        // ── Lista de módulos ───────────────────────────────────────────
        e('div', { className: 'dnd-card' },
            e('div', { className: 'dnd-card-label' }, 'MÓDULOS DISPONÍVEIS'),
            modulos.length === 0
                ? e('div', { style: { textAlign: 'center', padding: '32px 0', color: 'var(--t4)' } },
                    e('div', { style: { fontSize: 40, marginBottom: 12, opacity: 0.4 } }, '📜'),
                    e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 13, letterSpacing: 2 } }, 'NENHUM MÓDULO IMPORTADO'),
                    e('p', { style: { fontSize: 13, marginTop: 8 } }, 'Importe um arquivo JSON acima para começar.')
                  )
                : modulos.map(m =>
                    e('div', {
                        key: m.id,
                        style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '14px 0', borderBottom: '1px solid var(--b4)', gap: 12 }
                    },
                        e('div', { style: { flex: 1 } },
                            e('div', { style: { color: 'var(--g2)', fontWeight: 700, fontSize: 15 } }, m.nome),
                            e('div', { style: { color: 'var(--t3)', fontSize: 12 } },
                                (m.sistema || 'D&D 5e') + ' · ' + (m.total_cenas || 0) + ' etapas · ' + (m.total_npcs || 0) + ' NPCs'
                            )
                        ),
                        e('div', { style: { display: 'flex', gap: 8, flexShrink: 0 } },
                            e('button', {
                                className: 'btn btn-ghost',
                                disabled: !!desvinculando || !!excluindo,
                                onClick: () => desvincular(m),
                            }, desvinculando === m.id ? '⏳...' : '🔓 Desvincular'),
                            e('button', {
                                className: 'btn btn-danger',
                                disabled: !!excluindo || !!desvinculando,
                                onClick: () => excluir(m),
                            }, excluindo === m.id ? '⏳...' : '🗑 Excluir')
                        )
                    )
                  )
        )
    );
}

// ════════════════════════════════════════════
// HUD DO MESTRE — ESTADO COMPARTILHADO
// ════════════════════════════════════════════
// ════════════════════════════════════════════
// MAPA VTT — Fase 1
// ════════════════════════════════════════════
var CORES_TOKENS = ['#c9a84c','#4caf7d','#5b9bd5','#e85454','#b06ec4','#f5a623','#4bc9c9','#f06292','#80cbc4','#ffb74d'];

function MapaVTT({ personagens, campanha, modulo, aplicarDano, aplicarCura }) {
    var campId = campanha?.id;

    var [mapUrl,        setMapUrl]        = useState(modulo?.mapa_url || null);
    var [tokens,        setTokens]        = useState([]);
    var [dragging,      setDragging]      = useState(null);
    var [grid,          setGrid]          = useState(true);
    var [gridSize,      setGridSize]      = useState(50);
    var [uploading,     setUploading]     = useState(false);
    var [selectedId,    setSelectedId]    = useState(null);
    var [salvando,      setSalvando]      = useState(false);
    var [salvouMsg,     setSalvouMsg]     = useState(null);

    var mapRef  = useRef();
    var fileRef = useRef();

    // Carrega mapa e tokens do servidor ao abrir
    useEffect(function() {
        if (!campId) return;
        api('/mestre/mapa/' + campId).then(function(r) {
            if (r?.mapa_url) setMapUrl(r.mapa_url);
        }).catch(function(){});
        api('/mestre/tokens/' + campId).then(function(r) {
            if (Array.isArray(r?.tokens) && r.tokens.length > 0) {
                setTokens(r.tokens);
            }
        }).catch(function(){});
    }, [campId]);

    // Inicializa tokens com posições padrão quando personagens carregam
    useEffect(function() {
        if (personagens.length === 0) return;
        setTokens(function(prev) {
            var prevMap = {};
            prev.forEach(function(t) { prevMap[t.id] = t; });
            return personagens.map(function(p, i) {
                if (prevMap[p.id]) {
                    // Atualiza HP mas mantém posição
                    return Object.assign({}, prevMap[p.id], {
                        hp_atual: p.hp_atual ?? prevMap[p.id].hp_atual,
                        hp_max:   p.hp_max   ?? prevMap[p.id].hp_max,
                        imagem:   p.imagem_url || p.imagem || prevMap[p.id].imagem,
                    });
                }
                var col = i % 5;
                var row = Math.floor(i / 5);
                return {
                    id:       p.id,
                    nome:     p.nome,
                    imagem:   p.imagem_url || p.imagem || null,
                    hp_atual: p.hp_atual ?? p.hp_max ?? 20,
                    hp_max:   p.hp_max   ?? 20,
                    x:        0.05 + col * 0.09,
                    y:        0.82 + row * 0.10,
                    cor:      CORES_TOKENS[i % CORES_TOKENS.length],
                    tipo:     'jogador',
                };
            });
        });
    }, [personagens]);

    // Drag handlers
    var onMouseDown = function(ev, tokenId) {
        ev.preventDefault();
        ev.stopPropagation();
        var rect  = mapRef.current.getBoundingClientRect();
        var token = tokens.find(function(t) { return t.id === tokenId; });
        setDragging({
            tokenId: tokenId,
            startX:  ev.clientX - token.x * rect.width,
            startY:  ev.clientY - token.y * rect.height,
        });
        setSelectedId(tokenId);
    };

    var onMouseMove = useCallback(function(ev) {
        if (!dragging || !mapRef.current) return;
        var rect = mapRef.current.getBoundingClientRect();
        var nx   = (ev.clientX - dragging.startX) / rect.width;
        var ny   = (ev.clientY - dragging.startY) / rect.height;
        nx = Math.max(0.01, Math.min(0.97, nx));
        ny = Math.max(0.01, Math.min(0.97, ny));
        if (grid && gridSize > 0) {
            var cellW = gridSize / rect.width;
            var cellH = gridSize / rect.height;
            nx = Math.round(nx / cellW) * cellW;
            ny = Math.round(ny / cellH) * cellH;
        }
        setTokens(function(prev) {
            return prev.map(function(t) { return t.id === dragging.tokenId ? Object.assign({}, t, { x: nx, y: ny }) : t; });
        });
    }, [dragging, grid, gridSize]);

    var onMouseUp = useCallback(function() { setDragging(null); }, []);

    // Upload do mapa
    var uploadMapa = function(ev) {
        var file = ev.target.files[0];
        if (!file || !campId) return;
        setUploading(true);
        var fd = new FormData();
        fd.append('mapa', file);
        fetch(C.apiUrl + '/mestre/mapa/' + campId, {
            method:      'POST',
            credentials: 'include',
            headers:     { 'X-WP-Nonce': C.nonce },
            body:        fd,
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.mapa_url) setMapUrl(data.mapa_url);
        }).catch(function(e) { console.error('Upload mapa:', e); }).finally(function() {
            setUploading(false);
        });
        ev.target.value = '';
    };

    // Salvar posições dos tokens
    var salvarTokens = function() {
        if (!campId) return;
        setSalvando(true);
        api('/mestre/tokens/' + campId, 'POST', { tokens: tokens }).then(function() {
            setSalvouMsg('✓ Salvo!');
            setTimeout(function() { setSalvouMsg(null); }, 2000);
        }).catch(function(){}).finally(function() { setSalvando(false); });
    };

    // Adicionar NPC genérico
    var adicionarNPC = function() {
        var nome = window.prompt('Nome do NPC / Monstro:');
        if (!nome) return;
        var npcId = 'npc_' + Date.now();
        var idx   = tokens.length;
        setTokens(function(prev) {
            return prev.concat([{
                id:       npcId,
                nome:     nome,
                imagem:   null,
                hp_atual: 20,
                hp_max:   20,
                x:        0.5 + (Math.random() * 0.1 - 0.05),
                y:        0.3 + (Math.random() * 0.1 - 0.05),
                cor:      '#e85454',
                tipo:     'npc',
            }]);
        });
    };

    var removerSelecionado = function() {
        if (!selectedId) return;
        setTokens(function(prev) { return prev.filter(function(t) { return t.id !== selectedId; }); });
        setSelectedId(null);
    };

    // HP rápido via painel lateral
    var editarHP = function(tokenId, delta) {
        setTokens(function(prev) {
            return prev.map(function(t) {
                if (t.id !== tokenId) return t;
                var novoHP = Math.max(0, Math.min(t.hp_max, (t.hp_atual || 0) + delta));
                if (t.tipo === 'jogador' && delta < 0) aplicarDano(tokenId, Math.abs(delta));
                if (t.tipo === 'jogador' && delta > 0) aplicarCura(tokenId, delta);
                return Object.assign({}, t, { hp_atual: novoHP });
            });
        });
    };

    // ── Render ──
    var gridStyle = grid
        ? {
            backgroundImage: 'linear-gradient(rgba(201,168,76,.12) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.12) 1px,transparent 1px)',
            backgroundSize:  gridSize + 'px ' + gridSize + 'px',
          }
        : {};

    var tokenSelecionado = tokens.find(function(t) { return t.id === selectedId; });

    return e('div', { className: 'mapa-wrap' },

        // ── Área do mapa ──
        e('div', { style: { flex: 1, display: 'flex', flexDirection: 'column', overflow: 'hidden' } },

            // Toolbar
            e('div', { className: 'mapa-toolbar' },
                e('span', { className: 'hud-col-titulo', style: { margin: 0, flex: 0 } }, '🗺 Mapa de Batalha'),
                e('label', { className: 'mapa-toggle', title: 'Mostrar/ocultar grade' },
                    e('input', { type: 'checkbox', checked: grid, onChange: function(ev) { setGrid(ev.target.checked); } }),
                    ' Grade'
                ),
                grid && e('input', {
                    type: 'number', min: 20, max: 120, step: 5, value: gridSize,
                    className: 'mapa-grid-input',
                    title: 'Tamanho da célula (px)',
                    onChange: function(ev) { setGridSize(parseInt(ev.target.value) || 50); },
                }),
                e('input', { type: 'file', ref: fileRef, style: { display: 'none' }, accept: 'image/*', onChange: uploadMapa }),
                e('button', {
                    className: 'btn btn-ghost', style: { fontSize: 11 },
                    onClick: function() { fileRef.current && fileRef.current.click(); },
                    disabled: uploading,
                }, uploading ? '⏳' : '🖼 Mapa'),
                e('button', { className: 'btn btn-ghost', style: { fontSize: 11 }, onClick: adicionarNPC }, '👹 +NPC'),
                selectedId && e('button', { className: 'btn btn-ghost', style: { fontSize: 11, color: 'var(--verm-b)' }, onClick: removerSelecionado }, '🗑'),
                e('button', {
                    className: 'btn btn-gold', style: { fontSize: 11, marginLeft: 'auto' },
                    onClick: salvarTokens, disabled: salvando,
                }, salvouMsg || (salvando ? '⏳' : '💾 Salvar')),
            ),

            // Canvas do mapa
            e('div', Object.assign({ ref: mapRef, className: 'mapa-area' }, gridStyle, {
                onMouseMove: onMouseMove,
                onMouseUp:   onMouseUp,
                onMouseLeave: onMouseUp,
            }),
                // Imagem de fundo
                mapUrl
                    ? e('img', { src: mapUrl, className: 'mapa-bg', draggable: false, alt: 'Mapa' })
                    : e('div', { className: 'mapa-vazio' },
                        e('div', { style: { fontSize: 56, opacity: 0.15 } }, '🗺'),
                        e('div', { style: { color: 'var(--t4)', fontSize: 13 } }, 'Nenhum mapa carregado'),
                        e('button', {
                            className: 'btn btn-gold', style: { marginTop: 12, fontSize: 12, pointerEvents: 'all' },
                            onClick: function() { fileRef.current && fileRef.current.click(); },
                        }, '🖼 Carregar Mapa')
                      ),

                // Tokens
                tokens.map(function(token) {
                    var hpPct = Math.max(0, Math.min(100, token.hp_max > 0 ? Math.round(token.hp_atual / token.hp_max * 100) : 0));
                    var hpCor = hpPct > 50 ? '#4caf7d' : hpPct > 25 ? '#f5a623' : '#e85454';
                    var isDrag = dragging?.tokenId === token.id;
                    var isSel  = selectedId === token.id;

                    return e('div', {
                        key:         token.id,
                        className:   'mapa-token' + (isDrag ? ' dragging' : '') + (isSel ? ' selected' : '') + (token.tipo === 'npc' ? ' npc' : ''),
                        style:       { left: (token.x * 100) + '%', top: (token.y * 100) + '%', zIndex: isDrag ? 100 : isSel ? 50 : 10 },
                        onMouseDown: function(ev) { onMouseDown(ev, token.id); },
                    },
                        e('div', { className: 'token-ring', style: { borderColor: token.cor } },
                            token.imagem
                                ? e('img', { src: token.imagem, className: 'token-img', draggable: false })
                                : e('div', { className: 'token-initial', style: { background: token.cor + '22', color: token.cor } }, token.nome[0])
                        ),
                        e('div', { className: 'token-hp-bar' },
                            e('div', { className: 'token-hp-fill', style: { width: hpPct + '%', background: hpCor } })
                        ),
                        e('div', { className: 'token-name' }, token.nome.split(' ')[0]),
                        e('div', { className: 'token-hp-text' }, token.hp_atual + '/' + token.hp_max),
                    );
                })
            ) // fim mapa-area
        ),

        // ── Painel lateral de tokens ──
        e('div', { className: 'mapa-controles' },
            e('div', { className: 'hud-col-titulo', style: { fontSize: 9, padding: '8px 12px 0' } }, '⚔ Tokens'),
            e('div', { className: 'mapa-controles-scroll' },
                tokens.length === 0
                    ? e('div', { style: { color: 'var(--t4)', fontSize: 12, fontStyle: 'italic', textAlign: 'center', marginTop: 16 } }, 'Sem tokens')
                    : tokens.map(function(token) {
                        var hpPct = token.hp_max > 0 ? Math.round(token.hp_atual / token.hp_max * 100) : 0;
                        var hpCor = hpPct > 50 ? '#4caf7d' : hpPct > 25 ? '#f5a623' : '#e85454';
                        return e('div', {
                            key:       token.id,
                            className: 'token-lista-item' + (selectedId === token.id ? ' ativo' : ''),
                            onClick:   function() { setSelectedId(token.id); },
                        },
                            e('div', { className: 'token-mini-anel', style: { borderColor: token.cor } },
                                token.imagem
                                    ? e('img', { src: token.imagem, className: 'token-mini-img' })
                                    : e('span', { style: { color: token.cor, fontSize: 14 } }, token.nome[0])
                            ),
                            e('div', { style: { flex: 1, minWidth: 0 } },
                                e('div', { className: 'token-lista-nome' }, token.nome),
                                e('div', { className: 'token-lista-hp', style: { color: hpCor } }, token.hp_atual + '/' + token.hp_max + ' HP'),
                            ),
                            // Botões +/- HP rápido
                            e('div', { style: { display: 'flex', flexDirection: 'column', gap: 2, flexShrink: 0 } },
                                e('button', {
                                    style: { background: 'rgba(76,175,125,.15)', border: '1px solid rgba(76,175,125,.3)', borderRadius: 3, color: '#4caf7d', fontSize: 10, cursor: 'pointer', padding: '1px 5px', lineHeight: '14px' },
                                    onClick: function(ev) { ev.stopPropagation(); editarHP(token.id, 1); },
                                    title: '+1 HP',
                                }, '+'),
                                e('button', {
                                    style: { background: 'rgba(232,84,84,.15)', border: '1px solid rgba(232,84,84,.3)', borderRadius: 3, color: '#e85454', fontSize: 10, cursor: 'pointer', padding: '1px 5px', lineHeight: '14px' },
                                    onClick: function(ev) { ev.stopPropagation(); editarHP(token.id, -1); },
                                    title: '-1 HP',
                                }, '−')
                            )
                        );
                    })
            )
        )
    );
}

function HUDMestre({ usuario, layout }) {
    const [carregando, setCarregando]       = useState(true);
    const [campanha, setCampanha]           = useState(null);
    const [modulo, setModulo]               = useState(null);
    const [moduloId, setModuloId]           = useState(null);
    const [chapters, setChapters]           = useState([]);
    const [capituloAtivo, setCapituloAtivo] = useState(null);
    const [capIndex, setCapIndex]           = useState(0);
    const [gerandoImg, setGerandoImg]       = useState(false);
    const [personagens, setPersonagens]     = useState([]);
    const [objectives, setObjectives]       = useState([]);
    const [logEntradas, setLogEntradas]     = useState([]);
    const [erro, setErro]                   = useState(null);
    const [tabDireita, setTabDireita]       = useState('log'); // log | checklist | npcs
    const [tabPrincipal, setTabPrincipal]   = useState('cena'); // cena | jogadores | npcs | checklist | ia
    const logRef      = useRef();
    const textareaRef = useRef();

    useEffect(() => {
        (async () => {
            try {
                let campId = usuario?.campanha_ativa;
                if (!campId) {
                    const ud = await api('/usuario');
                    campId = ud?.campanha_ativa;
                }
                if (!campId) { setErro('nenhuma_campanha'); setCarregando(false); return; }

                const painelData = await api('/mestre/painel/' + campId);
                if (!painelData || painelData?.code) { setErro('painel_falhou'); setCarregando(false); return; }

                const campanhaObj = painelData?.campanha || painelData;
                setCampanha(campanhaObj);
                if (Array.isArray(painelData?.personagens)) setPersonagens(painelData.personagens);

                const checklists = [
                    ...(painelData?.checklist_obrig  || []),
                    ...(painelData?.checklist_secund || []),
                ];
                // Checklist sempre começa desmarcada — o mestre marca conforme avança
                if (checklists.length > 0) setObjectives(checklists.map(c => ({ ...c, concluida: false })));

                const mId = campanhaObj?.modulo_id || painelData?.modulo_id;
                if (!mId) {
                    // Campanha sem módulo vinculado — HUD carrega vazio mas funcional
                    setCarregando(false);
                    addLog('sistema', '⚠ Nenhum módulo vinculado. Use a aba Módulos para vincular um.');
                    return;
                }
                setModuloId(mId);

                const moduloData = await api('/modulo/' + mId);
                setModulo(moduloData);

                const caps = (moduloData?.chapters || []).map((cap, i) => ({
                    id:           cap.id           ?? (i + 1),
                    title:        cap.title         || cap.titulo        || `Capítulo ${i + 1}`,
                    sub:          cap.sub           || cap.subtitulo     || '',
                    icon:         cap.icon          || cap.icone         || '📖',
                    content:      cap.content       || cap.conteudo      || '',
                    blocks:       cap.blocks        || [],
                    image_prompt: cap.image_prompt  || cap.prompt_imagem || '',
                    imagem_url:   cap.imagem_url    || '',
                }));
                setChapters(caps);

                if (caps.length > 0) { setCapituloAtivo(caps[0]); setCapIndex(0); }

                setLogEntradas([{
                    hora:  new Date().toLocaleTimeString('pt-BR', { hour:'2-digit', minute:'2-digit' }),
                    tipo:  'sistema',
                    texto: `⚔ "${campanhaObj?.nome || moduloData?.nome}" — ${caps.length} capítulos carregados.`,
                }]);

            } catch (err) {
                console.error('HUD erro:', err);
                setErro('excecao');
            }
            setCarregando(false);
        })();
    }, [usuario?.campanha_ativa]);

    // addLog DEVE ser definido antes de gerarImagem para evitar stale closure
    const addLog = (tipo, texto) => setLogEntradas(prev => [...prev, {
        hora: new Date().toLocaleTimeString('pt-BR', { hour:'2-digit', minute:'2-digit' }),
        tipo, texto
    }]);

    // Gerar imagem — automático (sem imagem) ou manual
    const gerarImagem = useCallback(async () => {
        if (!capituloAtivo || !moduloId || gerandoImg) return;
        // Usa image_prompt do JSON, ou constrói a partir do título
        const prompt = capituloAtivo.image_prompt
            || `D&D 5e fantasy scene: ${capituloAtivo.title}, ${modulo?.nome || 'dungeon'}, dramatic lighting, detailed illustration`;
        setGerandoImg(true);
        try {
            const res = await api('/mestre/gerar-imagem-capitulo', 'POST', {
                image_prompt: prompt,
                modulo_id:    moduloId,
                cap_index:    capIndex,
            });
            if (res.sucesso && res.imagem_url) {
                setCapituloAtivo(prev => ({ ...prev, imagem_url: res.imagem_url }));
                setChapters(prev => prev.map((c, i) => i === capIndex ? { ...c, imagem_url: res.imagem_url } : c));
                addLog('sistema', `🎨 Imagem gerada para "${capituloAtivo.title}".`);
            } else if (res.erro) {
                addLog('sistema', `⚠ Falha ao gerar imagem: ${res.erro}`);
            }
        } catch(err) { addLog('sistema', `⚠ Erro: ${err.message}`); }
        setGerandoImg(false);
    }, [capituloAtivo, moduloId, capIndex, gerandoImg, modulo]);

    // Auto-gerar imagem quando capítulo sem imagem é selecionado (só se tiver prompt)
    useEffect(() => {
        if (!capituloAtivo || capituloAtivo.imagem_url || !capituloAtivo.image_prompt || !moduloId) return;
        gerarImagem();
    }, [capituloAtivo?.id]);

    useEffect(() => {
        if (logRef.current) logRef.current.scrollTop = logRef.current.scrollHeight;
    }, [logEntradas]);

    const selecionarCapitulo = useCallback((cap, idx) => {
        setCapituloAtivo(cap); setCapIndex(idx);
        addLog('sistema', `📖 "${cap.title}" selecionado.`);
    }, []);

    const toggleObjective = useCallback(async (obj, idx) => {
        const novoEstado = !obj.concluida;
        // Atualiza UI imediatamente (optimistic update)
        setObjectives(prev => prev.map((o, i) => i === idx ? { ...o, concluida: novoEstado } : o));
        // Persiste no servidor enviando o estado desejado explicitamente
        if (obj.id && !String(obj.id).startsWith('cap')) {
            try {
                await api('/mestre/checklist/' + obj.id + '/concluir', 'POST', { concluida: novoEstado });
            } catch(e) {
                // Reverte se falhar
                setObjectives(prev => prev.map((o, i) => i === idx ? { ...o, concluida: obj.concluida } : o));
            }
        }
    }, []);

    const narrar = () => {
        const txt = textareaRef.current?.value?.trim();
        if (!txt) return;
        addLog('narracao', txt);
        textareaRef.current.value = '';
    };

    const aplicarDano = async (personagem_id, qtd) => {
        const res = await api('/mestre/dano', 'POST', { personagem_id, dano: qtd });
        if (res.sucesso !== false) {
            setPersonagens(prev => prev.map(p => p.id === personagem_id ? { ...p, hp_atual: res.hp_atual ?? Math.max(0, p.hp_atual - qtd) } : p));
            addLog('acao', `⚔ Personagem sofreu ${qtd} de dano.`);
        }
    };

    const aplicarCura = async (personagem_id, qtd) => {
        const res = await api('/mestre/cura', 'POST', { personagem_id, cura: qtd });
        if (res.sucesso !== false) {
            setPersonagens(prev => prev.map(p => p.id === personagem_id ? { ...p, hp_atual: res.hp_atual ?? Math.min(p.hp_max, p.hp_atual + qtd) } : p));
            addLog('acao', `💚 Personagem curado em ${qtd} HP.`);
        }
    };

    if (carregando) return e(Spinner, { texto: 'CARREGANDO CAMPANHA...' });

    if (erro === 'nenhuma_campanha') return e('div', { className: 'hud-empty-root' },
        e('div', { style: { fontSize: 48, marginBottom: 16, opacity: 0.35 } }, '📜'),
        e('div', { className: 'hud-empty-titulo' }, 'Nenhuma campanha ativa'),
        e('p', { className: 'hud-empty-desc' },
            'Vá na aba ', e('strong', { style: { color: 'var(--g2)' } }, 'Módulos'),
            ' e clique em ', e('strong', { style: { color: 'var(--g2)' } }, '⚔ Vincular'), '.'
        )
    );

    if (erro) return e('div', { className: 'hud-empty-root' },
        e('div', { style: { fontSize: 40, marginBottom: 12, opacity: 0.35 } }, '⚠'),
        e('div', { className: 'hud-empty-titulo' }, erro === 'painel_falhou' ? 'Falha ao carregar campanha' : 'Erro inesperado'),
        e('p', { className: 'hud-empty-desc' },
            erro === 'painel_falhou'
                ? 'O servidor não respondeu. Verifique se o plugin está ativo e tente novamente.'
                : 'Algo deu errado. Verifique o console (F12) para detalhes.'
        ),
        e('button', {
            className: 'btn btn-gold',
            style: { marginTop: 16 },
            onClick: function() { setErro(null); setCarregando(true); window.location.reload(); }
        }, '🔄 Tentar Novamente')
    );

    const conteudoParsed = capituloAtivo ? parseConteudo(capituloAtivo.content) : null;

    // Dados compartilhados para todos os layouts
    const hudState = {
        campanha, modulo, moduloId, chapters, capituloAtivo, capIndex, gerandoImg,
        personagens, objectives, logEntradas, tabDireita, tabPrincipal,
        logRef, textareaRef, conteudoParsed,
        selecionarCapitulo, toggleObjective, narrar,
        setTabDireita, setTabPrincipal,
        aplicarDano, aplicarCura, gerarImagem,
    };

    // Renderizar layout baseado na preferência
    switch(layout) {
        default:  return e(HUDLayoutE, hudState);
    }
}

// ════════════════════════════════════════════
// COMPONENTES REUTILIZÁVEIS DOS HUDS
// ════════════════════════════════════════════
function CapitulosSidebar({ modulo, chapters, capituloAtivo, selecionarCapitulo, compact }) {
    return e('div', { className: 'hud-sidebar-caps', style: compact ? { width: 170 } : {} },
        e('div', { className: 'hud-col-titulo', style: { fontSize: compact ? 8 : 10 } },
            '📖 ' + (modulo?.nome ? (modulo.nome.length > 20 && compact ? modulo.nome.substring(0,18)+'…' : modulo.nome) : 'Capítulos')
        ),
        e('div', { className: 'hud-sidebar-scroll' },
            chapters.length === 0
                ? e('div', { className: 'hud-empty-msg' }, 'Nenhum capítulo.')
                : chapters.map((cap, i) =>
                    e('button', {
                        key: cap.id || i,
                        className: 'cap-btn' + (capituloAtivo?.id === cap.id ? ' ativo' : ''),
                        onClick: () => selecionarCapitulo(cap, i),
                    },
                        e('span', { className: 'cap-num' }, String(i+1).padStart(2,'0')),
                        e('span', { className: 'cap-nome' }, cap.title)
                    )
                  )
        )
    );
}

function PersonagensPanel({ personagens, aplicarDano, aplicarCura, compact }) {
    const [danoInput, setDanoInput] = useState({});
    const hpPct = (p) => Math.max(0, Math.min(100, Math.round(((p.hp_atual ?? p.hp_max) / (p.hp_max || 1)) * 100)));
    const hpCor = (pct) => pct > 50 ? 'var(--verde-b)' : pct > 25 ? 'var(--ambar-b)' : 'var(--verm-b)';

    if (personagens.length === 0) return e('div', { style: { color: 'var(--t4)', fontSize: 13, fontStyle: 'italic', textAlign: 'center', padding: '24px 0' } }, 'Nenhum aventureiro na sessão.');

    return e('div', { style: { display: 'flex', flexDirection: 'column', gap: 10 } },
        personagens.map(p => {
            const pct = hpPct(p);
            const cor = hpCor(pct);
            const hp  = p.hp_atual ?? p.hp_max ?? 0;
            const qtd = parseInt(danoInput[p.id] || 1);
            return e('div', { key: p.id, className: 'jogador-card' },
                e('div', { className: 'jogador-card-header' },
                    p.imagem_url || p.imagem
                        ? e('img', { src: p.imagem_url || p.imagem, className: 'jogador-retrato' })
                        : e('div', { className: 'jogador-retrato-placeholder' }, '⚔'),
                    e('div', { style: { flex: 1, minWidth: 0 } },
                        e('div', { className: 'jogador-nome' }, p.nome),
                        e('div', { className: 'jogador-sub' }, (p.classe || '?') + (compact ? '' : ' · ' + (p.raca || '?')) + ' Nv' + (p.nivel || 1)),
                        e('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 3 } },
                            e('span', { style: { fontSize: 12, color: cor, fontFamily: "'Cinzel',serif", fontWeight: 700 } }, hp + '/' + (p.hp_max || '?')),
                            e('span', { style: { fontSize: 10, color: 'var(--t4)' } }, 'CA ' + (p.ca || '?'))
                        ),
                        e('div', { className: 'hp-bar' }, e('div', { className: 'hp-fill', style: { width: pct + '%', background: cor } }))
                    )
                ),
                e('div', { className: 'jogador-controls' },
                    e('input', {
                        type: 'number', min: 1, max: 999, value: danoInput[p.id] || 1,
                        onChange: ev => setDanoInput(d => ({...d, [p.id]: ev.target.value})),
                        className: 'hp-input',
                    }),
                    e('button', { className: 'btn-hp btn-dano', onClick: () => aplicarDano(p.id, qtd) }, '⚔ -HP'),
                    e('button', { className: 'btn-hp btn-cura', onClick: () => aplicarCura(p.id, qtd) }, '💚 +HP')
                )
            );
        })
    );
}

function LogPanel({ logEntradas, logRef, textareaRef, narrar }) {
    return e('div', { style: { display: 'flex', flexDirection: 'column', height: '100%' } },
        e('div', { className: 'hud-col-titulo' }, '📜 Log da Sessão'),
        e('div', { className: 'hud-log-scroll', ref: logRef, style: { flex: 1 } },
            logEntradas.length === 0
                ? e('div', { style: { color: 'var(--t3)', fontStyle: 'italic', fontSize: 13, textAlign: 'center', marginTop: 24 } }, 'A sessão ainda não começou...')
                : logEntradas.map((entrada, i) =>
                    e('div', { key: i, className: 'log-entry' },
                        e('div', { className: 'log-timestamp' }, entrada.hora),
                        e('div', { className: 'log-texto log-' + (entrada.tipo || 'acao') }, entrada.texto)
                    )
                  )
        ),
        e('div', { className: 'log-input-area' },
            e('textarea', {
                ref: textareaRef, className: 'log-textarea',
                placeholder: 'Narre um evento... (Enter para enviar)',
                onKeyDown: ev => { if (ev.key === 'Enter' && !ev.shiftKey) { ev.preventDefault(); narrar(); } },
            }),
            e('button', { className: 'btn btn-gold', style: { padding: '0 14px', flexShrink: 0, alignSelf: 'flex-end', height: 38 }, onClick: narrar }, '▶')
        )
    );
}

function CenaContent({ capituloAtivo, gerandoImg, conteudoParsed, onGerarImagem }) {
    return e('div', { style: { display: 'flex', flexDirection: 'column', height: '100%', overflow: 'hidden' } },
        e('div', { className: 'hud-cena-imagem-wrap', style: { maxHeight: '40%', flexShrink: 0, position: 'relative' } },
            gerandoImg
                ? e('div', { className: 'hud-cena-placeholder' }, e('div', { style: { fontSize: 28, animation: 'spin 1.4s linear infinite' } }, '⏳'), e('div', { className: 'hud-cena-placeholder-txt' }, 'Gerando imagem...'))
                : capituloAtivo?.imagem_url
                    ? e('img', { className: 'hud-cena-img', src: capituloAtivo.imagem_url, alt: capituloAtivo.title, key: capituloAtivo.id, style: { maxHeight: '100%', width: '100%', objectFit: 'cover' } })
                    : e('div', { className: 'hud-cena-placeholder' }, e('div', { className: 'hud-cena-placeholder-icon' }, '🏰'), e('div', { className: 'hud-cena-placeholder-txt' }, capituloAtivo ? 'Sem imagem' : 'Selecione um capítulo')),
            capituloAtivo && !gerandoImg && onGerarImagem && e('button', {
                onClick: onGerarImagem,
                title: capituloAtivo.imagem_url ? 'Gerar nova imagem' : 'Gerar imagem com IA',
                style: {
                    position: 'absolute', bottom: 8, right: 8, zIndex: 10,
                    background: 'rgba(10,8,4,.85)', border: '1px solid var(--g5)',
                    borderRadius: 6, padding: '4px 10px', color: 'var(--g3)',
                    fontSize: 12, cursor: 'pointer', fontFamily: "'Cinzel',serif", letterSpacing: 1,
                },
            }, capituloAtivo.imagem_url ? '🔄 Nova Imagem' : '🎨 Gerar Imagem'),
            e('div', { className: 'hud-cena-moldura' }),
            e('div', { className: 'hud-cena-grad' })
        ),
        e('div', { className: 'hud-cena-info', style: { flex: 1, overflow: 'auto', padding: '12px 16px' } },
            capituloAtivo
                ? e('div', null,
                    e('div', { className: 'hud-cena-titulo' }, capituloAtivo.title),
                    conteudoParsed?.readAloud.length > 0 && e('div', { className: 'cap-read-aloud-wrap' },
                        e('div', { className: 'cap-bloco-label' }, '🔊 Leia em Voz Alta'),
                        ...conteudoParsed.readAloud.map((p, i) => e('p', { key: i, className: 'cap-read-aloud-txt' }, p))
                    ),
                    conteudoParsed?.dmNotes.length > 0 && e('div', { className: 'cap-dm-note-wrap' },
                        e('div', { className: 'cap-bloco-label' }, '📋 Nota do Mestre'),
                        ...conteudoParsed.dmNotes.map((n, i) => e('p', { key: i, className: 'cap-dm-note-txt' }, n))
                    )
                  )
                : e('div', null, e('div', { className: 'hud-cena-titulo' }, 'Aguardando...'), e('div', { className: 'hud-cena-descricao' }, 'Clique em um capítulo.'))
        )
    );
}

function ChecklistPanel({ objectives, toggleObjective }) {
    if (objectives.length === 0) return e('div', { style: { color: 'var(--t4)', fontSize: 13, fontStyle: 'italic', textAlign: 'center', padding: '24px 0' } }, 'Sem objetivos carregados.');
    return e('div', { className: 'hud-checklist-scroll' },
        objectives.map((obj, idx) =>
            e('label', { key: obj.id || idx, className: 'checklist-item' + (obj.concluida ? ' concluido' : '') },
                e('input', { type: 'checkbox', checked: !!obj.concluida, onChange: () => toggleObjective(obj, idx), style: { display: 'none' } }),
                e('span', { className: 'checklist-box' }, obj.concluida ? '✓' : ''),
                e('span', { className: 'checklist-txt' }, obj.titulo || obj.title || '')
            )
        )
    );
}

// ── Layout E: Sub-componentes ──────────────────────────────────────────────────

function SidebarCenasE({ chapters, capAtivo, selecionar, concluidas, toggleConcluida }) {
    if (chapters.length === 0) return e('div', { style:{ color:'#6a5a3a', fontSize:12, padding:16, textAlign:'center', fontStyle:'italic' } }, 'Nenhuma cena.');
    return e('div', { style:{ display:'flex', flexDirection:'column', gap:2, padding:6 } },
        chapters.map(function(cap, i) {
            var ativo = capAtivo && (capAtivo.id === cap.id);
            var done  = concluidas.has(i);
            return e('button', {
                key: cap.id || i,
                onClick: function() { selecionar(cap, i); },
                style:{
                    display:'flex', alignItems:'center', gap:8, width:'100%',
                    background: ativo ? 'rgba(200,164,90,0.15)' : 'none',
                    border:'none', borderRadius:6,
                    borderLeft: ativo ? '2px solid #c8a45a' : '2px solid transparent',
                    padding:'8px 10px', cursor:'pointer', textAlign:'left',
                    color: done ? '#4a8a4a' : (ativo ? '#c8a45a' : '#9a8a6a'),
                    fontSize:12, fontFamily:"'Cinzel',serif", transition:'background .15s',
                }
            },
                e('span', { style:{ fontSize:14, minWidth:20, textAlign:'center' } }, cap.icon || '📖'),
                e('div', { style:{ flex:1, minWidth:0 } },
                    e('div', { style:{ fontSize:11, letterSpacing:1, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' } }, cap.title),
                    cap.sub && e('div', { style:{ fontSize:9, color:'#5a4a2a', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', marginTop:2 } }, cap.sub)
                ),
                done && e('span', { style:{ color:'#4a8a4a', fontSize:11, flexShrink:0 } }, '✓')
            );
        })
    );
}

function ChecklistHUDE({ objectives, toggleObjective }) {
    if (!objectives || objectives.length === 0)
        return e('div', { style:{ color:'#6a5a3a', fontSize:12, padding:16, textAlign:'center', fontStyle:'italic' } }, 'Sem objetivos.');
    return e('div', { style:{ display:'flex', flexDirection:'column', gap:4, padding:8 } },
        objectives.map(function(obj, idx) {
            var done = !!obj.concluida;
            return e('div', {
                key: obj.id || idx,
                onClick: function() { toggleObjective(obj, idx); },
                style:{ display:'flex', alignItems:'flex-start', gap:8, padding:'6px 8px', borderRadius:6, cursor:'pointer', background: done ? 'rgba(74,138,74,0.1)' : 'none' }
            },
                e('div', { style:{ width:16, height:16, borderRadius:3, border:'1px solid '+(done?'#4a8a4a':'#5a4828'), background: done?'#4a8a4a':'transparent', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0, marginTop:1 } },
                    done && e('span', { style:{ color:'#fff', fontSize:9, lineHeight:1 } }, '✓')
                ),
                e('div', { style:{ fontSize:12, color: done?'#4a8a4a':'#9a8a6a', textDecoration: done?'line-through':'none', lineHeight:1.4 } }, obj.titulo || obj.title || '')
            );
        })
    );
}

function CenaHUDE({ cap, gerandoImg, uploadandoImg, onGerarImagem, onUploadImagem, npcs, monsters, mapas, onEnviarImagem }) {
    var fileRef    = useRef();
    var parsed     = cap ? parseConteudo(cap.content) : null;
    var [modalImg, setModalImg] = useState(null); // { url, label }
    var [enviado,  setEnviado]  = useState(false);

    var abrirModal = function(url, label) { setModalImg({ url: url, label: label }); setEnviado(false); };
    var fecharModal = function() { setModalImg(null); };
    var enviarParaJogadores = function() {
        if (modalImg && onEnviarImagem) {
            onEnviarImagem(modalImg.url);
            setEnviado(true);
        }
    };

    // Monta galeria de imagens de apoio da cena atual
    var galeriaImgs = [];
    if (cap && cap.imagem_url) galeriaImgs.push({ url: cap.imagem_url, label: cap.title || 'Cenário' });
    (npcs || []).forEach(function(n) { if (n.imagem_url) galeriaImgs.push({ url: n.imagem_url, label: n.nome }); });
    (monsters || []).forEach(function(m) { if (m.imagem_url) galeriaImgs.push({ url: m.imagem_url, label: m.name }); });
    (mapas || []).forEach(function(m) { if (m.url) galeriaImgs.push({ url: m.url, label: m.nome || 'Mapa' }); });

    return e('div', { style:{ display:'flex', flexDirection:'column', height:'100%', overflow:'hidden', position:'relative' } },

        // ── Modal de imagem ────────────────────────────────────────────────
        modalImg && e('div', {
            onClick: fecharModal,
            style:{ position:'absolute', inset:0, background:'rgba(0,0,0,0.88)', zIndex:100, display:'flex', alignItems:'center', justifyContent:'center', flexDirection:'column', gap:14, padding:20 }
        },
            e('img', { src:modalImg.url, style:{ maxWidth:'100%', maxHeight:'65vh', objectFit:'contain', borderRadius:8, boxShadow:'0 0 40px rgba(0,0,0,0.8)' }, onClick:function(ev){ev.stopPropagation();} }),
            e('div', { style:{ color:'#c8a45a', fontFamily:"'Cinzel',serif", fontSize:13, letterSpacing:1 } }, modalImg.label),
            e('div', { style:{ display:'flex', gap:10 }, onClick:function(ev){ev.stopPropagation();} },
                e('button', {
                    onClick: enviarParaJogadores,
                    style:{ background: enviado ? 'rgba(74,138,74,0.3)' : 'linear-gradient(135deg,#6b4f10,#c9a84c)', border: enviado ? '1px solid #4a8a4a' : 'none', borderRadius:8, color: enviado ? '#4ade80' : '#0a0704', fontFamily:"'Cinzel',serif", fontSize:12, fontWeight:700, padding:'10px 22px', cursor:'pointer', transition:'all .3s' }
                }, enviado ? '✓ Enviado!' : '📺 Enviar para Jogadores'),
                e('button', { onClick:fecharModal, style:{ background:'rgba(255,255,255,0.05)', border:'1px solid #3a3020', borderRadius:8, color:'#9a8a6a', fontSize:12, padding:'10px 18px', cursor:'pointer' } }, 'Fechar')
            )
        ),

        // ── Imagem da cena ─────────────────────────────────────────────────
        e('div', { style:{ position:'relative', height:220, flexShrink:0, background:'#0a0804', overflow:'hidden' } },
            (gerandoImg || uploadandoImg)
                ? e('div', { style:{ position:'absolute', inset:0, display:'flex', alignItems:'center', justifyContent:'center', flexDirection:'column', gap:8, color:'#c8a45a' } },
                    e('div', { style:{ fontSize:28, animation:'spin 1.4s linear infinite' } }, '⏳'),
                    e('div', { style:{ fontSize:11, fontFamily:"'Cinzel',serif", letterSpacing:2 } }, gerandoImg ? 'Gerando…' : 'Enviando…')
                  )
                : cap && cap.imagem_url
                    ? e('img', { src:cap.imagem_url, key:cap.id, style:{ width:'100%', height:'100%', objectFit:'cover', cursor:'pointer' }, onClick:function(){ abrirModal(cap.imagem_url, cap.title||'Cenário'); } })
                    : e('div', { style:{ position:'absolute', inset:0, display:'flex', alignItems:'center', justifyContent:'center', flexDirection:'column', gap:8, color:'#3a3020' } },
                        e('div', { style:{ fontSize:36 } }, cap ? '🏰' : '📖'),
                        e('div', { style:{ fontSize:11, fontFamily:"'Cinzel',serif", letterSpacing:2 } }, cap ? 'Sem imagem' : 'Selecione uma cena')
                      ),
            e('div', { style:{ position:'absolute', bottom:0, left:0, right:0, height:60, background:'linear-gradient(transparent,#0f0d09)', pointerEvents:'none' } }),
            cap && !gerandoImg && !uploadandoImg && e('div', { style:{ position:'absolute', bottom:8, right:8, display:'flex', gap:6 } },
                e('button', { onClick:onGerarImagem, style:{ background:'rgba(10,8,4,.85)', border:'1px solid #5a4828', borderRadius:5, color:'#c8a45a', fontSize:11, padding:'4px 10px', cursor:'pointer', fontFamily:"'Cinzel',serif" } }, cap.imagem_url ? '🔄 Nova' : '🎨 IA'),
                e('button', { onClick:function(){ fileRef.current && fileRef.current.click(); }, style:{ background:'rgba(10,8,4,.85)', border:'1px solid #5a4828', borderRadius:5, color:'#9a8a6a', fontSize:11, padding:'4px 10px', cursor:'pointer', fontFamily:"'Cinzel',serif" } }, '📁 Upload'),
                e('input', { ref:fileRef, type:'file', accept:'image/*', style:{ display:'none' }, onChange:function(ev){ var f=ev.target.files[0]; if(f) onUploadImagem(f); ev.target.value=''; } })
            )
        ),

        // ── Conteúdo da cena ───────────────────────────────────────────────
        e('div', { style:{ flex:1, overflowY:'auto', padding:'12px 16px', display:'flex', flexDirection:'column', gap:12 } },
            cap ? e('div', null,
                e('div', { style:{ fontFamily:"'Cinzel',serif", color:'#c8a45a', fontSize:16, fontWeight:700, marginBottom:cap.sub?4:12 } }, cap.title),
                cap.sub && e('div', { style:{ fontSize:12, color:'#6a5a3a', marginBottom:12 } }, cap.sub),
                cap.blocks && cap.blocks.length > 0 && cap.blocks.map(function(bloco, i) {
                    if (bloco.type === 'ra') return e('div', { key:i, style:{ background:'#1c1810', border:'1px solid #5a4828', borderLeft:'3px solid #c8a45a', borderRadius:'0 6px 6px 0', padding:'10px 14px', fontSize:13, color:'#e8d5a3', lineHeight:1.7, fontStyle:'italic' } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#c8a45a', marginBottom:6, fontStyle:'normal' } }, '🔊 LEIA EM VOZ ALTA'), bloco.text);
                    if (bloco.type === 'dm') return e('div', { key:i, style:{ background:'rgba(90,72,40,0.15)', border:'1px solid #3a3020', borderRadius:6, padding:'8px 12px', fontSize:12, color:'#b0a080', lineHeight:1.6 } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#6a5a3a', marginBottom:4 } }, '📋 MESTRE'), bloco.text);
                    if (bloco.type === 'warn') return e('div', { key:i, style:{ background:'rgba(200,60,60,0.1)', border:'1px solid rgba(200,60,60,0.3)', borderRadius:6, padding:'8px 12px', fontSize:12, color:'#e08080', lineHeight:1.6 } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#c06060', marginBottom:4 } }, '⚠ ATENÇÃO'), bloco.text);
                    if (bloco.type === 'rule') return e('div', { key:i, style:{ background:'rgba(60,100,160,0.1)', border:'1px solid rgba(60,100,160,0.3)', borderRadius:6, padding:'8px 12px', fontSize:12, color:'#a0c0e0', lineHeight:1.6, whiteSpace:'pre-wrap' } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#6090c0', marginBottom:4 } }, '📐 REGRA'), bloco.text);
                    if (bloco.type === 'section') return e('div', { key:i, style:{ fontFamily:"'Cinzel',serif", fontSize:10, letterSpacing:3, color:'#5a4828', borderBottom:'1px solid #2a2010', paddingBottom:4, marginTop:8 } }, bloco.text);
                    if (bloco.type === 'enemy') return e('div', { key:i, style:{ background:'rgba(139,26,26,0.12)', border:'1px solid rgba(139,26,26,0.4)', borderRadius:6, padding:'8px 12px' } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#c06060', marginBottom:4 } }, '⚔ INIMIGO'),
                        e('div', { style:{ fontFamily:"'Cinzel',serif", color:'#e8a080', fontSize:13, fontWeight:700, marginBottom:4 } }, bloco.name),
                        e('div', { style:{ fontSize:11, color:'#c09070', whiteSpace:'pre-wrap', lineHeight:1.6 } }, bloco.stats));
                    if (bloco.type === 'dcs') return e('div', { key:i, style:{ display:'flex', flexWrap:'wrap', gap:6 } },
                        (bloco.dcs||[]).map(function(dc, j) {
                            return e('div', { key:j, style:{ background:'rgba(60,160,60,0.1)', border:'1px solid rgba(60,160,60,0.3)', borderRadius:20, padding:'3px 10px', fontSize:11, color:'#80c080' } },
                                e('strong', null, 'CD '+dc.n), ' '+dc.label);
                        }));
                    return null;
                }),
                (!cap.blocks || cap.blocks.length === 0) && parsed && e('div', null,
                    parsed.readAloud.length > 0 && e('div', { style:{ background:'#1c1810', border:'1px solid #5a4828', borderLeft:'3px solid #c8a45a', borderRadius:'0 6px 6px 0', padding:'10px 14px', marginBottom:10 } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#c8a45a', marginBottom:6 } }, '🔊 LEIA EM VOZ ALTA'),
                        parsed.readAloud.map(function(t,i){ return e('p', { key:i, style:{ color:'#e8d5a3', fontSize:13, lineHeight:1.7, fontStyle:'italic', margin:0 } }, t); })
                    ),
                    parsed.dmNotes.length > 0 && e('div', { style:{ background:'rgba(90,72,40,0.15)', border:'1px solid #3a3020', borderRadius:6, padding:'8px 12px' } },
                        e('div', { style:{ fontSize:9, letterSpacing:3, color:'#6a5a3a', marginBottom:6 } }, '📋 NOTAS DO MESTRE'),
                        parsed.dmNotes.map(function(t,i){ return e('p', { key:i, style:{ color:'#b0a080', fontSize:12, lineHeight:1.6, margin:'0 0 6px' } }, t); })
                    )
                ),

                // ── Galeria de imagens de apoio ────────────────────────────
                galeriaImgs.length > 0 && e('div', { style:{ marginTop:12 } },
                    e('div', { style:{ fontSize:9, letterSpacing:3, color:'#4a3a2a', marginBottom:8, fontFamily:"'Cinzel',serif" } }, '🖼 IMAGENS DE APOIO — clique para enviar aos jogadores'),
                    e('div', { style:{ display:'grid', gridTemplateColumns:'repeat(auto-fill,minmax(90px,1fr))', gap:8 } },
                        galeriaImgs.map(function(img, i) {
                            return e('div', { key:i, onClick:function(){ abrirModal(img.url, img.label); }, style:{ cursor:'pointer', borderRadius:6, overflow:'hidden', border:'1px solid #3a3020', position:'relative', aspectRatio:'1', transition:'border-color .2s' },
                                onMouseEnter:function(ev){ ev.currentTarget.style.borderColor='#c8a45a'; },
                                onMouseLeave:function(ev){ ev.currentTarget.style.borderColor='#3a3020'; }
                            },
                                e('img', { src:img.url, style:{ width:'100%', height:'100%', objectFit:'cover' } }),
                                e('div', { style:{ position:'absolute', bottom:0, left:0, right:0, background:'linear-gradient(transparent,rgba(0,0,0,0.85))', padding:'6px 4px 3px', fontSize:9, color:'#c8a45a', fontFamily:"'Cinzel',serif", textAlign:'center', letterSpacing:0.5, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' } }, img.label),
                                e('div', { style:{ position:'absolute', top:3, right:3, background:'rgba(0,0,0,0.7)', borderRadius:3, padding:'1px 4px', fontSize:9, color:'#c8a45a' } }, '📺')
                            );
                        })
                    )
                )
            ) : e('div', { style:{ textAlign:'center', paddingTop:48, color:'#3a3020' } },
                e('div', { style:{ fontSize:32, marginBottom:8 } }, '📖'),
                e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:12, letterSpacing:2 } }, 'Selecione uma cena')
            )
        )
    );
}

function PainelDireitoE({ npcs, monsters, loot, onEnviarImagem }) {
    var [tab, setTab]       = useState('log');
    var [log, setLog]       = useState([]);
    var [modal, setModal]   = useState(null); // { tipo: 'npc'|'monster', data: {} }
    var [imgModal, setImgModal] = useState(null); // { url, label }
    var [enviado, setEnviado]   = useState(false);

    var rolar = function(faces) {
        var r = Math.ceil(Math.random() * faces);
        setLog(function(prev) {
            var hora = new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
            return [{ hora:hora, texto:'d'+faces+' → '+r, valor:r, dado:faces }].concat(prev.slice(0,19));
        });
    };

    var abrirImgModal = function(url, label, ev) { if(ev) ev.stopPropagation(); setImgModal({ url, label }); setEnviado(false); };
    var enviarImg = function() { if(imgModal && onEnviarImagem){ onEnviarImagem(imgModal.url); setEnviado(true); } };

    var DADOS = [4,6,8,10,12,20,100];
    var tabStyle = function(id) { return { flex:1, padding:'6px 0', background:'none', border:'none', borderBottom:'2px solid '+(tab===id?'#c8a45a':'transparent'), color:tab===id?'#c8a45a':'#5a4828', fontFamily:"'Cinzel',serif", fontSize:9, letterSpacing:1, cursor:'pointer' }; };
    var lastRoll = log[0];

    return e('div', { style:{ background:'#1c1810', borderLeft:'1px solid #3a3020', display:'flex', flexDirection:'column', overflow:'hidden', position:'relative' } },

        // ── Modal de detalhes NPC/Monstro ──────────────────────────────────
        modal && e('div', {
            onClick:function(){ setModal(null); },
            style:{ position:'absolute', inset:0, background:'rgba(0,0,0,0.92)', zIndex:50, overflowY:'auto', padding:16 }
        },
            e('div', { onClick:function(ev){ev.stopPropagation();}, style:{ display:'flex', flexDirection:'column', gap:12 } },
                // Cabeçalho com imagem
                e('div', { style:{ display:'flex', gap:12, alignItems:'flex-start' } },
                    modal.data.imagem_url || modal.data.image_url
                        ? e('div', { style:{ position:'relative', flexShrink:0 } },
                            e('img', { src:modal.data.imagem_url||modal.data.image_url, style:{ width:72, height:72, borderRadius:8, objectFit:'cover', cursor:'pointer', border:'1px solid #5a4828' }, onClick:function(){ abrirImgModal(modal.data.imagem_url||modal.data.image_url, modal.data.nome||modal.data.name); } }),
                            e('div', { style:{ position:'absolute', bottom:-2, right:-2, background:'#1c1810', border:'1px solid #5a4828', borderRadius:4, padding:'1px 4px', fontSize:9, color:'#c8a45a', cursor:'pointer' }, onClick:function(){ abrirImgModal(modal.data.imagem_url||modal.data.image_url, modal.data.nome||modal.data.name); } }, '📺')
                          )
                        : e('div', { style:{ width:72, height:72, borderRadius:8, background:'#2a1e0a', display:'flex', alignItems:'center', justifyContent:'center', fontSize:28, flexShrink:0 } }, modal.tipo==='npc'?'🧙':'💀'),
                    e('div', { style:{ flex:1 } },
                        e('div', { style:{ fontFamily:"'Cinzel',serif", color:'#c8a45a', fontSize:14, fontWeight:700 } }, modal.data.nome||modal.data.name),
                        modal.tipo === 'npc' && e('div', { style:{ fontSize:11, color:'#6a5a3a', marginTop:3 } }, (modal.data.raca||'') + (modal.data.papel?' · '+modal.data.papel:''))  ,
                        modal.tipo === 'monster' && e('div', { style:{ fontSize:11, color:'#7a5a4a', marginTop:3 } }, modal.data.type||''),
                        e('button', { onClick:function(){ setModal(null); }, style:{ background:'none', border:'1px solid #3a3020', borderRadius:4, color:'#6a5a3a', fontSize:10, padding:'3px 10px', cursor:'pointer', marginTop:8 } }, '✕ Fechar')
                    )
                ),
                // Detalhes NPC
                modal.tipo === 'npc' && e('div', { style:{ display:'flex', flexDirection:'column', gap:8 } },
                    modal.data.personalidade && e('div', { style:{ background:'rgba(90,72,40,0.15)', borderRadius:6, padding:'8px 10px' } },
                        e('div', { style:{ fontSize:9, letterSpacing:2, color:'#6a5a3a', marginBottom:4 } }, 'PERSONALIDADE'),
                        e('div', { style:{ fontSize:12, color:'#b0a080', lineHeight:1.6 } }, modal.data.personalidade)
                    ),
                    modal.data.aparencia && e('div', { style:{ background:'rgba(90,72,40,0.1)', borderRadius:6, padding:'8px 10px' } },
                        e('div', { style:{ fontSize:9, letterSpacing:2, color:'#6a5a3a', marginBottom:4 } }, 'APARÊNCIA'),
                        e('div', { style:{ fontSize:12, color:'#9a8a6a', lineHeight:1.6 } }, modal.data.aparencia)
                    ),
                    modal.data.objetivo && e('div', { style:{ background:'rgba(60,100,160,0.1)', borderRadius:6, padding:'8px 10px' } },
                        e('div', { style:{ fontSize:9, letterSpacing:2, color:'#6090c0', marginBottom:4 } }, 'OBJETIVO'),
                        e('div', { style:{ fontSize:12, color:'#a0c0e0', lineHeight:1.6 } }, modal.data.objetivo)
                    ),
                    modal.data.segredo && e('div', { style:{ background:'rgba(200,60,60,0.1)', border:'1px solid rgba(200,60,60,0.25)', borderRadius:6, padding:'8px 10px' } },
                        e('div', { style:{ fontSize:9, letterSpacing:2, color:'#c06060', marginBottom:4 } }, '🔒 SEGREDO'),
                        e('div', { style:{ fontSize:12, color:'#e08080', lineHeight:1.6 } }, modal.data.segredo)
                    )
                ),
                // Detalhes Monstro
                modal.tipo === 'monster' && e('div', { style:{ display:'flex', flexDirection:'column', gap:8 } },
                    modal.data.stats && e('div', { style:{ display:'flex', flexWrap:'wrap', gap:6 } },
                        (modal.data.stats||[]).map(function(s,i){ return e('div', { key:i, style:{ background:'rgba(200,100,60,0.15)', borderRadius:6, padding:'4px 10px', fontSize:11, color:'#e0a080' } }, s.l+': '+s.v); })
                    ),
                    modal.data.actions && modal.data.actions.length > 0 && e('div', null,
                        e('div', { style:{ fontSize:9, letterSpacing:2, color:'#c06060', marginBottom:6 } }, 'AÇÕES'),
                        modal.data.actions.map(function(a,i) {
                            return e('div', { key:i, style:{ background:'rgba(0,0,0,0.3)', borderRadius:6, padding:'6px 10px', marginBottom:6, fontSize:12 } },
                                e('strong', { style:{ color:'#e0a080' } }, a.name+': '),
                                e('span', { style:{ color:'#9a7a6a', lineHeight:1.6 } }, a.desc)
                            );
                        })
                    ),
                    modal.data.traits && modal.data.traits.length > 0 && e('div', null,
                        e('div', { style:{ fontSize:9, letterSpacing:2, color:'#c06060', marginBottom:6 } }, 'TRAÇOS'),
                        modal.data.traits.map(function(t,i) {
                            return e('div', { key:i, style:{ background:'rgba(139,26,26,0.1)', borderRadius:6, padding:'6px 10px', marginBottom:6, fontSize:12 } },
                                e('strong', { style:{ color:'#e0a080' } }, t.name+': '),
                                e('span', { style:{ color:'#9a7a6a', lineHeight:1.6 } }, t.desc)
                            );
                        })
                    )
                )
            )
        ),

        // ── Modal de imagem zoom/enviar ─────────────────────────────────────
        imgModal && e('div', {
            onClick:function(){ setImgModal(null); },
            style:{ position:'absolute', inset:0, background:'rgba(0,0,0,0.95)', zIndex:60, display:'flex', alignItems:'center', justifyContent:'center', flexDirection:'column', gap:12, padding:16 }
        },
            e('img', { src:imgModal.url, style:{ maxWidth:'100%', maxHeight:'60vh', objectFit:'contain', borderRadius:8, boxShadow:'0 0 40px rgba(0,0,0,0.8)' }, onClick:function(ev){ev.stopPropagation();} }),
            e('div', { style:{ color:'#c8a45a', fontFamily:"'Cinzel',serif", fontSize:12 } }, imgModal.label),
            e('div', { style:{ display:'flex', gap:8 }, onClick:function(ev){ev.stopPropagation();} },
                e('button', { onClick:enviarImg, style:{ background: enviado?'rgba(74,138,74,0.3)':'linear-gradient(135deg,#6b4f10,#c9a84c)', border: enviado?'1px solid #4a8a4a':'none', borderRadius:7, color:enviado?'#4ade80':'#0a0704', fontFamily:"'Cinzel',serif", fontSize:11, fontWeight:700, padding:'8px 18px', cursor:'pointer' } }, enviado?'✓ Enviado!':'📺 Enviar para Jogadores'),
                e('button', { onClick:function(){ setImgModal(null); }, style:{ background:'none', border:'1px solid #3a3020', borderRadius:7, color:'#6a5a3a', fontSize:11, padding:'8px 14px', cursor:'pointer' } }, 'Fechar')
            )
        ),

        // ── Tabs ────────────────────────────────────────────────────────────
        e('div', { style:{ display:'flex', borderBottom:'1px solid #3a3020', flexShrink:0 } },
            e('button', { onClick:function(){setTab('dados');}, style:tabStyle('dados') }, '🎲'),
            e('button', { onClick:function(){setTab('npcs');},  style:tabStyle('npcs')  }, 'NPCs'),
            e('button', { onClick:function(){setTab('monstros');}, style:tabStyle('monstros') }, '⚔'),
            e('button', { onClick:function(){setTab('loot');},  style:tabStyle('loot')  }, '💰')
        ),

        // ── Conteúdo ─────────────────────────────────────────────────────────
        e('div', { style:{ flex:1, overflowY:'auto', padding:10 } },

            // DADOS
            tab === 'dados' && e('div', null,
                lastRoll && e('div', { style:{ textAlign:'center', padding:'12px 0' } },
                    e('div', { style:{ fontFamily:"'Cinzel Decorative',serif", fontSize:36, color: lastRoll.valor===lastRoll.dado?'#c8a45a': lastRoll.valor===1?'#e85454':'#e8d5a3', textShadow:'0 0 20px currentColor' } }, lastRoll.valor),
                    e('div', { style:{ fontSize:10, color:'#5a4828', fontFamily:"'Cinzel',serif", letterSpacing:2 } }, 'd'+lastRoll.dado)
                ),
                e('div', { style:{ display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:6, marginTop:8 } },
                    DADOS.map(function(f) {
                        return e('button', { key:f, onClick:function(){rolar(f);}, style:{ background:'rgba(90,72,40,0.3)', border:'1px solid #3a3020', borderRadius:6, padding:'8px 4px', color:'#c8a45a', fontFamily:"'Cinzel',serif", fontSize:11, cursor:'pointer' } }, 'd'+f);
                    })
                ),
                log.length > 0 && e('div', { style:{ marginTop:12, borderTop:'1px solid #2a2010', paddingTop:8 } },
                    e('div', { style:{ fontSize:9, color:'#4a3a2a', letterSpacing:2, marginBottom:6 } }, 'HISTÓRICO'),
                    log.map(function(l,i){ return e('div', { key:i, style:{ fontSize:11, color:'#7a6a4a', padding:'2px 0' } }, e('span', { style:{ color:'#4a3a2a', marginRight:6 } }, l.hora), l.texto); })
                )
            ),

            // NPCs — clicável para detalhes, foto clicável para envio
            tab === 'npcs' && e('div', { style:{ display:'flex', flexDirection:'column', gap:8 } },
                npcs.length === 0 && e('div', { style:{ color:'#4a3a2a', fontSize:12, textAlign:'center', padding:16, fontStyle:'italic' } }, 'Nenhum NPC neste módulo.'),
                npcs.map(function(npc, i) {
                    return e('div', { key:i, onClick:function(){ setModal({ tipo:'npc', data:npc }); }, style:{ background:'rgba(90,72,40,0.15)', border:'1px solid #3a3020', borderRadius:8, padding:'10px 12px', cursor:'pointer', transition:'border-color .2s' },
                        onMouseEnter:function(ev){ ev.currentTarget.style.borderColor='#c8a45a'; },
                        onMouseLeave:function(ev){ ev.currentTarget.style.borderColor='#3a3020'; }
                    },
                        e('div', { style:{ display:'flex', gap:8, alignItems:'flex-start' } },
                            npc.imagem_url
                                ? e('div', { style:{ position:'relative', flexShrink:0 } },
                                    e('img', { src:npc.imagem_url, style:{ width:40, height:40, borderRadius:6, objectFit:'cover', cursor:'pointer', display:'block' }, onClick:function(ev){ abrirImgModal(npc.imagem_url, npc.nome, ev); } }),
                                    e('div', { style:{ position:'absolute', bottom:-2, right:-2, background:'#1c1810', border:'1px solid #5a4828', borderRadius:3, padding:'1px 3px', fontSize:8, color:'#c8a45a', cursor:'pointer' }, onClick:function(ev){ abrirImgModal(npc.imagem_url, npc.nome, ev); } }, '📺')
                                  )
                                : e('div', { style:{ width:40, height:40, borderRadius:6, background:'#2a1e0a', display:'flex', alignItems:'center', justifyContent:'center', fontSize:18, flexShrink:0 } }, '🧙'),
                            e('div', { style:{ flex:1, minWidth:0 } },
                                e('div', { style:{ fontFamily:"'Cinzel',serif", color:'#c8a45a', fontSize:12, fontWeight:700 } }, npc.nome),
                                e('div', { style:{ fontSize:10, color:'#6a5a3a', marginTop:2 } }, (npc.raca||'') + (npc.papel?' · '+npc.papel:'') ),
                                npc.personalidade && e('div', { style:{ fontSize:11, color:'#9a8a6a', marginTop:4, lineHeight:1.5 } }, npc.personalidade.substring(0,80)+(npc.personalidade.length>80?'…':''))
                            )
                        ),
                        npc.segredo && e('div', { style:{ marginTop:6, background:'rgba(200,60,60,0.08)', border:'1px solid rgba(200,60,60,0.2)', borderRadius:4, padding:'5px 8px', fontSize:11, color:'#c08080' } },
                            e('span', { style:{ fontSize:9, letterSpacing:2 } }, '🔒 SEGREDO  '), npc.segredo.substring(0,80)+(npc.segredo.length>80?'…':'')
                        ),
                        e('div', { style:{ fontSize:9, color:'#5a4828', marginTop:6, textAlign:'right', letterSpacing:1 } }, 'clique para ver detalhes →')
                    );
                })
            ),

            // MONSTROS — clicável para detalhes
            tab === 'monstros' && e('div', { style:{ display:'flex', flexDirection:'column', gap:8 } },
                monsters.length === 0 && e('div', { style:{ color:'#4a3a2a', fontSize:12, textAlign:'center', padding:16, fontStyle:'italic' } }, 'Nenhum monstro neste módulo.'),
                monsters.map(function(m, i) {
                    return e('div', { key:i, onClick:function(){ setModal({ tipo:'monster', data:m }); }, style:{ background:'rgba(139,26,26,0.1)', border:'1px solid rgba(139,26,26,0.3)', borderRadius:8, padding:'10px 12px', cursor:'pointer', transition:'border-color .2s' },
                        onMouseEnter:function(ev){ ev.currentTarget.style.borderColor='#e85454'; },
                        onMouseLeave:function(ev){ ev.currentTarget.style.borderColor='rgba(139,26,26,0.3)'; }
                    },
                        e('div', { style:{ display:'flex', gap:8, alignItems:'flex-start', marginBottom:6 } },
                            m.imagem_url
                                ? e('div', { style:{ position:'relative', flexShrink:0 } },
                                    e('img', { src:m.imagem_url, style:{ width:44, height:44, borderRadius:6, objectFit:'cover', cursor:'pointer', display:'block' }, onClick:function(ev){ abrirImgModal(m.imagem_url, m.name, ev); } }),
                                    e('div', { style:{ position:'absolute', bottom:-2, right:-2, background:'#1c1810', border:'1px solid rgba(139,26,26,0.5)', borderRadius:3, padding:'1px 3px', fontSize:8, color:'#e0a080', cursor:'pointer' }, onClick:function(ev){ abrirImgModal(m.imagem_url, m.name, ev); } }, '📺')
                                  )
                                : e('div', { style:{ width:44, height:44, borderRadius:6, background:'rgba(139,26,26,0.2)', display:'flex', alignItems:'center', justifyContent:'center', fontSize:20, flexShrink:0 } }, '💀'),
                            e('div', { style:{ flex:1 } },
                                e('div', { style:{ fontFamily:"'Cinzel',serif", color:'#e8a080', fontSize:12, fontWeight:700 } }, m.name),
                                e('div', { style:{ fontSize:10, color:'#7a5a4a', marginTop:2 } }, m.type||''),
                                m.stats && e('div', { style:{ display:'flex', flexWrap:'wrap', gap:4, marginTop:6 } },
                                    (m.stats||[]).map(function(s,j){ return e('div', { key:j, style:{ background:'rgba(200,100,60,0.15)', borderRadius:4, padding:'2px 8px', fontSize:10, color:'#e0a080' } }, s.l+': '+s.v); })
                                )
                            )
                        ),
                        e('div', { style:{ fontSize:9, color:'#7a3a2a', textAlign:'right', letterSpacing:1 } }, 'clique para ver detalhes →')
                    );
                })
            ),

            // LOOT
            tab === 'loot' && e('div', null,
                (!loot || (!loot.areas && !loot.magic)) && e('div', { style:{ color:'#4a3a2a', fontSize:12, textAlign:'center', padding:16, fontStyle:'italic' } }, 'Nenhum tesouro definido.'),
                loot && loot.magic && loot.magic.length > 0 && e('div', { style:{ marginBottom:10 } },
                    e('div', { style:{ fontSize:9, letterSpacing:3, color:'#5a4828', marginBottom:6 } }, '✨ ITENS MÁGICOS'),
                    loot.magic.map(function(item,i) {
                        return e('div', { key:i, style:{ background:'rgba(90,40,160,0.1)', border:'1px solid rgba(90,40,160,0.3)', borderRadius:6, padding:'8px 10px', marginBottom:6 } },
                            e('div', { style:{ fontFamily:"'Cinzel',serif", color:'#c8a0e0', fontSize:12, fontWeight:700 } }, item.name),
                            e('div', { style:{ fontSize:10, color:'#7a5a9a', marginTop:2 } }, item.rarity),
                            item.desc && e('div', { style:{ fontSize:11, color:'#9a8ab0', marginTop:4, lineHeight:1.5 } }, item.desc)
                        );
                    })
                ),
                loot && loot.areas && loot.areas.length > 0 && e('div', null,
                    e('div', { style:{ fontSize:9, letterSpacing:3, color:'#5a4828', marginBottom:6 } }, '💰 POR ÁREA'),
                    loot.areas.map(function(area,i) {
                        return e('div', { key:i, style:{ marginBottom:10 } },
                            e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:11, color:'#c8a45a', marginBottom:4 } }, area.title),
                            (area.items||[]).map(function(item,j) {
                                return e('div', { key:j, style:{ display:'flex', justifyContent:'space-between', padding:'3px 8px', background:'rgba(90,72,40,0.15)', borderRadius:4, marginBottom:3, fontSize:11 } },
                                    e('span', { style:{ color:'#b0a080' } }, item.name),
                                    e('span', { style:{ color:'#c8a45a', fontFamily:"'Cinzel',serif" } }, item.value)
                                );
                            })
                        );
                    })
                )
            )
        )
    );
}

function BarraDadosE() {
    // Barra inferior mínima — status e atalhos de dado rápido
    var [lastRoll, setLastRoll] = useState(null);
    var rolarRapido = function(d) {
        var r = Math.ceil(Math.random() * d);
        setLastRoll({ d:d, r:r });
    };
    return e('div', { style:{ height:36, background:'#120e08', borderTop:'1px solid #2a2010', display:'flex', alignItems:'center', gap:8, padding:'0 14px', flexShrink:0 } },
        e('div', { style:{ fontSize:10, color:'#3a2e1a', fontFamily:"'Cinzel',serif", letterSpacing:2, marginRight:4 } }, 'DADOS RÁPIDOS:'),
        [4,6,8,10,12,20].map(function(d) {
            return e('button', { key:d, onClick:function(){ rolarRapido(d); }, style:{ background:'none', border:'1px solid #2a2010', borderRadius:4, color:'#5a4828', fontSize:10, padding:'2px 8px', cursor:'pointer', fontFamily:"'Cinzel',serif" } }, 'd'+d);
        }),
        lastRoll && e('div', { key:lastRoll.r+lastRoll.d, style:{ marginLeft:'auto', fontFamily:"'Cinzel',serif", fontSize:13, color: lastRoll.r===lastRoll.d?'#c8a45a': lastRoll.r===1?'#e85454':'#e8d5a3', transition:'color .3s' } },
            'D'+lastRoll.d+' → ', e('strong', null, lastRoll.r)
        )
    );
}

// ── Layout E: Tela do Mestre Completa ────────────────────────────────────
function HUDLayoutE(p) {
    var [sideTab,    setSideTab]    = useState('chapters');
    var [concluidas, setConcluidas] = useState(new Set());
    var [uploadando, setUploadando] = useState(false);

    var chapters    = p.chapters || [];
    var npcs        = (p.modulo && Array.isArray(p.modulo.npcs))     ? p.modulo.npcs     : [];
    var monsters    = (p.modulo && Array.isArray(p.modulo.monsters)) ? p.modulo.monsters : [];
    var loot        = (p.modulo && p.modulo.loot) ? p.modulo.loot : {};

    var totalCheck  = p.objectives ? p.objectives.length : 0;
    var doneCheck   = p.objectives ? p.objectives.filter(function(o){ return o.concluida; }).length : 0;
    var progPct     = totalCheck > 0 ? Math.round(doneCheck/totalCheck*100) : 0;

    var toggleConcluida = function(idx) {
        setConcluidas(function(prev) {
            var next = new Set(prev);
            if (next.has(idx)) next.delete(idx); else next.add(idx);
            return next;
        });
    };

    // Upload manual de imagem
    var uploadImagem = useCallback(async function(file) {
        if (!p.moduloId || p.capIndex === undefined) return;
        setUploadando(true);
        try {
            var fd = new FormData();
            fd.append('imagem', file);
            var res = await fetch(C.apiUrl + '/mestre/upload-imagem-capitulo?modulo_id='+p.moduloId+'&cap_index='+p.capIndex, {
                method: 'POST', credentials: 'include',
                headers: { 'X-WP-Nonce': C.nonce },
                body: fd,
            });
            var data = await res.json();
            if (data.sucesso && data.imagem_url) {
                p.selecionarCapitulo(Object.assign({}, p.capituloAtivo, { imagem_url: data.imagem_url }), p.capIndex);
            }
        } catch(err) { console.error('Upload erro:', err); }
        setUploadando(false);
    }, [p.moduloId, p.capIndex, p.capituloAtivo]);

    return e('div', { style:{ display:'flex', flexDirection:'column', height:'100%', overflow:'hidden', background:'#0f0d09', color:'#e8d5a3', fontFamily:"'Lora',Georgia,serif" } },

        // ── Header ──────────────────────────────────────────────────────
        e('div', { style:{ background:'#1c1810', borderBottom:'1px solid #5a4828', display:'flex', alignItems:'center', padding:'0 16px', gap:12, height:52, flexShrink:0 } },
            e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:15, fontWeight:700, color:'#c8a45a', letterSpacing:2, textTransform:'uppercase', whiteSpace:'nowrap' } },
                '⚔ '+(p.modulo && p.modulo.nome ? p.modulo.nome : (p.campanha && p.campanha.nome ? p.campanha.nome : 'Campanha'))
            ),
            e('div', { style:{ display:'flex', alignItems:'center', gap:8, flex:1, maxWidth:320 } },
                e('div', { style:{ flex:1, height:6, background:'#3a3020', borderRadius:3, overflow:'hidden' } },
                    e('div', { style:{ height:'100%', borderRadius:3, width:progPct+'%', background:'linear-gradient(90deg,#8b1a1a,#c8a45a)', transition:'width .4s' } })
                ),
                e('span', { style:{ fontSize:11, color:'#9a8a6a', fontFamily:"'Cinzel',serif", letterSpacing:1, minWidth:55, textAlign:'right' } }, doneCheck+' / '+totalCheck)
            ),
            p.capIndex > 0 && e('button', { onClick: function(){ p.selecionarCapitulo(chapters[p.capIndex-1], p.capIndex-1); }, style:{ background:'none', border:'1px solid #3a3020', borderRadius:4, color:'#9a8a6a', fontSize:11, padding:'4px 10px', cursor:'pointer', fontFamily:"'Cinzel',serif", whiteSpace:'nowrap' } }, '← Anterior'),
            p.capituloAtivo && e('button', {
                onClick: function(){
                    toggleConcluida(p.capIndex);
                    // Avança automaticamente para próxima cena se existir
                    if (!concluidas.has(p.capIndex) && p.capIndex < chapters.length - 1) {
                        setTimeout(function(){ p.selecionarCapitulo(chapters[p.capIndex+1], p.capIndex+1); }, 300);
                    }
                },
                style:{ borderRadius:4, fontSize:11, padding:'4px 12px', cursor:'pointer', fontFamily:"'Cinzel',serif", whiteSpace:'nowrap', border:'1px solid '+(concluidas.has(p.capIndex) ? '#4a8a4a' : '#c8a45a'), color: concluidas.has(p.capIndex) ? '#4a8a4a' : '#c8a45a', background: concluidas.has(p.capIndex) ? 'rgba(74,138,74,0.1)' : 'rgba(200,164,90,0.1)' }
            }, concluidas.has(p.capIndex) ? '✓ Cena Concluída' : '⚔ Cena Concluída'),
            p.capIndex < chapters.length-1 && e('button', { onClick: function(){ p.selecionarCapitulo(chapters[p.capIndex+1], p.capIndex+1); }, style:{ background:'none', border:'1px solid #3a3020', borderRadius:4, color:'#9a8a6a', fontSize:11, padding:'4px 10px', cursor:'pointer', fontFamily:"'Cinzel',serif", whiteSpace:'nowrap' } }, 'Próxima →')
        ),

        // ── Corpo: sidebar | central | direita ───────────────────────────
        e('div', { style:{ display:'grid', gridTemplateColumns:'240px 1fr 280px', flex:1, overflow:'hidden' } },

            // Sidebar esquerda
            e('div', { style:{ background:'#1c1810', borderRight:'1px solid #3a3020', display:'flex', flexDirection:'column', overflow:'hidden' } },
                e('div', { style:{ display:'flex', borderBottom:'1px solid #3a3020', flexShrink:0 } },
                    e('div', { onClick: function(){ setSideTab('chapters'); }, style:{ flex:1, padding:8, fontFamily:"'Cinzel',serif", fontSize:10, letterSpacing:1, textAlign:'center', cursor:'pointer', color: sideTab==='chapters' ? '#c8a45a' : '#6a5a3a', borderBottom:'2px solid '+(sideTab==='chapters' ? '#c8a45a' : 'transparent') } }, 'CENAS'),
                    e('div', { onClick: function(){ setSideTab('checklist'); }, style:{ flex:1, padding:8, fontFamily:"'Cinzel',serif", fontSize:10, letterSpacing:1, textAlign:'center', cursor:'pointer', color: sideTab==='checklist' ? '#c8a45a' : '#6a5a3a', borderBottom:'2px solid '+(sideTab==='checklist' ? '#c8a45a' : 'transparent') } }, 'CHECKLIST')
                ),
                e('div', { style:{ flex:1, overflowY:'auto' } },
                    sideTab === 'chapters'
                        ? e(SidebarCenasE, { chapters:chapters, capAtivo:p.capituloAtivo, selecionar:p.selecionarCapitulo, concluidas:concluidas, toggleConcluida:toggleConcluida })
                        : e(ChecklistHUDE, { objectives:p.objectives||[], toggleObjective:p.toggleObjective })
                )
            ),

            // Área central
            e('div', { style:{ background:'#0f0d09', overflow:'hidden', display:'flex', flexDirection:'column' } },
                e(CenaHUDE, {
                    cap: p.capituloAtivo,
                    gerandoImg: p.gerandoImg,
                    uploadandoImg: uploadando,
                    onGerarImagem: p.gerarImagem,
                    onUploadImagem: uploadImagem,
                    npcs: npcs,
                    monsters: monsters,
                    mapas: (p.modulo && Array.isArray(p.modulo.mapas)) ? p.modulo.mapas : [],
                    onEnviarImagem: function(url) {
                        api('/mestre/enviar-imagem', 'POST', { imagem_url: url });
                    },
                })
            ),

            // Painel direito
            e(PainelDireitoE, { npcs:npcs, monsters:monsters, loot:loot, onEnviarImagem:function(url){ api('/mestre/enviar-imagem','POST',{imagem_url:url}); } })
        ),

        // Barra de dados
        e(BarraDadosE)
    );
}

// PAINEL DO MESTRE
// ════════════════════════════════════════════
// ════════════════════════════════════════════
// PLATFORM HEADER — barra de navegação fixa
// Usado por PainelMestre e TelaAventura
// Props: aba, setAba, abas[], usuario,
//        layoutAtual, onMudarLayout, onTrocarHistoria
// ════════════════════════════════════════════
function PlatformHeader({ aba, setAba, abas, usuario, layoutAtual, onMudarLayout, onTrocarHistoria }) {
    var p    = usuario && usuario.personagem;
    var tier = usuario && usuario.tier;
    var nome = usuario && (usuario.nome || usuario.display_name || '');

    return e('div', {
        style: {
            position: 'fixed', top: 0, left: 0, right: 0, zIndex: 1000,
            height: 52,
            background: 'linear-gradient(180deg,rgba(10,7,4,0.98),rgba(10,7,4,0.88))',
            backdropFilter: 'blur(12px)',
            borderBottom: '1px solid rgba(201,168,76,0.15)',
            display: 'flex', alignItems: 'center',
            padding: '0 16px', gap: 4,
        }
    },
        // Logo
        e('div', {
            style: {
                fontFamily: "'Cinzel Decorative',serif",
                color: '#c9a84c', fontSize: 15, fontWeight: 900,
                marginRight: 12, whiteSpace: 'nowrap', flexShrink: 0,
            }
        }, '⚔'),

        // Abas de navegação
        e('div', { style: { display: 'flex', alignItems: 'center', gap: 2, flex: 1 } },
            abas.map(function(a) {
                var ativo = aba === a.id;
                return e('button', {
                    key: a.id,
                    onClick: function() { setAba(a.id); },
                    style: {
                        background: ativo ? 'rgba(201,168,76,0.12)' : 'none',
                        border: 'none',
                        borderBottom: ativo ? '2px solid #c9a84c' : '2px solid transparent',
                        borderRadius: '4px 4px 0 0',
                        color: ativo ? '#c9a84c' : '#6a5a3a',
                        fontFamily: "'Cinzel',serif",
                        fontSize: 11, letterSpacing: 1,
                        padding: '0 14px', height: 52,
                        cursor: 'pointer', whiteSpace: 'nowrap',
                        transition: 'color .15s, background .15s',
                    }
                }, a.label);
            })
        ),

        // Controles de layout (só mestre, só se tiver campanha)
        onMudarLayout && layoutAtual && e('div', { style: { display: 'flex', gap: 4, marginRight: 8 } },
            ['E'].map(function(l) {
                return e('button', {
                    key: l,
                    onClick: function() { onMudarLayout(l); },
                    title: 'Layout ' + l,
                    style: {
                        width: 26, height: 26, borderRadius: 4,
                        border: '1px solid ' + (layoutAtual === l ? '#c9a84c' : '#2a1e0a'),
                        background: layoutAtual === l ? 'rgba(201,168,76,0.15)' : 'none',
                        color: layoutAtual === l ? '#c9a84c' : '#4a3a20',
                        fontFamily: "'Cinzel',serif", fontSize: 11,
                        cursor: 'pointer',
                    }
                }, l);
            })
        ),

        // Botão trocar história
        onTrocarHistoria && e('button', {
            onClick: onTrocarHistoria,
            title: 'Trocar de aventura',
            style: {
                background: 'none', border: '1px solid #2a1e0a', borderRadius: 6,
                color: '#4a3a20', fontFamily: "'Cinzel',serif",
                fontSize: 10, letterSpacing: 1, padding: '4px 10px',
                cursor: 'pointer', marginRight: 8, whiteSpace: 'nowrap',
            }
        }, '↺ TROCAR'),

        // Divider
        e('div', { style: { width: 1, height: 28, background: '#2a1e0a', flexShrink: 0 } }),

        // Badge count (se tiver)
        usuario && usuario.badge_count > 0 && e('div', {
            style: {
                display: 'flex', alignItems: 'center', gap: 4,
                color: '#c9a84c', fontSize: 11, padding: '0 10px',
                fontFamily: "'Cinzel',serif", whiteSpace: 'nowrap',
            }
        },
            '🏅 ', e('span', null, usuario.badge_count)
        ),

        // Nome / personagem
        e('div', { style: { display: 'flex', alignItems: 'center', gap: 8, paddingLeft: 8 } },
            p && p.imagem_url
                ? e('img', {
                    src: p.imagem_url,
                    style: { width: 30, height: 30, borderRadius: '50%', objectFit: 'cover', border: '2px solid #c9a84c44' }
                  })
                : e('div', {
                    style: {
                        width: 30, height: 30, borderRadius: '50%',
                        background: '#1a1208', border: '1px solid #2a1e0a',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 14,
                    }
                  }, tier === 'admin' || tier === 'tier1' || tier === 'tier2' ? '⚔' : '🧙'),

            e('div', { style: { textAlign: 'right' } },
                e('div', {
                    style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', fontSize: 11, fontWeight: 700 }
                }, p ? (p.nome + ' · Nv' + (p.nivel || 1)) : nome),
                e('div', {
                    style: { fontSize: 9, color: '#4a3a20', letterSpacing: 1, marginTop: 1 }
                }, TIER_LABEL && TIER_LABEL[tier] ? TIER_LABEL[tier] : (tier || ''))
            )
        )
    );
}

function PainelMestre({ usuario: usuarioInicial }) {
    const [aba, setAba]         = useState('sessao');
    const [usuario, setUsuario] = useState(usuarioInicial);
    const [layout, setLayout]   = useState(usuarioInicial?.layout_preferencia || 'E');
    const [layoutSelecionado, setLayoutSelecionado] = useState(!!usuarioInicial?.campanha_ativa);

    // Re-fetch usuário para pegar campanha_ativa mais recente
    useEffect(() => {
        api('/usuario').then(ud => {
            if (ud) {
                setUsuario(prev => {
                    // Nunca sobrescreve campanha_ativa se já estava definida no estado local
                    // (evita tela preta quando vindo de "Entrar como Mestre")
                    const campanha_ativa = prev.campanha_ativa || ud.campanha_ativa || null;
                    return { ...prev, ...ud, campanha_ativa };
                });
                setLayout(ud.layout_preferencia || 'E');
                if (ud.campanha_ativa) setLayoutSelecionado(true);
            }
        });
    }, []);

    const abas = [
        { id: 'sessao',    label: '⚔ Sessão' },
        { id: 'jogadores', label: '👥 Jogadores' },
        { id: 'modulos',   label: '📜 Módulos' },
    ];

    const mudarLayout = async (novoLayout) => {
        setLayout(novoLayout);
        await api('/layout-preferencia', 'POST', { layout: novoLayout });
    };

    const onVinculado = (campanha_id) => {
        setUsuario(prev => ({ ...prev, campanha_ativa: campanha_id }));
        setLayoutSelecionado(true);
        setAba('sessao');
    };

    const onLayoutSalvo = (l) => {
        setLayout(l);
        mudarLayout(l);
        setLayoutSelecionado(true);
    };

    const trocarHistoria = async () => {
        if (!window.confirm('Trocar de história vai encerrar a sessão atual. Continuar?')) return;
        await api('/desvincular-campanha', 'POST', { modulo_id: null, desativar_tudo: true });
        setUsuario(prev => ({ ...prev, campanha_ativa: null }));
        setLayoutSelecionado(false);
        setAba('sessao');
    };

    // Tela inicial sem campanha: escolher módulo + layout
    const semCampanha = !usuario?.campanha_ativa;

    return e('div', { className: 'dnd-app' },
        e(PlatformHeader, {
            aba, setAba, abas, usuario,
            layoutAtual: usuario?.campanha_ativa ? layout : null,
            onMudarLayout: mudarLayout,
            onTrocarHistoria: usuario?.campanha_ativa ? trocarHistoria : null,
        }),
        e('div', { className: 'dnd-content', style: aba === 'sessao' ? { padding: semCampanha ? 24 : 0, overflow: semCampanha ? 'auto' : 'hidden', height: semCampanha ? undefined : 'calc(100% - 0px)' } : {} },
            aba === 'sessao' && (
                semCampanha
                    ? e('div', { style: { maxWidth: 800, margin: '0 auto' } },
                        !layoutSelecionado && e(SelecaoModuloLayout, { onLayoutSalvo, layoutAtual: layout }),
                        e(SelecionarModulo, { onVinculado })
                      )
                    : e(HUDMestre, { usuario, layout })
            ),
            aba === 'jogadores' && e('div', { style: { padding: 24 } }, e(GestaoJogadores, { campanhaId: usuario?.campanha_ativa })),
            aba === 'modulos'   && e('div', { style: { padding: 24 } }, e(GestaoModulos))
        )
    );
}

// ════════════════════════════════════════════
// CRIAÇÃO DE PERSONAGEM — Visual e Imersivo
// ════════════════════════════════════════════
const RACAS_INFO = [
    { nome: 'Humano',     ico: '👤', desc: 'Versáteis e adaptáveis. Bônus em todos os atributos. Perfeitos para qualquer classe.' },
    { nome: 'Elfo',       ico: '🧝', desc: 'Ágeis e longevos. Bônus em Destreza. Visão no escuro e resistência a magia de sono.' },
    { nome: 'Elfa',       ico: '🧝‍♀️', desc: 'Graciosas e longevas. Bônus em Destreza. Affins com natureza e magia arcana.' },
    { nome: 'Anão',       ico: '⛏', desc: 'Resistentes e teimosos. Bônus em Constituição. Especialistas em trabalhos com pedra e metal.' },
    { nome: 'Halfling',   ico: '🌿', desc: 'Sortudos e furtivos. Bônus em Destreza. Podem rolar novamente em 1s naturais.' },
    { nome: 'Gnomo',      ico: '🔮', desc: 'Curiosos e inventivos. Bônus em Inteligência. Ilusionistas naturais com resistência a magia.' },
    { nome: 'Meio-Elfo',  ico: '✨', desc: 'Herdeiros de dois mundos. Bônus em Carisma e dois outros à escolha. Muito versáteis.' },
    { nome: 'Meio-Orc',   ico: '💪', desc: 'Fortes e determinados. Bônus em Força e Constituição. Sobrevivem a golpes letais uma vez por descanso.' },
    { nome: 'Tiefling',   ico: '😈', desc: 'Descendentes do Inferno. Bônus em Carisma e Inteligência. Resistência a fogo e poderes inatos.' },
    { nome: 'Draconato',  ico: '🐉', desc: 'Herdeiros de dragões. Bônus em Força e Carisma. Sopro de dragão e resistência elemental.' },
];

const CLASSES_INFO = [
    { nome: 'Bárbaro',    ico: '⚔',  desc: 'Guerreiro furioso. Rage dá dano extra e resistência. Alta Constituição e Força.' },
    { nome: 'Bardo',      ico: '🎵', desc: 'Artista mágico. Cura, buffs e magias. Alta Carisma. Jack-of-all-trades.' },
    { nome: 'Bruxo',      ico: '🕯',  desc: 'Pacto com entidade poderosa. Poucas magias mas muito poderosas. Alta Carisma.' },
    { nome: 'Clérigo',    ico: '✝',  desc: 'Servo divino. Cura, proteção e punição. Alta Sabedoria. Suporte essencial.' },
    { nome: 'Druida',     ico: '🌿', desc: 'Guardião da natureza. Transforma em animais. Alta Sabedoria. Versátil.' },
    { nome: 'Feiticeiro', ico: '✨', desc: 'Magia inata no sangue. Metamagia para customizar feitiços. Alta Carisma.' },
    { nome: 'Guerreiro',  ico: '🛡',  desc: 'Especialista em combate. Extra Attack e Action Surge. Qualquer arma/armadura.' },
    { nome: 'Ladino',     ico: '🗡',  desc: 'Mestre das sombras. Sneak Attack e Evasion. Alta Destreza. Dano explosivo.' },
    { nome: 'Mago',       ico: '📚', desc: 'Arcano supremo. Maior variedade de magias. Alta Inteligência. Poder crescente.' },
    { nome: 'Monge',      ico: '👊', desc: 'Artes marciais e ki. Flurry of Blows e Stunning Strike. Alta Sabedoria e Destreza.' },
    { nome: 'Paladino',   ico: '⚜',  desc: 'Cavaleiro sagrado. Smite divino e auras de proteção. Força e Carisma.' },
    { nome: 'Patrulheiro',ico: '🏹', desc: 'Caçador especializado. Hunter\'s Mark e Favored Enemy. Destreza e Sabedoria.' },
];

const ANTECEDENTES = ['Acólito','Artesão','Criminoso','Eremita','Forasteiro','Herói do Povo','Marinheiro','Nobre','Órfão','Sábio','Soldado'];
const ALINHAMENTOS = ['Leal e Bom','Neutro e Bom','Caótico e Bom','Leal e Neutro','Neutro','Caótico e Neutro','Leal e Mau','Neutro e Mau','Caótico e Mau'];
const ATTRS = [
    { key: 'forca',        label: 'FORÇA',         abbr: 'FOR', desc: 'Poder físico, atletismo, corpo a corpo.' },
    { key: 'destreza',     label: 'DESTREZA',       abbr: 'DES', desc: 'Agilidade, furtividade, ataques à distância.' },
    { key: 'constituicao', label: 'CONSTITUIÇÃO',   abbr: 'CON', desc: 'Resistência, pontos de vida, concentração.' },
    { key: 'inteligencia', label: 'INTELIGÊNCIA',   abbr: 'INT', desc: 'Memória, raciocínio, magias de mago.' },
    { key: 'sabedoria',    label: 'SABEDORIA',      abbr: 'SAB', desc: 'Percepção, intuição, magias de clérigo.' },
    { key: 'carisma',      label: 'CARISMA',        abbr: 'CAR', desc: 'Persuasão, liderança, magias de bardo.' },
];

function rolarAtributo() {
    const dados = [0,1,2,3].map(() => Math.ceil(Math.random() * 6));
    dados.sort((a,b) => a - b);
    return dados.slice(1).reduce((s,n) => s + n, 0);
}

function rolarTodosAtributos() {
    const result = {};
    ATTRS.forEach(a => { result[a.key] = rolarAtributo(); });
    return result;
}

function mod(v) {
    const m = Math.floor((v - 10) / 2);
    return (m >= 0 ? '+' : '') + m;
}

function CriacaoPersonagem({ usuario, onCriado }) {
    const [passo, setPasso]     = useState(1);
    const [criando, setCriando] = useState(false);
    const [erro, setErro]       = useState('');
    const [dados, setDados]     = useState({
        nome: '', raca: 'Humano', classe: 'Guerreiro', genero: 'Masculino',
        antecedente: 'Soldado', alinhamento: 'Neutro',
        atributos: rolarTodosAtributos(),
        aparencia: { cabelo: 'Castanho', olhos: 'Castanhos', porte: 'Atlético', pele: 'Moreno', traco: '' },
        lore: '',
    });

    // Dados animados
    const [rolando, setRolando]   = useState(false);
    const [tentativas, setTentativas] = useState(1);
    const [historico, setHistorico]   = useState([dados.atributos]);
    const [mostrarHistorico, setMostrarHistorico] = useState(false);

    // Lore
    const [gerandoLore, setGerandoLore] = useState(false);
    const [opcoesLore, setOpcoesLore]   = useState([]);

    // Preview de imagem
    const [imgPreview, setImgPreview]   = useState(null);

    const set = (campo, val) => setDados(d => ({ ...d, [campo]: val }));
    const setApar = (k, v) => setDados(d => ({ ...d, aparencia: { ...d.aparencia, [k]: v } }));

    const racaInfo  = RACAS_INFO.find(r => r.nome === dados.raca)  || RACAS_INFO[0];
    const classeInfo = CLASSES_INFO.find(c => c.nome === dados.classe) || CLASSES_INFO[0];

    const rolarNovamente = () => {
        if (tentativas >= 3) return;
        setRolando(true);
        setTimeout(() => {
            const novos = rolarTodosAtributos();
            setDados(d => ({ ...d, atributos: novos }));
            setHistorico(h => [...h, novos]);
            setTentativas(t => t + 1);
            setRolando(false);
        }, 600);
    };

    const usarRolagem = (atributos) => {
        setDados(d => ({ ...d, atributos }));
        setMostrarHistorico(false);
    };

    const gerarLore = async () => {
        setGerandoLore(true); setOpcoesLore([]);
        try {
            const res = await api('/personagem/lore-opcoes', 'POST', {
                nome:        dados.nome,
                raca:        dados.raca,
                classe:      dados.classe,
                antecedente: dados.antecedente,
                alinhamento: dados.alinhamento,
            });
            if (res.sucesso && Array.isArray(res.opcoes) && res.opcoes.length > 0) {
                setOpcoesLore(res.opcoes);
            } else if (res.erro) {
                setOpcoesLore(['❌ ' + res.erro]);
            }
        } catch (err) {
            setOpcoesLore(['❌ Erro de conexão. Verifique se a chave Groq está configurada.']);
        }
        setGerandoLore(false);
    };

    const criar = async () => {
        if (!dados.nome.trim()) { setErro('Escolha um nome para seu personagem.'); return; }
        setCriando(true); setErro('');
        try {
            const payload = { ...dados };
            const res = await api('/personagem', 'POST', payload);
            if (res.sucesso) {
                onCriado(res);
            } else {
                setErro((res.erro || 'Erro ao criar personagem.') + (res.db_erro ? ' | ' + res.db_erro : ''));
            }
        } catch { setErro('Erro de conexão. Tente novamente.'); }
        setCriando(false);
    };

    const passos = ['Identidade', 'Atributos', 'Aparência', 'Lore'];
    const labelStyle = { display: 'block', fontSize: 10, letterSpacing: 3, color: 'var(--t3)', fontFamily: "'Cinzel',serif", marginBottom: 6 };
    const selectStyle = { width: '100%', background: 'rgba(10,8,4,.95)', border: '1px solid rgba(255,255,255,.10)', borderRadius: 8, padding: '10px 12px', color: 'var(--t0)', fontFamily: 'inherit', fontSize: 14, outline: 'none', cursor: 'pointer' };

    return e('div', { className: 'dnd-card', style: { maxWidth: 760, margin: '0 auto' } },
        // Cabeçalho com steps
        e('div', { style: { textAlign: 'center', marginBottom: 28 } },
            e('div', { style: { fontSize: 32, marginBottom: 8 } }, '⚔'),
            e('div', { className: 'dnd-card-label', style: { textAlign: 'center' } }, 'CRIAR SEU PERSONAGEM'),
            e('div', { style: { display: 'flex', justifyContent: 'center', gap: 8, marginTop: 16, flexWrap: 'wrap' } },
                passos.map((s, i) =>
                    e('div', { key: i, style: { display: 'flex', alignItems: 'center', gap: 4 } },
                        e('div', { style: {
                            width: 30, height: 30, borderRadius: '50%',
                            background: passo > i+1 ? 'var(--g4)' : passo === i+1 ? 'var(--g3)' : 'var(--b3)',
                            border: passo === i+1 ? '2px solid var(--g2)' : '1px solid var(--b5)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            fontSize: 11, color: passo >= i+1 ? 'var(--g1)' : 'var(--t4)',
                            fontFamily: "'Cinzel',serif",
                        }}, passo > i+1 ? '✓' : String(i+1)),
                        e('span', { style: { fontSize: 11, color: passo === i+1 ? 'var(--t1)' : 'var(--t4)', fontFamily: "'Cinzel',serif", letterSpacing: 1 } }, s),
                        i < passos.length-1 && e('div', { style: { width: 20, height: 1, background: 'var(--b5)', margin: '0 2px' } })
                    )
                )
            )
        ),

        // ── Passo 1: Identidade ──────────────────────────────────────────────
        passo === 1 && e('div', null,
            e('div', { style: { marginBottom: 20 } },
                e('label', { style: labelStyle }, 'NOME DO PERSONAGEM'),
                e('input', { className: 'dnd-input', placeholder: 'Como você se chama?', value: dados.nome, onChange: ev => set('nome', ev.target.value), style: { marginBottom: 0 } })
            ),
            // Raça com cards visuais
            e('div', { style: { marginBottom: 20 } },
                e('label', { style: labelStyle }, 'RAÇA'),
                e('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: 8, marginBottom: 10 } },
                    RACAS_INFO.map(r =>
                        e('div', {
                            key: r.nome,
                            onClick: () => set('raca', r.nome),
                            style: {
                                padding: '10px 6px', borderRadius: 8, textAlign: 'center', cursor: 'pointer',
                                border: dados.raca === r.nome ? '2px solid var(--g2)' : '1px solid var(--b5)',
                                background: dados.raca === r.nome ? 'rgba(201,168,76,.1)' : 'var(--b2)',
                                transition: 'all .2s',
                            }
                        },
                            e('div', { style: { fontSize: 22, marginBottom: 4 } }, r.ico),
                            e('div', { style: { fontSize: 9, color: dados.raca === r.nome ? 'var(--g1)' : 'var(--t3)', fontFamily: "'Cinzel',serif", letterSpacing: 1 } }, r.nome)
                        )
                    )
                ),
                // Descrição da raça selecionada
                e('div', { style: { background: 'rgba(201,168,76,.06)', border: '1px solid rgba(201,168,76,.15)', borderRadius: 8, padding: '10px 14px', display: 'flex', gap: 10, alignItems: 'flex-start' } },
                    e('div', { style: { fontSize: 24, flexShrink: 0 } }, racaInfo.ico),
                    e('div', null,
                        e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, color: 'var(--g2)', letterSpacing: 2, marginBottom: 4 } }, racaInfo.nome.toUpperCase()),
                        e('p', { style: { fontSize: 13, color: 'var(--t2)', margin: 0, lineHeight: 1.6 } }, racaInfo.desc)
                    )
                )
            ),
            // Classe com cards visuais
            e('div', { style: { marginBottom: 20 } },
                e('label', { style: labelStyle }, 'CLASSE'),
                e('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(90px, 1fr))', gap: 8, marginBottom: 10 } },
                    CLASSES_INFO.map(c =>
                        e('div', {
                            key: c.nome,
                            onClick: () => set('classe', c.nome),
                            style: {
                                padding: '10px 6px', borderRadius: 8, textAlign: 'center', cursor: 'pointer',
                                border: dados.classe === c.nome ? '2px solid var(--g2)' : '1px solid var(--b5)',
                                background: dados.classe === c.nome ? 'rgba(201,168,76,.1)' : 'var(--b2)',
                                transition: 'all .2s',
                            }
                        },
                            e('div', { style: { fontSize: 20, marginBottom: 4 } }, c.ico),
                            e('div', { style: { fontSize: 8, color: dados.classe === c.nome ? 'var(--g1)' : 'var(--t3)', fontFamily: "'Cinzel',serif", letterSpacing: 1 } }, c.nome)
                        )
                    )
                ),
                e('div', { style: { background: 'rgba(201,168,76,.06)', border: '1px solid rgba(201,168,76,.15)', borderRadius: 8, padding: '10px 14px', display: 'flex', gap: 10, alignItems: 'flex-start' } },
                    e('div', { style: { fontSize: 24, flexShrink: 0 } }, classeInfo.ico),
                    e('div', null,
                        e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, color: 'var(--g2)', letterSpacing: 2, marginBottom: 4 } }, classeInfo.nome.toUpperCase()),
                        e('p', { style: { fontSize: 13, color: 'var(--t2)', margin: 0, lineHeight: 1.6 } }, classeInfo.desc)
                    )
                )
            ),
            e('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 12 } },
                e('div', null,
                    e('label', { style: labelStyle }, 'GÊNERO'),
                    e('select', { style: selectStyle, value: dados.genero, onChange: ev => set('genero', ev.target.value) },
                        ['Masculino','Feminino','Não-binário','Neutro'].map(g => e('option', { key: g, value: g }, g))
                    )
                ),
                e('div', null,
                    e('label', { style: labelStyle }, 'ANTECEDENTE'),
                    e('select', { style: selectStyle, value: dados.antecedente, onChange: ev => set('antecedente', ev.target.value) },
                        ANTECEDENTES.map(a => e('option', { key: a, value: a }, a))
                    )
                ),
                e('div', null,
                    e('label', { style: labelStyle }, 'ALINHAMENTO'),
                    e('select', { style: selectStyle, value: dados.alinhamento, onChange: ev => set('alinhamento', ev.target.value) },
                        ALINHAMENTOS.map(a => e('option', { key: a, value: a }, a))
                    )
                )
            )
        ),

        // ── Passo 2: Atributos ───────────────────────────────────────────────
        passo === 2 && e('div', null,
            e('div', { style: { background: 'rgba(201,168,76,.06)', border: '1px solid rgba(201,168,76,.15)', borderRadius: 8, padding: '12px 16px', marginBottom: 20 } },
                e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, color: 'var(--g2)', letterSpacing: 2, marginBottom: 6 } }, '🎲 ROLAGEM DE DADOS'),
                e('p', { style: { fontSize: 13, color: 'var(--t2)', margin: 0, lineHeight: 1.65 } },
                    'Cada atributo é gerado rolando ', e('strong', { style: { color: 'var(--g2)' } }, '4d6'), ', descartando o menor. ',
                    'Você tem ', e('strong', { style: { color: tentativas >= 3 ? 'var(--verm-b)' : 'var(--g2)' } }, (4 - tentativas) + ' tentativa(s) restante(s)'), '.'
                ),
                e('div', { style: { display: 'flex', gap: 8, marginTop: 10, flexWrap: 'wrap' } },
                    e('button', {
                        className: 'btn btn-gold',
                        disabled: tentativas >= 3 || rolando,
                        onClick: rolarNovamente,
                        style: { fontSize: 12 },
                    }, rolando ? '🎲 Rolando...' : `🎲 Rolar Novamente (${3 - tentativas} restante${3-tentativas !== 1 ? 's' : ''})`),
                    historico.length > 1 && e('button', {
                        className: 'btn btn-ghost',
                        onClick: () => setMostrarHistorico(m => !m),
                        style: { fontSize: 12 },
                    }, mostrarHistorico ? '▲ Ocultar Histórico' : '▼ Ver Histórico de Rolagens')
                ),
                // Histórico de rolagens
                mostrarHistorico && e('div', { style: { marginTop: 12, display: 'flex', flexDirection: 'column', gap: 6 } },
                    historico.map((h, idx) =>
                        e('div', { key: idx, style: { display: 'flex', alignItems: 'center', gap: 10, padding: '6px 10px', background: 'var(--b2)', borderRadius: 6, border: h === dados.atributos ? '1px solid var(--g2)' : '1px solid var(--b5)' } },
                            e('span', { style: { fontSize: 10, color: 'var(--t3)', fontFamily: "'Cinzel',serif", width: 60 } }, 'Tentativa ' + (idx+1)),
                            e('div', { style: { display: 'flex', gap: 6, flex: 1, flexWrap: 'wrap' } },
                                ATTRS.map(a =>
                                    e('span', { key: a.key, style: { fontSize: 11, color: 'var(--t2)' } },
                                        e('span', { style: { fontSize: 9, color: 'var(--t4)' } }, a.abbr + ' '),
                                        e('strong', { style: { color: 'var(--g1)' } }, h[a.key])
                                    )
                                )
                            ),
                            h !== dados.atributos && e('button', {
                                className: 'btn btn-ghost', style: { fontSize: 10, padding: '3px 8px' },
                                onClick: () => usarRolagem(h),
                            }, 'Usar Esta')
                        )
                    )
                )
            ),
            e('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12, opacity: rolando ? 0.5 : 1, transition: 'opacity .3s' } },
                ATTRS.map(attr =>
                    e('div', { key: attr.key, style: { background: 'var(--b2)', borderRadius: 10, padding: '14px 12px', textAlign: 'center', border: '1px solid var(--b5)' } },
                        e('div', { style: { fontSize: 9, letterSpacing: 3, color: 'var(--t4)', fontFamily: "'Cinzel',serif", marginBottom: 4 } }, attr.label),
                        e('div', { style: { fontSize: 28, color: 'var(--g1)', fontFamily: "'Cinzel',serif", fontWeight: 700, lineHeight: 1, marginBottom: 4 } }, dados.atributos[attr.key]),
                        e('div', { style: { fontSize: 13, color: 'var(--g3)', marginBottom: 4 } }, mod(dados.atributos[attr.key])),
                        e('div', { style: { fontSize: 11, color: 'var(--t4)', lineHeight: 1.5 } }, attr.desc)
                    )
                )
            )
        ),

        // ── Passo 3: Aparência ───────────────────────────────────────────────
        passo === 3 && e('div', null,
            e('p', { style: { color: 'var(--t2)', fontSize: 14, marginBottom: 16, lineHeight: 1.65 } },
                'Sua aparência define como a IA gerará seu retrato. ',
                e('strong', { style: { color: 'var(--g2)' } }, 'Quanto mais detalhado, melhor o resultado!')
            ),
            e('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 } },
                [
                    { k: 'cabelo', l: 'COR DO CABELO', opts: ['Preto','Castanho','Loiro','Ruivo','Branco','Cinza','Azul','Roxo','Prata'] },
                    { k: 'olhos',  l: 'COR DOS OLHOS', opts: ['Castanhos','Azuis','Verdes','Cinzas','Negros','Âmbar','Violeta','Dourados'] },
                    { k: 'porte',  l: 'PORTE',         opts: ['Magro','Atlético','Robusto','Corpulento','Esguio','Mediano'] },
                    { k: 'pele',   l: 'TOM DE PELE',   opts: ['Claro','Moreno','Escuro','Azulado','Esverdeado','Cinzento','Avermelhado'] },
                ].map(field =>
                    e('div', { key: field.k },
                        e('label', { style: labelStyle }, field.l),
                        e('select', { style: selectStyle, value: dados.aparencia[field.k], onChange: ev => setApar(field.k, ev.target.value) },
                            field.opts.map(o => e('option', { key: o, value: o }, o))
                        )
                    )
                )
            ),
            e('div', null,
                e('label', { style: labelStyle }, 'TRAÇO MARCANTE (opcional)'),
                e('input', { className: 'dnd-input', placeholder: 'Ex: cicatriz no rosto, tatuagem runica, olhos flamejantes...', value: dados.aparencia.traco, onChange: ev => setApar('traco', ev.target.value), style: { marginBottom: 0 } })
            )
        ),

        // ── Passo 4: Lore ────────────────────────────────────────────────────
        passo === 4 && e('div', null,
            // Card explicativo sobre Lore
            e('div', { style: { background: 'rgba(201,168,76,.06)', border: '1px solid rgba(201,168,76,.15)', borderRadius: 10, padding: '16px', marginBottom: 20 } },
                e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, color: 'var(--g2)', letterSpacing: 2, marginBottom: 8 } }, '📖 O QUE É LORE?'),
                e('p', { style: { fontSize: 14, color: 'var(--t1)', margin: '0 0 8px', lineHeight: 1.7 } },
                    'Lore é a ', e('strong', { style: { color: 'var(--g1)' } }, 'história de origem'), ' do seu personagem: de onde veio, o que o motivou a aventurar, quais são seus medos, sonhos e segredos.'
                ),
                e('p', { style: { fontSize: 13, color: 'var(--t3)', margin: 0, lineHeight: 1.65 } },
                    'Uma boa lore torna o jogo muito mais imersivo — o Mestre pode criar eventos e NPCs baseados nela. É opcional, mas altamente recomendada.'
                )
            ),
            e('textarea', {
                className: 'dnd-input',
                style: { minHeight: 120, resize: 'vertical', lineHeight: 1.7, fontSize: 14 },
                placeholder: 'Escreva a história do seu personagem... ou use a IA para gerar opções!',
                value: dados.lore,
                onChange: ev => set('lore', ev.target.value),
            }),
            // Gerador de Lore com IA
            e('div', { style: { marginTop: 12 } },
                e('button', {
                    className: 'btn btn-ghost',
                    disabled: gerandoLore || !dados.nome.trim(),
                    onClick: gerarLore,
                    style: { marginBottom: 12 },
                }, gerandoLore ? '⏳ Gerando opções...' : '🤖 Gerar 3 Opções com IA'),
                !dados.nome.trim() && e('p', { style: { fontSize: 12, color: 'var(--t4)', margin: '4px 0 0' } }, 'Defina o nome do personagem (Passo 1) para usar a IA.'),
                opcoesLore.length > 0 && e('div', { style: { display: 'flex', flexDirection: 'column', gap: 10 } },
                    e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 10, color: 'var(--t3)', letterSpacing: 2, marginBottom: 4 } }, 'ESCOLHA UMA OPÇÃO OU USE COMO INSPIRAÇÃO:'),
                    opcoesLore.map((opcao, i) =>
                        e('div', {
                            key: i,
                            style: {
                                padding: '12px 14px', borderRadius: 8, cursor: 'pointer',
                                border: dados.lore === opcao ? '1px solid var(--g2)' : '1px solid var(--b5)',
                                background: dados.lore === opcao ? 'rgba(201,168,76,.08)' : 'var(--b2)',
                                transition: 'all .2s',
                            },
                            onClick: () => set('lore', opcao),
                        },
                            e('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 } },
                                e('span', { style: { fontFamily: "'Cinzel',serif", fontSize: 9, color: 'var(--g3)', letterSpacing: 2 } }, 'OPÇÃO ' + (i+1)),
                                dados.lore === opcao && e('span', { style: { fontSize: 10, color: 'var(--g2)' } }, '✓ Selecionada')
                            ),
                            e('p', { style: { fontSize: 13, color: 'var(--t2)', margin: 0, lineHeight: 1.65 } }, opcao)
                        )
                    )
                )
            )
        ),

        // Erro
        erro && e('div', { style: { background: 'rgba(122,24,24,.2)', border: '1px solid var(--vermelho)', borderRadius: 8, padding: '10px 14px', color: 'var(--verm-b)', fontSize: 14, marginTop: 16 } }, '⚠ ' + erro),

        // Botões de navegação
        e('div', { style: { display: 'flex', gap: 10, marginTop: 24, justifyContent: 'flex-end' } },
            passo > 1 && e('button', { className: 'btn btn-ghost', onClick: () => setPasso(p => p - 1) }, '← Voltar'),
            passo < 4
                ? e('button', {
                    className: 'btn btn-gold',
                    onClick: () => {
                        if (passo === 1 && !dados.nome.trim()) { setErro('Escolha um nome primeiro.'); return; }
                        setErro(''); setPasso(p => p + 1);
                    }
                  }, 'Próximo →')
                : e('button', {
                    className: 'btn btn-gold',
                    disabled: criando,
                    onClick: criar,
                  }, criando ? '⏳ Criando personagem...' : '⚔ Criar Personagem')
        )
    );
}
// COLE ESTE BLOCO ONDE QUISER QUE AS HABILIDADES APARE�AM NO ECR� DA FICHA:
e('div', { style: { marginTop: '30px', borderTop: '1px solid var(--b3)', paddingTop: '20px' } },
    e('h3', { className: 'cinzel', style: { color: 'var(--g2)', fontSize: '18px', marginBottom: '15px' } }, 'Habilidades de Classe'),
    
    // Este � o "chamador" propriamente dito!
    e(PainelHabilidadesClasse, { habilidades: C.personagem_ativo ? C.personagem_ativo.habilidades_classe : [] })
),
// ════════════════════════════════════════════
// HUD DO JOGADOR
// ════════════════════════════════════════════
function HUDJogador({ usuario, onPersonagemCriado, irParaFicha }) {
    const personagem = usuario?.personagem;
    const hpPct = personagem ? Math.round((personagem.hp_atual / (personagem.hp_max || 1)) * 100) : 0;
    const hpCor = hpPct > 50 ? 'var(--verde-b)' : hpPct > 25 ? 'var(--ambar-b)' : 'var(--verm-b)';

    // Polling: detectar início de sessão e imagem enviada pelo mestre
    const [sessaoAtiva, setSessaoAtiva] = useState(false);
    const [imagemMestre, setImagemMestre] = useState(null); // { url, ts }
    const [imagemTs,     setImagemTs]     = useState(0);
    const [mostraImagem, setMostraImagem] = useState(false);
    const imagemTsRef = useRef(0); // ref para evitar re-criação do interval

    useEffect(function() {
        var intervalo = setInterval(async function() {
            try {
                // Uma única chamada retorna status + imagem (endpoint consolidado)
                var poll = await api('/sessao/poll');
                if (poll && poll.status === 'em_andamento') setSessaoAtiva(true);
                if (poll && poll.imagem_url && poll.ts && poll.ts !== imagemTsRef.current) {
                    imagemTsRef.current = poll.ts;
                    setImagemMestre({ url: poll.imagem_url, ts: poll.ts });
                    setImagemTs(poll.ts);
                    setMostraImagem(true);
                }
            } catch(e) {}
        }, 5000);
        return function() { clearInterval(intervalo); };
    }, []); // sem deps — imagemTsRef evita o re-create do interval

    if (!personagem) return e('div', { className: 'hud-empty-root' },
        e('div', { style: { fontSize: 48, marginBottom: 16, opacity: 0.35 } }, '⚔'),
        e('div', { className: 'hud-empty-titulo' }, 'Crie seu Personagem'),
        e('p', { className: 'hud-empty-desc' }, 'Você ainda não tem um personagem. Clique abaixo para começar sua jornada.'),
        e('button', { className: 'btn btn-gold', style: { marginTop: 16, padding: '14px 32px', fontSize: 15 }, onClick: onPersonagemCriado }, '⚔ Criar Personagem Agora')
    );

    return e('div', { style: { padding: '72px 16px 48px', overflowY: 'auto', height: '100%', boxSizing: 'border-box' } },

        // ── Modal de imagem do mestre ────────────────────────────────────
        mostraImagem && imagemMestre && e('div', {
            style: { position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.92)', zIndex: 200, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 16, padding: 24 },
            onClick: function() { setMostraImagem(false); }
        },
            e('div', { style: { color: 'var(--g2)', fontFamily: "'Cinzel',serif", fontSize: 11, letterSpacing: 3, marginBottom: 4 } }, '📺 O MESTRE MOSTROU UMA IMAGEM'),
            e('img', { src: imagemMestre.url, style: { maxWidth: '90vw', maxHeight: '70vh', objectFit: 'contain', borderRadius: 10, boxShadow: '0 0 60px rgba(200,164,90,0.3)', border: '1px solid var(--g3)' }, onClick: function(ev) { ev.stopPropagation(); } }),
            e('button', { style: { background: 'rgba(255,255,255,0.05)', border: '1px solid var(--b5)', borderRadius: 8, color: 'var(--t3)', padding: '8px 24px', cursor: 'pointer', fontFamily: "'Cinzel',serif", fontSize: 12 }, onClick: function() { setMostraImagem(false); } }, 'Fechar')
        ),

        e('div', { style: { maxWidth: 680, margin: '0 auto' } },

            // ── Card personagem ──────────────────────────────────────────
            e('div', { className: 'dnd-card', style: { marginBottom: 16 } },
                e('div', { style: { display: 'flex', gap: 16, alignItems: 'flex-start' } },
                    personagem.imagem
                        ? e('img', { src: personagem.imagem, style: { width: 88, height: 88, borderRadius: 10, objectFit: 'cover', border: '2px solid var(--g3)', flexShrink: 0 } })
                        : e('div', { style: { width: 88, height: 88, borderRadius: 10, background: 'var(--b3)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 36, border: '1px solid var(--b5)', flexShrink: 0 } }, '⚔'),
                    e('div', { style: { flex: 1 } },
                        e('div', { style: { fontSize: 22, color: 'var(--g1)', fontFamily: "'Cinzel',serif", fontWeight: 700, marginBottom: 2 } }, personagem.nome),
                        e('div', { style: { fontSize: 13, color: 'var(--t2)', marginBottom: 10 } }, personagem.classe + ' · ' + personagem.raca + ' · Nível ' + personagem.nivel),
                        e('div', { style: { display: 'flex', gap: 20, flexWrap: 'wrap', marginBottom: 8 } },
                            e('div', null,
                                e('span', { style: { fontSize: 15, color: hpCor, fontFamily: "'Cinzel',serif", fontWeight: 700 } }, '❤ ' + personagem.hp_atual + '/' + personagem.hp_max + ' HP'),
                                e('div', { className: 'hp-bar', style: { marginTop: 4, width: 140 } },
                                    e('div', { className: 'hp-fill', style: { width: hpPct + '%', background: hpCor } })
                                )
                            ),
                            e('div', { style: { textAlign: 'center' } },
                                e('div', { style: { fontSize: 20, color: 'var(--g1)', fontFamily: "'Cinzel',serif", fontWeight: 700 } }, personagem.ca || 10),
                                e('div', { style: { fontSize: 9, color: 'var(--t4)', letterSpacing: 2 } }, 'CA')
                            )
                        ),
                        e('button', { className: 'btn btn-ghost', style: { fontSize: 11, padding: '4px 12px' }, onClick: irParaFicha }, '📋 Ver Ficha Completa')
                    )
                )
            ),

            // ── Atributos ────────────────────────────────────────────────
            personagem.atributos && e('div', { className: 'dnd-card', style: { marginBottom: 16 } },
                e('div', { className: 'dnd-card-label', style: { marginBottom: 10 } }, 'ATRIBUTOS'),
                e('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(90px, 1fr))', gap: 8 } },
                    [
                        { key: 'forca',        abbr: 'FOR' },
                        { key: 'destreza',     abbr: 'DES' },
                        { key: 'constituicao', abbr: 'CON' },
                        { key: 'inteligencia', abbr: 'INT' },
                        { key: 'sabedoria',    abbr: 'SAB' },
                        { key: 'carisma',      abbr: 'CAR' },
                    ].map(function(a) {
                        var val = personagem.atributos[a.key] || 10;
                        var m = Math.floor((val - 10) / 2);
                        return e('div', { key: a.key, style: { background: 'var(--b2)', borderRadius: 8, padding: '10px 4px', textAlign: 'center', border: '1px solid var(--b5)' } },
                            e('div', { style: { fontSize: 8, letterSpacing: 2, color: 'var(--t4)', fontFamily: "'Cinzel',serif", marginBottom: 4 } }, a.abbr),
                            e('div', { style: { fontSize: 20, color: 'var(--g1)', fontFamily: "'Cinzel',serif", fontWeight: 700 } }, val),
                            e('div', { style: { fontSize: 11, color: 'var(--t3)', marginTop: 2 } }, (m >= 0 ? '+' : '') + m)
                        );
                    })
                )
            ),

            // ── Status da sessão ─────────────────────────────────────────
            sessaoAtiva
                ? e('div', { className: 'dnd-card', style: { textAlign: 'center', padding: '28px 20px', border: '1px solid var(--g3)', background: 'rgba(200,164,90,0.05)' } },
                    e('div', { style: { fontSize: 32, marginBottom: 12 } }, '⚔'),
                    e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 14, letterSpacing: 2, color: 'var(--g1)', marginBottom: 8 } }, 'SESSÃO EM ANDAMENTO'),
                    e('p', { style: { color: 'var(--t3)', fontSize: 13, margin: 0 } }, 'O Mestre está conduzindo a aventura. Aguarde eventos e imagens aqui.'),
                    imagemMestre && e('button', { style: { marginTop: 16, background: 'rgba(200,164,90,0.1)', border: '1px solid var(--g3)', borderRadius: 8, color: 'var(--g2)', fontFamily: "'Cinzel',serif", fontSize: 12, padding: '8px 20px', cursor: 'pointer' }, onClick: function() { setMostraImagem(true); } }, '🖼 Ver última imagem')
                  )
                : e('div', { className: 'dnd-card', style: { textAlign: 'center', padding: '28px 20px' } },
                    e('div', { style: { fontSize: 32, marginBottom: 12, opacity: 0.4 } }, '⏳'),
                    e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 13, letterSpacing: 2, color: 'var(--t3)', marginBottom: 8 } }, 'AGUARDANDO O MESTRE'),
                    e('p', { style: { color: 'var(--t4)', fontSize: 13, margin: 0 } }, 'Quando o Mestre iniciar a sessão, você será notificado automaticamente.'),
                    e('p', { style: { color: 'var(--t4)', fontSize: 12, marginTop: 8, opacity: 0.6 } }, 'Enquanto isso, explore sua ficha na aba 📋 Ficha.')
                )
        )
    );
}

// ════════════════════════════════════════════
// TELA DO AVENTUREIRO (JOGADOR)
// ════════════════════════════════════════════
function TelaAventura({ usuario: usuarioInicial }) {
    const temPersonagem = !!usuarioInicial?.personagem;
    const [aba, setAba]         = useState(temPersonagem ? 'sessao' : 'ficha');
    const [usuario, setUsuario] = useState(usuarioInicial);
    // Se não tem personagem, já inicia no modo criação
    const [criandoPersonagem, setCriandoPersonagem] = useState(!temPersonagem);

    const abas = [
        { id: 'sessao',     label: '⚔ Sessão' },
        { id: 'ficha',      label: '📋 Ficha' },
        { id: 'inventario', label: '🎒 Inventário' },
    ];

    const irParaCriarPersonagem = () => { setCriandoPersonagem(true); setAba('ficha'); };

    const onPersonagemCriado = async (res) => {
        setCriandoPersonagem(false);
        try {
            const userData = await api('/usuario');
            if (userData) setUsuario(prev => ({ ...prev, ...userData }));
        } catch {}
        setAba('sessao');
    };

    return e('div', { className: 'dnd-app' },
        e(PlatformHeader, {
            aba, setAba, abas, usuario,
            layoutAtual: null,
            onMudarLayout: null,
            onTrocarHistoria: null,
        }),
        aba === 'sessao'
            ? e('div', { className: 'dnd-content', style: { overflow: 'hidden', height: 'calc(100% - 52px)', padding: 0 } },
                e(HUDJogador, { usuario, onPersonagemCriado: irParaCriarPersonagem, irParaFicha: () => setAba('ficha') })
              )
            : e('div', { className: 'dnd-scroll-page', style: { top: 52, padding: 24 } },
                aba === 'ficha' && (
                    criandoPersonagem || !usuario?.personagem
                        ? e(CriacaoPersonagem, { usuario, onCriado: onPersonagemCriado })
                        : e('div', { className: 'dnd-card', style: { maxWidth: 600, margin: '0 auto' } },
                            e('div', { className: 'dnd-card-label' }, '📋 ' + (usuario.personagem.nome || 'Ficha').toUpperCase()),
                            e('p', { style: { color: 'var(--t2)', fontSize: 15 } }, usuario.personagem.classe + ' · ' + usuario.personagem.raca + ' · Nível ' + usuario.personagem.nivel),
                            e('button', { className: 'btn btn-ghost', style: { marginTop: 8 }, onClick: () => setCriandoPersonagem(true) }, '✏ Editar Personagem')
                          )
                ),
                aba === 'inventario' && e('div', { className: 'dnd-card', style: { maxWidth: 600, margin: '0 auto' } },
                    e('div', { className: 'dnd-card-label' }, '🎒 INVENTÁRIO'),
                    e('p', { style: { color: 'var(--t3)', fontStyle: 'italic' } }, 'Itens recebidos do Mestre aparecerão aqui.')
                )
              )
    );
}

// ════════════════════════════════════════════
// HEADER LOGADO (aparece na LP quando logado)
// ════════════════════════════════════════════
function HeaderLogado({ usuario, onSair }) {
    var p = usuario.personagem;
    var tier = usuario.tier;
    return e('div', {
        style: {
            position: 'fixed', top: 0, right: 0, left: 0, zIndex: 1000,
            background: 'linear-gradient(180deg,rgba(10,7,4,0.97),rgba(10,7,4,0.85))',
            backdropFilter: 'blur(12px)',
            borderBottom: '1px solid rgba(201,168,76,0.15)',
            display: 'flex', alignItems: 'center', padding: '0 24px',
            height: 64, gap: 16,
        }
    },
        // Logo
        e('div', { style: { fontFamily: "'Cinzel Decorative',serif", color: '#c9a84c', fontSize: 18, fontWeight: 900, flex: 1 } }, '⚔ DnD Master'),

        // Info do personagem (se tiver)
        p && e('div', { style: { display: 'flex', alignItems: 'center', gap: 12 } },
            p.imagem
                ? e('img', { src: p.imagem, style: { width: 38, height: 38, borderRadius: '50%', objectFit: 'cover', border: '2px solid #c9a84c44' } })
                : e('div', { style: { width: 38, height: 38, borderRadius: '50%', background: '#1a1208', border: '2px solid #c9a84c44', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18 } }, '⚔'),
            e('div', { style: { textAlign: 'right' } },
                e('div', { style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', fontSize: 13, fontWeight: 700 } },
                    p.nome + ' · Nv ' + p.nivel),
                e('div', { style: { fontSize: 11, color: '#6a5a3a' } }, usuario.nome)
            )
        ),

        // Sem personagem: só nome
        !p && e('div', { style: { textAlign: 'right', marginRight: 4 } },
            e('div', { style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', fontSize: 13 } }, usuario.nome),
            e('div', { style: { fontSize: 10, color: '#6a5a3a', letterSpacing: 1 } }, TIER_LABEL[tier] || tier)
        ),

        e('div', { style: { width: 1, height: 32, background: '#2a1e0a' } }),

        e('a', {
            href: C.painelUrl || '/dnd-painel',
            style: { color: '#c9a84c', textDecoration: 'none', fontFamily: "'Cinzel',serif", fontSize: 12, letterSpacing: 1, padding: '6px 14px', border: '1px solid #c9a84c33', borderRadius: 6 }
        }, '🏠 Dashboard'),

        e('button', {
            onClick: onSair,
            style: { background: 'none', border: '1px solid #3a2a10', borderRadius: 6, color: '#6a5a3a', fontFamily: "'Cinzel',serif", fontSize: 12, padding: '6px 14px', cursor: 'pointer', letterSpacing: 1 }
        }, 'SAIR')
    );
}

// ════════════════════════════════════════════
// TIER LABELS E CORES
// ════════════════════════════════════════════
var TIER_LABEL = {
    admin: 'ADMINISTRADOR',
    tier1: 'MESTRE · TIER 1',
    tier2: 'MESTRE · TIER 2',
    tier3: 'AVENTUREIRO',
};

var TIER_COLOR = {
    admin:  { bg: 'linear-gradient(135deg,#1a0f2e,#2d1b4e)', border: '#7c3aed', glow: '#7c3aed33' },
    tier1:  { bg: 'linear-gradient(135deg,#1a0808,#2e1010)', border: '#dc2626', glow: '#dc262633' },
    tier2:  { bg: 'linear-gradient(135deg,#080f1a,#10182e)', border: '#2563eb', glow: '#2563eb33' },
    tier3:  { bg: 'linear-gradient(135deg,#0a0f08,#121e0c)', border: '#16a34a', glow: '#16a34a33' },
};

// ════════════════════════════════════════════
// DASHBOARD — CARDS MTG
// ════════════════════════════════════════════
function DashboardCard({ icone, titulo, desc, cor, onClick, badge }) {
    var [hover, setHover] = useState(false);
    var c = TIER_COLOR[cor] || TIER_COLOR.tier3;

    return e('div', {
        onClick: onClick,
        onMouseEnter: function() { setHover(true); },
        onMouseLeave: function() { setHover(false); },
        style: {
            position: 'relative',
            background: c.bg,
            border: '2px solid ' + (hover ? c.border : c.border + '66'),
            borderRadius: 16,
            padding: '0',
            cursor: 'pointer',
            transition: 'all 0.25s',
            transform: hover ? 'translateY(-6px) scale(1.02)' : 'translateY(0) scale(1)',
            boxShadow: hover ? '0 16px 48px ' + c.glow + ', 0 0 0 1px ' + c.border + '44' : '0 4px 16px rgba(0,0,0,0.4)',
            overflow: 'hidden',
            minHeight: 260,
            display: 'flex',
            flexDirection: 'column',
        }
    },
        // Ornamento topo (estilo MTG)
        e('div', { style: {
            height: 6, background: 'linear-gradient(90deg,transparent,' + c.border + ',transparent)',
            opacity: hover ? 1 : 0.5, transition: 'opacity 0.25s',
        }}),

        // Arte — gradiente com ícone centralizado
        e('div', { style: {
            flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center',
            padding: '32px 24px 16px',
            background: 'radial-gradient(ellipse 80% 70% at 50% 40%,' + c.glow + ' 0%, transparent 70%)',
        }},
            e('div', { style: {
                fontSize: 72, lineHeight: 1,
                filter: hover ? 'drop-shadow(0 0 20px ' + c.border + ')' : 'none',
                transition: 'filter 0.25s',
            }}, icone)
        ),

        // Linha divisória ornamentada
        e('div', { style: { margin: '0 16px', height: 1, background: 'linear-gradient(90deg,transparent,' + c.border + '66,transparent)' } }),

        // Texto
        e('div', { style: { padding: '14px 20px 20px' } },
            e('div', { style: {
                fontFamily: "'Cinzel',serif", color: '#d4c4a4',
                fontSize: 14, fontWeight: 700, letterSpacing: 1, marginBottom: 6,
            }}, titulo),
            e('div', { style: { fontSize: 12, color: '#6a5a3a', lineHeight: 1.6 } }, desc)
        ),

        // Badge de notificação
        badge > 0 && e('div', { style: {
            position: 'absolute', top: 12, right: 12,
            background: '#dc2626', color: '#fff',
            borderRadius: '50%', width: 22, height: 22,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontSize: 11, fontWeight: 700,
        }}, badge > 9 ? '9+' : badge),

        // Ornamento fundo
        e('div', { style: {
            height: 4, background: 'linear-gradient(90deg,transparent,' + c.border + '44,transparent)',
            opacity: hover ? 1 : 0.3, transition: 'opacity 0.25s',
        }})
    );
}

function Dashboard({ usuario, onNavegar }) {
    var tier = usuario.tier;
    var [inscricoesPendentes, setInscricoesPendentes] = useState(0);

    useEffect(function() {
        // Verifica inscrições pendentes nas campanhas do mestre
        if (usuario.canMestrar && usuario.campanha_ativa) {
            api('/campanhas/' + usuario.campanha_ativa + '/inscricoes')
                .then(function(data) {
                    if (Array.isArray(data)) {
                        setInscricoesPendentes(data.filter(function(i) { return i.status === 'pendente'; }).length);
                    }
                }).catch(function() {});
        }
    }, []);

    // Define cards por tier
    var cards = [];

    if (tier === 'admin') {
        cards = [
            { icone: '🎨', titulo: 'Landing Page',   desc: 'Edite textos, cores, efeitos e módulos da página inicial.',          cor: 'admin', tela: 'lp_editor' },
            { icone: '📜', titulo: 'Módulos',         desc: 'Importe e gerencie módulos de aventura JSON.',                        cor: 'admin', tela: 'modulos' },
            { icone: '⚔',  titulo: 'Campanhas',       desc: 'Crie campanhas, abra inscrições e inicie partidas.',                  cor: 'tier1', tela: 'campanhas' },
            { icone: '👥', titulo: 'Usuários',         desc: 'Gerencie usuários e seus tiers de acesso.',                           cor: 'admin', tela: 'usuarios' },
            { icone: '🗡', titulo: 'Jogar',            desc: 'Explore campanhas abertas ou entre na sua sessão ativa.',             cor: 'tier3', tela: 'jogar' },
            { icone: '⚙',  titulo: 'Configurações',   desc: 'Chave Groq, ajustes gerais do sistema.',                              cor: 'admin', tela: 'config' },
            { icone: '🔗', titulo: 'WP Admin',         desc: 'Acesse o painel administrativo do WordPress.',                        cor: 'admin', href: usuario.adminUrl },
        ];
    } else if (tier === 'tier1') {
        cards = [
            { icone: '⚔',  titulo: 'Minhas Campanhas', desc: 'Gerencie suas campanhas como Mestre. Crie, abra e conduza sessões.', cor: 'tier1', tela: 'campanhas', badge: inscricoesPendentes },
            { icone: '🗡', titulo: 'Jogar',              desc: 'Participe de campanhas como jogador.',                               cor: 'tier3', tela: 'jogar' },
            { icone: '📜', titulo: 'Módulos',            desc: 'Importe e gerencie módulos de aventura.',                            cor: 'tier1', tela: 'modulos' },
            { icone: '👥', titulo: 'Usuários',           desc: 'Crie e gerencie usuários Tier 2 e Tier 3.',                          cor: 'tier2', tela: 'usuarios' },
        ];
    } else if (tier === 'tier2') {
        cards = [
            { icone: '⚔',  titulo: 'Minhas Campanhas', desc: 'Gerencie suas campanhas como Mestre.',                                cor: 'tier1', tela: 'campanhas', badge: inscricoesPendentes },
            { icone: '🗡', titulo: 'Jogar',              desc: 'Participe de campanhas como jogador.',                               cor: 'tier3', tela: 'jogar' },
            { icone: '👥', titulo: 'Usuários',           desc: 'Convide novos jogadores (Tier 3).',                                  cor: 'tier2', tela: 'usuarios' },
        ];
    } else {
        // tier3
        cards = [
            { icone: '🗡', titulo: 'Aventuras',     desc: 'Veja campanhas abertas e participe da sua próxima aventura.',            cor: 'tier3', tela: 'jogar' },
            { icone: '📋', titulo: 'Meu Personagem',desc: 'Veja e gerencie seu personagem ativo.',                                  cor: 'tier3', tela: 'ficha' },
        ];
    }

    return e('div', { style: { minHeight: '100vh', background: '#0a0704', padding: '80px 32px 48px' } },
        // Header da dashboard
        e('div', { style: { maxWidth: 1100, margin: '0 auto' } },
            e('div', { style: { marginBottom: 48 } },
                e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, letterSpacing: 6, color: '#4a3a2a', marginBottom: 12 } },
                    TIER_LABEL[tier] || tier),
                e('h1', { style: { fontFamily: "'Cinzel Decorative',serif", color: '#c9a84c', fontSize: 'clamp(28px,5vw,52px)', fontWeight: 900, marginBottom: 8 } },
                    'Bem-vindo(a), ' + usuario.nome.split(' ')[0]),
                usuario.personagem && e('div', { style: { color: '#6a5a3a', fontSize: 14, fontFamily: "'Cinzel',serif" } },
                    '⚔ ' + usuario.personagem.nome + ' · ' + usuario.personagem.classe + ' Nível ' + usuario.personagem.nivel
                ),
                !usuario.personagem && tier === 'tier3' && e('div', { style: { color: '#6a5a3a', fontSize: 13 } },
                    'Crie seu personagem e aguarde o Mestre te convidar para uma campanha.'
                )
            ),

            // Grid de cards
            e('div', { style: {
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
                gap: 24,
            }},
                ...cards.map(function(card, i) {
                    return e(DashboardCard, {
                        key: i,
                        icone: card.icone,
                        titulo: card.titulo,
                        desc: card.desc,
                        cor: card.cor,
                        badge: card.badge || 0,
                        onClick: function() {
                            if (card.href) { window.open(card.href, '_blank'); return; }
                            onNavegar(card.tela);
                        }
                    });
                })
            )
        )
    );
}

// ════════════════════════════════════════════
// CAMPANHAS ABERTAS — TELA DO JOGADOR
// ════════════════════════════════════════════
function TelaCampanhasAbertas({ usuario, onEntrarCampanha, onEntrarComoMestre }) {
    var [campanhas, setCampanhas] = useState(null);
    var [loading, setLoading] = useState(true);

    useEffect(function() {
        api('/campanhas/abertas').then(function(data) {
            setCampanhas(Array.isArray(data) ? data : []);
            setLoading(false);
        });
    }, []);

    var fazerInscricao = function(campanha_id) {
        api('/campanhas/' + campanha_id + '/inscrever', 'POST').then(function(r) {
            if (r.sucesso) {
                setCampanhas(function(prev) {
                    return prev.map(function(c) {
                        return c.id === campanha_id ? Object.assign({}, c, { inscricao: 'pendente' }) : c;
                    });
                });
            }
        });
    };

    if (loading) return e(Spinner, { texto: 'Buscando campanhas...' });

    return e('div', { style: { minHeight: '100vh', background: '#0a0704', padding: '80px 24px 48px' } },
        e('div', { style: { maxWidth: 900, margin: '0 auto' } },
            e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, letterSpacing: 6, color: '#4a3a2a', marginBottom: 12 } }, 'AVENTURAS'),
            e('h1', { style: { fontFamily: "'Cinzel Decorative',serif", color: '#c9a84c', fontSize: 'clamp(24px,4vw,42px)', marginBottom: 40 } }, 'Campanhas Abertas'),

            campanhas.length === 0 && e('div', { style: { textAlign: 'center', padding: '80px 24px', color: '#4a3a2a' } },
                e('div', { style: { fontSize: 48, marginBottom: 16, opacity: 0.3 } }, '⏳'),
                e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 14, letterSpacing: 2 } }, 'Nenhuma campanha aberta no momento'),
                e('p', { style: { fontSize: 13, marginTop: 12 } }, 'Aguarde o Mestre abrir uma campanha para participar.')
            ),

            e('div', { style: { display: 'grid', gap: 20 } },
                (campanhas || []).map(function(c) {
                    var inscricao = c.inscricao;
                    return e('div', { key: c.id, style: {
                        background: '#120e04', border: '1px solid #2a1e0a',
                        borderRadius: 14, padding: '24px', display: 'flex', gap: 20, alignItems: 'flex-start',
                    }},
                        c.capa_url
                            ? e('img', { src: c.capa_url, style: { width: 80, height: 80, borderRadius: 8, objectFit: 'cover', flexShrink: 0 } })
                            : e('div', { style: { width: 80, height: 80, borderRadius: 8, background: '#1a1208', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 32, flexShrink: 0 } }, '📜'),
                        e('div', { style: { flex: 1 } },
                            e('div', { style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', fontSize: 16, fontWeight: 700, marginBottom: 4 } }, c.nome),
                            e('div', { style: { fontSize: 12, color: '#6a5a3a', marginBottom: 8 } }, c.sistema + (c.modulo_nome ? ' · ' + c.modulo_nome : '')),
                            c.modulo_desc && e('p', { style: { color: '#7a6a4a', fontSize: 13, lineHeight: 1.6, marginBottom: 12 } }, c.modulo_desc)
                        ),
                        e('div', { style: { flexShrink: 0 } },
                            c.is_mestre && e('button', {
                                onClick: function() { (onEntrarComoMestre || onEntrarCampanha)(c.id); },
                                style: { background: 'linear-gradient(135deg,#1a3a6b,#2563eb)', border: '1px solid #3b82f6', borderRadius: 8, color: '#fff', fontFamily: "'Cinzel',serif", fontSize: 13, fontWeight: 700, padding: '10px 20px', cursor: 'pointer' }
                            }, '⚔ Entrar como Mestre'),
                            !c.is_mestre && !inscricao && e('button', {
                                onClick: function() { fazerInscricao(c.id); },
                                style: { background: 'linear-gradient(135deg,#6b4f10,#c9a84c)', border: 'none', borderRadius: 8, color: '#0a0704', fontFamily: "'Cinzel',serif", fontSize: 13, fontWeight: 700, padding: '10px 20px', cursor: 'pointer' }
                            }, '⚔ Participar'),
                            !c.is_mestre && inscricao === 'pendente' && e('div', { style: { background: '#1a1208', border: '1px solid #c9a84c44', borderRadius: 8, padding: '10px 16px', color: '#c9a84c', fontSize: 12, fontFamily: "'Cinzel',serif", textAlign: 'center' } },
                                '⏳ Aguardando', e('br'), e('span', { style: { fontSize: 10, color: '#6a5a3a' } }, 'aprovação do Mestre')
                            ),
                            !c.is_mestre && inscricao === 'aprovado' && e('button', {
                                onClick: function() { onEntrarCampanha(c.id); },
                                style: { background: '#16a34a22', border: '1px solid #16a34a', borderRadius: 8, color: '#4ade80', fontFamily: "'Cinzel',serif", fontSize: 13, padding: '10px 20px', cursor: 'pointer' }
                            }, '✅ Entrar na Sessão'),
                            !c.is_mestre && inscricao === 'rejeitado' && e('div', { style: { color: '#dc2626', fontSize: 12, padding: '10px 16px' } }, '✕ Não aprovado')
                        )
                    );
                })
            )
        )
    );
}

// ════════════════════════════════════════════
// GESTÃO DE CAMPANHAS (MESTRE)
// ════════════════════════════════════════════
function GestaCampanhas({ usuario, onIniciarSessao }) {
    var [campanhas, setCampanhas] = useState(null);
    var [modulos, setModulos] = useState([]);
    var [criando, setCriando] = useState(false);
    var [form, setForm] = useState({ nome: '', modulo_id: '' });
    var [inscricoesPorCampanha, setInscricoesPorCampanha] = useState({});
    var [inscExpanded, setInscExpanded] = useState({});
    var [loading, setLoading] = useState(true);

    var buscarInscricoes = function(campanha_id) {
        return api('/campanhas/' + campanha_id + '/inscricoes').then(function(data) {
            setInscricoesPorCampanha(function(prev) {
                var n = Object.assign({}, prev);
                n[campanha_id] = Array.isArray(data) ? data : [];
                return n;
            });
        });
    };

    var carregar = function() {
        api('/campanha').then(function(data) {
            var lista = Array.isArray(data) ? data : [];
            setCampanhas(lista);
            setLoading(false);
            // Auto-carrega inscrições de todas campanhas que precisam
            lista.forEach(function(c) {
                if (c.status === 'aberta' || c.status === 'em_andamento') {
                    buscarInscricoes(c.id);
                }
            });
        });
        api('/modulos').then(function(data) {
            setModulos(Array.isArray(data) ? data : []);
        });
    };

    useEffect(function() { carregar(); }, []);

    var criarCampanha = function() {
        if (!form.nome.trim()) return;
        api('/campanha', 'POST', { nome: form.nome, modulo_id: form.modulo_id || null }).then(function() {
            setCriando(false);
            setForm({ nome: '', modulo_id: '' });
            carregar();
        });
    };

    var abrirInscricoes = function(id) {
        api('/campanhas/' + id + '/abrir', 'POST').then(function() { carregar(); });
    };

    var iniciarPartida = function(c) {
        // Muda status para em_andamento e entra na sessão
        api('/campanhas/' + c.id + '/iniciar', 'POST').then(function(r) {
            if (r && r.sucesso) onIniciarSessao(c.id);
        });
    };

    var aprovar = function(insc_id, campanha_id) {
        api('/inscricoes/' + insc_id + '/aprovar', 'POST').then(function() { buscarInscricoes(campanha_id); });
    };

    var rejeitar = function(insc_id, campanha_id) {
        api('/inscricoes/' + insc_id + '/rejeitar', 'POST').then(function() { buscarInscricoes(campanha_id); });
    };

    var excluirCampanha = function(campanha) {
        if (!window.confirm('Excluir a campanha "' + campanha.nome + '"? Esta ação não pode ser desfeita.')) return;
        api('/campanha/' + campanha.id, 'DELETE').then(function(r) {
            if (r && r.sucesso) carregar();
        });
    };

    var toggleInsc = function(id) {
        setInscExpanded(function(prev) {
            var n = Object.assign({}, prev);
            n[id] = !n[id];
            if (n[id] && !inscricoesPorCampanha[id]) buscarInscricoes(id);
            return n;
        });
    };

    if (loading) return e(Spinner, { texto: 'Carregando campanhas...' });

    var STATUS_LABEL = { rascunho: '📝 Rascunho', aberta: '🟢 Aberta', ativa: '⚔ Ativa', em_andamento: '⚔ Em Andamento', encerrada: '✕ Encerrada' };

    return e('div', { style: { minHeight: '100vh', background: '#0a0704', padding: '80px 24px 48px' } },
        e('div', { style: { maxWidth: 900, margin: '0 auto' } },
            e('div', { style: { display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', marginBottom: 40, flexWrap: 'wrap', gap: 16 } },
                e('div', null,
                    e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, letterSpacing: 6, color: '#4a3a2a', marginBottom: 8 } }, 'MESTRE'),
                    e('h1', { style: { fontFamily: "'Cinzel Decorative',serif", color: '#c9a84c', fontSize: 'clamp(24px,4vw,38px)' } }, 'Minhas Campanhas')
                ),
                e('button', {
                    onClick: function() { setCriando(!criando); },
                    style: { background: 'linear-gradient(135deg,#6b4f10,#c9a84c)', border: 'none', borderRadius: 10, color: '#0a0704', fontFamily: "'Cinzel',serif", fontSize: 13, fontWeight: 700, padding: '12px 24px', cursor: 'pointer' }
                }, criando ? '✕ Cancelar' : '+ Nova Campanha')
            ),

            // Form criar
            criando && e('div', { style: { background: '#120e04', border: '1px solid #2a1e0a', borderRadius: 14, padding: 24, marginBottom: 24 } },
                e('div', { style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', marginBottom: 16, fontSize: 14 } }, 'NOVA CAMPANHA'),
                e('input', { className: 'dnd-input', placeholder: 'Nome da campanha...', value: form.nome, onChange: function(ev) { setForm(function(f) { return Object.assign({}, f, { nome: ev.target.value }); }); }, style: { marginBottom: 12 } }),
                e('select', { className: 'dnd-input', value: form.modulo_id, onChange: function(ev) { setForm(function(f) { return Object.assign({}, f, { modulo_id: ev.target.value }); }); }, style: { marginBottom: 16 } },
                    e('option', { value: '' }, '— Selecionar módulo (opcional) —'),
                    ...modulos.map(function(m) { return e('option', { key: m.id, value: m.id }, m.nome); })
                ),
                e('button', { onClick: criarCampanha, style: { background: 'linear-gradient(135deg,#6b4f10,#c9a84c)', border: 'none', borderRadius: 8, color: '#0a0704', fontFamily: "'Cinzel',serif", fontSize: 13, fontWeight: 700, padding: '10px 24px', cursor: 'pointer' } }, 'Criar Campanha')
            ),

            // Lista vazia
            (!campanhas || campanhas.length === 0) && e('div', { style: { textAlign: 'center', padding: '60px 0', color: '#4a3a2a' } },
                e('div', { style: { fontSize: 48, marginBottom: 16, opacity: 0.3 } }, '📜'),
                e('div', { style: { fontFamily: "'Cinzel',serif", letterSpacing: 2, fontSize: 13 } }, 'Nenhuma campanha criada')
            ),

            e('div', { style: { display: 'grid', gap: 16 } },
                (campanhas || []).map(function(c) {
                    var insc      = inscricoesPorCampanha[c.id] || null;
                    var pendentes = insc ? insc.filter(function(i) { return i.status === 'pendente'; }) : [];
                    var aprovados = insc ? insc.filter(function(i) { return i.status === 'aprovado'; }) : [];
                    var expanded  = !!inscExpanded[c.id];

                    return e('div', { key: c.id, style: { background: '#120e04', border: '1px solid #2a1e0a', borderRadius: 14, overflow: 'hidden' } },

                        // ── Cabeçalho do card ──────────────────────────────────────────
                        e('div', { style: { padding: '20px 24px', display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' } },
                            e('div', { style: { flex: 1, minWidth: 0 } },
                                e('div', { style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', fontSize: 16, fontWeight: 700 } }, c.nome),
                                e('div', { style: { fontSize: 12, color: '#6a5a3a', marginTop: 4 } }, STATUS_LABEL[c.status] || c.status)
                            ),

                            // ── Botões por status ──────────────────────────────────────
                            // RASCUNHO: abrir inscrições
                            c.status === 'rascunho' && e('button', {
                                onClick: function() { abrirInscricoes(c.id); },
                                style: { background: '#16a34a22', border: '1px solid #16a34a', color: '#4ade80', borderRadius: 8, padding: '8px 18px', fontSize: 12, fontFamily: "'Cinzel',serif", cursor: 'pointer' }
                            }, '🟢 Abrir Inscrições'),

                            // ABERTA: ver pedidos + iniciar (sempre visível quando aberta)
                            c.status === 'aberta' && e('div', { style: { display: 'flex', gap: 8, flexWrap: 'wrap' } },
                                e('button', {
                                    onClick: function() { toggleInsc(c.id); },
                                    style: { background: '#1a1208', border: '1px solid #c9a84c44', color: '#c9a84c', borderRadius: 8, padding: '8px 14px', fontSize: 12, cursor: 'pointer', position: 'relative' }
                                },
                                    '👥 Pedidos',
                                    pendentes.length > 0 && e('span', { style: { position: 'absolute', top: -6, right: -6, background: '#dc2626', color: '#fff', borderRadius: '50%', width: 18, height: 18, fontSize: 10, display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700 } }, pendentes.length)
                                ),
                                // Botão INICIAR — aparece quando há aprovados OU como ação principal
                                e('button', {
                                    onClick: function() { iniciarPartida(c); },
                                    style: {
                                        background: aprovados.length > 0 ? 'linear-gradient(135deg,#6b4f10,#c9a84c)' : '#1a1208',
                                        border: aprovados.length > 0 ? 'none' : '1px solid #5a4828',
                                        color: aprovados.length > 0 ? '#0a0704' : '#5a4828',
                                        borderRadius: 8, padding: '8px 18px', fontSize: 12,
                                        fontFamily: "'Cinzel',serif", fontWeight: 700, cursor: 'pointer',
                                        opacity: aprovados.length > 0 ? 1 : 0.5,
                                    }
                                }, aprovados.length > 0 ? '⚔ Iniciar Partida' : '⚔ Iniciar (sem jogadores)')
                            ),

                            // EM ANDAMENTO: entrar na sessão
                            c.status === 'em_andamento' && e('div', { style: { display: 'flex', gap: 8 } },
                                e('button', {
                                    onClick: function() { toggleInsc(c.id); },
                                    style: { background: '#1a1208', border: '1px solid #c9a84c44', color: '#c9a84c', borderRadius: 8, padding: '8px 14px', fontSize: 12, cursor: 'pointer' }
                                }, '👥 Jogadores' + (aprovados.length > 0 ? ' (' + aprovados.length + ')' : '')),
                                e('button', {
                                    onClick: function() { onIniciarSessao(c.id); },
                                    style: { background: 'linear-gradient(135deg,#6b4f10,#c9a84c)', border: 'none', color: '#0a0704', borderRadius: 8, padding: '8px 18px', fontSize: 12, fontFamily: "'Cinzel',serif", fontWeight: 700, cursor: 'pointer' }
                                }, '⚔ Entrar na Sessão')
                            ),

                            // Botão excluir — sempre visível
                            e('button', {
                                onClick: function() { excluirCampanha(c); },
                                title: 'Excluir campanha',
                                style: { background: 'none', border: '1px solid rgba(220,38,38,0.3)', borderRadius: 8, color: '#7a3030', padding: '8px 12px', fontSize: 12, cursor: 'pointer', transition: 'all .2s', flexShrink: 0 },
                                onMouseEnter: function(ev) { ev.currentTarget.style.borderColor='#dc2626'; ev.currentTarget.style.color='#dc2626'; ev.currentTarget.style.background='rgba(220,38,38,0.1)'; },
                                onMouseLeave: function(ev) { ev.currentTarget.style.borderColor='rgba(220,38,38,0.3)'; ev.currentTarget.style.color='#7a3030'; ev.currentTarget.style.background='none'; },
                            }, '🗑')
                        ),

                        // ── Lista de inscrições (expansível) ──────────────────────────
                        expanded && e('div', { style: { borderTop: '1px solid #1a1208', padding: '16px 24px' } },
                            insc === null && e('div', { style: { color: '#5a4828', fontSize: 12, textAlign: 'center' } }, 'Carregando...'),
                            insc && insc.length === 0 && e('div', { style: { color: '#5a4828', fontSize: 13, fontStyle: 'italic', padding: '8px 0' } }, 'Nenhum pedido de participação ainda.'),
                            insc && insc.length > 0 && e('div', null,
                                e('div', { style: { fontFamily: "'Cinzel',serif", fontSize: 11, letterSpacing: 2, color: '#4a3a2a', marginBottom: 12 } }, 'PEDIDOS DE PARTICIPAÇÃO'),
                                e('div', { style: { display: 'grid', gap: 8 } },
                                    insc.map(function(i) {
                                        return e('div', { key: i.id, style: { display: 'flex', alignItems: 'center', gap: 12, padding: '8px 12px', background: '#0a0704', borderRadius: 8 } },
                                            i.imagem
                                                ? e('img', { src: i.imagem, style: { width: 32, height: 32, borderRadius: '50%', objectFit: 'cover' } })
                                                : e('div', { style: { width: 32, height: 32, borderRadius: '50%', background: '#1a1208', display: 'flex', alignItems: 'center', justifyContent: 'center' } }, '⚔'),
                                            e('div', { style: { flex: 1 } },
                                                e('div', { style: { color: '#d4b896', fontSize: 13 } }, i.nome),
                                                e('div', { style: { color: '#6a5a3a', fontSize: 11 } }, i.personagem ? i.personagem + ' · ' + i.classe : 'Sem personagem')
                                            ),
                                            i.status === 'pendente' && e('div', { style: { display: 'flex', gap: 6 } },
                                                e('button', { onClick: function() { aprovar(i.id, c.id); }, style: { background: '#16a34a', border: 'none', borderRadius: 6, color: '#fff', padding: '4px 12px', fontSize: 11, cursor: 'pointer' } }, '✓ Aprovar'),
                                                e('button', { onClick: function() { rejeitar(i.id, c.id); }, style: { background: '#dc2626', border: 'none', borderRadius: 6, color: '#fff', padding: '4px 12px', fontSize: 11, cursor: 'pointer' } }, '✕')
                                            ),
                                            i.status === 'aprovado' && e('div', { style: { color: '#4ade80', fontSize: 12 } }, '✅ Aprovado'),
                                            i.status === 'rejeitado' && e('div', { style: { color: '#dc2626', fontSize: 12 } }, '✕ Rejeitado')
                                        );
                                    })
                                )
                            )
                        )
                    );
                })
            )
        )
    );
}

// ════════════════════════════════════════════
// PAINEL UNIFICADO — router interno
// ════════════════════════════════════════════
// ════════════════════════════════════════════
// TELA PERSONAGENS — múltiplos (máx 3) + inventário
// ════════════════════════════════════════════
var TIPO_ITEM_LABEL = { arma:'⚔ Arma', armadura:'🛡 Armadura', pocao:'🧪 Poção', magico:'✨ Mágico', misc:'📦 Misc' };

function Inventario({ personagemId, somenteLeitura }) {
    var [items, setItems]   = useState(null);
    var [form, setForm]     = useState({ nome:'', tipo:'misc', quantidade:1, valor:'', descricao:'' });
    var [adding, setAdding] = useState(false);
    var [salvando, setSalvando] = useState(false);
    var sty = { background:'var(--b3)', border:'1px solid var(--b5)', borderRadius:6, padding:'8px 12px', color:'var(--t1)', fontSize:13 };

    var carregar = function() {
        api('/inventario/' + personagemId).then(function(r){ setItems(Array.isArray(r) ? r : []); }).catch(function(){ setItems([]); });
    };
    useEffect(function(){ if (personagemId) carregar(); }, [personagemId]);

    var remover = function(itemId) {
        api('/inventario/' + personagemId + '/' + itemId, 'DELETE').then(carregar);
    };

    var salvar = function() {
        if (!form.nome.trim()) return;
        setSalvando(true);
        api('/inventario/' + personagemId, 'POST', form).then(function(){
            setForm({ nome:'', tipo:'misc', quantidade:1, valor:'', descricao:'' });
            setAdding(false);
            carregar();
        }).finally(function(){ setSalvando(false); });
    };

    if (!items) return e('div', { style:{ color:'var(--t4)', fontSize:13, padding:'24px 0', textAlign:'center' } }, '⏳ Carregando...');

    var porTipo = {};
    items.forEach(function(it) { var t = it.tipo || 'misc'; if (!porTipo[t]) porTipo[t]=[]; porTipo[t].push(it); });

    return e('div', null,
        e('div', { style:{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:16 } },
            e('div', { className:'hud-col-titulo', style:{ margin:0 } }, '🎒 INVENTÁRIO (' + items.length + ' itens)'),
            !somenteLeitura && e('button', { className:'btn btn-gold', style:{ fontSize:12 }, onClick:function(){ setAdding(!adding); } }, adding ? '✕' : '+ Adicionar')
        ),

        somenteLeitura && e('div', { style:{ background:'rgba(201,168,76,.06)', border:'1px solid rgba(201,168,76,.15)', borderRadius:8, padding:'8px 14px', marginBottom:16, fontSize:12, color:'var(--g4)' } },
            '🔒 Itens são adicionados pelo Mestre durante as sessões.'
        ),

        !somenteLeitura && adding && e('div', { style:{ background:'#0d0a06', border:'1px solid var(--b4)', borderRadius:10, padding:16, marginBottom:16, display:'grid', gridTemplateColumns:'1fr 1fr', gap:10 } },
            e('input', { placeholder:'Nome do item *', value:form.nome,
                onChange:function(ev){ setForm(function(f){ return Object.assign({},f,{nome:ev.target.value}); }); },
                style: Object.assign({}, sty, {gridColumn:'1/-1'}) }),
            e('select', { value:form.tipo,
                onChange:function(ev){ setForm(function(f){ return Object.assign({},f,{tipo:ev.target.value}); }); },
                style: sty },
                Object.keys(TIPO_ITEM_LABEL).map(function(t){ return e('option',{key:t,value:t},TIPO_ITEM_LABEL[t]); })
            ),
            e('input', { placeholder:'Qtd', type:'number', min:1, value:form.quantidade,
                onChange:function(ev){ setForm(function(f){ return Object.assign({},f,{quantidade:parseInt(ev.target.value)||1}); }); },
                style: sty }),
            e('input', { placeholder:'Valor (ex: 50po)', value:form.valor,
                onChange:function(ev){ setForm(function(f){ return Object.assign({},f,{valor:ev.target.value}); }); },
                style: sty }),
            e('textarea', { placeholder:'Descrição (opcional)', value:form.descricao,
                onChange:function(ev){ setForm(function(f){ return Object.assign({},f,{descricao:ev.target.value}); }); },
                rows:2, style: Object.assign({}, sty, {gridColumn:'1/-1', resize:'vertical'}) }),
            e('button', { className:'btn btn-gold', onClick:salvar, disabled:salvando, style:{ gridColumn:'1/-1', fontSize:12 } }, salvando ? '⏳' : '💾 Adicionar ao Inventário')
        ),

        items.length === 0 && !adding && e('div', { style:{ color:'var(--t4)', fontSize:13, fontStyle:'italic', textAlign:'center', padding:'32px 0' } }, 'Nenhum item no inventário. Explore o mundo e adquira equipamentos!'),

        Object.keys(porTipo).map(function(tipo) {
            return e('div', { key:tipo, style:{ marginBottom:16 } },
                e('div', { style:{ fontSize:10, fontFamily:"'Cinzel',serif", letterSpacing:4, color:'var(--g5)', marginBottom:8 } }, (TIPO_ITEM_LABEL[tipo] || tipo).toUpperCase()),
                e('div', { style:{ display:'flex', flexDirection:'column', gap:6 } },
                    porTipo[tipo].map(function(it) {
                        return e('div', { key:it.id, style:{ background:'#0d0a06', border:'1px solid var(--b4)', borderRadius:8, padding:'10px 14px', display:'flex', alignItems:'center', gap:12 } },
                            e('div', { style:{ flex:1, minWidth:0 } },
                                e('div', { style:{ color:'var(--t1)', fontSize:13, fontWeight:600 } }, it.nome + (it.quantidade > 1 ? ' x'+it.quantidade : '')),
                                it.descricao && e('div', { style:{ color:'var(--t4)', fontSize:11, marginTop:2 } }, it.descricao),
                                it.valor && e('div', { style:{ color:'var(--g4)', fontSize:11, marginTop:2 } }, '💰 '+it.valor)
                            ),
                            !somenteLeitura && e('button', { onClick:function(){ remover(it.id); }, style:{ background:'none', border:'none', color:'var(--verm-b)', cursor:'pointer', fontSize:16, padding:'2px 6px', opacity:0.6 }, title:'Remover' }, '🗑')
                        );
                    })
                )
            );
        })
    );
}


function TelaPersonagens({ usuario, onUsuarioAtualizado }) {
    var [personagens,   setPersonagens]   = useState(null);
    var [abaAtiva,      setAbaAtiva]      = useState('personagens');
    var [personagemSel, setPersonagemSel] = useState(null);
    var [ativando,      setAtivando]      = useState(null);
    var [zoomImg,       setZoomImg]       = useState(null);
    var [detalhes,      setDetalhes]      = useState(null); // personagem id para modal de detalhes
    var [fichaData,     setFichaData]     = useState(null); // dados completos carregados

    var [badgeCount, setBadgeCount] = useState(usuario.badge_count || 0);

    var carregar = function() {
        api('/meus-personagens').then(function(r){
            // Nova API retorna {personagens, badge_count}
            var lista = Array.isArray(r) ? r : (r && Array.isArray(r.personagens) ? r.personagens : []);
            if (r && r.badge_count !== undefined) setBadgeCount(r.badge_count);
            setPersonagens(lista);
            if (!personagemSel && lista.length > 0) setPersonagemSel(lista.find(function(p){ return p.ativo; }) || lista[0]);
        });
    };
    useEffect(function(){ carregar(); }, []);

    var ativar = function(id) {
        setAtivando(id);
        api('/personagem/' + id + '/ativar', 'POST').then(function(){
            carregar();
            api('/usuario').then(function(ud){ if (ud) onUsuarioAtualizado(ud); });
        }).finally(function(){ setAtivando(null); });
    };

    var onCriado = function() {
        carregar();
        setAbaAtiva('personagens');
        api('/usuario').then(function(ud){ if (ud) onUsuarioAtualizado(ud); });
    };

    var podecriar = personagens && personagens.length < 5;

    var abrirDetalhes = function(p) {
        setFichaData(null);
        setDetalhes(p);
        api('/personagem/' + p.id).then(function(r){ setFichaData(r); });
    };

    // Modal de ficha completa
    var ModalDetalhes = detalhes && e('div', {
        onClick: function(ev){ if(ev.target===ev.currentTarget){ setDetalhes(null); setFichaData(null); } },
        style:{ position:'fixed', inset:0, zIndex:9998, background:'rgba(0,0,0,.82)', display:'flex', alignItems:'center', justifyContent:'center', padding:'20px' }
    },
        e('div', { style:{ background:'#120e04', border:'1px solid var(--b4)', borderRadius:18, maxWidth:640, width:'100%', maxHeight:'90vh', overflowY:'auto', position:'relative' } },
            // Header
            e('div', { style:{ display:'flex', alignItems:'center', gap:20, padding:'28px 28px 20px', borderBottom:'1px solid var(--b3)' } },
                detalhes.imagem_url
                    ? e('img', { src:detalhes.imagem_url, onClick:function(){ setZoomImg(detalhes.imagem_url); }, style:{ width:80, height:80, borderRadius:'50%', objectFit:'cover', border:'2px solid var(--g4)', cursor:'zoom-in', flexShrink:0 } })
                    : e('div', { style:{ width:80, height:80, borderRadius:'50%', background:'var(--b3)', display:'flex', alignItems:'center', justifyContent:'center', fontSize:28, flexShrink:0 } }, '⚔'),
                e('div', { style:{ flex:1, minWidth:0 } },
                    e('h2', { style:{ fontFamily:"'Cinzel Decorative',serif", color:'var(--g2)', fontSize:20, margin:'0 0 4px' } }, detalhes.nome),
                    e('div', { style:{ color:'var(--t3)', fontFamily:"'Cinzel',serif", fontSize:12, letterSpacing:2 } }, detalhes.classe + ' · ' + detalhes.raca + ' · Nível ' + detalhes.nivel),
                    e('div', { style:{ marginTop:8, display:'flex', gap:12, flexWrap:'wrap' } },
                        e('span', { style:{ fontSize:12, color:'var(--t2)' } }, '❤ ' + (detalhes.hp_atual||0) + '/' + (detalhes.hp_max||0) + ' HP'),
                        e('span', { style:{ fontSize:12, color:'var(--t2)' } }, '🛡 CA ' + (detalhes.ca||10)),
                        e('span', { style:{ fontSize:12, color:'var(--t2)' } }, '⭐ ' + (detalhes.xp||0) + ' XP')
                    )
                ),
                e('button', { onClick:function(){ setDetalhes(null); setFichaData(null); }, style:{ position:'absolute', top:16, right:16, background:'none', border:'none', color:'var(--t4)', fontSize:22, cursor:'pointer', lineHeight:1 } }, '✕')
            ),
            // Corpo
            !fichaData && e('div', { style:{ padding:'40px', textAlign:'center', color:'var(--t4)', fontSize:13 } }, '⏳ Carregando ficha...'),
            fichaData && e('div', { style:{ padding:'24px 28px 28px' } },
                // Atributos
                fichaData.atributos && Object.keys(fichaData.atributos).length > 0 && e('div', { style:{ marginBottom:24 } },
                    e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:10, letterSpacing:4, color:'var(--g5)', marginBottom:12 } }, 'ATRIBUTOS'),
                    e('div', { style:{ display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap:8 } },
                        ['for','des','con','int','sab','car'].map(function(attr) {
                            var val = fichaData.atributos[attr];
                            if (!val) return null;
                            var mod = Math.floor((parseInt(val)-10)/2);
                            var modStr = mod >= 0 ? '+'+mod : ''+mod;
                            var labels = {for:'Força',des:'Destreza',con:'Constituição',int:'Inteligência',sab:'Sabedoria',car:'Carisma'};
                            return e('div', { key:attr, style:{ background:'var(--b3)', border:'1px solid var(--b4)', borderRadius:8, padding:'10px', textAlign:'center' } },
                                e('div', { style:{ fontSize:20, fontWeight:700, color:'var(--g2)', fontFamily:"'Cinzel',serif" } }, val),
                                e('div', { style:{ fontSize:11, color:'var(--t3)', marginTop:2 } }, modStr),
                                e('div', { style:{ fontSize:9, letterSpacing:2, color:'var(--t4)', fontFamily:"'Cinzel',serif", marginTop:4 } }, labels[attr].substring(0,3).toUpperCase())
                            );
                        })
                    )
                ),
                // Backstory
                fichaData.backstory && e('div', { style:{ marginBottom:20 } },
                    e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:10, letterSpacing:4, color:'var(--g5)', marginBottom:8 } }, 'HISTÓRIA DE ORIGEM'),
                    e('p', { style:{ color:'var(--t2)', fontSize:13, lineHeight:1.75, background:'var(--b2)', border:'1px solid var(--b3)', borderRadius:8, padding:'14px 16px', margin:0 } }, fichaData.backstory)
                ),
                // Personalidade / Ideal / Vínculo / Fraqueza
                e('div', { style:{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10 } },
                    [['personalidade','⚡ Personalidade'],['ideal','🎯 Ideal'],['vinculo','🔗 Vínculo'],['fraqueza','⚠ Fraqueza']].map(function(item) {
                        var val = fichaData[item[0]];
                        if (!val) return null;
                        return e('div', { key:item[0], style:{ background:'var(--b2)', border:'1px solid var(--b3)', borderRadius:8, padding:'12px 14px' } },
                            e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:9, letterSpacing:3, color:'var(--g5)', marginBottom:6 } }, item[1]),
                            e('p', { style:{ color:'var(--t2)', fontSize:12, lineHeight:1.6, margin:0 } }, val)
                        );
                    })
                )
            )
        )
    );

    return e('div', { style:{ minHeight:'100vh', background:'#0a0704', paddingTop:60 } },

        // Modal detalhes do personagem
        ModalDetalhes,

        // Modal zoom imagem
        zoomImg && e('div', {
            onClick:function(){ setZoomImg(null); },
            style:{ position:'fixed', inset:0, zIndex:9999, background:'rgba(0,0,0,.88)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'zoom-out' }
        }, e('img', { src:zoomImg, style:{ maxWidth:'90vw', maxHeight:'90vh', borderRadius:12, border:'2px solid var(--g4)', boxShadow:'0 0 60px rgba(201,168,76,.25)' } })),

        e('div', { style:{ maxWidth:800, margin:'0 auto', padding:'32px 24px' } },
            e('div', { style:{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:32 } },
                e('div', null,
                    e('div', { style:{ fontFamily:"'Cinzel',serif", fontSize:11, letterSpacing:6, color:'#4a3a2a', marginBottom:8 } }, 'PERSONAGEM'),
                    e('h1', { style:{ fontFamily:"'Cinzel Decorative',serif", color:'#c9a84c', fontSize:'clamp(22px,4vw,34px)', margin:0 } }, 'Meus Personagens')
                ),
                podecriar && e('button', {
                    className:'btn btn-gold',
                    onClick:function(){ setAbaAtiva('criar'); },
                    style:{ fontSize:13 },
                }, '+ Novo Personagem (' + (personagens ? personagens.length : 0) + '/5)')
            ),

            // Tabs
            e('div', { style:{ display:'flex', gap:2, borderBottom:'1px solid var(--b3)', marginBottom:24 } },
                [['personagens','⚔ Personagens'], ['inventario','🎒 Inventário'], ['conquistas','🏆 Conquistas']].map(function(tab){
                    return e('button', {
                        key:tab[0],
                        onClick:function(){ setAbaAtiva(tab[0]); },
                        style:{
                            fontFamily:"'Cinzel',serif", fontSize:11, letterSpacing:2, padding:'8px 18px',
                            background:'none', border:'none', cursor:'pointer',
                            color: abaAtiva===tab[0] ? 'var(--g2)' : 'var(--t4)',
                            borderBottom: abaAtiva===tab[0] ? '2px solid var(--g2)' : '2px solid transparent',
                            marginBottom:-1,
                        }
                    }, tab[1]);
                })
            ),

            // ABA: CRIAR
            abaAtiva === 'criar' && e('div', null,
                e('button', { className:'btn btn-ghost', style:{ fontSize:12, marginBottom:16 }, onClick:function(){ setAbaAtiva('personagens'); } }, '← Voltar'),
                e(CriacaoPersonagem, { usuario:usuario, onCriado:onCriado })
            ),

            // ABA: CONQUISTAS
            abaAtiva === 'conquistas' && e(TelaConquistas, { usuario: usuario }),

            // ABA: PERSONAGENS
            abaAtiva === 'personagens' && e('div', null,
                !personagens && e('div', { style:{ textAlign:'center', padding:40, color:'var(--t4)' } }, '⏳ Carregando...'),
                personagens && personagens.length === 0 && e('div', { style:{ textAlign:'center', padding:'60px 24px' } },
                    e('div', { style:{ fontSize:48, marginBottom:16, opacity:.25 } }, '⚔'),
                    e('div', { style:{ fontFamily:"'Cinzel',serif", color:'var(--g4)', fontSize:16, marginBottom:12 } }, 'Nenhum personagem criado'),
                    e('p', { style:{ color:'var(--t4)', fontSize:14, marginBottom:24 } }, 'Crie seu herói e embarque na aventura!'),
                    e('button', { className:'btn btn-gold', onClick:function(){ setAbaAtiva('criar'); } }, '+ Criar Personagem')
                ),
                personagens && personagens.length > 0 && e('div', { style:{ display:'flex', flexDirection:'column', gap:16 } },
                    personagens.map(function(p) {
                        var hpPct = p.hp_max > 0 ? Math.round(p.hp_atual/p.hp_max*100) : 100;
                        var hpCor = hpPct > 50 ? 'var(--verde-b)' : hpPct > 25 ? 'var(--ambar-b)' : 'var(--verm-b)';
                        var isAtivo = !!p.ativo;
                        return e('div', {
                            key:p.id,
                            style:{
                                background: isAtivo ? 'rgba(201,168,76,.06)' : '#120e04',
                                border:'1px solid ' + (isAtivo ? 'rgba(201,168,76,.35)' : '#2a1e0a'),
                                borderRadius:14, padding:'20px 24px',
                                display:'flex', gap:20, alignItems:'flex-start',
                            }
                        },
                            // Foto com zoom
                            e('div', { style:{ flexShrink:0, position:'relative' } },
                                p.imagem_url
                                    ? e('img', {
                                        src:p.imagem_url,
                                        onClick:function(){ setZoomImg(p.imagem_url); },
                                        style:{ width:80, height:80, borderRadius:'50%', objectFit:'cover', border:'2px solid ' + (isAtivo ? 'var(--g2)' : 'var(--b5)'), cursor:'zoom-in', display:'block' },
                                        title:'Clique para ampliar'
                                    })
                                    : e('div', { style:{ width:80, height:80, borderRadius:'50%', background:'var(--b3)', display:'flex', alignItems:'center', justifyContent:'center', fontSize:28, border:'2px solid var(--b5)' } }, '⚔'),
                                isAtivo && e('div', { style:{ position:'absolute', bottom:-2, right:-2, background:'var(--g2)', borderRadius:'50%', width:20, height:20, display:'flex', alignItems:'center', justifyContent:'center', fontSize:10, color:'#0a0704', fontWeight:700 } }, '★')
                            ),
                            // Info
                            e('div', { style:{ flex:1, minWidth:0 } },
                                e('div', { style:{ display:'flex', alignItems:'center', gap:8, marginBottom:4, flexWrap:'wrap' } },
                                    e('span', { style:{ fontFamily:"'Cinzel Decorative',serif", color:'var(--g2)', fontSize:17 } }, p.nome),
                                    isAtivo && e('span', { style:{ background:'rgba(201,168,76,.15)', border:'1px solid rgba(201,168,76,.3)', borderRadius:4, padding:'1px 8px', fontSize:9, fontFamily:"'Cinzel',serif", letterSpacing:2, color:'var(--g3)' } }, 'ATIVO')
                                ),
                                e('div', { style:{ color:'var(--t3)', fontFamily:"'Cinzel',serif", fontSize:12, letterSpacing:2, marginBottom:10 } }, p.classe + ' · ' + p.raca + ' · Nível ' + p.nivel),
                                e('div', { style:{ display:'flex', alignItems:'center', gap:8 } },
                                    e('span', { style:{ fontSize:12, color:hpCor, fontFamily:"'Cinzel',serif", fontWeight:700 } }, (p.hp_atual||0) + '/' + (p.hp_max||0) + ' HP'),
                                    e('div', { style:{ flex:1, height:4, background:'var(--b4)', borderRadius:2, overflow:'hidden' } },
                                        e('div', { style:{ width:hpPct+'%', height:'100%', background:hpCor, borderRadius:2 } })
                                    )
                                )
                            ),
                            // Ações
                            e('div', { style:{ display:'flex', flexDirection:'column', gap:8, flexShrink:0 } },
                                !isAtivo && e('button', {
                                    className:'btn btn-gold',
                                    style:{ fontSize:11 },
                                    disabled:ativando===p.id,
                                    onClick:function(){ ativar(p.id); }
                                }, ativando===p.id ? '⏳' : '★ Ativar'),
                                e('button', {
                                    className:'btn btn-ghost',
                                    style:{ fontSize:11 },
                                    onClick:function(){ abrirDetalhes(p); }
                                }, '📋 Ver Ficha'),
                                e('button', {
                                    className:'btn btn-ghost',
                                    style:{ fontSize:11 },
                                    onClick:function(){ setPersonagemSel(p); setAbaAtiva('inventario'); }
                                }, '🎒 Inventário')
                            )
                        );
                    })
                )
            ),

            // ABA: INVENTÁRIO
            abaAtiva === 'inventario' && e('div', null,
                personagens && personagens.length > 1 && e('div', { style:{ display:'flex', gap:8, marginBottom:20, flexWrap:'wrap' } },
                    personagens.map(function(p){
                        var sel = personagemSel?.id === p.id;
                        return e('button', {
                            key:p.id,
                            onClick:function(){ setPersonagemSel(p); },
                            style:{ fontFamily:"'Cinzel',serif", fontSize:11, padding:'6px 14px', borderRadius:6, border:'1px solid', cursor:'pointer', background: sel ? 'rgba(201,168,76,.12)' : 'transparent', borderColor: sel ? 'var(--g2)' : 'var(--b5)', color: sel ? 'var(--g2)' : 'var(--t3)' }
                        }, p.nome);
                    })
                ),
                personagemSel
                    ? e(Inventario, { personagemId: personagemSel.id, somenteLeitura: !usuario.isMestre && !usuario.isAdmin })
                    : e('div', { style:{ color:'var(--t4)', textAlign:'center', padding:40 } }, 'Selecione um personagem acima.')
            )
        )
    );
}

function PainelApp({ usuario: usuarioInicial }) {
    var [tela, setTela]       = useState('dashboard');
    var [usuario, setUsuario] = useState(usuarioInicial);

    var fazerLogout = function() {
        api('/logout', 'POST').then(function(r) {
            window.location.href = r.redirect || C.homeUrl || '/';
        });
    };

    var navHeader = e('div', {
        style: {
            position: 'fixed', top: 0, right: 0, left: 0, zIndex: 1000,
            background: 'linear-gradient(180deg,rgba(10,7,4,0.97),rgba(10,7,4,0.85))',
            backdropFilter: 'blur(12px)',
            borderBottom: '1px solid rgba(201,168,76,0.15)',
            display: 'flex', alignItems: 'center', padding: '0 24px', height: 60, gap: 12,
        }
    },
        // Logo / Home
        e('button', {
            onClick: function() { setTela('dashboard'); },
            style: { background: 'none', border: 'none', fontFamily: "'Cinzel Decorative',serif", color: '#c9a84c', fontSize: 16, fontWeight: 900, cursor: 'pointer', flex: 1 }
        }, '⚔ DnD Master'),

        // Breadcrumb de tela atual
        tela !== 'dashboard' && e('div', { style: { fontSize: 11, color: '#4a3a2a', fontFamily: "'Cinzel',serif", letterSpacing: 2 } },
            '← ', e('button', { onClick: function() { setTela('dashboard'); }, style: { background: 'none', border: 'none', color: '#6a5a3a', cursor: 'pointer', fontSize: 11, fontFamily: "'Cinzel',serif" } }, 'Dashboard')
        ),

        // Personagem (se tiver)
        usuario.personagem && e('div', { style: { display: 'flex', alignItems: 'center', gap: 8 } },
            usuario.personagem.imagem
                ? e('img', { src: usuario.personagem.imagem, style: { width: 32, height: 32, borderRadius: '50%', objectFit: 'cover', border: '1px solid #c9a84c33' } })
                : e('div', { style: { width: 32, height: 32, borderRadius: '50%', background: '#1a1208', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 14 } }, '⚔'),
            e('div', null,
                e('div', { style: { fontFamily: "'Cinzel',serif", color: '#c9a84c', fontSize: 11 } }, usuario.personagem.nome + ' Nv' + usuario.personagem.nivel),
                e('div', { style: { fontSize: 10, color: '#4a3a2a' } }, usuario.nome)
            )
        ),
        !usuario.personagem && e('div', { style: { fontFamily: "'Cinzel',serif", color: '#4a3a2a', fontSize: 12 } }, usuario.nome),

        // Badge counter no header
        e('div', {
            onClick: function() { setTela('ficha'); },
            style: { display:'flex', alignItems:'center', gap:5, cursor:'pointer', padding:'4px 10px', borderRadius:20, border:'1px solid #2a1e0a', transition:'border-color .2s' },
            title: 'Minhas Conquistas',
        },
            e('span', { style: { fontSize: 14 } }, '🏅'),
            e('span', { style: { fontFamily:"'Cinzel',serif", fontSize: 11, color:'#c9a84c', minWidth:12 } },
                usuario.badge_count || 0
            )
        ),

        e('button', {
            onClick: fazerLogout,
            style: { background: 'none', border: '1px solid #2a1e0a', borderRadius: 6, color: '#4a3a2a', fontFamily: "'Cinzel',serif", fontSize: 11, padding: '5px 12px', cursor: 'pointer', letterSpacing: 1 }
        }, 'SAIR')
    );

    var renderTela = function() {
        if (tela === 'dashboard') return e(Dashboard, { usuario, onNavegar: setTela });
        if (tela === 'campanhas') return e(GestaCampanhas, { usuario, onIniciarSessao: function(id) {
            setUsuario(function(u) { return Object.assign({}, u, { campanha_ativa: id }); });
            setTela('sessao_mestre');
        }});
        if (tela === 'jogar') return e(TelaCampanhasAbertas, { usuario,
            onEntrarCampanha: function(id) {
                setUsuario(function(u) { return Object.assign({}, u, { campanha_ativa: id }); });
                setTela('sessao_jogador');
            },
            onEntrarComoMestre: function(id) {
                // Persiste no servidor ANTES de trocar de tela para evitar que o
                // useEffect de PainelMestre sobrescreva campanha_ativa com null
                api('/mestre/ativar-por-campanha', 'POST', { campanha_id: id }).then(function(res) {
                    if (res && res.sucesso) {
                        setUsuario(function(u) { return Object.assign({}, u, { campanha_ativa: id }); });
                        setTela('sessao_mestre');
                    } else {
                        // Fallback: tenta mesmo assim (pode ser race condition de permissão)
                        setUsuario(function(u) { return Object.assign({}, u, { campanha_ativa: id }); });
                        setTela('sessao_mestre');
                    }
                }).catch(function() {
                    setUsuario(function(u) { return Object.assign({}, u, { campanha_ativa: id }); });
                    setTela('sessao_mestre');
                });
            },
        });
        if (tela === 'sessao_mestre')  return e(PainelMestre, { usuario });
        if (tela === 'sessao_jogador') return e(TelaAventura, { usuario });
        if (tela === 'ficha') return e(TelaPersonagens, { usuario, onUsuarioAtualizado: function(ud){ setUsuario(function(u){ return Object.assign({},u,ud); }); } });
        if (tela === 'modulos') return e('div', { style: { paddingTop: 60 } }, e(GestaoModulos));
        if (tela === 'usuarios') return e('div', { style: { paddingTop: 60 } }, e(GestaoJogadores));
        if (tela === 'lp_editor') { window.open(C.adminUrl + 'admin.php?page=dnd-master-lp', '_blank'); return e(Dashboard, { usuario, onNavegar: setTela }); }
        return e(Dashboard, { usuario, onNavegar: setTela });
    };

    // Telas de sessão gerenciam seu próprio layout/scroll interno
    var isSessao = tela === 'sessao_mestre' || tela === 'sessao_jogador';

    return e('div', { style: { display:'flex', flexDirection:'column', height:'100%', overflow:'hidden' } },
        !isSessao && navHeader,
        e(AchievementManager, { usuario: usuario }),
        isSessao
            ? e('div', { style: { flex:1, overflow:'hidden' } }, renderTela())
            : e('div', { className:'dnd-scroll-page', style: { top:60 } }, renderTela())
    );
}

// ════════════════════════════════════════════
// APP ROOT
// ════════════════════════════════════════════
function App() {
    var [showLogin, setShowLogin] = useState(false);
    var pagina  = C.pagina;
    var usuario = C.usuario;

    var fazerLogout = function() {
        api('/logout', 'POST').then(function(r) {
            window.location.href = r.redirect || C.homeUrl || '/';
        });
    };

    // Painel unificado (pós-login)
    if (pagina === 'painel' && usuario) return e(PainelApp, { usuario });

    // Home / LP
    return e('div', null,
        // Header logado na LP
        usuario && e(HeaderLogado, { usuario, onSair: fazerLogout }),
        // Espaçador quando header logado está presente
        usuario && e('div', { style: { height: 64 } }),
        e(LandingPage, { onEntrar: function() {
            if (usuario) { window.location.href = C.painelUrl || '/dnd-painel'; }
            else setShowLogin(true);
        }}),
        showLogin && e(LoginModal, { fechar: function() { setShowLogin(false); } })
    );
}

// ── Mount ─────────────────────────────────────────────────────────────────────
const container = document.getElementById('dnd-root');
if (container) {
    const root = ReactDOM.createRoot ? ReactDOM.createRoot(container) : null;
    if (root) root.render(e(App));
    else ReactDOM.render(e(App), container);
}

})();
