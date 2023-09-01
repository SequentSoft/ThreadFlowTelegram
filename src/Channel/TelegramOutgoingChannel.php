<?php

namespace SequentSoft\ThreadFlowTelegram\Channel;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\ButtonInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\CommonKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\FileOutgoingRegularMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\ForwardOutgoingRegularMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\ImageOutgoingRegularMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Service\TypingOutgoingServiceMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\WithKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Keyboard\InlineKeyboard;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingMessage;

class TelegramOutgoingChannel implements OutgoingChannelInterface
{
    public function __construct(
        protected SimpleConfigInterface $config,
    ) {
    }

    public function send(
        OutgoingMessageInterface $message,
        SessionInterface $session,
        ?PageInterface $contextPage = null
    ): OutgoingMessageInterface {
        $this->storeKeyboardMapToSession($message, $session);

        if ($message instanceof TextOutgoingMessage) {
            $text = $message->getText();

            if ($message->getId()) {
                $this->editMessageTextViaTelegramApi(
                    array_filter([
                        'chat_id' => $message->getContext()->getRoom()->getId(),
                        'message_id' => $message->getId(),
                        'text' => $text,
                        'reply_markup' => $this->keyboardToArray($message, $contextPage),
                    ])
                );
            } else {
                $result = $this->sendMessageViaTelegramApi(
                    array_filter([
                        'chat_id' => $message->getContext()->getRoom()->getId(),
                        'text' => $text,
                        'reply_markup' => $this->keyboardToArray($message, $contextPage),
                    ])
                );

                $message->setId($result['message_id'] ?? null);
            }
        }

        if ($message instanceof ForwardOutgoingRegularMessageInterface) {
            $result = $this->sendForwardViaTelegramApi(
                array_filter([
                    'chat_id' => $message->getContext()->getRoom()->getId(),
                    'from_chat_id' => $message->getTargetMessage()->getContext()->getRoom()->getId(),
                    'message_id' => $message->getTargetMessage()->getId(),
                    'reply_markup' => $this->keyboardToArray($message, $contextPage),
                ])
            );

            $message->setId($result['message_id'] ?? null);
        }

        if ($message instanceof ImageOutgoingRegularMessageInterface) {
            $result = $this->sendPhotoViaTelegramApi(
                array_filter([
                    'chat_id' => $message->getContext()->getRoom()->getId(),
                    'photo' => $message->getImageUrl(),
                    'caption' => $message->getCaption(),
                    'reply_markup' => $this->keyboardToArray($message, $contextPage),
                ])
            );

            $message->setId($result['message_id'] ?? null);
        }

        if ($message instanceof FileOutgoingRegularMessageInterface) {
            if ($message->getPath() !== null) {
                $client = $this->getClient($this->getApiToken());

                $response = $client->post('sendDocument', [
                    'multipart' => [
                        [
                            'name' => 'chat_id',
                            'contents' => $message->getContext()->getRoom()->getId(),
                        ],
                        [
                            'name' => 'document',
                            'contents' => Utils::tryFopen($message->getPath(), 'r'),
                        ],
                        [
                            'name' => 'caption',
                            'contents' => $message->getCaption(),
                        ],
                        [
                            'name' => 'reply_markup',
                            'contents' => $this->keyboardToArray($message, $contextPage) ?: '',
                        ],
                    ],
                ]);

                $result = json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                )['result'] ?? [];

                $message->setId($result['message_id'] ?? null);

                return $message;
            }

            $payload = array_filter([
                'chat_id' => $message->getContext()->getRoom()->getId(),
                'document' => $message->getUrl(),
                'caption' => $message->getCaption(),
                'reply_markup' => $this->keyboardToArray($message, $contextPage),
            ]);

            $client = $this->getClient($this->getApiToken());

            $response = $client->post('sendDocument', [
                'json' => $payload,
            ]);

            $result = json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['result'] ?? [];

            $message->setId($result['message_id'] ?? null);

            return $message;
        }

        if ($message instanceof TypingOutgoingServiceMessageInterface) {
            $this->sendTypingMessageViaTelegramApi(
                array_filter([
                    'chat_id' => $message->getContext()->getRoom()->getId(),
                    'action' => $message->getType()->value,
                ])
            );
        }

        return $message;
    }

    protected function storeKeyboardMapToSession(
        OutgoingMessageInterface $message,
        SessionInterface $session
    ): void {
        if (! $message instanceof WithKeyboardInterface) {
            return;
        }

        $keyboard = $message->getKeyboard();

        if ($keyboard === null) {
            return;
        }

        if ($keyboard instanceof InlineKeyboard) {
            return;
        }

        $keyboardMap = [];

        foreach ($keyboard->getRows() as $row) {
            foreach ($row->getButtons() as $button) {
                $keyboardMap[$button->getText()] = $button->getCallbackData() ?? '';
            }
        }

        $session->set('$telegramKeyboardMap', $keyboardMap);
    }

    protected function editMessageTextViaTelegramApi(array $payload): array
    {
        $client = $this->getClient($this->getApiToken());

        $response = $client->post('editMessageText', [
            'json' => $payload,
        ]);

        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['result'] ?? [];
    }

    protected function getClient(string $token): Client
    {
        return new Client([
            'base_uri' => "https://api.telegram.org/bot{$token}/",
        ]);
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    /**
     * Generate telegram api keyboard payload
     */
    protected function keyboardToArray(
        OutgoingMessageInterface $message,
        ?PageInterface $contextPage
    ): array {
        if (! $message instanceof WithKeyboardInterface) {
            return [];
        }

        $keyboard = $message->getKeyboard();

        if ($keyboard === null) {
            return [];
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
            'keyboard' => $result,
        ];
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

    protected function sendMessageViaTelegramApi(array $payload): array
    {
        $client = $this->getClient($this->getApiToken());

        $response = $client->post('sendMessage', [
            'json' => $payload,
        ]);

        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['result'] ?? [];
    }

    protected function sendForwardViaTelegramApi(array $payload): array
    {
        $client = $this->getClient($this->getApiToken());

        $response = $client->post('forwardMessage', [
            'json' => $payload,
        ]);

        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['result'] ?? [];
    }

    protected function sendPhotoViaTelegramApi(array $payload): array
    {
        $client = $this->getClient($this->getApiToken());

        $response = $client->post('sendPhoto', [
            'json' => $payload,
        ]);

        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['result'] ?? [];
    }

    protected function sendTypingMessageViaTelegramApi(array $payload): void
    {
        $client = $this->getClient($this->getApiToken());

        $client->post('sendChatAction', [
            'json' => $payload,
        ]);
    }
}
