<?php

namespace App\Http\Middleware;

use App\Services\TelegramInitDataService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachVerifiedTelegramInitData
{
    public function __construct(private TelegramInitDataService $telegramInitDataService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $initData = (string) ($request->header('X-Telegram-Init-Data') ?? $request->input('init_data', ''));

        if ($initData !== '') {
            $isVerified = $this->telegramInitDataService->verify($initData);
            $request->attributes->set('telegram_init_data', $initData);
            $request->attributes->set('telegram_init_data_verified', $isVerified);
            $request->attributes->set('telegram_init_data_payload', $this->telegramInitDataService->parse($initData));
        }

        return $next($request);
    }
}
