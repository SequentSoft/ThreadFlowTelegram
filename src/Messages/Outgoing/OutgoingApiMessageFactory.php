<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\ApiMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing\OutgoingApiMessageFactoryInterface;

class OutgoingApiMessageFactory implements OutgoingApiMessageFactoryInterface
{
    /**
     * @var array <class-string<ApiMessageInterface>>
     */
    protected array $apiMessageClasses = [];

    /**
     * @param class-string<ApiMessageInterface>|array $apiMessageClass
     */
    public function addApiMessageTypeClass(string|array $apiMessageClass): self
    {
        if (is_array($apiMessageClass)) {
            foreach ($apiMessageClass as $item) {
                $this->addApiMessageTypeClass($item);
            }
            return $this;
        }

        array_unshift($this->apiMessageClasses, $apiMessageClass);

        return $this;
    }

    public function make(CommonOutgoingMessageInterface $message, ?PageInterface $contextPage = null): ApiMessageInterface
    {
        foreach ($this->apiMessageClasses as $apiMessageClass) {
            if ($apiMessageClass::canCreateFromMessage($message)) {
                return $apiMessageClass::createFromMessage($message, $contextPage);
            }
        }

        throw new \InvalidArgumentException('Message type is not supported: ' . get_class($message));
    }
}
