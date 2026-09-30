<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * The settings live in a tiny table that is read on every booking event,
     * so they are cached and the cache is dropped on every write.
     */
    public const CACHE_KEY = 'settings.all';

    protected $fillable = [
        'key', 'value',
    ];

    /**
     * The raw value of a setting, or the given default when it was never saved.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::values()[$key] ?? $default;
    }

    /**
     * Read a setting as a boolean. Anything but "0"/"false"/"" is truthy.
     */
    public static function boolean(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Read a setting as a trimmed string.
     */
    public static function string(string $key, string $default = ''): string
    {
        $value = static::get($key);

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Read a setting as a list, splitting a comma (or newline) separated value.
     *
     * @return list<string>
     */
    public static function list(string $key): array
    {
        $parts = preg_split('/[\r\n,]+/', static::string($key)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $part): bool => $part !== ''));
    }

    /**
     * Create or update a single setting.
     */
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => static::castToStorage($value)]
        );

        static::flushCache();
    }

    /**
     * Create or update several settings at once.
     *
     * @param  array<string, mixed>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => static::castToStorage($value)]
            );
        }

        static::flushCache();
    }

    /**
     * Every stored setting as a key => value map.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        return Cache::rememberForever(
            static::CACHE_KEY,
            fn (): array => static::query()->pluck('value', 'key')->all()
        );
    }

    public static function flushCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Push the stored SMTP credentials into Laravel's live mail config so that
     * every outgoing message in this request uses the admin's settings instead
     * of the .env fallback.
     *
     * Symfony only understands "smtp" (STARTTLS) and "smtps" (SSL/TLS) DSN
     * schemes. Legacy values like "tls" and "ssl" are mapped accordingly.
     */
    public static function applyMailConfig(): void
    {
        $host = static::string('mail.smtp_host');

        if (! $host) {
            return;
        }

        $encryption = static::string('mail.smtp_encryption', 'tls');

        $scheme = match (strtolower($encryption)) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => null,
        };

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', (int) (static::string('mail.smtp_port') ?: 587));
        Config::set('mail.mailers.smtp.username', static::string('mail.smtp_username'));
        Config::set('mail.mailers.smtp.password', static::string('mail.smtp_password'));
        Config::set('mail.from.address', static::string('mail.from_address', (string) config('mail.from.address')));
        Config::set('mail.from.name', static::string('mail.from_name', (string) config('mail.from.name')));
    }

    /**
     * Booleans are stored as "1"/"0" so an unchecked checkbox round-trips
     * through the form as a disabled toggle instead of an empty string.
     */
    protected static function castToStorage(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
