<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceDownloadController;
use App\Models\Interpreter;
use App\Models\InterpreterFilter;
use App\Models\InterpreterLanguage;
use App\Models\Language;
use App\Models\SubClient;
use App\Models\SubClientDynamicFields;
use App\Models\SubClientFilter;
use App\Models\SubClientType;
use Illuminate\Support\Facades\Route;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

Route::get('/', function () {
    return view('welcome');
});


Route::get('test-asterisk', function () {

    $phoneNumbers = Interpreter::pluck('phone')->toArray();
    // dd($phoneNumbers);
    $numbers = [];
    foreach ($phoneNumbers as $phone) {
        $numbers[] = substr($phone,1);
    }
    // $phoneNumbers = array(
    //     '2512399192',
    //     '5614860789',
    //     '8016088863'
    // );

    // Convert the array to a comma-separated list
    $phoneNumbersList = implode(',', $numbers);

    echo $phoneNumbersList;
    die();


    // Define the phone numbers as an array
    $phoneNumbers = array(
        '2512399192',
        '5614860789',
        '8016088863'
    );

    // Convert the array to a comma-separated list
    $phoneNumbersList = implode(',', $phoneNumbers);

    // Initialize cURL session
    $curl = curl_init();

    // Set the URL and other options for the cURL request
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://sip.elogixit.com/ast/api/dialer.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query(array(
            'action' => 'originate',
            'phonenumbers' => $phoneNumbersList,
            'ext' => '2000',
            'apptid' => '202504'
        )),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
    ));

    // Execute the cURL request and get the response
    $response = curl_exec($curl);

    // Check for cURL errors
    if (curl_errno($curl)) {
        echo 'cURL Error: ' . curl_error($curl);
    } else {
        // Print the response from the server
        echo "Response from Server:\n";
        echo "<pre>";
        print_r($response);
        echo "</pre>";
    }

    // Close the cURL session
    curl_close($curl);
});

Route::get('/import-subclients', function () {
    $path = storage_path('app/public/subclients.xlsx');
    # open the file
    set_time_limit(300);
    \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    SubClient::truncate();
    SubClientType::truncate();
    SubClientFilter::truncate();
    SubClientDynamicFields::truncate();
    $reader = ReaderEntityFactory::createXLSXReader();
    $reader->open($path);
    # read each cell of each row of each sheet
    $sheet_count = 0;
    $data = [];
    $top_funds_data = [];
    $all_series = [];
    $neglect_cols = ['US Based Interpreters', 'Non US Based Interpreters', 'English to Target Language', 'Spanish to Target Language'];
    $filters = ['us_based', 'non_us_based', 'english_to_target', 'spanish_to_target'];
    foreach ($reader->getSheetIterator() as $sheet) {
        $type = SubClientType::create([
            'name' => $sheet->getName()
        ]);
        $row_count = -1;

        $dynamic_fields = [];
        foreach ($sheet->getRowIterator() as $row) {
            $row_count++;
            if ($row_count == 0) {
                foreach ($row->getCells() as $cell) {
                    $dynamic_fields[] = $cell->getValue();
                }
            }
            if ($row_count > 0) {
                $subclient = [];
                $subclient_filter = [];
                $subclient_dynamic_fields = [];
                $cell_count = 0;
                foreach ($row->getCells() as $cell) {
                    if ($cell_count == 0) {
                        $subclient['name'] = $cell->getValue();
                        $subclient['type_id'] = $type->id;
                        $subclient = SubClient::create($subclient);
                        $subclient_filter['subclient_id'] = $subclient->id;
                        $subclient_dynamic_fields['subclient_id'] = $subclient->id;
                    } else {
                        if (!in_array($dynamic_fields[$cell_count], $neglect_cols)) {
                            if ($cell->getValue() == 'TRUE') {
                                $subclient_dynamic_fields['name'] = $dynamic_fields[$cell_count];
                                SubClientDynamicFields::create($subclient_dynamic_fields);
                            }
                        } else {
                            if ($dynamic_fields[$cell_count] == 'US Based Interpreters') {
                                $subclient_filter['us_based'] = $cell->getValue() == 'TRUE' ? 1 : 0;
                            } else if ($dynamic_fields[$cell_count] == 'Non US Based Interpreters') {
                                $subclient_filter['non_us_based'] = $cell->getValue() == 'TRUE' ? 1 : 0;
                            } else if ($dynamic_fields[$cell_count] == 'English to Target Language') {
                                $subclient_filter['english_to_target'] = $cell->getValue() == 'TRUE' ? 1 : 0;
                            } else if ($dynamic_fields[$cell_count] == 'Spanish to Target Language') {
                                $subclient_filter['spanish_to_target'] = $cell->getValue() == 'TRUE' ? 1 : 0;
                            }
                        }
                    }
                    //                 if ($cell->getValue() != '' && $cell->getValue() != 'Series') {
                    //                     $all_series[] = $cell->getValue();
                    //                 }
                    //             }
                    $cell_count++;
                }

                SubClientFilter::create($subclient_filter);
            }
        }

        // echo $sheet->getName();
        // if ($sheet_count == 1) {
        //     $row_count = -1;
        //     foreach ($sheet->getRowIterator() as $row) {
        //         $row_count++;

        //         if ($row_count == 0) {
        //             foreach ($row->getCells() as $cell) {
        //                 if ($cell->getValue() != '' && $cell->getValue() != 'Series') {
        //                     $all_series[] = $cell->getValue();
        //                 }
        //             }
        //             continue;
        //         }

        //         if ($row_count > 1) {
        //             $cell_count = 0;
        //             foreach ($row->getCells() as $cell) {
        //                 $data[$row_count - 2][] = $cell->getValue();
        //                 $cell_count++;
        //             }
        //         }
        //     }
        // } else if ($sheet_count == 3) {
        //     $row_count = 0;
        //     foreach ($sheet->getRowIterator() as $row) {
        //         $row_count++;

        //         if ($row_count > 1) {
        //             $cell_count = 0;
        //             foreach ($row->getCells() as $cell) {
        //                 $top_funds_data[$row_count - 1][] = $cell->getValue();
        //                 $cell_count++;
        //             }
        //         }
        //         // $result[] = [
        //         //     'deals' => count($investments),
        //         //     'name' => $fg['fundName'],
        //         //     'tvpi' => $benchmark['tvpiBenchmark']??null,
        //         //     'irr' => $benchmark['irrBenchmark']??null,
        //         //     'dpi' => $benchmark['dpiBenchmark']??null,
        //         //     'aum' => $aum,
        //         //     'exits' => $exits
        //         // ];

        //     }

        //     // die(json_encode($top_funds_data));

        //     break;
        // }
        // $sheet_count++;
    }
    $reader->close();
});

Route::get('/import-interpreters', function () {
    $path = storage_path('app/public/interpreters.xlsx');
    # open the file
    set_time_limit(300);
    \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    Interpreter::truncate();
    InterpreterFilter::truncate();
    InterpreterLanguage::truncate();
    $reader = ReaderEntityFactory::createXLSXReader();
    $reader->open($path);
    # read each cell of each row of each sheet

    $fields = ['code', 'first_name', 'last_name', 'phone', 'secondary_number', 'email', 'secondary_email', 'notes'];
    $filters = ['us_authorized', 'legal', 'court', 'certified', 'SOSI'];

    foreach ($reader->getSheetIterator() as $sheet) {
        $row_count = -1;
        foreach ($sheet->getRowIterator() as $row) {
            $row_count++;
            // if($row_count==0){
            //     foreach ($row->getCells() as $cell) {
            //         $dynamic_fields[] = $cell->getValue();
            //     }
            // }
            if ($row_count > 0) {
                $interpreter_data = [];
                $interpreter_filters = [];
                $cell_count = 0;
                foreach ($row->getCells() as $cell) {
                    if ($cell_count < 8) {
                        $interpreter_data[$fields[$cell_count]] = $cell->getValue();
                    } else if ($cell_count < 13) {
                        $interpreter_filters[$filters[$cell_count - 8]] = $cell->getValue();
                    } else if ($cell_count == 13) {
                        $interpreter = Interpreter::create($interpreter_data);
                        $interpreter_filters['interpreter_id'] = $interpreter->id;
                        InterpreterFilter::create($interpreter_filters);

                        $languages = $cell->getValue();
                        if (!empty($languages)) {
                            $languages = explode(',', $languages);
                            foreach ($languages as $lang) {
                                $lang = Language::updateOrCreate([
                                    'name' => trim($lang)
                                ], [
                                    'name' => trim($lang)
                                ]);
                                InterpreterLanguage::create([
                                    'interpreter_id' => $interpreter->id,
                                    'language_id' => $lang->id,
                                ]);
                            }
                        }
                    }

                    $cell_count++;
                }
            }
        }

        // echo $sheet->getName();
        // if ($sheet_count == 1) {
        //     $row_count = -1;
        //     foreach ($sheet->getRowIterator() as $row) {
        //         $row_count++;

        //         if ($row_count == 0) {
        //             foreach ($row->getCells() as $cell) {
        //                 if ($cell->getValue() != '' && $cell->getValue() != 'Series') {
        //                     $all_series[] = $cell->getValue();
        //                 }
        //             }
        //             continue;
        //         }

        //         if ($row_count > 1) {
        //             $cell_count = 0;
        //             foreach ($row->getCells() as $cell) {
        //                 $data[$row_count - 2][] = $cell->getValue();
        //                 $cell_count++;
        //             }
        //         }
        //     }
        // } else if ($sheet_count == 3) {
        //     $row_count = 0;
        //     foreach ($sheet->getRowIterator() as $row) {
        //         $row_count++;

        //         if ($row_count > 1) {
        //             $cell_count = 0;
        //             foreach ($row->getCells() as $cell) {
        //                 $top_funds_data[$row_count - 1][] = $cell->getValue();
        //                 $cell_count++;
        //             }
        //         }
        //         // $result[] = [
        //         //     'deals' => count($investments),
        //         //     'name' => $fg['fundName'],
        //         //     'tvpi' => $benchmark['tvpiBenchmark']??null,
        //         //     'irr' => $benchmark['irrBenchmark']??null,
        //         //     'dpi' => $benchmark['dpiBenchmark']??null,
        //         //     'aum' => $aum,
        //         //     'exits' => $exits
        //         // ];

        //     }

        //     // die(json_encode($top_funds_data));

        //     break;
        // }
        // $sheet_count++;
    }
    $reader->close();
});
