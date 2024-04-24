<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\Room;

trait CreatesRoomFromDataTrait
{
    public static function createRoomFromData(array $data): Room
    {
        $room = new Room($data['message']['chat']['id']);

        return $room
            ->setName($data['message']['chat']['title'] ?? '')
            ->setType($data['message']['chat']['type']);
    }
}
