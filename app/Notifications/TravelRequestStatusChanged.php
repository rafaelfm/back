<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected TravelRequest $travelRequest,
        protected string $previousStatus,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Status do pedido de viagem #{$this->travelRequest->id} atualizado")
            ->greeting("Olá {$notifiable->name},")
            ->line("O status do seu pedido de viagem para {$this->travelRequest->location_label} foi alterado de {$this->previousStatus} para {$this->travelRequest->status}.")
            ->line('Datas:')
            ->line(" • Ida: {$this->travelRequest->departure_date->format('d/m/Y')}")
            ->line(" • Volta: {$this->travelRequest->return_date->format('d/m/Y')}")
            ->line('Caso tenha dúvidas, entre em contato com a equipe responsável.')
            ->salutation('Atenciosamente, Equipe de Viagens Corporativas');
    }
}
