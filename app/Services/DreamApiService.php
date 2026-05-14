<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DreamApiService
{
    public function analyzeDream($dreamText)
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

        $prompt = "Sen uzman bir rüya tabircisi ve görsel sanatçısın. Aşağıdaki rüyayı derinlemesine, edebi ve psikolojik bir dille analiz et.
        Analizinin sonuna, rüyadaki en vurucu sahneleri yapay zekanın çizebilmesi için İngilizce promptlar ekle.

        Yanıtını KESİNLİKLE şu formatta ver:

        (Buraya Türkçe rüya analizini yaz)

        [SUBJECT] (Karakter tasviri İngilizce) [/SUBJECT]

        |||SCENE_START|||
        [SCENE] (1. sahne detaylı İngilizce prompt) [/SCENE]
        [SCENE] (2. sahne detaylı İngilizce prompt) [/SCENE]
        [SCENE] (3. sahne detaylı İngilizce prompt) [/SCENE]
        |||SCENE_END|||

        Rüya: " . $dreamText;

        try {
            $response = Http::withoutVerifying()
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // DNS çözümleme hatasını (IPv6) devre dışı bırakır
                        CURLOPT_CONNECTTIMEOUT => 10,           // Bağlantı kurma süresi
                    ]
                ])
                ->timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'];
            }

            Log::error('Gemini API Hatası: ' . $response->body());
            throw new \Exception("Google API Yanıt Vermedi: " . $response->status());

        } catch (\Exception $e) {
            Log::error('DreamApiService Hatası: ' . $e->getMessage());
            throw $e;
        }
    }
}
