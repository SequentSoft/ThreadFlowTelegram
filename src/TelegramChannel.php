<?php

namespace SequentSoft\ThreadFlowTelegram;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Channel\Channel;
use SequentSoft\ThreadFlow\Contracts\Chat\MessageContextInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherFactoryInterface;
use SequentSoft\ThreadFlow\Contracts\Events\EventBusInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\CommonIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\WithKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionStoreInterface;
use SequentSoft\ThreadFlow\Keyboard\Buttons\TextButton;
use SequentSoft\ThreadFlow\Keyboard\InlineKeyboard;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\InteractsWithHttpInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramClickedIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramInlineButtonCallbackIncomingMessage;

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

    protected function testInputText(string $text, MessageContextInterface $context): CommonIncomingMessageInterface
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

    protected function dispatch(CommonIncomingMessageInterface $message, SessionInterface $session): void
    {
        parent::dispatch($this->preprocess($message, $session), $session);
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
        CommonIncomingMessageInterface $message,
        SessionInterface $session,
    ): CommonIncomingMessageInterface {
        if ($message instanceof TelegramInlineButtonCallbackIncomingMessage) {
            $clickMessage = new TelegramClickedIncomingMessage(
                id: $message->getId(),
                context: $message->getContext(),
                timestamp: $message->getTimestamp(),
                button: new TextButton(
                    title: $message->getText(),
                    callbackData: $message->getText(),
                ),
            );

            $clickMessage->setPageId($message->getPageId());

            return $clickMessage;
        }

        if ($message instanceof IncomingMessageInterface) {
            $map = $session->getServiceData()->get('KeyboardMap', []);

            $text = $message->getText();

            if ($text && isset($map[$text])) {
                $button = unserialize($map[$text]);

                if ($button->isAnswerAsText()) {
                    $message->setText($button->getCallbackData());
                    return $message;
                }

                return new TelegramClickedIncomingMessage(
                    id: $message->getId(),
                    context: $message->getContext(),
                    timestamp: $message->getTimestamp(),
                    button: $button,
                );
            }
        }

        return $message;
    }

    protected function storeKeyboardMapToSession(
        CommonOutgoingMessageInterface $message,
        SessionInterface               $session
    ): void {
        $session->getServiceData()->delete('KeyboardMap');

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
                $keyboardMap[$button->getTitle()] = serialize($button);
            }
        }

        $session->getServiceData()->set('KeyboardMap', $keyboardMap);
    }

    protected function outgoing(
        CommonOutgoingMessageInterface $message,
        ?SessionInterface              $session,
        ?PageInterface                 $contextPage
    ): CommonOutgoingMessageInterface {

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
