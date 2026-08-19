<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReservationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $passagerNom,
        public string $trajetLieu,
        public int $places
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_reservation',
            'title' => '🚗 Nouvelle réservation !',
            'message' => "{$this->passagerNom} a réservé {$this->places} place(s) pour votre trajet vers {$this->trajetLieu}.",
            'passager' => $this->passagerNom,
            'places' => $this->places
        ];
    }
}
