<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Keyboard\ButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\CommonKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\WithKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Keyboard\InlineKeyboard;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\ApiMessageInterface;

abstract class BaseApiMessage implements ApiMessageInterface
{
    final public function __construct(
        protected OutgoingMessageInterface $outgoingMessage,
        protected ?PageInterface $contextPage = null
    ) {
        if (! $outgoingMessage->getContext()) {
            throw new \InvalidArgumentException('Message context is required');
        }
    }

    public static function createFromMessage(
        OutgoingMessageInterface $outgoingMessage,
        ?PageInterface $contextPage = null
    ): static {
        return new static($outgoingMessage, $contextPage);
    }

    protected function makeInlineKeyboardButton(ButtonInterface $button, ?string $stateId): array
    {
        return [
            'text' => $button->getText(),
            'callback_data' => "{$stateId}:" . $button->getCallbackData() ?? '',
        ];
    }

    protected function makeKeyboardButton(ButtonInterface $button): array
    {
        return array_filter([
            'text' => $button->getText(),
            'request_contact' => $button->isRequestContact(),
            'request_location' => $button->isRequestLocation(),
        ]);
    }

    protected function makeMessageKeyboardPayload(
        OutgoingMessageInterface $message,
        ?PageInterface $contextPage
    ): ?array {
        if (! $message instanceof WithKeyboardInterface) {
            return null;
        }

        $keyboard = $message->getKeyboard();

        if ($keyboard === null) {
            return null;
        }

        $result = [];

        if ($keyboard instanceof InlineKeyboard) {
            $stateId = $contextPage?->getState()?->getId();

            foreach ($keyboard->getRows() as $row) {
                $result[] = array_map(function ($button) use ($stateId) {
                    return $this->makeInlineKeyboardButton($button, $stateId);
                }, $row->getButtons());
            }

            return [
                'inline_keyboard' => $result,
            ];
        }

        foreach ($keyboard->getRows() as $row) {
            $result[] = array_map(function ($button) {
                return $this->makeKeyboardButton($button);
            }, $row->getButtons());
        }

        if ($keyboard instanceof CommonKeyboardInterface) {
            return [
                'keyboard' => $result,
                'input_field_placeholder' => $keyboard->getPlaceholder(),
                'resize_keyboard' => $keyboard->isResizable(),
                'one_time_keyboard' => $keyboard->isOneTime(),
            ];
        }

        return [
            'keyboard' => $result ?: null,
        ];
    }

    public function sendVia(HttpClientInterface $client): array
    {
        if (! $this->outgoingMessage->getContext()) {
            throw new \InvalidArgumentException('Message context is required');
        }

        return $this->send(
            $client,
            $this->outgoingMessage,
            array_filter([
                'chat_id' => $this->outgoingMessage->getContext()->getRoom()->getId(),
                'message_id' => $this->outgoingMessage->getId(),
                'reply_markup' => $this->makeMessageKeyboardPayload($this->outgoingMessage, $this->contextPage),
            ])
        );
    }

    abstract protected function send(
        HttpClientInterface $client,
        OutgoingMessageInterface $outgoingMessage,
        array $data,
    ): array;

    abstract public static function canCreateFromMessage(OutgoingMessageInterface $outgoingMessage): bool;
}
