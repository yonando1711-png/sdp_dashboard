<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
    
    /**
     * Get all Odoo settings as array
     */
    public static function getOdooConfig(): array
    {
        return [
            'url' => static::get('odoo_url', ''),
            'db' => static::get('odoo_db', ''),
            'user' => static::get('odoo_user', ''),
            'password' => static::get('odoo_password', ''),
        ];
    }
}
