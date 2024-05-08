<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use SequentSoft\ThreadFlowTelegram\DataFetchers\InvokableDataFetcher;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class WebhookHandleController
{
    public function __invoke(Request $request, ThreadFlowTelegram $threadFlowTelegram): JsonResponse
    {
        return $this->handle($request, $threadFlowTelegram);
    }

    public function handle(Request $request, ThreadFlowTelegram $threadFlowTelegram): JsonResponse
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        try {
            $channel = $threadFlowTelegram->channel($request->get('channel'));
            $configuredSecretToken = $channel->getConfig()->get('webhook_secret_token');

            if ($configuredSecretToken && $configuredSecretToken !== $secretToken) {
                throw new RuntimeException('Invalid secret token');
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => 'ignored',
                'reason' => $e->getMessage(),
            ]);
        }

        $invokableDataFetcher = new InvokableDataFetcher();
        $channel->listen($invokableDataFetcher);
        $invokableDataFetcher($request->all());

        return response()->json([
            'status' => 'handled',
        ]);
    }
}
