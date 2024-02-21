<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;

interface OutgoingApiMessageFactoryInterface
{
    /**
     * @param class-string<ApiMessageInterface>|array $apiMessageClass
     */
    public function addApiMessageTypeClass(string|array $apiMessageClass): self;

    public function make(CommonOutgoingMessageInterface $message, ?PageInterface $contextPage = null): ApiMessageInterface;
}
