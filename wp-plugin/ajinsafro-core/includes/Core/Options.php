<?php

namespace Ajinsafro\Core;

class Options
{
    const OPTION_KEY = 'ajinsafro_settings';

    private static $defaults = [
        'laravel_base_url' => '',
        'booking_checkout_base_url' => '',
        'hmac_secret' => '',
        'enable_sync' => false,
        'cache_ttl_seconds' => 300,
        'auto_inject_builder' => true,
        'auto_inject_position' => 'after',
    ];

    public static function get($key = null, $default = null)
    {
        $options = get_option(self::OPTION_KEY, self::$defaults);
        $options = wp_parse_args($options, self::$defaults);

        if ($key === null) {
            return $options;
        }

        return $options[$key] ?? $default;
    }

    public static function update($key, $value)
    {
        $options = self::get();
        $options[$key] = $value;
        update_option(self::OPTION_KEY, $options);
    }

    public static function update_all($options)
    {
        $current = self::get();
        $updated = wp_parse_args($options, $current);
        update_option(self::OPTION_KEY, $updated);
    }

    public static function delete()
    {
        delete_option(self::OPTION_KEY);
    }
}
