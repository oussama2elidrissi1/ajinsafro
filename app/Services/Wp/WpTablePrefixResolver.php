<?php

namespace App\Services\Wp;

use Illuminate\Support\Facades\DB;

/**
 * Résout le préfixe des tables WordPress (ex: cFdgeZ_).
 * Utilise la config de la connexion wp, ou détecte automatiquement via *_posts / *_postmeta.
 */
class WpTablePrefixResolver
{
    private static ?string $resolved = null;

    /**
     * Retourne le préfixe des tables WP (ex: cFdgeZ_).
     * Ne jamais utiliser wp_posts / wp_postmeta en dur.
     */
    public static function getPrefix(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $fromConfig = config('database.connections.wp.prefix');
        if (!empty($fromConfig)) {
            self::$resolved = $fromConfig;
            return self::$resolved;
        }

        self::$resolved = self::detectFromDatabase();
        return self::$resolved;
    }

    /**
     * Détecte le préfixe en cherchant une table *_posts sur la connexion wp.
     */
    private static function detectFromDatabase(): string
    {
        try {
            $driver = DB::connection('wp')->getDriverName();
            $table = $driver === 'mysql'
                ? DB::connection('wp')->selectOne("SHOW TABLES LIKE '%_posts'")
                : null;
            if ($table) {
                $fullName = (array) $table;
                $name = reset($fullName);
                if ($name && str_ends_with($name, '_posts')) {
                    return substr($name, 0, -strlen('_posts')) ?: 'wp_';
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('WpTablePrefixResolver: could not detect prefix', ['error' => $e->getMessage()]);
        }

        return 'wp_';
    }

    /**
     * Réinitialise le cache (utile en tests).
     */
    public static function reset(): void
    {
        self::$resolved = null;
    }
}
