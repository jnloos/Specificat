<?php

namespace App\Services\OpenAI;

use Native\Desktop\Facades\Settings;
use OpenAI;
use Throwable;

class KeyForOpenAI
{
    public static function get(): ?string
    {
        return Settings::get(KeyForOpenAI::class.'.key');
    }

    public static function set(string $key): void
    {
        Settings::set(KeyForOpenAI::class.'.key', $key);
    }

    public static function validate(?string $key = null): bool
    {
        if (! $key) {
            $key = self::get();
        }

        if (! $key) {
            self::cacheStatus(false);
            return false;
        }

        try {
            $client = OpenAI::client($key);
            $client->models()->list();

            self::cacheStatus(true);
            return true;
        } catch (Throwable $e) {
            // network / timeout / DNS / etc.
            self::cacheStatus(false);
            return false;
        }
    }

    public static function status(): bool
    {
        $cachedStatus = Settings::get(KeyForOpenAI::class.'.status');

        // If status is not cached but a key exists, validate it
        if ($cachedStatus === null && self::get()) {
            return self::validate();
        }

        return $cachedStatus ?? false;
    }

    protected static function cacheStatus(bool $status): void
    {
        Settings::set(KeyForOpenAI::class.'.status', $status);
    }
}
