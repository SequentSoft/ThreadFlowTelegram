<?php

namespace SequentSoft\ThreadFlowTelegram\Channel;

use Closure;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Config\SimpleConfigInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Session\SessionInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

class TelegramIncomingChannel implements IncomingChannelInterface
{
    public function __construct(
        protected IncomingMessagesFactoryInterface $messagesFactory,
        protected SimpleConfigInterface $config,
    ) {}

    public function listen(DataFetcherInterface $fetcher, Closure $callback): void
    {
        $fetcher->fetch(fn (array $update) => $callback(
            $this->messagesFactory->make($update)
        ));
    }

    protected function replaceTextUsingKeyboardMapping(
        IncomingMessageInterface $message,
        SessionInterface $session
    ): IncomingMessageInterface {
        $map = $session->get('$telegramKeyboardMap', []);

        $text = $message->getText();

        if ($text && isset($map[$text])) {
            $message->setText($map[$text]);
        }

        return $message;
    }

    public function preprocess(IncomingMessageInterface $message, SessionInterface $session): IncomingMessageInterface
    {
        return $this->replaceTextUsingKeyboardMapping($message, $session);
    }

    public function config(): SimpleConfigInterface
    {
        return $this->config;
    }
}
