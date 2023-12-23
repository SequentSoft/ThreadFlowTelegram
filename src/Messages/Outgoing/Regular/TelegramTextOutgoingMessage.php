<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Regular;

use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingMessage;

class TelegramTextOutgoingMessage extends TextOutgoingMessage
{
    protected ?string $parseMode = null;

    public static function make(string $text, $keyboard = null): TelegramTextOutgoingMessage
    {
        return new static($text, $keyboard);
    }

    public function withHtmlParseMode(): self
    {
        $this->parseMode = 'HTML';

        return $this;
    }

    public function withMarkdownParseMode(): self
    {
        $this->parseMode = 'MarkdownV2';

        return $this;
    }

    public function withParseMode(?string $parseMode): self
    {
        $this->parseMode = $parseMode;

        return $this;
    }

    public function getParseMode(): ?string
    {
        return $this->parseMode;
    }
}
