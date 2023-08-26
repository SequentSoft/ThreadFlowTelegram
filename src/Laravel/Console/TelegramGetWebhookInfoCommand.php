<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramGetWebhookInfoCommand extends Command
{
    protected $signature = 'thread-flow:telegram:webhook-info {channel=telegram}';

    protected $description = 'Gets webhook info for Telegram bot';

    /**
     * Handles the console command.
     * @throws JsonException
     * @throws RequestException
     * @throws ChannelNotConfiguredException
     */
    public function handle(ThreadFlowTelegram $threadFlowTelegram): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Info');

        $channelName = $this->argument('channel');
        $config = $threadFlowTelegram->getTelegramChannelConfig($channelName);
        $token = $config->get('api_token');

        $response = Http::post("https://api.telegram.org/bot{$token}/getWebhookInfo")->throw()->json();

        $this->line(json_encode($response, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->output->success('Successfully got webhook info');
    }
}
