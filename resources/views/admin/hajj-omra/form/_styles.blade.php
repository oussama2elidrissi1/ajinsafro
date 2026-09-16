{{--
    Styles de l'editeur d'offre Hajj & Omra.
    Portee volontairement limitee au conteneur .ho-editor : aucun style global n'est
    modifie, et le passage en RTL ne bascule jamais toute la page.

    Les jetons ci-dessous reprennent la maquette « Offre - Editeur 8 etapes » : les
    classes Bootstrap utilisees par les huit panneaux sont redefinies ici, de sorte que
    chaque etape adopte la charte sans dupliquer de styles dans les gabarits.
--}}
<style>
    .ho-editor {
        --ho-ink: #10263d;
        --ho-ink-soft: #274a68;
        --ho-muted: #5b7690;
        --ho-label: #41627f;
        --ho-line: #dce7f2;
        --ho-line-soft: #e3ecf5;
        --ho-tint: #f1f6fb;
        --ho-tint-soft: #f8fbfe;
        --ho-primary: #144e8c;
        --ho-primary-dark: #0f3d70;
        --ho-accent: #f5821f;
        --ho-ok-bg: #e8f6f1;
        --ho-ok-fg: #0f6b52;
        --ho-warn-bg: #fdf6ea;
        --ho-warn-fg: #8a5a00;
        --ho-warn-line: #f4e3c4;
        --ho-danger: #c0392b;
        --ho-mono: ui-monospace, "JetBrains Mono", "SFMono-Regular", Menlo, Consolas, monospace;
        color: var(--ho-ink);
    }
    .ho-editor .ho-mono { font-family: var(--ho-mono); }

    /* --- En-tete de l'offre --- */
    .ho-editor .ho-head {
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .ho-editor .ho-head__main { flex: 1 1 400px; min-width: 0; }
    .ho-editor .ho-head__facts {
        display: flex;
        gap: .85rem;
        flex-wrap: wrap;
        font-size: .8rem;
        color: #4d6a85;
        margin: 0;
    }
    .ho-editor .ho-head__facts .ho-sep { color: #c9dcee; }
    .ho-editor .ho-head__side { flex: 0 1 300px; min-width: 250px; }
    .ho-editor .ho-progress {
        height: 6px;
        border-radius: 6px;
        background: #eef4fa;
        overflow: hidden;
    }
    .ho-editor .ho-progress > span {
        display: block;
        height: 6px;
        border-radius: 6px;
        background: linear-gradient(90deg, var(--ho-primary), #3f93d4);
    }
    .ho-editor .ho-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--ho-accent); flex: none;
    }

    /* --- Navigation par etapes --- */
    .ho-editor .ho-steps {
        display: flex;
        gap: .25rem;
        overflow-x: auto;
        border-bottom: 1px solid var(--ho-line);
        padding-bottom: 0;
    }
    .ho-editor .ho-step {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .62rem .95rem .8rem;
        border: 0;
        background: none;
        color: #7d94a9;
        border-bottom: 2.5px solid transparent;
        white-space: nowrap;
        font-size: .82rem;
    }
    .ho-editor .ho-step:hover { color: var(--ho-ink); }
    .ho-editor .ho-step.is-done { color: var(--ho-label); }
    .ho-editor .ho-step.active {
        color: var(--ho-ink);
        border-bottom-color: var(--ho-primary);
        font-weight: 600;
    }
    .ho-editor .ho-step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 50%;
        border: 1px solid var(--ho-line);
        background: #fff;
        font-family: var(--ho-mono);
        font-size: .66rem;
    }
    .ho-editor .ho-step.is-done .ho-step-num { background: var(--ho-ok-bg); color: var(--ho-ok-fg); border-color: #d8ede5; }
    .ho-editor .ho-step.active .ho-step-num { background: var(--ho-primary); color: #fff; border-color: var(--ho-primary); }
    .ho-editor .ho-step.has-error .ho-step-num { background: var(--ho-danger); color: #fff; border-color: var(--ho-danger); }

    .ho-editor .ho-panel { display: none; padding-top: 1.25rem; }
    .ho-editor .ho-panel.active { display: block; }

    /* --- Sections --- */
    .ho-editor .ho-card {
        background: #fff;
        border: 1px solid var(--ho-line-soft);
        border-radius: 16px;
        padding: 1.25rem clamp(1rem, 2vw, 1.5rem);
        margin-bottom: 1rem;
    }
    .ho-editor .ho-eyebrow {
        font-size: .68rem;
        letter-spacing: .13em;
        text-transform: uppercase;
        color: var(--ho-muted);
        margin-bottom: .9rem;
    }
    .ho-editor .ho-card__head {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .ho-editor .ho-card__head > div:first-child { flex: 1 1 300px; min-width: 0; }
    .ho-editor .ho-card__head p { margin: .3rem 0 0; font-size: .8rem; color: #4d6a85; line-height: 1.5; max-width: 62ch; }

    /* --- Champs : la charte s'applique aux classes Bootstrap deja en place --- */
    .ho-editor .form-label,
    .ho-editor label.form-label { font-size: .78rem; color: var(--ho-label); margin-bottom: .35rem; }
    .ho-editor .form-control,
    .ho-editor .form-select {
        border: 1px solid var(--ho-line);
        border-radius: 10px;
        padding: .62rem .75rem;
        font-size: .84rem;
        color: var(--ho-ink);
    }
    .ho-editor .form-control-sm, .ho-editor .form-select-sm { border-radius: 8px; padding: .48rem .6rem; font-size: .8rem; }
    .ho-editor .form-control:focus,
    .ho-editor .form-select:focus {
        border-color: var(--ho-accent);
        box-shadow: 0 0 0 3px rgba(245, 130, 31, .16);
    }
    .ho-editor input[type="number"],
    .ho-editor input[type="date"] { font-family: var(--ho-mono); }
    .ho-editor .form-text { font-size: .72rem; color: var(--ho-muted); }
    .ho-editor .form-check-input:checked { background-color: var(--ho-primary); border-color: var(--ho-primary); }
    .ho-editor .input-group-text { background: var(--ho-tint); border-color: var(--ho-line); color: var(--ho-label); font-size: .76rem; }

    .ho-editor .btn-outline-primary {
        color: var(--ho-primary);
        border-color: #cfe0f0;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        padding: .45rem .9rem;
    }
    .ho-editor .btn-outline-primary:hover { background: var(--ho-tint); color: var(--ho-primary-dark); border-color: #cfe0f0; }
    .ho-editor .btn-outline-secondary { border-radius: 999px; font-size: .78rem; border-color: var(--ho-line); color: var(--ho-label); }
    .ho-editor .btn-outline-danger { border-radius: 999px; font-size: .78rem; color: var(--ho-danger); border-color: #f3d3ce; }
    .ho-editor .btn-outline-danger:hover { background: #fdeeec; color: var(--ho-danger); border-color: #f3d3ce; }

    .ho-editor .ho-pill {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .78rem;
        color: var(--ho-label);
        background: #f6fafd;
        border: 1px solid #e6eff8;
        border-radius: 999px;
        padding: .4rem .8rem;
        white-space: nowrap;
    }
    .ho-editor .ho-badge {
        font-size: .68rem;
        font-weight: 600;
        border-radius: 999px;
        padding: .2rem .55rem;
        background: var(--ho-ok-bg);
        color: var(--ho-ok-fg);
    }
    .ho-editor .ho-badge--warm { background: var(--ho-warn-bg); color: var(--ho-warn-fg); }

    /* --- Bascule de langue --- */
    .ho-editor [data-lang-pane="ar"] { display: none; }
    .ho-editor.lang-ar [data-lang-pane="fr"] { display: none; }
    .ho-editor.lang-ar [data-lang-pane="ar"] { display: block; }

    /* RTL cible : seuls les champs arabes basculent, jamais la mise en page globale.
       Les nombres, prix et dates restent dans des champs LTR dedies. */
    .ho-editor [data-lang-pane="ar"] input[type="text"],
    .ho-editor [data-lang-pane="ar"] textarea {
        direction: rtl;
        text-align: right;
        font-family: "Segoe UI", Tahoma, "Noto Naskh Arabic", "Traditional Arabic", sans-serif;
    }
    .ho-editor .ho-lang-badge {
        font-size: .62rem;
        font-family: var(--ho-mono);
        padding: .1rem .35rem;
        border-radius: .25rem;
        background: var(--ho-tint);
        color: var(--ho-muted);
    }

    /* --- Lignes repetables --- */
    .ho-editor .ho-row {
        border: 1px solid var(--ho-line-soft);
        border-radius: 13px;
        padding: .95rem;
        margin-bottom: .65rem;
        background: #fbfdff;
    }
    .ho-editor .ho-row.is-removed { display: none; }
    .ho-editor .ho-handle { cursor: grab; color: #a9bfd3; }
    .ho-editor .gu-mirror { opacity: .85; }
    .ho-editor .progress { background: #e6eff8; }
    .ho-editor .progress-bar { background: var(--ho-primary); }
    .ho-editor .table > :not(caption) > * > * { border-color: #f2f7fb; }

    /* --- Accordeons (groupes de tarifs, formules) --- */
    .ho-editor .ho-acc {
        border: 1px solid var(--ho-line-soft);
        border-radius: 13px;
        overflow: hidden;
        background: #fff;
        margin-bottom: .7rem;
    }
    .ho-editor .ho-acc.is-open { border-color: #cfe0f0; background: var(--ho-tint-soft); }
    .ho-editor .ho-acc__head {
        display: flex;
        align-items: center;
        gap: .8rem;
        flex-wrap: wrap;
        width: 100%;
        border: 0;
        background: none;
        text-align: start;
        padding: .85rem 1rem;
    }
    .ho-editor .ho-acc__title { font-size: .88rem; font-weight: 600; min-width: 0; }
    .ho-editor .ho-acc__sub { display: block; margin-top: .15rem; font-size: .75rem; color: #4d6a85; font-weight: 400; }
    .ho-editor .ho-acc__end { margin-inline-start: auto; display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; }
    .ho-editor .ho-acc__from { font-size: .78rem; white-space: nowrap; }
    .ho-editor .ho-acc__chevron { color: #7d94a9; transition: transform .15s ease; }
    .ho-editor .ho-acc.is-open .ho-acc__chevron { transform: rotate(180deg); }
    .ho-editor .ho-acc__body { border-top: 1px solid #e9f0f7; background: #fff; padding: 1rem; }
    .ho-editor .ho-acc:not(.is-open) .ho-acc__body { display: none; }
    .ho-editor .ho-acc__tag {
        width: 1.6rem; height: 1.6rem; border-radius: 8px; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: var(--ho-mono); font-size: .7rem;
        background: #eaf2fa; color: var(--ho-primary);
    }

    /* --- Galerie --- */
    .ho-editor .ho-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .65rem;
    }
    .ho-editor .ho-thumb {
        position: relative;
        border: 1px solid var(--ho-line);
        border-radius: 11px;
        overflow: hidden;
        aspect-ratio: 4 / 3;
        background: var(--ho-tint);
    }
    .ho-editor .ho-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ho-editor .ho-thumb .ho-thumb-remove {
        position: absolute; top: .3rem; right: .3rem;
        border: 0; border-radius: .25rem;
        background: rgba(0,0,0,.55); color: #fff;
        width: 1.5rem; height: 1.5rem; line-height: 1;
    }
    .ho-editor .ho-dropzone {
        border: 1.5px dashed #c3d9ec;
        border-radius: 14px;
        padding: 1.5rem;
        text-align: center;
        color: var(--ho-muted);
        background: var(--ho-tint-soft);
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .ho-editor .ho-dropzone.dragover { border-color: var(--ho-primary); background: var(--ho-tint); }
    .ho-editor .ho-main-preview { max-height: 190px; border-radius: 12px; object-fit: cover; }

    /* --- Panneau lateral de l'etape 1 --- */
    .ho-editor .ho-preview-card {
        background: #0f3557;
        color: #fff;
        border-radius: 16px;
        padding: 1.25rem;
    }
    .ho-editor .ho-preview-card .ho-eyebrow { color: #9dc0dd; }
    .ho-editor .ho-preview-card__inner {
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.14);
        border-radius: 12px;
        padding: .85rem;
    }
    .ho-editor .ho-preview-card__media {
        height: 74px; border-radius: 8px; background: rgba(255,255,255,.09);
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; color: #9dc0dd; overflow: hidden;
    }
    .ho-editor .ho-preview-card__media img { width: 100%; height: 100%; object-fit: cover; }
    .ho-editor .ho-preview-card__price { font-family: var(--ho-mono); font-size: 1.2rem; color: #ffb46b; }
    .ho-editor .ho-todo {
        display: flex; gap: .65rem; align-items: flex-start;
        padding: .65rem 0; border-bottom: 1px solid #f0f5fa;
        font-size: .8rem; color: var(--ho-ink-soft); line-height: 1.45;
    }
    .ho-editor .ho-todo:last-child { border-bottom: 0; }
    .ho-editor .ho-todo__mark {
        width: 1.1rem; height: 1.1rem; border-radius: 50%; flex: none; margin-top: .1rem;
        display: flex; align-items: center; justify-content: center; font-size: .66rem;
        background: var(--ho-warn-bg); color: var(--ho-warn-fg);
    }
    .ho-editor .ho-todo.is-done .ho-todo__mark { background: var(--ho-ok-bg); color: var(--ho-ok-fg); }

    /* --- Apercu moteur de recherche --- */
    .ho-editor .ho-serp {
        background: var(--ho-tint-soft);
        border: 1px solid #e6eff8;
        border-radius: 12px;
        padding: .9rem 1rem;
    }
    .ho-editor .ho-serp__url { font-family: var(--ho-mono); font-size: .72rem; color: #1a7f4b; }
    .ho-editor .ho-serp__title { font-size: 1rem; color: #1a49b8; margin-top: .2rem; }
    .ho-editor .ho-serp__desc { font-size: .78rem; color: #4d6a85; line-height: 1.5; margin: .2rem 0 0; }

    /* --- Barre d'actions collante --- */
    .ho-actionbar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(8px);
        border-top: 1px solid var(--ho-line);
        padding: .7rem 0;
        margin-top: 1.25rem;
    }
    .ho-actionbar .btn { border-radius: 999px; font-size: .8rem; font-weight: 600; }
</style>
