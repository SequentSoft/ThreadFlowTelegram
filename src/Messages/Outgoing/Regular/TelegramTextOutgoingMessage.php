<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Regular;

use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingMessage;

class TelegramTextOutgoingMessage extends TextOutgoingMessage
{
    public static function make(string $text, $keyboard = null): TelegramTextOutgoingMessage
    {
        return new static($text, $keyboard);
    }
}
