<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use JsonException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramDeleteWebhookCommand extends Command
{
    protected $signature = 'threadflow:telegram-webhook-remove {--channel=telegram}';

    protected $description = 'Deletes webhook for Telegram bot';

    /**
     * Handles the console command.
     * @throws JsonException
     * @throws ChannelNotConfiguredException
     */
    public function handle(ThreadFlowTelegram $telegramChannelManager): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Delete');

        $channel = $telegramChannelManager->channel($this->option('channel'));

        $this->line(
            json_encode($channel->deleteWebhook(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );

        $this->output->success('Successfully deleted webhook');
    }
}
