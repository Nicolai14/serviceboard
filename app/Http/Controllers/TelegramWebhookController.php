<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramBotService $bot): JsonResponse
    {
        $expected = (string) config('serverflow.telegram.webhook_secret');

        if ($expected === '' || ! hash_equals($expected, $secret)) {
            return response()->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $bot->handleUpdate($request->all());

        return response()->json(['ok' => true]);
    }
}
