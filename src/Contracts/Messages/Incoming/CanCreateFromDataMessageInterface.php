<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming;

interface CanCreateFromDataMessageInterface
{
    public static function canCreateFromData(array $data): bool;

    public static function createFromData(IncomingMessagesFactoryInterface $factory, array $data): self;
}
