<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\FileIncomingRegularMessage;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\StickerIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\GetFileTrait;

class TelegramStickerIncomingRegularMessage extends StickerIncomingRegularMessage implements
    CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;
    use GetFileTrait;

    protected ?string $fileId = null;
    protected ?string $fileUniqueId = null;
    protected ?int $fileSize = null;
    protected ?string $botToken = null;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['sticker']);
    }

    public static function createFromData(IncomingChannelInterface $channel, array $data): self
    {
        $message = new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($data),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            url: null,
            name: $data['message']['sticker']['emoji'] ?? null,
            stickerId: null,
            emoji: $data['message']['sticker']['emoji'] ?? null,
        );

        $file = $data['message']['sticker'];

        $message->setFileId($file['file_id']);
        $message->setFileUniqueId($file['file_unique_id']);
        $message->setFileSize($file['file_size'] ?? null);
        $message->setBotToken($channel->getConfig()->get('api_token'));

        $message->setRaw($data);

        return $message;
    }

    public function setBotToken(string $botToken): self
    {
        $this->botToken = $botToken;
        return $this;
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
