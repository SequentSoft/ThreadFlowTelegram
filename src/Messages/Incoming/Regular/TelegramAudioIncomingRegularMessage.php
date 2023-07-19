<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\AudioIncomingRegularMessage;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\FileIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\GetFileTrait;

class TelegramAudioIncomingRegularMessage extends AudioIncomingRegularMessage implements
    CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;
    use GetFileTrait;

    protected ?string $fileId = null;
    protected ?string $fileUniqueId = null;
    protected ?int $fileSize = null;
    protected ?string $mimetype = null;
    protected ?int $duration = null;
    protected ?string $botToken = null;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['voice']);
    }

    public static function createFromData(
        IncomingChannelInterface $channel,
        IncomingMessagesFactoryInterface $factory,
        array $data
    ): self {
        $message = new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($data, $channel, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            url: null,
            name: null,
        );

        $file = $data['message']['voice'];

        $message->setFileId($file['file_id']);
        $message->setFileUniqueId($file['file_unique_id']);
        $message->setFileSize($file['file_size'] ?? null);
        $message->setMimetype($file['mime_type'] ?? null);
        $message->setDuration($file['duration'] ?? null);
        $message->setBotToken($channel->getConfig()->get('api_token'));

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

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(string $mimetype): self
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    public function setBotToken(string $botToken): self
    {
        $this->botToken = $botToken;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getUrl(): ?string
    {
        if (! is_null($this->url)) {
            return $this->url;
        }

        $this->url = $this->getTelegramFileUrl(
            $this->botToken,
            $this->fileId,
        );

        return $this->url;
    }
}
