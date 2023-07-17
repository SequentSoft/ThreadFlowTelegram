<?php

namespace SequentSoft\ThreadFlowTelegram\Channel;

use GuzzleHttp\Client;
use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingRegularMessage;

class TelegramOutgoingChannel implements OutgoingChannelInterface
{
    public function __construct(
        protected SimpleConfigInterface $config,
    ) {
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    protected function getClient(string $token): Client
    {
        return new Client([
            'base_uri' => "https://api.telegram.org/bot{$token}/",
        ]);
    }

    protected function sendMessageViaTelegramApi(array $payload): void
    {
        $client = $this->getClient($this->getApiToken());

        $client->post('sendMessage', [
            'json' => $payload,
        ]);
    }

    /**
     * Generate telegram api keyboard payload
     */
    protected function keyboardToArray(
        TextOutgoingRegularMessage $message
    ): array {
        $keyboard = $message->getKeyboard();

        if ($keyboard === null) {
            return [];
        }

        $result = [];

        foreach ($keyboard->getRows() as $row) {
            $result[] = array_map(function ($button) {
                return [
                    'text' => $button->getText(),
                    'callback_data' => $button->getCallbackData() ?? '',
                ];
            }, $row->getButtons());
        }

        return [
            'keyboard' => $result,
            'resize_keyboard' => true,
        ];
    }

    protected function storeKeyboardMapToSession(
        OutgoingMessageInterface $message,
        SessionInterface $session
    ): void {
        $keyboard = $message->getKeyboard();

        if ($keyboard === null) {
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

    public function send(
        OutgoingMessageInterface $message,
        SessionInterface $session
    ): OutgoingMessageInterface {
        $this->storeKeyboardMapToSession($message, $session);

        if ($message instanceof TextOutgoingRegularMessage) {
            $text = $message->getText();
            $this->sendMessageViaTelegramApi(
                array_filter([
                    'chat_id' => $message->getContext()->getRoom()->getId(),
                    'text' => $text,
                    'reply_markup' => $this->keyboardToArray($message),
                ])
            );
        }

        return $message;
    }
}
