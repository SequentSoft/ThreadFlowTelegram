<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlowTelegram\Enums\Messages\EmojiReaction;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Service\TelegramMessageReactionOutgoingServiceMessage;

trait WithMessageReactions
{
    public function sendReaction(EmojiReaction $reaction): void
    {
        TelegramMessageReactionOutgoingServiceMessage::make(
            $this->getId(),
            $reaction
        )->reply();
    }

    abstract protected function getId();
}
