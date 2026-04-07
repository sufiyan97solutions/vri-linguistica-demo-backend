<?php
namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    // public function toMail($notifiable)
    // {
    //     $frontendUrl = config('app.frontend_url');

    //     return (new MailMessage)
    //         ->subject('Reset Password Notification')
    //         ->line('You are receiving this email because we received a password reset request for your account.')
    //         ->action('Reset Password', "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}")
    //         ->line('If you did not request a password reset, no further action is required.');
    // }


    public function toMail($notifiable)
    {
        $frontendUrl = config('app.frontend_url');
        $logoUrl = asset('logo.png');
        $name = $notifiable->name;

        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->greeting("Hello, {$name}!")
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}")
            ->line('If you did not request a password reset, no further action is required.')
            ->markdown('emails.custom_reset', [
                'url' => "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}",
                'logoUrl' => $logoUrl,
                'name' => $name,
            ]);
    }


}
