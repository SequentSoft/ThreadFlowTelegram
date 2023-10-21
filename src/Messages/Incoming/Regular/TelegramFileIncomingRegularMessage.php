<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\FileIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\InteractsWithHttpInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\GetFileTrait;

class TelegramFileIncomingRegularMessage extends FileIncomingRegularMessage implements
    CanCreateFromDataMessageInterface,
    InteractsWithHttpInterface
{
    use CreatesMessageContextFromDataTrait;
    use GetFileTrait;

    protected ?string $fileId = null;
    protected ?string $fileUniqueId = null;
    protected ?int $fileSize = null;
    protected ?string $mimetype = null;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['document'])
            && ! isset($data['message']['animation']);
    }

    public function setFileId(string $fileId): self
    {
        $this->fileId = $fileId;
        return $this;
    }

    public function getFileId(): ?string
    {
        return $this->fileId;
    }

    public function setFileUniqueId(string $fileUniqueId): self
    {
        $this->fileUniqueId = $fileUniqueId;
        return $this;
    }

    public function getFileUniqueId(): ?string
    {
        return $this->fileUniqueId;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setMimetype(string $mimetype): self
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, array $data): self
    {
        $document = $data['message']['document'];

        $message = new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            url: null,
            name: $document['file_name'] ?? null,
        );

        $message->setFileId($document['file_id']);
        $message->setFileUniqueId($document['file_unique_id']);
        $message->setFileSize($document['file_size'] ?? null);
        $message->setMimetype($document['mime_type'] ?? null);

        $message->setRaw($data);

        return $message;
    }

    public function getUrl(): ?string
    {
        if (! is_null($this->url)) {
            return $this->url;
        }

        $this->url = $this->getTelegramFileUrl($this->fileId);

        return $this->url;
    }
}
