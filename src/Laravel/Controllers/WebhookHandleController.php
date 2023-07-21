<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Controllers;

use Exception;
use Illuminate\Http\Request;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class WebhookHandleController
{
    public function handle(Request $request, ThreadFlowTelegram $threadFlowTelegram)
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $channel = $request->get('channel');

        try {
            $config = $threadFlowTelegram->getTelegramChannelConfig($channel);
            $configuredSecretToken = $config->get('webhook_secret_token');

            if ($configuredSecretToken && $configuredSecretToken !== $secretToken) {
                throw new Exception('Invalid secret token');
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
