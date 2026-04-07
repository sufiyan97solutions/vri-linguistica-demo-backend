<?php

// namespace App\Jobs;

// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Facades\Mail;

// class SendMailJob implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     /**
//      * Create a new job instance.
//      */
//     protected $userData;

//     public function __construct(array $userData)
//     {
//         $this->userData = $userData;
//     }

//     public function handle()
//     {
//         try {
//             $name = $this->userData['name'];
//             $last_name = $this->userData['last_name'];
//             $email = $this->userData['email'];
//             $password = $this->userData['password'];
//             $subject = 'Your Account Credentials';
//             $content = 'Your account has been created successfully. Below are your credentials for logging in:';

//             Mail::send('emails.user_created', [
//                 'name' => $name,
//                 'last_name' => $last_name,
//                 'subject' => $subject,
//                 'content' => $content,
//                 'email' => $email,
//                 'password' => $password,
//             ], function ($mail) use ($email, $subject) {
//                 $mail->to($email)
//                     ->subject($subject);
//             });
//         } catch (\Exception $e) {
//             \Log::error($e->getMessage());
//         }
//     }
// }


namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\NotificationMail;
use App\Models\AppointmentInvites;
use Mail;

class SendApptInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $ids;
    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->ids)){
            foreach ($this->ids as $key => $id) {

                $invitee = AppointmentInvites::find($id);
                if($invitee){
                    if($invitee->interpreter->email){

                        $recipient = $invitee->interpreter->email;
                        $subject = 'Appointment Invitation';
                        $link = config('app.frontend_url').'/interview-invite?token='.$invitee->token;
                        $button_text = 'View Appointment';
                        $text = "<p>Dear ".$invitee->interpreter->first_name." ".$invitee->interpreter->last_name.",</p>
                        <p>".$invitee->appointment?->facility?->abbreviation." has a ".$invitee->appointment?->language?->name." appointment available on ".date('M d, Y, h:i a',strtotime($invitee->appointment?->datetime))." at ".$invitee->appointment?->facility?->abbreviation.".
                        </p>
                        <p>
                        To view the appointment, please click the button below to accept if you are available or reject if you are not available.</p>";
                        $payload = [
                            'recipient'=>$recipient,
                            'subject'=>$subject,
                            'link'=>$link,
                            'button_text'=>$button_text,
                            'text'=>$text,
                        ];
                        Mail::to($payload['recipient'])->send(new NotificationMail($payload));
                    }
                }

            }
        }
    }
}
