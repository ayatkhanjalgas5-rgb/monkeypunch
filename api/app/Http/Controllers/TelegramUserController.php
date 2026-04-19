<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelegramUserController extends Controller
{
    public function referredUsers(Request $request)
    {
        $user = $request->user();

        $referredUsers = TelegramUser::with(['level'])
            ->where('referred_by', $user->telegram_id)
            ->paginate($request->get('per_page') ?? 10);

        return JsonResource::collection($referredUsers);
    }
}
