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
    </style>
</head>
<body>
    {{-- First Table start --}}
    <table style="width: 100%; border: 2px solid black; border-bottom: 0px solid;">
        <tr>
            <td style="width: 65%; padding:5px;">
                <h2 style="font-size: 25px; margin:0; margin-top:8px;margin-bottom:18px;">Document Translation Quote</h2>
                <p style="font-size: 11px; line-height: 1.1; width: 350px; margin-bottom:0px;">
                    The quoted amount is an estimate calculated considering the anticipated final word count and any required formatting.
                    Please note that it may be subject to adjustments on the final invoice.
                </p>
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
                <span>Quote Date:</span> <span style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}">{{ optional($translation->created_at)->format('Y-m-d') }}</span>
            </td>
            <td class="p-size">
                <span>Customer Contact Information</span>
            </td>
        </tr>
        <tr style="width: 100%;">
            <td>
                <table style="width: 100%; border: none; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td class="p-size" style="width: 25%; padding: 0; border: none;">Quote&nbsp;Number:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 0px; margin-bottom:4px;width : 95%;">{{ $translation->transid ?? '' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-size" style="padding: 0; border: none;">Prepared&nbsp;By:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 0px 0px; line-height: 1.2; width : 95%;">
                                Translations Department
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-size" style="padding: 0; border: none;">Phone:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="padding: 0px 0px; line-height: 1.2; width : 95%;">
                                +1 (801) 608-8863
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-size" style="padding: 0; border: none;">E-Mail:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="padding: 0px 0px; line-height: 1.2; width : 95%;">
                                sales@algovisolutions.com
                            </div>
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
                    <tr>
                        <td class="p-size" style="padding: 0; border: none;">Phone:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 10px 0px 0px 0px; margin-bottom:4px; line-height: 1.2; width : 100%;">
                                {{ $translation->translationDetails->requester_phone ?? '' }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-size" style="padding: 0; border: none;">E-mail:</td>
                        <td style="padding: 0; border: none;">
                            <div class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }} padding: 10px 0px 0px 0px; line-height: 1.2; width : 100%;">
                                {{ $translation->translationDetails->requester_email ?? $clientEmail ?? '' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    {{-- First Table end --}}
    <p class="section-title mt-10" style="font-size:12px;">Service Scope:</p>
    <p style="font-size: 12px; line-height: 1.2; margin: 0;">
    Algovi CRM utilizes OCR process to capture the word count of the document. Based on this, an Initial Quote is provided, estimating the final word count.<br>
    Once the project is assigned to a translator, the project commences, encompassing content translation and minor document formatting. The Algovi CRM translator completes the initial translation of the document. Subsequently, the document undergoes a thorough review process and proofreading.<br>
    A final review is conducted to ensure the accuracy and quality of the translation. Upon completion, the Translation Department electronically delivers the finalized document to the Client.
    </p>

    <p class="section-title mt-10" style="font-size: 22px;">List of Documents:</p>
    {{-- Second Table start --}}
    <table class="doc-table">
        <thead>
            <tr>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Document&nbsp;#</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">File/Document Name or ID</th>
                <th class="font-th" style="{{ $bgColor == 0 ? 'background-color: #E7E6E6;' : '' }}">Word Count</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $docNum = 1; 
                $uniqueFiles = collect($breakdown)->unique('file_name');
            @endphp
            @foreach($uniqueFiles as $row)
                <tr>
                    <td class="p-size" style="background:#E7E6E6;text-align:center;">{{ $docNum++ }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">{{ $row['file_name'] }}</td>
                    <td class="p-size" style="{{ $bgColor == 0 ? 'background-color: #FCE4D6;' : '' }}text-align:center;">{{ $row['word_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{-- Second Table end --}}

    {{-- Show rates info --}}
    <p class="section-title" style="font-size: 16px; margin-top: 20px;">Translation Rates:</p>
    <ul style="font-size: 13px;">
        <li>Spanish Translation Rate: ${{ number_format($spanishRate, 2) }} per word (Formatting: ${{ number_format($spanishFormatting, 2) }})</li>
        <li>Other Language Translation Rate: ${{ number_format($otherRate, 2) }} per word (Formatting: ${{ number_format($otherFormatting, 2) }})</li>
    </ul>

    {{-- Page break before cost section --}}
    <div style="page-break-before: always;"></div>
    <div style="width:100%; text-align:right; margin-bottom:10px;">
        <img src="{{ public_path('logo.png') }}" alt="Logo" style="max-width: 200px; height: auto;">
    </div>
    <p class="section-title" style="font-size: 22px; margin-top:10px;margin-bottom:15px;">Estimated Project Cost:</p>
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
    <p style="font-size:12px; line-height:1.1;">
        ***The amount provided in this quote is an estimate and is based on the projected final word count and required formatting. Be aware that it will be adjusted once the project has been completed, on the final invoice.
    </p>
    <p style="font-size:12px; line-height:1.1;margin: 0 0 1px 0; padding: 0;">
       By signing below, you accept the initiation and completion of this project and acknowledge your institution's responsibility for any charges incurred with Algovi CRM after the project has been completed.
    </p>
    <table style="margin-top:30px; border-collapse:collapse; width:100%;">
        <tbody>
            <tr>
                <td style="width:46%; border-bottom:1px solid #000; padding:0px 0px 10px 20px; font-size:14px;">
                    <p style="margin: 0; ">{{ $clientName ?? '' }}</p>
                </td>

                <td style="width:8%;"></td>

                <td style="width:46%; border-bottom:1px solid #000; padding:0px 20px 10px 0px;">
                    {{-- <img src="{{ public_path('logo.png') }}" alt="Logo" style="width: 250px; heifht:50px; margin: 0;"> --}}
                </td>
            </tr>

            <tr>
                <td style="width:46%; padding:10px 0px 0px 0px;">
                    <p style="margin: 0; font-size:12px;">Print Name</p>
                </td>
                <td style="width:8%;"></td>
                <td style="width:46%; padding:10px 0px 0px 0px;">
                    <p style="margin: 0; font-size:12px;">Customer Approval Signature</p>
                </td>
            </tr>
        </tbody>
    </table>

<table style="margin-top:40px;">
    <tbody>
        <tr>
            <td style="width:46%; border-bottom:1px solid #000; padding:0px 0px 2px 5px; font-size:14px;">
                <p style="margin: 0; ">{{ now()->format('m/d/Y') }}</p>
            </td>
            <td></td>
        </tr>
        <tr>
            <td style="width:46%; padding:10px 0px 0px 0px;">
                <p style="margin: 0; font-size:12px;">Date</p>
            </td>
        </tr>
    </tbody>
</table>


</body>
</html>