<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use SequentSoft\ThreadFlow\Messages\Incoming\Regular\ClickIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\WithMessageReactions;

class TelegramClickedIncomingMessage extends ClickIncomingMessage
{
    use WithMessageReactions;
}
