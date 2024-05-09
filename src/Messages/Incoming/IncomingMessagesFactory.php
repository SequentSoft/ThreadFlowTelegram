<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming;

use InvalidArgumentException;
use JsonException;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\BasicIncomingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Service\TelegramBotStartedIncomingMessage;

class IncomingMessagesFactory implements IncomingMessagesFactoryInterface
{
    protected array $messageTypes = [];

    /** @var class-string<CanCreateFromDataMessageInterface>|null */
    protected ?string $fallbackMessageClass = null;

    public function addMessageTypeClass(string|array $messageClass): self
    {
        if (is_array($messageClass)) {
            foreach ($messageClass as $item) {
                $this->addMessageTypeClass($item);
            }

            return $this;
        }

        $this->validateMessageClass($messageClass);

        array_unshift($this->messageTypes, $messageClass);

        return $this;
    }

    /**
     * @param class-string<CanCreateFromDataMessageInterface> $messageClass
     */
    public function registerFallbackMessage(string $messageClass): void
    {
        $this->validateMessageClass($messageClass);
        $this->fallbackMessageClass = $messageClass;
    }

    /**
     * @param class-string<CanCreateFromDataMessageInterface> $messageClass
     */
    protected function validateMessageClass(string $messageClass): void
    {
        if (! is_subclass_of($messageClass, CanCreateFromDataMessageInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Message class %s must implement %s', $messageClass, CanCreateFromDataMessageInterface::class)
            );
        }
    }

    /**
     * @throws JsonException
     */
    public function make(string $channelName, array $data): BasicIncomingMessageInterface
    {
        // handle "/start" command
        if (TelegramBotStartedIncomingMessage::canCreateFromData($data)) {
            return TelegramBotStartedIncomingMessage::createFromData($this, $channelName, $data);
        }

        foreach ($this->messageTypes as $messageClass) {
            if ($messageClass::canCreateFromData($data)) {
                return $messageClass::createFromData($this, $channelName, $data);
            }
        }

        if ($this->fallbackMessageClass) {
            /** @var class-string<CanCreateFromDataMessageInterface> $fallbackMessageClass */
            $fallbackMessageClass = $this->fallbackMessageClass;

            $message = $fallbackMessageClass::createFromData($this, $channelName, $data);

            if ($message instanceof BasicIncomingMessageInterface) {
                return $message;
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Fallback message class %s must implement %s',
                    $fallbackMessageClass,
                    BasicIncomingMessageInterface::class
                )
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'Message class for data %s not found',
                json_encode($data, JSON_THROW_ON_ERROR)
            )
        );
    }
}
