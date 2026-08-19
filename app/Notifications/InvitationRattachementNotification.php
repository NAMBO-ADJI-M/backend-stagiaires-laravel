<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvitationRattachementNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $entrepriseNom,
        public string $code
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invitation_rattachement',
            'title' => '🤝 Nouvelle invitation !',
            'message' => "L'entreprise {$this->entrepriseNom} souhaite assurer le suivi de votre stage. Enregistrez vite le code : {$this->code}",
            'code' => $this->code,
            'entreprise' => $this->entrepriseNom,
        ];
    }
}
