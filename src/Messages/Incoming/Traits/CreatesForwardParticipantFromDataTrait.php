<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\Participant;

trait CreatesForwardParticipantFromDataTrait
{
    public static function createForwardParticipantFromData(array $data): ?Participant
    {
        $id = $data['message']['forward_from']['id'] ?? null;

        if (!$id) {
            return null;
        }

        $participant = new Participant($id);

        return $participant
            ->setFirstName($data['message']['forward_from']['first_name'] ?? '')
            ->setLastName($data['message']['forward_from']['last_name'] ?? '')
            ->setUsername($data['message']['forward_from']['username'] ?? '')
            ->setLanguage($data['message']['forward_from']['language_code'] ?? '')
        ;
    }
}
