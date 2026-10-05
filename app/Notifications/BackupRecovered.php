<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Notifications\Messages\MailMessage;

class BackupRecovered extends AbstractNotification
{
    public function __construct(protected Backup $backup) {}

    public function rawText(): string
    {
        return __("The :type backup on server [:server] is running again.\n:link", [
            'type' => $this->backup->type->getText(),
            'server' => $this->backup->server->name,
            'link' => url('/servers/'.$this->backup->server_id.'/backups/'.$this->backup->id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject(__('Backup recovered'))
            ->line(__('The :type backup on your server [:server] is running again.', [
                'type' => $this->backup->type->getText(),
                'server' => $this->backup->server->name,
            ]))
            ->action(__('View backup'), url('/servers/'.$this->backup->server_id.'/backups/'.$this->backup->id));
    }
}
