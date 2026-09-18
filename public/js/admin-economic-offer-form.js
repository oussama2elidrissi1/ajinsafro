/*
 * Formulaire d'offre Formule economique.
 *
 * Confort de saisie uniquement : sans JavaScript, le formulaire reste
 * complet et envoie les memes champs. Les pastilles de prestations ont pour
 * source de verite un textarea cache, qui conserve donc les valeurs
 * existantes meme si ce script ne s'execute pas.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-oef-form]');
    if (!form) {
        return;
    }

    var racine = form.closest('form') || form;

    // ── Compteurs de caracteres ───────────────────────────────────────────
    function majCompteur(champ) {
        var cible = document.getElementById(champ.getAttribute('data-oef-count'));
        if (!cible) {
            return;
        }
        var longueur = champ.value.length;
        var limite = parseInt(champ.getAttribute('data-oef-limit'), 10);

        if (limite) {
            cible.textContent = longueur + ' / ' + limite + ' caractères';
            cible.classList.toggle('is-over', longueur > limite);
        } else {
            cible.textContent = longueur + ' caractères · 160 conseillés';
        }
    }

    // ── Places reservees = totales - disponibles ──────────────────────────
    function majReservees() {
        var total = form.querySelector('[data-oef-total]');
        var dispo = form.querySelector('[data-oef-available]');
        var reserve = form.querySelector('[data-oef-reserved]');
        if (!total || !dispo || !reserve) {
            return;
        }
        reserve.value = Math.max(0, (parseInt(total.value, 10) || 0) - (parseInt(dispo.value, 10) || 0));
    }

    // ── Remise affichee ───────────────────────────────────────────────────
    function majRemise() {
        var prix = form.querySelector('[data-oef-price]');
        var ancien = form.querySelector('[data-oef-old-price]');
        var cible = form.querySelector('[data-oef-discount]');
        if (!prix || !ancien || !cible) {
            return;
        }
        var p = parseFloat(prix.value);
        var a = parseFloat(ancien.value);

        if (a > 0 && p > 0 && a > p) {
            cible.textContent = 'Remise affichée : −' + Math.round((1 - p / a) * 100) + ' %';
        } else {
            cible.textContent = 'Aucune remise affichée';
        }
    }

    // ── Pastilles de prestations ──────────────────────────────────────────
    function majStock(groupe) {
        var stock = groupe.querySelector('[data-oef-chip-store]');
        if (!stock) {
            return;
        }
        var valeurs = Array.prototype.map.call(
            groupe.querySelectorAll('[data-oef-chip] > span'),
            function (el) { return el.textContent.trim(); }
        );
        stock.value = valeurs.join('\n');
    }

    function ajouteChip(groupe, texte) {
        var liste = groupe.querySelector('[data-oef-chip-list]');
        if (!liste || !texte) {
            return;
        }
        var chip = document.createElement('span');
        chip.className = 'oef-chip';
        chip.setAttribute('data-oef-chip', '');

        var libelle = document.createElement('span');
        libelle.textContent = texte;

        var retirer = document.createElement('button');
        retirer.type = 'button';
        retirer.title = 'Retirer';
        retirer.setAttribute('data-oef-chip-remove', '');
        retirer.textContent = '×';

        chip.appendChild(libelle);
        chip.appendChild(retirer);
        liste.appendChild(chip);
        majStock(groupe);
    }

    // ── Repetiteurs ───────────────────────────────────────────────────────
    function ajouteLigne(repeteur) {
        var cle = repeteur.getAttribute('data-oef-repeater');
        var modele = document.querySelector('[data-oef-template="' + cle + '"]');
        var lignes = repeteur.querySelector('[data-oef-rows]');
        if (!modele || !lignes) {
            return;
        }
        // Index unique : le controleur reindexe a l'enregistrement.
        var index = Date.now() % 100000;
        var html = modele.innerHTML.split('__INDEX__').join(String(index));
        var hote = document.createElement('div');
        hote.innerHTML = html;

        Array.prototype.forEach.call(hote.children, function (enfant) {
            lignes.appendChild(enfant.cloneNode(true));
        });
        marqueModifie();
    }

    // ── Etat « modifications non enregistrees » ───────────────────────────
    var etat = form.querySelector('[data-oef-dirty]');
    var modifie = false;

    function marqueModifie() {
        if (modifie || !etat) {
            return;
        }
        modifie = true;
        etat.textContent = 'Modifications non enregistrées.';
        etat.classList.add('is-dirty');
    }

    // ── Branchements ──────────────────────────────────────────────────────
    form.addEventListener('input', function (event) {
        var cible = event.target;

        if (cible.hasAttribute && cible.hasAttribute('data-oef-count')) {
            majCompteur(cible);
        }
        if (cible.matches('[data-oef-total], [data-oef-available]')) {
            majReservees();
        }
        if (cible.matches('[data-oef-price], [data-oef-old-price]')) {
            majRemise();
        }
        marqueModifie();
    });

    form.addEventListener('change', function (event) {
        var cible = event.target;

        if (cible.matches('[data-oef-meals]')) {
            var bloc = form.querySelector('[data-oef-meal-plan]');
            if (bloc) {
                bloc.hidden = !cible.checked;
            }
        }
        if (cible.matches('.oef-service input')) {
            cible.closest('.oef-service').classList.toggle('is-on', cible.checked);
        }
        if (cible.matches('.oef-switch input')) {
            var libelle = cible.parentElement.querySelector('.oef-switch__label');
            if (libelle) {
                libelle.textContent = cible.checked
                    ? libelle.getAttribute('data-on')
                    : libelle.getAttribute('data-off');
            }
        }
        marqueModifie();
    });

    form.addEventListener('click', function (event) {
        var ajout = event.target.closest('[data-oef-add]');
        if (ajout) {
            ajouteLigne(ajout.closest('[data-oef-repeater]'));
            return;
        }

        var suppression = event.target.closest('[data-oef-remove]');
        if (suppression) {
            var ligne = suppression.closest('[data-oef-row]');
            if (ligne) {
                ligne.remove();
                marqueModifie();
            }
            return;
        }

        var retraitChip = event.target.closest('[data-oef-chip-remove]');
        if (retraitChip) {
            var groupe = retraitChip.closest('[data-oef-tags]');
            retraitChip.closest('[data-oef-chip]').remove();
            majStock(groupe);
            marqueModifie();
        }
    });

    form.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }
        var saisie = event.target.closest('[data-oef-chip-input]');
        if (!saisie) {
            return;
        }
        // Entree ajoute une pastille et n'envoie pas le formulaire.
        event.preventDefault();
        var texte = saisie.value.trim();
        if (texte) {
            ajouteChip(saisie.closest('[data-oef-tags]'), texte);
            saisie.value = '';
            marqueModifie();
        }
    });

    racine.addEventListener('submit', function () {
        if (etat) {
            etat.textContent = 'Enregistrement en cours…';
            etat.classList.remove('is-dirty');
        }
    });

    majReservees();
    majRemise();
})();
