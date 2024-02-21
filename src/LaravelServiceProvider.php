<?php

namespace SequentSoft\ThreadFlowTelegram;

use Illuminate\Support\ServiceProvider;
use SequentSoft\ThreadFlow\Contracts\Channel\ChannelManagerInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherFactoryInterface;
use SequentSoft\ThreadFlow\Contracts\Events\EventBusInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionStoreInterface;
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
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramAudioIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramContactIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramFileIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramImageIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramInlineButtonCallbackIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramLocationIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramStickerIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramTextIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramUnknownIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramVideoIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\FileApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\ForwardApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\ImageApiMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api\MessageReactionApiMessage;
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
            TelegramTextIncomingMessage::class,
            TelegramContactIncomingMessage::class,
            TelegramLocationIncomingMessage::class,
            TelegramImageIncomingMessage::class,
            TelegramFileIncomingMessage::class,
            TelegramStickerIncomingMessage::class,
            TelegramVideoIncomingMessage::class,
            TelegramAudioIncomingMessage::class,
            TelegramInlineButtonCallbackIncomingMessage::class,
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
            MessageReactionApiMessage::class,
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
            ChannelManagerInterface::class,
            fn (ChannelManagerInterface $channelManager) => $channelManager->registerChannelDriver(
                'telegram',
                fn (
                    string $channelName,
                    ConfigInterface $config,
                    SessionStoreInterface $sessionStore,
                    DispatcherFactoryInterface $dispatcherFactory,
                    EventBusInterface $eventBus
                ) => new TelegramChannel(
                    $channelName,
                    $config,
                    $sessionStore,
                    $dispatcherFactory,
                    $eventBus,
                    $this->app->make(HttpClientFactoryInterface::class),
                    $this->app->make(IncomingMessagesFactoryInterface::class),
                    $this->app->make(OutgoingApiMessageFactoryInterface::class),
                )
            )
        );

        $this->app->afterResolving(
            IncomingMessagesFactoryInterface::class,
            fn (IncomingMessagesFactory $factory) => $factory
                ->addMessageTypeClass($this->getDefaultIncomingMessagesTypes())
                ->registerFallbackMessage(TelegramUnknownIncomingMessage::class)
        );

        $this->app->afterResolving(
            OutgoingApiMessageFactoryInterface::class,
            fn (OutgoingApiMessageFactoryInterface $factory) => $factory
                ->addApiMessageTypeClass($this->getDefaultOutgoingApiMessagesTypes())
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
