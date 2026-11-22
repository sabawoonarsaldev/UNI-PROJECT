<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Settings of the whole system, kept in the "settings" table as key => JSON value
class Settings
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $row = DB::table('settings')->where('key', $key)->first();

        if (! $row) {
            return $default;
        }

        return self::$cache[$key] = json_decode($row->value, true);
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value)]);

        self::$cache[$key] = $value;
    }
}