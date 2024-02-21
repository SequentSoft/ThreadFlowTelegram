<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Service;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Messages\Outgoing\Service\OutgoingServiceMessage;
use SequentSoft\ThreadFlowTelegram\Enums\Messages\EmojiReaction;

class TelegramMessageReactionCommonOutgoingServiceMessage extends OutgoingServiceMessage implements CommonOutgoingMessageInterface
{
    protected bool $isBig = false;

    final public function __construct(protected string $targetMessageId, protected EmojiReaction $reaction)
    {
    }

    public static function make(string $targetMessageId, EmojiReaction $reaction): static
    {
        return new static($targetMessageId, $reaction);
    }

    public function isBig(): bool
    {
        return $this->isBig;
    }

    public function setIsBig(bool $isBig = true): self
    {
        $this->isBig = $isBig;

        return $this;
    }

    public function getType(): string
    {
        return 'emoji';
    }

    public function getTargetMessageId(): string
    {
        return $this->targetMessageId;
    }

    public function getReaction(): EmojiReaction
    {
        return $this->reaction;
    }
}
