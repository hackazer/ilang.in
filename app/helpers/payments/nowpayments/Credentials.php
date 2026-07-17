<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class Credentials
{
    private const SECRET_FIELDS = ['api_key', 'ipn_secret', 'dashboard_password'];

    /**
     * @param array<string, mixed>|object $submitted
     * @param array<string, mixed>|object $existing
     * @return array<string, mixed>
     */
    public static function prepareForStorage(array|object $submitted, array|object $existing, callable $encrypt): array
    {
        $submitted = self::toArray($submitted);
        $stored = self::toArray($existing);

        foreach ($submitted as $key => $value) {
            if (in_array($key, self::SECRET_FIELDS, true) || str_ends_with((string) $key, '_encrypted')) {
                continue;
            }

            $stored[(string) $key] = $value;
        }

        foreach (self::SECRET_FIELDS as $field) {
            $encryptedField = $field.'_encrypted';
            $secret = trim((string) ($submitted[$field] ?? ''));

            if ($secret !== '') {
                $stored[$encryptedField] = $encrypt($secret);
            } elseif (!isset($stored[$encryptedField]) && trim((string) ($stored[$field] ?? '')) !== '') {
                $stored[$encryptedField] = $encrypt((string) $stored[$field]);
            }

            unset($stored[$field]);
        }

        $stored['environment'] = ($stored['environment'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';
        $stored['default_mode'] = self::validMode((string) ($stored['default_mode'] ?? 'prepaid'));
        $stored['enabled_modes'] = self::enabledModes($stored['enabled_modes'] ?? ['prepaid']);

        return $stored;
    }

    /**
     * @param array<string, mixed>|object $stored
     * @return array<string, mixed>
     */
    public static function runtime(array|object $stored, callable $decrypt): array
    {
        $runtime = self::toArray($stored);

        foreach (self::SECRET_FIELDS as $field) {
            $encryptedField = $field.'_encrypted';
            $ciphertext = trim((string) ($runtime[$encryptedField] ?? ''));
            $runtime[$field] = $ciphertext !== '' ? $decrypt($ciphertext) : trim((string) ($runtime[$field] ?? ''));
            unset($runtime[$encryptedField]);
        }

        $runtime['enabled_modes'] = self::enabledModes($runtime['enabled_modes'] ?? ['prepaid']);
        $runtime['default_mode'] = self::validMode((string) ($runtime['default_mode'] ?? 'prepaid'));

        return $runtime;
    }

    /**
     * @param array<string, mixed>|object $stored
     * @return array<string, mixed>
     */
    public static function renderable(array|object $stored): array
    {
        $renderable = self::toArray($stored);

        foreach (self::SECRET_FIELDS as $field) {
            $encryptedField = $field.'_encrypted';
            $renderable[$field.'_configured'] = trim((string) ($renderable[$encryptedField] ?? $renderable[$field] ?? '')) !== '';
            unset($renderable[$field], $renderable[$encryptedField]);
        }

        $renderable['enabled_modes'] = self::enabledModes($renderable['enabled_modes'] ?? ['prepaid']);
        $renderable['default_mode'] = self::validMode((string) ($renderable['default_mode'] ?? 'prepaid'));

        return $renderable;
    }

    /**
     * @return list<string>
     */
    private static function enabledModes(mixed $modes): array
    {
        if (is_string($modes)) {
            $modes = array_filter(array_map('trim', explode(',', $modes)));
        }

        if (!is_array($modes)) {
            $modes = [];
        }

        $valid = [];

        foreach ($modes as $mode) {
            $mode = self::validMode((string) $mode, '');

            if ($mode !== '') {
                $valid[] = $mode;
            }
        }

        return array_values(array_unique($valid ?: ['prepaid']));
    }

    private static function validMode(string $mode, string $fallback = 'prepaid'): string
    {
        return in_array($mode, ['prepaid', 'email', 'custodial'], true) ? $mode : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(array|object $value): array
    {
        return is_array($value) ? $value : get_object_vars($value);
    }
}
