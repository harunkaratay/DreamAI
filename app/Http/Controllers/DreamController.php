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
    // analiz sayfasını dönderen fonksiyon
    public function index()
    {
        return view('admin.dream.analyze');
    }

    // analiz yapılması için api servisini çağısan fonskiyon
    public function analyze(Request $request, DreamApiService $apiService)
    {
        set_time_limit(180);
        $request->validate(['dream' => 'required|min:10']);
        $dreamText = $request->input('dream');

        try {
            // buluttan gemini apisinden metni al
            $fullContent = $apiService->analyzeDream($dreamText);

            $subjectDesc = "";

            // görsel için promptları ayıklama
            if (preg_match('/\[SUBJECT\](.*?)\[\/SUBJECT\]/is', $fullContent, $matches)) {
                $subjectDesc = trim($matches[1]);
                $fullContent = str_replace($matches[0], '', $fullContent);
            }

            $analysisText = trim($fullContent);
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

            // veri tabanına sadece analiz metninin kaydı
            $log = DreamLog::create([
                'user_id' => Auth::id(),
                'dream_text' => $dreamText,
                'analysis_text' => $analysisText,
                'images' => null
            ]);

            // analizin hangi rüyaya ait olduğunun bilgilerinin verilmesi
            return view('admin.dream.analyze', [
                'analysis' => $analysisText,
                'original_dream' => $dreamText,
                'prompts' => $prompts,
                'log_id' => $log->id
            ]);

            //hatanın yakalanması
        } catch (\Exception $e) {

            return back()->with('error', 'Sistem Hatası: ' . $e->getMessage());
        }
    }


    public function generateImage(Request $request)
    {
        // işlemcinin görseli üretmesi için gerekli sürenin belirlenmesi
        set_time_limit(300);
        $logId = $request->input('log_id');
        $prompts = $request->input('prompts');
        $generatedImages = [];

        if (!is_array($prompts)) { $prompts = json_decode($prompts, true); }

        // ComfyUI JSON Şablonunun okunması
        $path = storage_path('app/comfy_workflow.json');

        $workflowJson = file_get_contents($path);

        foreach ($prompts as $key => $singlePrompt) {
            try {
                $workflow = json_decode($workflowJson, true);

                // görsel promptunun analizi gerkli noda aktarımı
                $cleanPrompt = trim(preg_replace('/[^a-zA-Z0-9\s,]/', '', $singlePrompt));
                $workflow['2']['inputs']['text'] = $cleanPrompt . ", cinematic lighting, ultra-detailed, photorealistic, 8k";

                // ComfyUI u çizimi başlatma
                $response = Http::post('http://127.0.0.1:8188/prompt', [
                    'prompt' => $workflow
                ]);

                if ($response->successful()) {
                    $promptId = $response->json()['prompt_id'];
                    $isDone = false;
                    $fileNameFromComfy = '';

                    // resmin üretilmesini bekle her 2 saniyede bir kontrol et
                    for ($i = 0; $i < 20; $i++) {
                        sleep(2); // ComfyUI'ı yormamak için 2 saniye bekle

                        $historyRes = Http::get("http://127.0.0.1:8188/history/{$promptId}");
                        $historyData = $historyRes->json();

                        // eğer resim geçmişe düştüyse çizim bitmiştir
                        if (!empty($historyData[$promptId])) {
                            $outputs = $historyData[$promptId]['outputs'];

                            // hangi node ID sinde resmi kaydettiyse bulup dosya adını alıyoruz
                            foreach ($outputs as $nodeId => $nodeOutput) {
                                if (isset($nodeOutput['images'][0]['filename'])) {
                                    $fileNameFromComfy = $nodeOutput['images'][0]['filename'];
                                    break 2; // Döngüleri kır ve çık
                                }
                            }
                        }
                    }

                    // resmi ComfyUIdan alıp laravelin içine public/storage kaydet
                    if ($fileNameFromComfy) {
                        $imageBinary = Http::get("http://127.0.0.1:8188/view?filename={$fileNameFromComfy}")->body();

                        if (!Storage::disk('public')->exists('dreams')) {
                            Storage::disk('public')->makeDirectory('dreams');
                        }

                        // karışmamsı için benzersiz bir isimle kaydet
                        $saveName = 'dreams/dream_' . uniqid() . '_' . $key . '.png';
                        Storage::disk('public')->put($saveName, $imageBinary);

                        $generatedImages[] = 'storage/' . $saveName;
                    }
                } else {
                    \Log::error("ComfyUI API'ye ulaşılamadı. Port 8188 açık mı?");
                }
            } catch (\Exception $e) {
                \Log::error('ComfyUI Çizim Hatası: ' . $e->getMessage());
                continue;
            }
        }

        // çizilen resimleri veritabanına kaydet ve sayfaya gönder
        $log = DreamLog::find($logId);

        if ($log) {
            $log->images = $generatedImages;
            $log->save();

            return view('admin.dream.image', [
                'images' => $generatedImages,
                'log_id' => $log->id
            ]);
        }

        return "Hata: Kayıt bulunamadı.";
    }

    // dream log kodları
    public function dreamList()
    {
        $logs = DreamLog::where('user_id', Auth::id())->latest()->paginate(12);
        return view('admin.dreamlog.list', compact('logs'));
    }

    public function dreamShow($id)
    {
        $log = DreamLog::where('user_id', Auth::id())->findOrFail($id);
        $images = $log->images ?? [];
        return view('admin.dreamlog.show', compact('log', 'images'));
    }

    public function dreamLogDelete($id)
    {
        $log = DreamLog::where('user_id', Auth::id())->findOrFail($id);

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

        $log->delete();
        return redirect()->route('dreamlogList')->with('success', 'Rüya başarıyla silindi.');
    }
}
