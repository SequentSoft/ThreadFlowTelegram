<?php

namespace SequentSoft\ThreadFlowTelegram\Channel;

use SequentSoft\ThreadFlow\Contracts\Channel\Outgoing\OutgoingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\WithKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Keyboard\InlineKeyboard;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\ApiMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;

class TelegramOutgoingChannel implements OutgoingChannelInterface
{
    public function __construct(
        protected HttpClientFactoryInterface $httpClientFactory,
        protected OutgoingApiMessageFactoryInterface $outgoingApiMessageFactory,
        protected SimpleConfigInterface $config,
    ) {
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClientFactory->create($this->getApiToken());
    }

    public function send(
        OutgoingMessageInterface $message,
        SessionInterface $session,
        ?PageInterface $contextPage = null
    ): OutgoingMessageInterface {
        $this->storeKeyboardMapToSession($message, $session);

        $apiMessage = $this->outgoingApiMessageFactory
            ->make($message, $contextPage);

        $result = $apiMessage->sendVia(
            $this->getHttpClient()
        );

        $message->setId($result['message_id'] ?? null);

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
}
