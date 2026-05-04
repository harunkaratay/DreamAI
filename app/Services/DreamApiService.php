<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DreamApiService
{
    public function analyzeDream($dreamText)
    {
        $llmUrl = config('services.ollama.url', 'http://127.0.0.1:11434/api/chat');

        // Eski projendeki o meşhur profesyonel prompt
        $systemPrompt =
            'Sen bilge, mistik ve derin görüşlü bir rüya rehberisin.

                --- GÖREV 1: GİZLİ TEKNİK VERİ (KULLANICI KESİNLİKLE GÖRMEYECEK) ---
                Önce, rüyayı gören kişinin fiziksel görünümünü (Cinsiyet, saç, yaş, kıyafet) İngilizce olarak analiz et.
                Bunu çıktının EN BAŞINA şu formatta yaz ve kapat:
                [SUBJECT] (örneğin: a young man with messy black hair, wearing grey t-shirt) [/SUBJECT]

                (Bu alan bittikten sonra bir alt satıra geç ve analize başla. UNUTMA BU ALANI ASLA KULLANICI GÖRMEMELİ)

                --- GÖREV 2: SOHBET TARZI RÜYA YORUMU (TÜRKÇE) ---
                Kullanıcıyla birebir, samimi ve derin bir sohbet başlat.
                - ASLA "Rüya sahibi" veya "Kişi" deme. Doğrudan "SEN" diye hitap et.
                - Örnek: "Bu rüyanda gördüğün kurt, senin bastırdığın öfkeni temsil ediyor..."
                - Sanki bir terapist koltuğunda karşılıklı oturuyorsunuz.
                - Metin akıcı, gizemli ve sürükleyici olsun.
                - Metnin sonuna, geleceğe dair "Senin için 2 Kehanetim var:" diyerek 2 net öngörü ekle.

                --- GÖREV 3: SİNEMATİK GÖRSEL KOMUTLAR (İNGİLİZCE) ---
                Yorum bittikten sonra ALT SATIRA GEÇ ve tam olarak şunu yaz: |||SCENE_START|||
                Ardından rüyayı anlatan 4 karelik İngilizce prompt oluştur.

                KURALLAR:
                - Karakter için asla "I" veya "me" deme. Sadece yukarıda belirlediğin [SUBJECT] etiketini kullan.
                - [SCENE] ... [/SCENE] blokları kullan.

                ÇIKTI ŞABLONU:
                [SUBJECT] ... [/SUBJECT]
                (Buraya Türkçe sohbet tarzı yorum gelecek...)
                |||SCENE_START|||
                [SCENE] ... [/SCENE]
                [SCENE] ... [/SCENE]
                ...';


        $response = Http::timeout(180)->post($llmUrl, [
            'model' => config('services.ollama.model', 'qwen2.5:14b'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $dreamText]
            ],
            'stream' => false
        ]);

        return $response->json()['message']['content'] ?? null;
    }
}
