<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlowTelegram\Enums\Messages\EmojiReaction;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Service\TelegramMessageReactionOutgoingServiceMessage;

trait WithMessageReactions
{
    public function addReaction(string|EmojiReaction $reaction): self
    {
        if (is_string($reaction)) {
            $reaction = EmojiReaction::tryFrom($reaction);
        }

        if (! $reaction) {
            throw new \InvalidArgumentException('This emoji is not supported as a reaction');
        }

        TelegramMessageReactionOutgoingServiceMessage::make(
            $this->getId(),
            $reaction
        )->reply();

        return $this;
    }

    abstract protected function getId();
}
