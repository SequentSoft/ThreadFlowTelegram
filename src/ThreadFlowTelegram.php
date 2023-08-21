<?php

namespace SequentSoft\ThreadFlowTelegram;

use Closure;
use SequentSoft\ThreadFlow\Contracts\BotInterface;
use SequentSoft\ThreadFlow\Contracts\BotManagerInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelRegistryInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherFactoryInterface;
use SequentSoft\ThreadFlow\DataFetchers\InvokableDataFetcher;
use SequentSoft\ThreadFlow\Events\Message\IncomingMessageProcessingEvent;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\Channel\TelegramIncomingChannel;

class ThreadFlowTelegram
{
    public function __construct(protected BotManagerInterface $botManager) {}

    public function handleData(string $channelName, array $data): void
    {
        $invokableDataFetcher = new InvokableDataFetcher();

        $this->listen($channelName, $invokableDataFetcher);

        $invokableDataFetcher($data);
    }

    public function listen(string $channelName, DataFetcherInterface $dataFetcher): void
    {
        $this->botManager->channel($channelName)->listen($dataFetcher);
    }

    /**
     * @throws ChannelNotConfiguredException
     */
    public function getTelegramChannelConfig(string $channelName): SimpleConfigInterface
    {
        $config = $this->botManager->channel($channelName)->getConfig();

        if ($config->get('driver') !== 'telegram') {
            throw new ChannelNotConfiguredException("Channel {$channelName} is not configured for Telegram.");
        }

        return $config;
    }
}
