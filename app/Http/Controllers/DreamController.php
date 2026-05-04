<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DreamLog;
use Illuminate\Support\Facades\Auth;
use App\Services\DreamApiService;
use Illuminate\Support\Facades\Storage; // Resim silmek için gerekli

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
            // servisten veriyi çek (Servis metodunun adının bu olduğundan emin ol)
            $fullContent = $apiService->analyzeDream($dreamText);

            // veriyi ayıkla görsel üretimi için lazım
            $subjectDesc = "";
            if (preg_match('/\[SUBJECT\](.*?)\[\/SUBJECT\]/s', $fullContent, $matches)) {
                $subjectDesc = trim($matches[1]);
                $fullContent = str_replace($matches[0], '', $fullContent);
            }

            $analysisText = $fullContent;
            $prompts = []; // Eski Blade yapın için prompts değişkenini başlatıyoruz

            if (str_contains($fullContent, '|||SCENE_START|||')) {
                $parts = explode('|||SCENE_START|||', $fullContent);
                $analysisText = trim($parts[0]);

                // --- PROMPTLARI AYIKLAMA KISMI ---
                $hiddenPart = $parts[1];
                if (preg_match_all('/\[SCENE\](.*?)\[\/SCENE\]/s', $hiddenPart, $matches)) {
                    $prompts = array_map(function($p) use ($subjectDesc) {
                        $cleanPrompt = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ', ', $p)));
                        if (str_contains($cleanPrompt, '[SUBJECT]')) {
                            return str_replace('[SUBJECT]', $subjectDesc, $cleanPrompt);
                        }
                        return $subjectDesc . ", " . $cleanPrompt;
                    }, $matches[1]);
                }

                session(['pending_prompts' => $prompts]); // Görsel için gerekli promptlar
            }

            // veri tabanına kayıt
            $log = DreamLog::create([
                'user_id' => Auth::id(),
                'dream_text' => $dreamText,
                'analysis_text' => $analysisText,
            ]);

            // veriyi kullanıcıya geri dönder (
            return view('admin.dream.analyze', [
                'analysis' => $analysisText,
                'original_dream' => $dreamText,
                'prompts' => $prompts
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Sistem Hatası: ' . $e->getMessage());
        }
    }

    // görsel için kodlar gelecek
    public function generateImage(Request $request)
    {
        // coming soon...
    }


    // --- dream log kodları ---

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
        $images = is_array($log->images) ? $log->images : (json_decode($log->images, true) ?? []);

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

        return redirect()->route('dreamlog.list')->with('success', 'Rüya başarıyla silindi.');
    }
}
