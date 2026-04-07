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
use Mail;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $details;
    protected $order_id;
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->details['subject']=='Your Account Credentials'){
                    try {
                        $email=$this->details['email'];
                        $subject=$this->details['subject'];
                        Mail::send('emails.user_created', [
                            'name' => $this->details['name'],
                            'subject' => $this->details['subject'],
                            'content' => $this->details['content'],
                            'email' => $this->details['email'],
                            'password' => $this->details['password'],
                        ], function ($mail) use ($email, $subject) {
                            $mail->to($email)
                                ->subject($subject);
                        });
                    } catch (\Exception $e) {
                        \Log::error($e->getMessage());
                    }
        }
        else{
            $mail = new \App\Mail\NotificationMail($this->details);
            if (!empty($this->details['quotation_pdf']) && file_exists($this->details['quotation_pdf'])) {
                $mail->attach($this->details['quotation_pdf'], [
                    'as' => 'Quotation.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
            if(is_array($this->details['recipient'])){
                // Make first valid email as 'to', others as 'cc'
                $to = null;
                $cc = [];

                foreach ($this->details['recipient'] as $recipient) {
                    if (is_string($recipient) && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        if ($to === null) {
                            $to = $recipient;
                        } else {
                            $cc[] = $recipient;
                        }
                    }
                }

                if ($to) {
                    Mail::to($to)->cc($cc)->send($mail);
                }
            }
            else{
                Mail::to($this->details['recipient'])->send($mail);
            }
        }
    }
}
