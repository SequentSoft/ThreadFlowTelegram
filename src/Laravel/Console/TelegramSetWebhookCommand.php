<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use JsonException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'threadflow:telegram-webhook-set {--channel=telegram}';

    protected $description = 'Sets webhook for Telegram bot';

    /**
     * @throws ChannelNotConfiguredException
     * @throws JsonException
     */
    public function handle(ThreadFlowTelegram $telegramChannelManager): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Set');

        $channelName = $this->option('channel');
        $channel = $telegramChannelManager->channel($channelName);
        $url = route('threadflow.telegram.webhook', ['channel' => $channelName]);

        $this->line('URL: ' . $url);

        $this->line(
            json_encode($channel->setWebhook($url), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );

        $this->output->success('Webhook has been set successfully');
    }
}
