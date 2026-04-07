<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreated extends Mailable
{
    use Queueable, SerializesModels;

    protected $userData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return $this->subject('Your Account Credentials')
                    ->view('emails.user_created')
                    ->with([
                        'name' => $this->userData['name'],
                        'email' => $this->userData['email'],
                        'password' => $this->userData['password'],
                        'content' => 'Your account has been created successfully. Below are your credentials for logging in:'
                    ]);
    }

}
