<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use GuzzleHttp\Client;

trait GetFileTrait
{
    public function getTelegramFileData(string $token, string $fileId): array
    {
        $client = new Client([
            'base_uri' => "https://api.telegram.org/bot{$token}/",
        ]);

        $response = $client->post('getFile', [
            'json' => [
                'file_id' => $fileId,
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return  $response['result'];
    }

    public function getTelegramFileUrl(string $token, string $fileId): string
    {
        $data = $this->getTelegramFileData($token, $fileId);

        return "https://api.telegram.org/file/bot{$token}/" . $data['file_path'];
    }
}
