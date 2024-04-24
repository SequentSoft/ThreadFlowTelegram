<?php

namespace SequentSoft\ThreadFlowTelegram;

use SequentSoft\ThreadFlow\Contracts\Channel\ChannelManagerInterface;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;

class ThreadFlowTelegram
{
    public function __construct(protected ChannelManagerInterface $channelManager)
    {
    }

    /**
     * @throws ChannelNotConfiguredException
     */
    public function channel(string $channelName): TelegramChannel
    {
        $channel = $this->channelManager->channel($channelName);

        if (! $channel instanceof TelegramChannel) {
            throw new ChannelNotConfiguredException("Channel '{$channelName}' is not configured for Telegram");
        }

        return $channel;
    }
}
