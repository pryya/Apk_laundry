<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ExpensesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // DEFINISIKAN GLOBAK VARIABLE

    protected $expenses;
    protected $user;

    public function __construct($expenses, $user)
    {
        // ASSIGN DATA YANG DITERIMA KE DALAM GLOBAK VARIABLE
        $this->expenses = $expenses;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toDatabase($notifiable)
    {
       return [
           'sendor_id' => $this->user->id,
           'sender_name' => $this->user->name,
           'expenses' => $this->expenses
       ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toBroadcastMessage($notifiable)
    {
        return new BroadcastMessage([
            'sender_id' => $this->user->id,
            'sender_name' => $this->user->name,
            'expenses' => $this->expenses
        ]);
    }
}
