<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class WebhookHandleController
{
    public function handle(Request $request, ThreadFlowTelegram $threadFlowTelegram): JsonResponse
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $channel = $request->get('channel');

        try {
            $config = $threadFlowTelegram->getTelegramChannelConfig($channel);
            $configuredSecretToken = $config->get('webhook_secret_token');

            if ($configuredSecretToken && $configuredSecretToken !== $secretToken) {
                throw new RuntimeException('Invalid secret token');
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => 'ignored',
                'reason' => $e->getMessage(),
            ]);
        }

        $threadFlowTelegram->handleData($channel, $request->all());

        return response()->json([
            'status' => 'handled',
        ]);
    }
}
