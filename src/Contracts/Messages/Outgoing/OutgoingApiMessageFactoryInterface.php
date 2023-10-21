<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;

interface OutgoingApiMessageFactoryInterface
{
    /**
     * @param class-string<ApiMessageInterface>|array $apiMessageClass
     */
    public function addApiMessageTypeClass(string|array $apiMessageClass): self;

    public function make(OutgoingMessageInterface $message, ?PageInterface $contextPage = null): ApiMessageInterface;
}
