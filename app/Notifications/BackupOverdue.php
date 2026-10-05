<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Notifications\Messages\MailMessage;

class BackupOverdue extends AbstractNotification
{
    public function __construct(protected Backup $backup) {}

    public function rawText(): string
    {
        return __("A scheduled :type backup on server [:server] did not run.\nCheck that Vito's scheduler and queue are running.\n:link", [
            'type' => $this->backup->type->getText(),
            'server' => $this->backup->server->name,
            'link' => url('/servers/'.$this->backup->server_id.'/backups/'.$this->backup->id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Backup overdue'))
            ->line(__('A scheduled :type backup on your server [:server] did not run.', [
                'type' => $this->backup->type->getText(),
                'server' => $this->backup->server->name,
            ]))
            ->line(__("Check that Vito's scheduler and queue are running."))
            ->action(__('View backup'), url('/servers/'.$this->backup->server_id.'/backups/'.$this->backup->id));
    }
}
