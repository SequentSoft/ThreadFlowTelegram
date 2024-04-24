<?php

namespace SequentSoft\ThreadFlowTelegram\Testing;

use Closure;
use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\BasicIncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Testing\ResultsRecorderInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

class PendingTestInput extends \SequentSoft\ThreadFlow\Testing\PendingTestInput
{
    public function __construct(
        protected string $channelName,
        protected Closure $run,
        protected IncomingMessagesFactoryInterface $messagesFactory,
    ) {
        parent::__construct($channelName, $run);
    }

    /**
     * Send a text message or a message instance of any type to the fake channel.
     */
    public function input(string|BasicIncomingMessageInterface|Closure $message): ResultsRecorderInterface
    {
        if ($message instanceof Closure) {
            $message = $message($this->getContext());
        }

        $message = is_string($message)
            ? $this->messagesFactory->make($this->channelName, [
                'message' => [
                    'from' => [
                        'id' => $this->getContext()->getParticipant()->getId(),
                    ],
                    'chat' => [
                        'id' => $this->getContext()->getRoom()->getId(),
                        'type' => $this->getContext()->getRoom()->getType() ?? 'private',
                    ],
                    'text' => $message,
                    'message_id' => 'test',
                    'date' => (new DateTimeImmutable())->getTimestamp(),
                ],
            ])
            : $message;

        return $this->run($message);
    }
}
