<?php

use Firebase\JWT\JWT;

if(!function_exists('sendMail'))
{
    function sendMail(array $details){
        dispatch(new \App\Jobs\SendMailJob($details));
    }
}

if(!function_exists('timeInReadble'))
{
    function timeInReadble($time){
        $hours = floor($time / 60);
        $minutes = $time % 60;
        return $hours . ($hours > 1 ? ' hours':' hour') . ' , '. $minutes .' min';
    }
}


if(!function_exists('randomPassword'))
{
    function randomPassword($length) {

		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890$@!%]*}{['; //remember to declare $pass as an array
		
		$pass = array(); //remember to declare $pass as an array
		
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		
		for ($i = 0; $i < $length; $i++) {
			
			$n = rand(0, $alphaLength);
			
			$pass[] = $alphabet[$n];
		
		}
		
		return implode($pass); //turn the array into a string
	
	}
}


if (!function_exists('sendMail')) {
    function sendMail(array $details){
        dispatch(new \App\Jobs\SendMailJob($details));
    }
}

if (!function_exists('sendApptInvites')) {
    function sendApptInvites(array $ids){
        dispatch(new \App\Jobs\SendApptInviteJob($ids));
    }
}


if(!function_exists('gen_uuid'))
{
    function gen_uuid() {
        return sprintf( '%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}


if(!function_exists('generateHmsToken'))
{
    function generateManagementToken()
    {
        $payload = [
            'access_key' => config('app.hms_app_access_key'),
            'type' => 'management',
            'version' => 2,
            'iat' => time(),
            'exp' => time() + 3600,
            'jti' => Str::uuid()->toString(),
        ];

        return JWT::encode($payload, config('app.hms_app_secret_key'), 'HS256');
    }
}


if(!function_exists('createRoom'))
{
    function createRoom($appointment_details)
    {
        $roomName = 'appt_' . \Str::random(8);
        $token = generateManagementToken();

        $response = Http::withToken($token)
            ->post('https://api.100ms.live/v2/rooms', [
                'name' => $roomName,
                'template_id' => config('app.hms_template_id'),
                'description' => $appointment_details['description'],
                'recording_info' => [
                    'enabled' => false,
                ]
            ]);

        $room = $response->json();
        // dd($room);

        return [
            'room_id' => $room['id'],
            'room_name' => $roomName
        ];
    }
}


if (!function_exists('generateQuotationCostTable')) {
    function generateQuotationCostTable($translation) {
        // Get rates from the client's interpretationRates
        $account = $translation->accounts;
        $rateModel = $account->interpretationRates->first();
        $spanishRate = $rateModel->spanish_translation_rate ?? 0;
        $spanishFormatting = $rateModel->spanish_formatting_rate ?? 0;
        $otherRate = $rateModel->other_translation_rate ?? 0;
        $otherFormatting = $rateModel->other_formatting_rate ?? 0;

        $breakdown = [];
        $formattingRequested = $translation->translationDetails->formatting ?? 0;
        $targetLanguages = $translation->translationTargetLanguages;
        $files = $translation->translationFiles;
        foreach ($files as $file) {
            $individual_amount = 0;
            foreach ($targetLanguages as $targetLang) {
                $langName = $targetLang->language->name ?? '';
                $isSpanish = strtolower($langName) === 'spanish';
                $rate = $isSpanish ? $spanishRate : $otherRate;
                $formatting = $formattingRequested ? ($isSpanish ? $spanishFormatting : $otherFormatting) : 0;
                $wordCount = $file->word_count ?? 0;
                $subTotal = $wordCount * $rate;
                $total = $subTotal + $formatting;
                $breakdown[] = [
                    'file_name' => $file->original_file_name,
                    'target_language' => $langName,
                    'rate' => $rate,
                    'word_count' => $wordCount,
                    'formatting' => $formatting,
                    'sub_total' => $subTotal,
                    'total' => $total,
                ];
                $individual_amount += $total;
            }
            $file->update(['amount' => $individual_amount]);
        }
        return $breakdown;
    }
}
