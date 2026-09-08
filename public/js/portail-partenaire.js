/**
 * Portail Agence Partenaire — tiroir de navigation.
 * La bascule navigation / tiroir est pilotée par CSS (media queries) ; ce script
 * ne gère que l'ouverture, la fermeture et l'accessibilité du panneau.
 */
(function () {
    'use strict';

    function init() {
        var toggle = document.querySelector('[data-pp-drawer-toggle]');
        var drawer = document.querySelector('[data-pp-drawer]');
        var backdrop = document.querySelector('[data-pp-backdrop]');

        if (!toggle || !drawer) {
            return;
        }

        function setOpen(open) {
            drawer.classList.toggle('is-open', open);
            if (backdrop) {
                backdrop.classList.toggle('is-open', open);
            }
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            setOpen(!drawer.classList.contains('is-open'));
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setOpen(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        // Repasser au-dessus du seuil laisse la navigation horizontale visible :
        // le tiroir doit alors être refermé pour ne pas rester ouvert en arrière-plan.
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1180) {
                setOpen(false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
