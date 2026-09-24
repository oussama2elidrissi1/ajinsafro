<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pose les en-têtes de sécurité que le serveur n'envoie pas.
 *
 * Le back-office manipule des données clients et des réservations : sans ces en-têtes, il reste
 * exposé au clickjacking, à la confusion de type MIME et à la fuite d'URL internes vers des sites
 * tiers via le Referer.
 *
 * Choix volontairement conservateurs, pour ne rien casser en production :
 *
 * - `X-Frame-Options: SAMEORIGIN` plutôt que `DENY` : l'admin affiche des aperçus WordPress.
 * - HSTS sans `includeSubDomains` ni `preload` : un sous-domaine encore servi en HTTP deviendrait
 *   injoignable, et `preload` est difficilement réversible.
 * - Pas de `Content-Security-Policy` : les vues du thème utilisent du script inline, une politique
 *   posée à l'aveugle casserait l'interface. Elle demande un inventaire préalable.
 *
 * Un en-tête déjà posé — par Apache, par un contrôleur — n'est jamais écrasé.
 */
class SecurityHeaders
{
    /**
     * En-têtes ajoutés à toute réponse HTML.
     *
     * @var array<string, string>
     */
    private const HEADERS = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Permitted-Cross-Domain-Policies' => 'none',
    ];

    /** Un an : valeur usuelle, sans sous-domaines ni preload. */
    private const HSTS = 'max-age=31536000';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // HSTS n'a de sens qu'une fois la connexion déjà chiffrée : l'envoyer en clair ne protège
        // de rien et gênerait un développement local en HTTP.
        if ($request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', self::HSTS);
        }

        return $response;
    }
}
