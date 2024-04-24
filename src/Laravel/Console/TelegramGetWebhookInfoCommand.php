<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use JsonException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramGetWebhookInfoCommand extends Command
{
    protected $signature = 'threadflow:telegram-webhook-info {--channel=telegram}';

    protected $description = 'Gets webhook info for Telegram bot';

    /**
     * @throws JsonException
     * @throws ChannelNotConfiguredException
     */
    public function handle(ThreadFlowTelegram $telegramChannelManager): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Info');

        $channel = $telegramChannelManager->channel($this->option('channel'));

        $this->line(
            json_encode($channel->getWebhookInfo(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );

        $this->output->success('Successfully got webhook info');
    }
}
