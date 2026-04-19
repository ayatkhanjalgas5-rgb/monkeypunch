<?php

namespace App\Services;

class TelegramInitDataService
{
    public function parse(string $initData): array
    {
        $pairs = [];
        parse_str($initData, $pairs);

        foreach ($pairs as $key => $value) {
            if (is_string($value) && $this->looksLikeJson($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $pairs[$key] = $decoded;
                }
            }
        }

        return $pairs;
    }

    public function verify(string $initData, ?string $botToken = null): bool
    {
        $botToken ??= (string) config('services.telegram.bot_token');

        if ($initData === '' || $botToken === '') {
            return false;
        }

        parse_str($initData, $data);
        $hash = $data['hash'] ?? null;

        if (!$hash || !is_string($hash)) {
            return false;
        }

        unset($data['hash']);
        ksort($data);

        $checkString = collect($data)
            ->map(function ($value, $key) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                return $key.'='.$value;
            })
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

        return hash_equals($calculatedHash, $hash);
    }

    public function extractUser(string $initData): ?array
    {
        $data = $this->parse($initData);
        $user = $data['user'] ?? null;

        return is_array($user) ? $user : null;
    }

    private function looksLikeJson(string $value): bool
    {
        $trimmed = trim($value);
        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }
}
