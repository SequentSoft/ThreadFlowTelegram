<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Regular;

use SequentSoft\ThreadFlow\Contracts\Keyboard\KeyboardInterface;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingRegularMessage;

class TelegramTextOutgoingRegularMessage extends TextOutgoingRegularMessage
{
    public static function make(
        string $text,
        $keyboard = null,
    ): TelegramTextOutgoingRegularMessage {
        return new static($text, $keyboard);
    }
}
