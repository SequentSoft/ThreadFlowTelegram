<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\Participant;

trait CreatesParticipantFromDataTrait
{
    public static function createParticipantFromData(array $data): Participant
    {
        $participant = new Participant($data['message']['from']['id']);

        return $participant
            ->setFirstName($data['message']['from']['first_name'] ?? '')
            ->setLastName($data['message']['from']['last_name'] ?? '')
            ->setUsername($data['message']['from']['username'] ?? '')
            ->setLanguage($data['message']['from']['language_code'] ?? '')
        ;
    }
}
