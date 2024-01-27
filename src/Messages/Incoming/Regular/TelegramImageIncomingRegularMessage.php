<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\ImageIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\InteractsWithHttpInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\GetFileTrait;

class TelegramImageIncomingRegularMessage extends ImageIncomingRegularMessage implements
    CanCreateFromDataMessageInterface,
    InteractsWithHttpInterface
{
    use CreatesMessageContextFromDataTrait;
    use GetFileTrait;

    protected ?string $fileId = null;
    protected ?string $fileUniqueId = null;
    protected ?int $fileSize = null;
    protected ?int $width = null;
    protected ?int $height = null;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['photo']);
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, string $channelName, array $data): self
    {
        $message = new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($channelName, $data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            url: null,
            name: null,
        );

        $lastImage = $data['message']['photo'][count($data['message']['photo']) - 1];

        $message->setFileId($lastImage['file_id']);
        $message->setFileUniqueId($lastImage['file_unique_id']);
        $message->setFileSize($lastImage['file_size'] ?? null);
        $message->setWidth($lastImage['width']);
        $message->setHeight($lastImage['height']);

        $message->setRaw($data);

        return $message;
    }

    public function getFileId(): ?string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId): self
    {
        $this->fileId = $fileId;
        return $this;
    }

    public function getFileUniqueId(): ?string
    {
        return $this->fileUniqueId;
    }

    public function setFileUniqueId(string $fileUniqueId): self
    {
        $this->fileUniqueId = $fileUniqueId;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
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
