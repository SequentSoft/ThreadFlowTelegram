<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\DataFetchers\LongPollingDataFetcher;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramDeleteWebhookCommand extends Command
{
    protected $signature = 'thread-flow:telegram:webhook-delete {channel=telegram}';

    protected $description = 'Deletes webhook for Telegram bot';

    /**
     * Handles the console command.
     * @throws JsonException
     * @throws RequestException
     * @throws ChannelNotConfiguredException
     */
    public function handle(ThreadFlowTelegram $threadFlowTelegram)
    {
        $this->output->title('ThreadFlow Telegram Webhook Delete');

        $channelName = $this->argument('channel');
        $config = $threadFlowTelegram->getTelegramChannelConfig($channelName);
        $token = $config->get('api_token');

        $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook")->throw()->json();

        $this->line(json_encode($response, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->output->success('Successfully deleted webhook');
    }
}