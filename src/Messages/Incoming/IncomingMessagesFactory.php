<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming;

use InvalidArgumentException;
use JsonException;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

class IncomingMessagesFactory implements IncomingMessagesFactoryInterface
{
    protected array $messages = [];

    /** @var class-string<CanCreateFromDataMessageInterface>|null */
    protected ?string $fallbackMessageClass = null;

    public function registerMessage(string $key, string $messageClass): void
    {
        $this->validateMessageClass($messageClass);
        $this->messages[$key] = $messageClass;
    }

    /**
     * @param class-string<CanCreateFromDataMessageInterface> $messageClass
     * @return void
     */
    public function registerFallbackMessage(string $messageClass): void
    {
        $this->validateMessageClass($messageClass);
        $this->fallbackMessageClass = $messageClass;
    }

    /**
     * @param class-string<CanCreateFromDataMessageInterface> $messageClass
     * @return void
     */
    protected function validateMessageClass(string $messageClass): void
    {
        if (!is_subclass_of($messageClass, CanCreateFromDataMessageInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Message class %s must implement %s', $messageClass, CanCreateFromDataMessageInterface::class)
            );
        }
    }

    /**
     * @throws JsonException
     */
    public function make(array $data): IncomingMessageInterface
    {
        foreach ($this->messages as $messageClass) {
            if ($messageClass::canCreateFromData($data)) {
                return $messageClass::createFromData($this, $data);
            }
        }

        if ($this->fallbackMessageClass) {
            /** @var class-string<CanCreateFromDataMessageInterface> $fallbackMessageClass */
            $fallbackMessageClass = $this->fallbackMessageClass;
            return $fallbackMessageClass::createFromData($this, $data);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Message class for data %s not found',
                json_encode($data, JSON_THROW_ON_ERROR)
            )
        );
    }
}
