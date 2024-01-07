<?php

namespace SequentSoft\ThreadFlowTelegram;

use SequentSoft\ThreadFlow\Contracts\Channel\ChannelManagerInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\DataFetchers\InvokableDataFetcher;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;

class ThreadFlowTelegram
{
    public function __construct(protected ChannelManagerInterface $channelManager)
    {
    }

    public function handleData(string $channelName, array $data): void
    {
        $invokableDataFetcher = new InvokableDataFetcher();

        $this->listen($channelName, $invokableDataFetcher);

        $invokableDataFetcher($data);
    }

    public function listen(string $channelName, DataFetcherInterface $dataFetcher): void
    {
        $this->channelManager->channel($channelName)->listen($dataFetcher);
    }

    /**
     * @throws ChannelNotConfiguredException
     */
    public function getTelegramChannelConfig(string $channelName): ConfigInterface
    {
        $config = $this->channelManager->channel($channelName)->getConfig();

        if ($config->get('driver') !== 'telegram') {
            throw new ChannelNotConfiguredException("Channel {$channelName} is not configured for Telegram.");
        }

        return $config;
    }
}
