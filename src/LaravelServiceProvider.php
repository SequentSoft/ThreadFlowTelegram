<?php

namespace SequentSoft\ThreadFlowTelegram;

use Illuminate\Support\ServiceProvider;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlowTelegram\Channel\TelegramIncomingChannel;
use SequentSoft\ThreadFlowTelegram\Channel\TelegramOutgoingChannel;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Laravel\Console\TelegramLongPollingCommand;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\IncomingMessagesFactory;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramAudioIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramContactIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramFileIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramImageIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramLocationIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramStickerIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramTextIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramUnknownIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramVideoIncomingRegularMessage;

class LaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(IncomingMessagesFactoryInterface::class, IncomingMessagesFactory::class);
    }

    protected function getMessagesMap(): array
    {
        return [
            'text' => TelegramTextIncomingRegularMessage::class,
            'contact' => TelegramContactIncomingRegularMessage::class,
            'location' => TelegramLocationIncomingRegularMessage::class,
            'image' => TelegramImageIncomingRegularMessage::class,
            'file' => TelegramFileIncomingRegularMessage::class,
            'sticker' => TelegramStickerIncomingRegularMessage::class,
            'video' => TelegramVideoIncomingRegularMessage::class,
            'audio' => TelegramAudioIncomingRegularMessage::class,
        ];
    }

    public function boot()
    {
        $this->app->afterResolving(
            IncomingMessagesFactoryInterface::class,
            function (IncomingMessagesFactory $factory) {
                foreach ($this->getMessagesMap() as $key => $class) {
                    $factory->registerMessage($key, $class);
                }

                $factory->registerFallbackMessage(TelegramUnknownIncomingRegularMessage::class);
            }
        );

        $this->app->afterResolving(
            IncomingChannelRegistryInterface::class,
            function (IncomingChannelRegistryInterface $registry) {
                $registry->register(
                    'telegram',
                    function (SimpleConfigInterface $config) {
                        return new TelegramIncomingChannel(
                            $this->app->make(
                                IncomingMessagesFactoryInterface::class
                            ),
                            $config
                        );
                    }
                );
            }
        );

        $this->app->afterResolving(
            OutgoingChannelRegistryInterface::class,
            function (OutgoingChannelRegistryInterface $registry) {
                $registry->register(
                    'telegram',
                    function (SimpleConfigInterface $config) {
                        return new TelegramOutgoingChannel($config);
                    }
                );
            }
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                TelegramLongPollingCommand::class,
            ]);
        }
    }
}
