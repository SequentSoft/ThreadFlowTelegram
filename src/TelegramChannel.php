<?php

namespace SequentSoft\ThreadFlowTelegram;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Channel\Channel;
use SequentSoft\ThreadFlow\Contracts\Chat\MessageContextInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherFactoryInterface;
use SequentSoft\ThreadFlow\Contracts\Events\EventBusInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\IncomingRegularMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\WithKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionStoreInterface;
use SequentSoft\ThreadFlow\Keyboard\InlineKeyboard;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\InteractsWithHttpInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;

class TelegramChannel extends Channel
{
    public function __construct(
        protected string $channelName,
        protected ConfigInterface $config,
        protected SessionStoreInterface $sessionStore,
        protected DispatcherFactoryInterface $dispatcherFactory,
        protected EventBusInterface $eventBus,
        protected HttpClientFactoryInterface $httpClientFactory,
        protected IncomingMessagesFactoryInterface $messagesFactory,
        protected OutgoingApiMessageFactoryInterface $outgoingApiMessageFactory,
    ) {
        parent::__construct(
            $channelName,
            $config,
            $sessionStore,
            $dispatcherFactory,
            $eventBus,
        );
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    protected function testInputText(string $text, MessageContextInterface $context): IncomingMessageInterface
    {
        return $this->messagesFactory->make($this->channelName, [
            'message' => [
                'from' => [
                    'id' => $context->getParticipant()->getId(),
                ],
                'chat' => [
                    'id' => $context->getRoom()->getId(),
                    'type' => $context->getRoom()->getType() ?? 'private',
                ],
                'text' => $text,
                'message_id' => 'test',
                'date' => (new DateTimeImmutable())->getTimestamp(),
            ],
        ]);
    }

    protected function dispatch(IncomingMessageInterface $message, SessionInterface $session): void
    {
        $this->preprocess($message, $session);
        parent::dispatch($message, $session);
    }

    public function listen(DataFetcherInterface $fetcher): void
    {
        $apiToken = $this->getApiToken();

        $fetcher->fetch(function (array $update) use ($apiToken) {
            $message = $this->messagesFactory->make($this->channelName, $update);

            if ($message instanceof InteractsWithHttpInterface) {
                $message->setApiToken($apiToken);
                $message->setHttpClientFactory($this->httpClientFactory);
            }

            $this->incoming($message);
        });
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClientFactory->create($this->getApiToken());
    }

    protected function preprocess(
        IncomingMessageInterface $message,
        SessionInterface $session,
    ): IncomingMessageInterface {
        if ($message instanceof IncomingRegularMessageInterface) {
            $map = $session->get('$telegramKeyboardMap', []);

            $text = $message->getText();

            if ($text && isset($map[$text])) {
                $message->setText($map[$text]);
            }
        }

        return $message;
    }

    protected function storeKeyboardMapToSession(
        OutgoingMessageInterface $message,
        SessionInterface $session
    ): void {
        $session->delete('$telegramKeyboardMap');

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

    protected function outgoing(
        OutgoingMessageInterface $message,
        ?SessionInterface $session,
        ?PageInterface $contextPage
    ): OutgoingMessageInterface {

        if ($session) {
            $this->storeKeyboardMapToSession($message, $session);
        }

        $apiMessage = $this->outgoingApiMessageFactory
            ->make($message, $contextPage);

        $result = $apiMessage->sendVia(
            $this->getHttpClient()
        );

        $message->setId($result['message_id'] ?? null);

        return $message;
    }
}
