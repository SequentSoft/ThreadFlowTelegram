<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use JsonException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramDeleteWebhookCommand extends Command
{
    protected $signature = 'thread-flow:telegram:webhook-delete {channel=telegram}';

    protected $description = 'Deletes webhook for Telegram bot';

    /**
     * Handles the console command.
     * @throws JsonException
     * @throws ChannelNotConfiguredException
     */
    public function handle(ThreadFlowTelegram $threadFlowTelegram, HttpClientFactoryInterface $httpClientFactory): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Delete');

        $channelName = $this->argument('channel');
        $config = $threadFlowTelegram->getTelegramChannelConfig($channelName);
        $token = $config->get('api_token');

        $parsedData = $httpClientFactory->create($token)
            ->postJson('deleteWebhook', [])
            ->getParsedData();

        $this->line(json_encode($parsedData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->output->success('Successfully deleted webhook');
    }
}
