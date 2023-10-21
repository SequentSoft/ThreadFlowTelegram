<?php

namespace SequentSoft\ThreadFlowTelegram\Channel;

use Closure;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\Regular\IncomingRegularMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\PageStateInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\InteractsWithHttpInterface;

class TelegramIncomingChannel implements IncomingChannelInterface
{
    public function __construct(
        protected HttpClientFactoryInterface $httpClientFactory,
        protected IncomingMessagesFactoryInterface $messagesFactory,
        protected SimpleConfigInterface $config,
    ) {
    }

    protected function getApiToken(): string
    {
        return $this->config->get('api_token');
    }

    public function listen(DataFetcherInterface $fetcher, Closure $callback): void
    {
        $apiToken = $this->getApiToken();

        $fetcher->fetch(function (array $update) use ($callback, $apiToken) {
            $message = $this->messagesFactory->make($update);

            if ($message instanceof InteractsWithHttpInterface) {
                $message->setApiToken($apiToken);
                $message->setHttpClientFactory($this->httpClientFactory);
            }

            $callback($message);
        });
    }

    public function preprocess(
        IncomingMessageInterface $message,
        SessionInterface $session,
        PageStateInterface $pageState
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
}
