<?php

namespace SequentSoft\ThreadFlowTelegram;

use Illuminate\Support\ServiceProvider;
use SequentSoft\ThreadFlow\Contracts\BotInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Events\Message\IncomingMessageProcessingEvent;
use SequentSoft\ThreadFlowTelegram\Channel\TelegramIncomingChannel;
use SequentSoft\ThreadFlowTelegram\Channel\TelegramOutgoingChannel;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;
use SequentSoft\ThreadFlowTelegram\HttpClient\GuzzleHttpClientFactory;
use SequentSoft\ThreadFlowTelegram\Laravel\Console\TelegramDeleteWebhookCommand;
use SequentSoft\ThreadFlowTelegram\Laravel\Console\TelegramGetWebhookInfoCommand;
use SequentSoft\ThreadFlowTelegram\Laravel\Console\TelegramSetWebhookCommand;
use SequentSoft\ThreadFlowTelegram\Laravel\Controllers\WebhookHandleController;
use SequentSoft\ThreadFlowTelegram\Laravel\Console\TelegramLongPollingCommand;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\IncomingMessagesFactory;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramAudioIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramContactIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramFileIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramImageIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramInlineButtonCallbackIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramLocationIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramStickerIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramTextIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramUnknownIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramVideoIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\FileApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\ForwardApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\ImageApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\TextApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\TypingApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\OutgoingApiMessageFactory;

class LaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IncomingMessagesFactoryInterface::class, IncomingMessagesFactory::class);
        $this->app->singleton(OutgoingApiMessageFactoryInterface::class, OutgoingApiMessageFactory::class);
        $this->app->singleton(HttpClientFactoryInterface::class, GuzzleHttpClientFactory::class);
    }

    protected function getDefaultIncomingMessagesTypes(): array
    {
        return [
            TelegramTextIncomingRegularMessage::class,
            TelegramContactIncomingRegularMessage::class,
            TelegramLocationIncomingRegularMessage::class,
            TelegramImageIncomingRegularMessage::class,
            TelegramFileIncomingRegularMessage::class,
            TelegramStickerIncomingRegularMessage::class,
            TelegramVideoIncomingRegularMessage::class,
            TelegramAudioIncomingRegularMessage::class,
            TelegramInlineButtonCallbackIncomingRegularMessage::class,
        ];
    }

    protected function getDefaultOutgoingApiMessagesTypes(): array
    {
        return [
            FileApiMessage::class,
            ForwardApiMessage::class,
            ImageApiMessage::class,
            TextApiMessage::class,
            TypingApiMessage::class,
        ];
    }

    protected function bootWebhookRoutes(): void
    {
        foreach ($this->app->get('config')->get('thread-flow.channels', []) as $channelData) {
            $driver = $channelData['driver'] ?? null;
            $webhookUrl = ltrim(parse_url($channelData['webhook_url'] ?? '', PHP_URL_PATH), '/');
            $apiToken = $channelData['api_token'] ?? null;

            if ($driver === 'telegram' && $webhookUrl && $apiToken) {
                $this->app->get('router')->post(
                    $webhookUrl,
                    [WebhookHandleController::class, 'handle']
                );
            }
        }
    }

    public function boot(): void
    {
        $this->app->afterResolving(
            IncomingMessagesFactoryInterface::class,
            fn(IncomingMessagesFactory $factory) => $factory
                ->addMessageTypeClass($this->getDefaultIncomingMessagesTypes())
                ->registerFallbackMessage(TelegramUnknownIncomingRegularMessage::class)
        );

        $this->app->afterResolving(
            OutgoingApiMessageFactoryInterface::class,
            fn(OutgoingApiMessageFactoryInterface $factory) => $factory
                ->addApiMessageTypeClass($this->getDefaultOutgoingApiMessagesTypes())
        );

        $this->app->afterResolving(
            IncomingChannelRegistryInterface::class,
            fn(IncomingChannelRegistryInterface $registry) => $registry->register(
                'telegram',
                fn(SimpleConfigInterface $config) => new TelegramIncomingChannel(
                    $this->app->make(HttpClientFactoryInterface::class),
                    $this->app->make(IncomingMessagesFactoryInterface::class),
                    $config
                )
            )
        );

        $this->app->afterResolving(
            OutgoingChannelRegistryInterface::class,
            fn(OutgoingChannelRegistryInterface $registry) => $registry->register(
                'telegram',
                fn(SimpleConfigInterface $config) => new TelegramOutgoingChannel(
                    $this->app->make(HttpClientFactoryInterface::class),
                    $this->app->make(OutgoingApiMessageFactoryInterface::class),
                    $config
                )
            )
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                TelegramLongPollingCommand::class,
                TelegramSetWebhookCommand::class,
                TelegramGetWebhookInfoCommand::class,
                TelegramDeleteWebhookCommand::class,
            ]);
        }

        $this->bootWebhookRoutes();
    }
}
