<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming;

use InvalidArgumentException;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

class IncomingMessagesFactory implements IncomingMessagesFactoryInterface
{
    protected array $messages = [];

    protected ?string $fallbackMessageClass = null;

    public function registerMessage(string $key, string $messageClass): void
    {
        $this->validateMessageClass($messageClass);
        $this->messages[$key] = $messageClass;
    }

    public function registerFallbackMessage(string $messageClass): void
    {
        $this->validateMessageClass($messageClass);
        $this->fallbackMessageClass = $messageClass;
    }

    protected function validateMessageClass(string $messageClass): void
    {
        if (!is_subclass_of($messageClass, CanCreateFromDataMessageInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Message class %s must implement %s', $messageClass, CanCreateFromDataMessageInterface::class)
            );
        }
    }

    public function make(IncomingChannelInterface $channel, array $data): IncomingMessageInterface
    {
        foreach ($this->messages as $messageClass) {
            if ($messageClass::canCreateFromData($data)) {
                return $messageClass::createFromData($channel, $this, $data);
            }
        }

        if ($this->fallbackMessageClass) {
            return $this->fallbackMessageClass::createFromData($channel, $this, $data);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Message class for data %s not found',
                json_encode($data, JSON_THROW_ON_ERROR)
            )
        );
    }
}
