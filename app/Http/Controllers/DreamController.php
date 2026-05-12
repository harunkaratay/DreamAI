<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DreamLog;
use Illuminate\Support\Facades\Auth;
use App\Services\DreamApiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DreamController extends Controller
{
    public function index()
    {
        return view('admin.dream.analyze');
    }

    public function analyze(Request $request, DreamApiService $apiService)
    {
        set_time_limit(180);
        $request->validate(['dream' => 'required|min:10']);
        $dreamText = $request->input('dream');

        try {
            // 1. Qwen'den analizi al
            $fullContent = $apiService->analyzeDream($dreamText);

            $subjectDesc = "";

            // === GELİŞMİŞ GİZLEME VE YAKALAMA ALGORİTMASI ===

            // Durum 1: Normal [SUBJECT]...[/SUBJECT] formatını yakala
            if (preg_match('/\[SUBJECT\](.*?)\[\/SUBJECT\]/is', $fullContent, $matches)) {
                $subjectDesc = trim($matches[1]);
                $fullContent = str_replace($matches[0], '', $fullContent);
            }
            // Durum 2: Yapay Zeka başlık koymayı unutup sadece [/SUBJECT] ile bitirmişse
            elseif (preg_match('/^(.*?)\[\/SUBJECT\]/is', $fullContent, $matches)) {
                $subjectDesc = trim($matches[1]);
                $fullContent = str_replace($matches[0], '', $fullContent);
            }

            // Arka planda çizim için gidecek metnin içindeki gereksiz köşeli parantezleri temizle
            $subjectDesc = trim(str_replace(['[', ']'], '', $subjectDesc));

            // ZORUNLU TEMİZLİK: Ne olursa olsun, metnin içinde kalma ihtimali olan etiketleri yok et
            $fullContent = preg_replace('/\[SUBJECT\]|\[\/SUBJECT\]/i', '', $fullContent);

            // 🔥 EKSTRA GÜVENLİK: En başta kalan [a young man...] gibi köşeli parantezli metinleri temizle
            $fullContent = preg_replace('/^\[.*?\]\s*/s', '', trim($fullContent));

            // Son temizlik
            $fullContent = trim($fullContent);

            $analysisText = $fullContent;
            $prompts = [];

            if (str_contains($fullContent, '|||SCENE_START|||')) {
                $parts = explode('|||SCENE_START|||', $fullContent);
                $analysisText = trim($parts[0]);

                if (preg_match_all('/\[SCENE\](.*?)\[\/SCENE\]/s', $parts[1], $matches)) {
                    $prompts = array_map(function($p) use ($subjectDesc) {
                        $cleanPrompt = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ', ', $p)));
                        return str_contains($cleanPrompt, '[SUBJECT]')
                            ? str_replace('[SUBJECT]', $subjectDesc, $cleanPrompt)
                            : $subjectDesc . ", " . $cleanPrompt;
                    }, $matches[1]);
                }
            }

            // 2. Veritabanına SADECE METNİ kaydet (Henüz resim yok)
            $log = DreamLog::create([
                'user_id' => Auth::id(),
                'dream_text' => $dreamText,
                'analysis_text' => $analysisText,
                'images' => null // Resim sütunu şimdilik boş
            ]);

            // 3. Sayfaya log_id ve promptları gönder (Resim üretme butonu için lazım olacak)
            return view('admin.dream.analyze', [
                'analysis' => $analysisText,
                'original_dream' => $dreamText,
                'prompts' => $prompts,
                'log_id' => $log->id // GÜNCELLEME İÇİN KRİTİK
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Sistem Hatası: ' . $e->getMessage());
        }
    }


    public function generateImage(Request $request)
    {
        set_time_limit(300);

        $logId = $request->input('log_id');
        $prompts = $request->input('prompts');

        if (!is_array($prompts)) {
            $prompts = json_decode($prompts, true);
        }

        if (!$prompts || empty($prompts)) {
            return back()->with('error', 'Görsel tasviri bulunamadı.');
        }

        $apiBaseUrl = env('WINDOWS_SD_API_BASE_URL');
        $generatedImages = [];

        $masterStyle = "(masterpiece, best quality, ultra-detailed:1.2), cinematic lighting, 8k uhd, ";
        $masterNegative = "(deformed, distorted, disfigured:1.3), bad anatomy, blurry, watermark, text";

        foreach ($prompts as $key => $singlePrompt) {
            try {
                $response = Http::timeout(300)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post($apiBaseUrl . '/sdapi/v1/txt2img', [
                        'prompt' => $masterStyle . $singlePrompt,
                        'negative_prompt' => $masterNegative,
                        'steps' => 6,
                        'cfg_scale' => 2.0,
                        'width' => 1024,
                        'height' => 1024,
                        'sampler_name' => "DPM++ SDE", // Cloudflare için daha stabil
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $base64 = $json['images'][0] ?? null;

                    if ($base64) {
                        $imageContent = base64_decode($base64);
                        $fileName = 'dreams/' . uniqid('dream_') . '_' . $key . '.png';
                        Storage::disk('public')->put($fileName, $imageContent);
                        $generatedImages[] = 'storage/' . $fileName;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Görsel Üretim Hatası: ' . $e->getMessage());
                continue;
            }
        }

        // 🔥 KRİTİK DÜZELTME: Veritabanına kaydet ve AYNI sayfaya her şeyle dön
        $log = DreamLog::find($logId);

        if ($log) {
            $log->images = $generatedImages;
            $log->save();

            // Resimler oluştuktan sonra seni image.blade'e değil,
            // Analiz sonucunun olduğu asıl sayfaya, resimlerle beraber gönderiyoruz.
            return view('admin.dream.analyze', [
                'analysis' => $log->analysis_text,
                'original_dream' => $log->dream_text,
                'images' => $generatedImages, // Artık bu değişken Blade'de kullanılabilir
                'prompts' => [], // Butonun tekrar çıkmaması için boşaltıyoruz
                'log_id' => $log->id
            ]);
        }

        return back()->with('error', 'Kayıt bulunamadı.');
    }

    // dream log kodları

    public function dreamList()
    {
        // kullanıcının rüyalarını en yeniden eskiye doğru sıralar
        $logs = DreamLog::where('user_id', Auth::id())->latest()->paginate(12);
        return view('admin.dreamlog.list', compact('logs'));
    }

    public function dreamShow($id)
    {
        // sadece o anki kullanıcıya ait olan rüyayı getirir
        $log = DreamLog::where('user_id', Auth::id())->findOrFail($id);
        $images = $log->images ?? [];
        return view('admin.dreamlog.show', compact('log', 'images'));
    }

    public function dreamLogDelete($id)
    {
        $log = DreamLog::where('user_id', Auth::id())->findOrFail($id);

        // eğer rüyaya ait görseller varsa onları da siliyoruz
        if ($log->images) {
            $images = is_array($log->images) ? $log->images : json_decode($log->images, true);
            if (is_array($images)) {
                foreach ($images as $path) {
                    $storagePath = str_replace('storage/', '', $path);
                    if (Storage::disk('public')->exists($storagePath)) {
                        Storage::disk('public')->delete($storagePath);
                    }
                }
            }
        }

        // veritabanından kaydı sil
        $log->delete();

        return redirect()->route('dreamlogList')->with('success', 'Rüya başarıyla silindi.');
    }
}
