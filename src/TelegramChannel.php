<?php

namespace SequentSoft\ThreadFlowTelegram;

use SequentSoft\ThreadFlow\Channel\Channel;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Contracts\Dispatcher\DispatcherInterface;
use SequentSoft\ThreadFlow\Contracts\Events\EventBusInterface;
use SequentSoft\ThreadFlow\Contracts\Keyboard\SimpleKeyboardInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\BasicIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\ClickIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\TextIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionStoreInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\InteractsWithHttpInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular\TelegramClickedIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Testing\PendingTestInput;

class TelegramChannel extends Channel
{
    public function __construct(
        protected string $channelName,
        protected ConfigInterface $config,
        protected SessionStoreInterface $sessionStore,
        protected DispatcherInterface $dispatcher,
        protected EventBusInterface $eventBus,
        protected HttpClientFactoryInterface $httpClientFactory,
        protected IncomingMessagesFactoryInterface $messagesFactory,
        protected OutgoingApiMessageFactoryInterface $outgoingApiMessageFactory,
    ) {
        parent::__construct($channelName, $config, $sessionStore, $dispatcher, $eventBus);
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    public function test(): PendingTestInput
    {
        return new PendingTestInput(
            $this->channelName,
            $this->pendingTestInputCallback(...),
            $this->messagesFactory
        );
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

    protected function prepareIncomingKeyboardClick(
        BasicIncomingMessageInterface $message,
        SessionInterface $session,
        SimpleKeyboardInterface $keyboard
    ): ?ClickIncomingMessageInterface {
        if (! $message instanceof TextIncomingMessageInterface) {
            return null;
        }

        if (! $button = $keyboard->getButtonByTitle($message->getText())) {
            return null;
        }

        if ($button->isAnswerAsText()) {
            return null;
        }

        return TelegramClickedIncomingMessage::make(
            button: $button,
            id: $message->getId(),
            context: $message->getContext(),
            timestamp: $message->getTimestamp(),
        );
    }

    protected function outgoing(
        BasicOutgoingMessageInterface $message,
        ?SessionInterface $session,
        ?PageInterface $contextPage
    ): BasicOutgoingMessageInterface {
        $result = $this->outgoingApiMessageFactory
            ->make($message, $contextPage)
            ->sendVia($this->getHttpClient());

        $message->setId($result['message_id'] ?? null);

        return $message;
    }

    public function getWebhookInfo(): array
    {
        return $this->getHttpClient()->postJson('getWebhookInfo', [])->getParsedData();
    }

    public function deleteWebhook(): array
    {
        return $this->getHttpClient()->postJson('deleteWebhook', [])->getParsedData();
    }

    public function setWebhook(
        string $url,
        ?string $ipAddress = null,
        ?int $maxConnections = null,
        ?string $secretToken = null,
        array $allowedUpdates = []
    ): array {
        return $this->getHttpClient()->postJson('setWebhook', array_filter([
            'url' => $url,
            'ip_address' => $this->config->get('webhook_ip_address') ?? $ipAddress,
            'max_connections' => $this->config->get('webhook_max_connections') ?? $maxConnections,
            'secret_token' => $this->config->get('webhook_secret_token') ?? $secretToken,
            'allowed_updates' => $this->config->get('webhook_allowed_updates') ?? $allowedUpdates,
        ]))->getParsedData();
    }
}
