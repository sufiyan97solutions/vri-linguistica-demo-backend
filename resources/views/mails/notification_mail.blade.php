<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Notifications</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
            background-color: #f4f4f7;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-top: 3%;
        }

        .email-header {
            text-align: center;
            padding: 20px;
            background-color: #f4f4f7;
        }

        .email-header img {
            max-width: 180px;
        }

        .email-body {
            padding: 30px;
        }

        .email-body h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .email-body p {
            font-size: 16px;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-image: none;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .email-footer {
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #999;
        }

        .email-footer p {
            margin-bottom: 5px;
        }

        .image-header {
            height: 50px !important;
            display: flex;
            justify-content: center;
        }

        .button-primary {
            color: white !important;
            font-weight: bold;
            background-color: #6f8dff !important;
            border-bottom: 8px solid #6f8dff !important;
            border-left: 18px solid #6f8dff !important;
            border-right: 18px solid #6f8dff !important;
            border-top: 8px solid #6f8dff !important;
            color: #f5f7ff !important;
            text-decoration: none !important
        }
        @media(max-width: 500px){
          ul{
            padding:0px;
            padding-left:12px;
          }
        }
    </style>
</head>

<body style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; -webkit-text-size-adjust: none; background-color: #ffffff; color: #718096; height: 100%; line-height: 1.4; margin: 0; padding: 0; width: 100% !important;">
    <div class="email-container" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); overflow: hidden; margin-top: 3%;">

        <!-- Email Body -->
        <div class="email-body" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; padding: 30px;">
            <div class="image-header" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; height: 50px !important; display: flex; justify-content: center;">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; max-width: 100%;">
            </div>
            <br>
            <h1 style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; color: #3d4852; margin-top: 0; text-align: left; font-size: 20px; font-weight: bold; margin-bottom: 20px;">{{$details['subject']}}</h1>
            <h2 style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; font-size: 16px; font-weight: bold; margin-top: 0; text-align: left;">Hello, {{$details['name']}}!</h2>
            <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; line-height: 1.5em; margin-top: 0; text-align: left; font-size: 16px; margin-bottom: 30px;">{!! $details['content'] !!}</p>
            <br>
            <br>
            @if (!empty($details['redirect_link']))
            <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; line-height: 1.5em; margin-top: 0; text-align: left; font-size: 16px; margin-bottom: 30px;">
                <a class="button-primary" href="{{ $details['redirect_link'] }}" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; font-weight: bold; background-color: #6f8dff !important; border-bottom: 8px solid #6f8dff !important; border-left: 18px solid #6f8dff !important; border-right: 18px solid #6f8dff !important; border-top: 8px solid #6f8dff !important; color: #f5f7ff !important; text-decoration: none !important;">
                    {{$details['button_text'] ?? 'Visit Link'}}
                </a>
            </p>
            @endif
                        <br>
            <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; line-height: 1.5em; margin-top: 0; text-align: left; font-size: 16px; margin-bottom: 30px;">
              Regards, <br><br> 
              ALGOVI Solutions, Inc.<br>
              Ultimate Solution to Manage<br>
              Interpreters and Interpreting Services<br>
            </p>
        </div>

        <div class="email-footer" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; padding: 30px; text-align: center; font-size: 14px; color: #999;">
            <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; margin-bottom: 5px;">© {{ date('Y') }} {{config('app.name')}}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>