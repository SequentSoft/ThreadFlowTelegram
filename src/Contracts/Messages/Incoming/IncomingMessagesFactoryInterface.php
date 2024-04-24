<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming;

use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\BasicIncomingMessageInterface;

interface IncomingMessagesFactoryInterface
{
    public function addMessageTypeClass(string|array $messageClass): self;

    public function make(string $channelName, array $data): BasicIncomingMessageInterface;
}
