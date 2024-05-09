<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Keyboard\ButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\Buttons\BackButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\Buttons\ContactButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\Buttons\LocationButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\Buttons\TextButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\SimpleKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\WithKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Keyboard\InlineKeyboard;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\ApiMessageInterface;

abstract class BaseApiMessage implements ApiMessageInterface
{
    final public function __construct(
        protected BasicOutgoingMessageInterface $outgoingMessage,
        protected ?PageInterface $contextPage = null
    ) {
        if (! $outgoingMessage->getContext()) {
            throw new \InvalidArgumentException('Message context is required');
        }
    }

    public static function createFromMessage(
        BasicOutgoingMessageInterface $outgoingMessage,
        ?PageInterface $contextPage = null
    ): static {
        return new static($outgoingMessage, $contextPage);
    }

    protected function makeInlineKeyboardButton(ButtonInterface $button, ?string $stateId): array
    {
        return match (true) {
            $button instanceof ContactButtonInterface => [
                'text' => $button->getTitle(),
                'request_contact' => true,
            ],
            $button instanceof LocationButtonInterface => [
                'text' => $button->getTitle(),
                'request_location' => true,
            ],
            $button instanceof TextButtonInterface, $button instanceof BackButtonInterface => [
                'text' => $button->getTitle(),
                'callback_data' => "{$stateId}:" . ($button->getCallbackData() ?? ''),
            ],
            default => [
                'text' => $button->getTitle(),
                'callback_data' => "{$stateId}:" . $button->getTitle(),
            ]
        };
    }

    protected function makeKeyboardButton(ButtonInterface $button): array
    {
        return match (true) {
            $button instanceof ContactButtonInterface => [
                'text' => $button->getTitle(),
                'request_contact' => true,
            ],
            $button instanceof LocationButtonInterface => [
                'text' => $button->getTitle(),
                'request_location' => true,
            ],
            $button instanceof TextButtonInterface, $button instanceof BackButtonInterface => [
                'text' => $button->getTitle(),
                'callback_data' => $button->getCallbackData() ?? '',
            ],
            default => [
                'text' => $button->getTitle(),
                'callback_data' => $button->getTitle(),
            ]
        };
    }

    protected function makeReplyMarkup(
        BasicOutgoingMessageInterface $message,
        ?PageInterface $contextPage
    ): ?array {
        $replyMarkup = [];

        $keyboard = $this->makeMessageKeyboardPayload($message, $contextPage);

        if ($keyboard) {
            $replyMarkup = array_merge($replyMarkup, $keyboard);
        } else {
            $replyMarkup['remove_keyboard'] = true;
        }

        return $replyMarkup;
    }

    protected function makeMessageKeyboardPayload(
        BasicOutgoingMessageInterface $message,
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
            $stateId = $contextPage?->getId() ?? '';

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

        if ($keyboard instanceof SimpleKeyboardInterface) {
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
                'reply_markup' => $this->makeReplyMarkup($this->outgoingMessage, $this->contextPage),
            ])
        );
    }

    abstract protected function send(
        HttpClientInterface $client,
        BasicOutgoingMessageInterface $outgoingMessage,
        array $data,
    ): array;

    abstract public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool;
}
