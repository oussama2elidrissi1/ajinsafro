{{--
    Styles de l'editeur d'offre Hajj & Omra.
    Portee volontairement limitee au conteneur .ho-editor : aucun style global n'est
    modifie, et le passage en RTL ne bascule jamais toute la page.
--}}
<style>
    .ho-editor .ho-steps {
        display: flex;
        gap: .25rem;
        overflow-x: auto;
        border-bottom: 1px solid var(--bs-border-color);
        padding-bottom: 0;
    }
    .ho-editor .ho-step {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .6rem .9rem;
        border: 0;
        background: none;
        color: var(--bs-secondary-color);
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        font-size: .875rem;
    }
    .ho-editor .ho-step:hover { color: var(--bs-body-color); }
    .ho-editor .ho-step.active {
        color: var(--bs-primary);
        border-bottom-color: var(--bs-primary);
        font-weight: 500;
    }
    .ho-editor .ho-step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 50%;
        background: var(--bs-secondary-bg);
        font-size: .72rem;
    }
    .ho-editor .ho-step.active .ho-step-num { background: var(--bs-primary); color: #fff; }
    .ho-editor .ho-step.has-error .ho-step-num { background: var(--bs-danger); color: #fff; }

    .ho-editor .ho-panel { display: none; padding-top: 1.25rem; }
    .ho-editor .ho-panel.active { display: block; }

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
        font-size: .68rem;
        padding: .1rem .4rem;
        border-radius: .25rem;
        background: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
    }

    /* --- Lignes repetables --- */
    .ho-editor .ho-row {
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        padding: .85rem;
        margin-bottom: .65rem;
        background: var(--bs-body-bg);
    }
    .ho-editor .ho-row.is-removed { display: none; }
    .ho-editor .ho-handle { cursor: grab; color: var(--bs-secondary-color); }
    .ho-editor .gu-mirror { opacity: .85; }

    /* --- Galerie --- */
    .ho-editor .ho-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .65rem;
    }
    .ho-editor .ho-thumb {
        position: relative;
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        overflow: hidden;
        aspect-ratio: 4 / 3;
        background: var(--bs-secondary-bg);
    }
    .ho-editor .ho-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ho-editor .ho-thumb .ho-thumb-remove {
        position: absolute; top: .3rem; right: .3rem;
        border: 0; border-radius: .25rem;
        background: rgba(0,0,0,.55); color: #fff;
        width: 1.5rem; height: 1.5rem; line-height: 1;
    }
    .ho-editor .ho-dropzone {
        border: 1px dashed var(--bs-border-color);
        border-radius: .5rem;
        padding: 1.5rem;
        text-align: center;
        color: var(--bs-secondary-color);
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .ho-editor .ho-dropzone.dragover { border-color: var(--bs-primary); background: var(--bs-secondary-bg); }
    .ho-editor .ho-main-preview { max-height: 190px; border-radius: .5rem; object-fit: cover; }

    /* --- Barre d'actions collante --- */
    .ho-actionbar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        padding: .75rem 0;
        margin-top: 1.25rem;
    }
</style>
