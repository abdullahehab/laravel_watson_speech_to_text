<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Http\Request;

class ExampleController extends Controller
{
    public function convertFileAction(Request $request)
    {
        $id = uniqid();
        $fileInputName = $id . '.aac';
        $fileOutputName = $id . '.mp3';
        $target_path = 'temp/';
        move_uploaded_file($_FILES["file"]["tmp_name"],$target_path. $fileInputName);
        exec("ffmpeg -i ".$target_path.$fileInputName." ".$target_path.$fileOutputName);

        unlink($target_path.$fileInputName);

        $text = $this->convertSoundToTextAction($target_path.$fileOutputName);

        unlink($target_path.$fileOutputName);

        $response = json_decode($text, true);
        $results = $response['results'];
        $results = $this->search($results, 'transcript');
        return response()->json($results);
    }

    public function search($array, $key)
    {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key])) {
                $results[] = $array[$key];
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, $this->search($subarray, $key));
            }
        }

        return $results;
    }
    public function convertSoundToTextAction($file)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://gateway-syd.watsonplatform.net/speech-to-text/api/v1/recognize?model=ar-AR_BroadbandModel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic YXBpa2V5OnEwbTdoTEp2TXpRLUZNSnJOdXF1VFJHZWxrcHpyNHFhWGM3S2F5ZXByamRH",
                "Content-Type: audio/mp3",
            ),
            CURLOPT_POSTFIELDS  =>  file_get_contents($file)
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        return $response;
    }
}
