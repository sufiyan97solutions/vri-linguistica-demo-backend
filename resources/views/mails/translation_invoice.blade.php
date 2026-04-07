@php
    $bgColor = 0;
    // If cost_table is passed as JSON string, decode it
    if (isset($cost_table) && is_string($cost_table)) {
        $breakdown = json_decode($cost_table, true);
    } elseif (isset($cost_table) && is_array($cost_table)) {
        $breakdown = $cost_table;
    } else {
        $breakdown = [];
    }
    // Calculate grand total from breakdown
    $grandTotal = collect($breakdown)->sum(function($row) {
        return isset($row['total']) ? (float)$row['total'] : 0;
    });
@endphp
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: sans-serif;
            /* font-size: 12px; */
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-box, .section, .doc-table {
            border: 2px solid #000;
        }
        .header-box td, .doc-table td, .doc-table th {
            border: 2px solid #000;
            padding: 5px;
        }
        .header-left {
            width: 60%;
        }
        .header-right {
            width: 40%;
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
        }
        .logo {
            width: 150px;
        }
        .text-center {
            text-align: center;
        }
        .mt-10 {
            margin-top: 10px;
        }
        .line-tight {
            line-height: 1.1 !important;
        }
        .p-size{
            font-size: 12px;
        }
        .font-th{
            font-size: 12px;
            line-height: 1.1;
        }
        .doc-table thead tr .font-th{
            border-bottom: none;
        }
        .doc-table tbody tr .p-size{
            border-top: none;
        }
        .doc-table tfoot tr td {
            border: none !important; 
        }
        .doc-table tfoot tr td {
            border: none !important; 
        }
        .doc-table {
            border: none;
        }
        .ribbon-container {
            position: relative;
            width: 100%;
            height: 0;
            display: none;
        }
        .ribbon {
            position: fixed;
            top: 30px;
            right: -60px;
            width: 220px;
            height: 40px;
            background: {{ isset($paid) && $paid ? '#4CAF50' : '#F44336' }};
            color: #fff;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            line-height: 40px;
            transform: rotate(45deg);
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
            letter-spacing: 2px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    {{-- First Table start --}}
    <table style="width: 100%; border: 2px solid black; border-bottom: 0px solid;">
        <tr>
            <td style="width: 65%; padding:5px;">
                <h2 style="font-size: 25px; margin:0; margin-top:8px;margin-bottom:18px;">Document Translation Invoice</h2>
                <p style="font-size: 16px; font-weight: bold; margin: 0;">Status: <span style="color: {{ isset($paid) && $paid ? 'green' : 'red' }};">{{ isset($paid) && $paid ? 'Paid' : 'Unpaid' }}</span></p>
            </td>
            <td style="width: 45%;">
                <img src="{{ public_path('logo.png') }}" alt="Logo" style="max-width: 150px; height: auto; display: block; margin-left: auto; margin-right: 0;">
            </td>
        </tr>
    </table>

    <table class="header-box">
        <tr>
            <td class="header-left" style="width: 45%;">
                <p class="line-tight" style="font-size: 13px;">
                ALGOVI Solutions, Inc.<br>
                Ultimate Solution to Manage<br>
                Interpreters and Interpreting Services
                </p>
            </td>
            <td style="padding: 0;">
                <table style="width: 100%; border: none; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td class="p-size" style="width: 25%; padding: 0; border: none; padding-left:5px">Client ID:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 0px; margin-bottom:4px;width : 64%; margin-left: -20px;">
                                {{ $translation->accounts->id ?? '' }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-size" style="padding: 0; border: none; padding-left:5px">Client&nbsp;Name:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 10px 0px 0px 0px; line-height: 1.2; width : 64%; margin-left: -20px;">
                                {{ $clientName ?? '' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="p-size">
                <span>Invoice Date:</span> <span style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}">{{ now()->format('Y-m-d') }}</span>
            </td>
            <td class="p-size">
                <span>Customer Contact Information</span>
            </td>
        </tr>
        <tr style="width: 100%;">
            <td>
                <table style="width: 100%; border: none; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td class="p-size" style="width: 25%; padding: 0; border: none;">Invoice&nbsp;Number:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 0px; margin-bottom:4px;width : 95%;">{{ $translation->translationInvoices->invoice_number ?? '' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table style="width: 100%; border: none; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td class="p-size" style="width: 25%; padding: 0; border: none;">Name:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 10px 0px 0px 0px; margin-bottom:4px;width : 100%;">{{ $clientName ?? '' }}</div>
                        </td>
                    </tr>
                    /* <tr>
                        <td class="p-size" style="padding: 0; border: none;">Phone:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 10px 0px 0px 0px; margin-bottom:4px; line-height: 1.2; width : 100%;">
                                {{ $translation->translationDetails->requester_phone ?? '' }}
                            </div>
                        </td>
                    </tr> */
                    <tr>
                        <td class="p-size" style="padding: 0; border: none;">E-mail:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 10px 0px 0px 0px; line-height: 1.2; width : 100%;">
                                {{ $clientEmail ?? '' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    {{-- First Table end --}}
    <p class="section-title mt-10" style="font-size:12px;">Project Cost Breakdown:</p>
    {{-- Third Table start --}}
    <table class="doc-table">
        <thead>
            <tr>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Document #</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">File Name</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Target Language</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Rate/Word</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Word Count</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Formatting</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Sub Total</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $docNum = 1; @endphp
            @foreach($breakdown as $row)
                <tr>
                    <td class="p-size" style="background:#E7E6E6;text-align:center;">{{ $docNum++ }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">{{ $row['file_name'] }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">{{ $row['target_language'] }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">${{ number_format($row['rate'], 2) }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">{{ $row['word_count'] }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">${{ number_format($row['formatting'], 2) }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">${{ number_format($row['sub_total'], 2) }}</td>
                    <td class="p-size" style="text-align:center;">${{ number_format($row['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="font-size:11px; text-align: right; border: 1px solid #000 !important;">Grand Total</td>
                <td style="text-align: center; border: 1px solid #000 !important; font-size: 18px;">${{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    {{-- Third Table end --}}
</body>
</html>