<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming;

use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;

interface IncomingMessagesFactoryInterface
{
    public function addMessageTypeClass(string|array $messageClass): self;

    public function make(array $data): IncomingMessageInterface;
}
