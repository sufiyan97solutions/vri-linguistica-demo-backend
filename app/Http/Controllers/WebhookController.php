<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AppointmentDetail;
use App\Models\AppointmentLog;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        
        Log::info('webhook: '.$request->type.' : '. json_encode($request->data));
        
        $event = $request->type;
        $data = $request->data;
        $roomId = $data['room_id'] ?? null;

        $room = AppointmentDetail::where('room_id', $roomId)->first();

        if (!$room) {
            Log::warning("Unknown room ID: $roomId");
            return response()->json(['message' => 'Room not found'], 404);
        }

        switch ($event) {
            case 'peer.join.success':
                if($data['role'] == 'provider'){
                    $log_changes = 'Provider: ' . $room->provider_name . ' joined the video call';
                }
                else if($data['role'] == 'interpreter'){
                    $log_changes = 'Interpreter: ' . $room->appointment->interpreter->user->name . ' joined the video call';
                }
                else{
                    break;
                }

                AppointmentLog::create([
                    'appointment_id' => $room->appointment->id,
                    'date' => date('Y-m-d',strtotime($data['joined_at'])),
                    'time' => date('h:i a',strtotime($data['joined_at'])),
                    'event' => ucfirst($data['role']). ' joined the video call',
                    'notes' => $log_changes,
                ]);
                if($data['role'] == 'interpreter'){
                    if($room->appointment->appointmentAssign->checkin_date == NULL){
                        $room->appointment->appointmentAssign()->update([
                            'checkin_date' => date('Y-m-d',strtotime($data['joined_at'])),
                            'checkin_time' => date('H:i',strtotime($data['joined_at'])),
                        ]);
                    }
                }
                break;

            case 'peer.leave.success':
                // Update leave time
                if($data['role'] == 'provider'){
                    $log_changes = 'Provider: ' . $room->provider_name . ' left the video call';
                }
                else if($data['role'] == 'interpreter'){
                    $log_changes = 'Interpreter: ' . $room->appointment->interpreter->user->name . ' left the video call';
                }
                else{
                    break;
                }

                AppointmentLog::create([
                    'appointment_id' => $room->appointment->id,
                    'date' => date('Y-m-d',strtotime($data['left_at'])),
                    'time' => date('h:i a',strtotime($data['left_at'])),
                    'event' => ucfirst($data['role']). ' left the video call',
                    'notes' => $log_changes,
                ]);
                if($data['role'] == 'interpreter'){
                    // if($room->appointment->appointmentAssign->checkout_date == NULL){
                        $room->appointment->appointmentAssign()->update([
                            'checkout_date' => date('Y-m-d',strtotime($data['left_at'])),
                            'checkout_time' => date('H:i',strtotime($data['left_at'])),
                        ]);
                    // }
                }
                break;

            // default:
            //     Log::info('webhook: '. json_encode($request));
            case 'beam.recording.success':
                $recordingUrl = $data['recording_path'] ?? null;
                if ($recordingUrl) {
                    $recordingUrl = str_replace('s3:','https:',$recordingUrl);
                    $room->update([
                        'recording'=>$recordingUrl
                    ]);

                    AppointmentLog::create([
                        'appointment_id' => $room->appointment->id,
                        'date' => date('Y-m-d'),
                        'time' => date('h:i a'),
                        'event' => 'recording available',
                        'notes' => 'Recording is now available for VRI',
                    ]);
                }

                break;
        }

        return response()->json(['status' => 'ok']);
    }
}
