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
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramTextIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramUnknownIncomingRegularMessage;

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
