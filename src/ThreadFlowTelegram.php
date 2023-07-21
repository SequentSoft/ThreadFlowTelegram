<?php

namespace SequentSoft\ThreadFlowTelegram;

use Closure;
use SequentSoft\ThreadFlow\Contracts\BotInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherFactoryInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\DataFetchers\InvokableDataFetcher;
use SequentSoft\ThreadFlow\Exceptions\Channel\InvalidChannelDriverException;

class ThreadFlowTelegram
{
    public function __construct(
        protected IncomingChannelRegistryInterface $incomingChannelRegistry,
        protected OutgoingChannelRegistryInterface $outgoingChannelRegistry,
        protected DispatcherFactoryInterface $dispatcherFactory,
        protected BotInterface $bot,
    ) {
    }

    public function handleData(
        string $channelName,
        array $data,
        ?Closure $beforeDispatchCallback = null,
        ?Closure $outgoingCallback = null
    ): void {
        $invokableDataFetcher = new InvokableDataFetcher();

        $this->listen($channelName, $invokableDataFetcher, $beforeDispatchCallback, $outgoingCallback);

        $invokableDataFetcher($data);
    }

    public function listen(
        string $channelName,
        DataFetcherInterface $dataFetcher,
        ?Closure $beforeDispatchCallback = null,
        ?Closure $outgoingCallback = null
    ): void {
        $config = $this->getTelegramChannelConfig($channelName);

        $dispatcherName = $config->get('dispatcher');

        $outgoingChannel = $this->outgoingChannelRegistry->get($channelName, $config);
        $incomingChannel = $this->incomingChannelRegistry->get($channelName, $config);
        $dispatcher = $this->dispatcherFactory->make($dispatcherName);

        $incomingChannel->listen(
            $dataFetcher,
            function (IncomingMessageInterface $message) use (
                $outgoingCallback,
                $beforeDispatchCallback,
                $channelName,
                $dispatcher,
                $outgoingChannel,
                $incomingChannel
            ) {
                if ($beforeDispatchCallback) {
                    $beforeDispatchCallback($message);
                }

                $dispatcher->dispatch(
                    $channelName,
                    $message,
                    fn(IncomingMessageInterface $message, SessionInterface $session) => $incomingChannel->preprocess(
                        $message,
                        $session
                    ),
                    function (
                        OutgoingMessageInterface $message,
                        SessionInterface $session
                    ) use (
                        $outgoingChannel,
                        $outgoingCallback
                    ) {
                        if ($outgoingCallback) {
                            $outgoingCallback($message, $session);
                        }

                        $outgoingChannel->send($message, $session);
                    },
                );
            }
        );
    }

    public function getTelegramChannelConfig(string $channelName): ConfigInterface
    {
        $config = $this->bot->getChannelConfig($channelName);

        if ($config->get('driver') !== 'telegram') {
            throw new InvalidChannelDriverException("Channel {$channelName} is not configured for Telegram.");
        }

        return $config;
    }
}
