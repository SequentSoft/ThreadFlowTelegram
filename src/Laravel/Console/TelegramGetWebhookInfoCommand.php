<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use JsonException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramGetWebhookInfoCommand extends Command
{
    protected $signature = 'threadflow:telegram-webhook-info {--channel=telegram}';

    protected $description = 'Gets webhook info for Telegram bot';

    /**
     * Handles the console command.
     * @throws JsonException
     * @throws RequestException
     * @throws ChannelNotConfiguredException
     */
    public function handle(ThreadFlowTelegram $threadFlowTelegram, HttpClientFactoryInterface $httpClientFactory): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Info');

        $channelName = $this->option('channel');
        $config = $threadFlowTelegram->getTelegramChannelConfig($channelName);
        $token = $config->get('api_token');

        $parsedData = $httpClientFactory->create($token)
            ->postJson('getWebhookInfo', [])
            ->getParsedData();

        $this->line(json_encode($parsedData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->output->success('Successfully got webhook info');
    }
}
