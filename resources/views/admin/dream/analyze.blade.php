@extends('admin.layout.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 font-weight-bold text-primary">Rüyanızı Anlatın</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('dreamAnalyze') }}" method="POST" id="analyzeForm">
                            @csrf
                            <div class="form-group mb-4 mt-2">
                                <textarea
                                    name="dream"
                                    id="dreamContent"
                                    class="form-control p-3"
                                    rows="12"
                                    required
                                    style="resize: none;"
                                    placeholder="Gece vakti karanlık bir ormanda yürüyordum, gökyüzü mordu ve uzaktan bir piyano sesi geliyordu...">{{ $original_dream ?? '' }}</textarea>
                                <small class="form-text text-muted mt-2">
                                    Daha iyi bir sonuç almak için rüyanızdaki renkleri, mekanları ve hislerinizi detaylıca yazın.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold">
                                Analizi Başlat
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 font-weight-bold text-primary ">Analiz Sonuçları</h6>
                    </div>
                    <div class="card-body overflow-auto mt-2" style="max-height: 650px;">

                        @if(isset($analysis))
                            <div class="mb-4">
                                <div class="p-3 bg-light rounded border" id="markdownResult"></div>
                                <textarea id="rawAnalysisData" class="d-none">{{ $analysis }}</textarea>
                            </div>

                            <hr>

                            <div class="mt-4">
                                <h6 class="font-weight-bold text-primary">Görselleştirme Seçenekleri</h6>
                                <p class="text-muted small mb-3">
                                    Analiz sonucunda <strong>{{ count($prompts) }} adet sahne</strong> tespit ettik. Bunları görselleşirmemizi ister misiniz?
                                </p>

                                <form action="{{ route('dreamImage') }}" method="POST" id="imageGenForm">
                                    @csrf
                                    <input type="hidden" name="log_id" value="{{ $log_id }}">

                                    @foreach($prompts as $prompt)
                                        <input type="hidden" name="prompts[]" value="{{ $prompt }}">
                                    @endforeach

                                    <button type="submit" class="btn btn-primary btn-block py-2">
                                        Sahne Görsellerini Oluştur
                                    </button>
                                </form>
                                @if(isset($images) && count($images) > 0)
                                    <div class="mt-4">
                                        <h6 class="font-weight-bold text-primary mb-3">Rüya Sahneleri</h6>
                                        <div class="row">
                                            @foreach($images as $img)
                                                <div class="col-md-6 mb-3">
                                                    <img src="{{ asset($img) }}" class="img-fluid rounded shadow-sm border" alt="Rüya Sahnesi">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                        @else
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                <div class="text-center">
                                    <p class="mb-0">Henüz bir analiz yapılmadı.</p>
                                    <small>Lütfen rüyanızı giriniz.</small>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="dream-loader" class="dream-loader-overlay" style="display: none;">
        <div class="loader-content">

            <div class="spinner-container">
                <div class="spinner-outer"></div>
                <div class="spinner-inner"></div>

                <div class="icon-wrapper">
                    <svg id="icon-analyze" class="loader-icon active" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <svg id="icon-symbol" class="loader-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <svg id="icon-paint" class="loader-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                    <svg id="icon-finish" class="loader-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
            </div>

            <div class="text-area">
                <h3 id="loader-title">İşlem Başlatılıyor...</h3>
                <p id="loader-subtitle">Lütfen bekleyiniz</p>
            </div>

            <div class="progress-container">
                <div id="progress-bar" class="progress-fill"></div>
            </div>
        </div>
    </div>

    <style>
        .dream-loader-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .loader-content { text-align: center; display: flex; flex-direction: column; align-items: center; }
        .spinner-container { position: relative; width: 150px; height: 150px; margin-bottom: 2rem; }
        .spinner-outer {
            position: absolute; inset: 0; border-radius: 50%;
            border: 4px solid #f3e8ff; border-top-color: #9333ea;
            animation: spin 1s linear infinite;
        }
        .spinner-inner {
            position: absolute; inset: 15px; border-radius: 50%;
            border: 4px solid #faf5ff; border-bottom-color: #c084fc;
            animation: spin 3s linear infinite reverse;
        }
        .icon-wrapper { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
        .loader-icon {
            width: 60px; height: 60px; color: #7e22ce; position: absolute;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0; transform: scale(0.5);
        }
        .loader-icon.active { opacity: 1; transform: scale(1); }
        .text-area h3 { font-size: 1.75rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; transition: opacity 0.3s; }
        .text-area p { font-size: 1.1rem; color: #9333ea; font-weight: 500; transition: opacity 0.3s; }
        .progress-container { width: 320px; height: 8px; background-color: #f3e8ff; border-radius: 999px; margin-top: 2rem; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #c084fc, #9333ea, #4f46e5); width: 0%; transition: width 0.5s ease-out; border-radius: 999px; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        // Markdown Render (Sonuç ekranı için)
        document.addEventListener('DOMContentLoaded', function() {
            const rawData = document.getElementById('rawAnalysisData');
            const resultArea = document.getElementById('markdownResult');
            if (rawData && resultArea && rawData.value.trim() !== "") {
                resultArea.innerHTML = marked.parse(rawData.value);
            }
        });


        // loading görünümü

        document.addEventListener('DOMContentLoaded', function() {
            const analyzeForm = document.getElementById('analyzeForm');
            const imageForm = document.getElementById('imageGenForm');
            const loader = document.getElementById('dream-loader');

            // UI elementleri
            const titleEl = document.getElementById('loader-title');
            const subtitleEl = document.getElementById('loader-subtitle');
            const progressBar = document.getElementById('progress-bar');

            // ikon
            const icons = {
                analyze: document.getElementById('icon-analyze'),
                symbol: document.getElementById('icon-symbol'),
                paint: document.getElementById('icon-paint'),
                finish: document.getElementById('icon-finish'),
            };

            // metinler
            const analysisScenarios = [
                { icon: 'icon-analyze', title: "Rüya Sunuculara İletiliyor...", sub: "Bağlantı kuruluyor" },
                { icon: 'icon-analyze', title: "Yapay Zeka Metni Okuyor...", sub: "İçerik işleniyor" },
                { icon: 'icon-symbol',  title: "Bağlam Çözümleniyor...", sub: "Zaman ve mekan algısı taranıyor" },
                { icon: 'icon-symbol',  title: "Gizli Semboller Aranıyor...", sub: "Bilinçaltı simgeleri ayrıştırılıyor" },
                { icon: 'icon-symbol',  title: "Psikolojik Arketipler...", sub: "Arketip eşleşmeleri yapılıyor" },
                { icon: 'icon-symbol',  title: "Duygu Analizi Yapılıyor...", sub: "Baskın hisler tespit ediliyor" },
                { icon: 'icon-paint',   title: "Anlam Örüntüleri...", sub: "Parçalar birleştiriliyor" },
                { icon: 'icon-finish',  title: "Rapor Hazırlanıyor...", sub: "Sonuç oluşturuluyor" }
            ];

            const imageScenarios = [
                { icon: 'icon-analyze', title: "Sahne Yapısı Kuruluyor...", sub: "Görsel planlama yapılıyor" },
                { icon: 'icon-paint',   title: "Kompozisyon Hazırlanıyor...", sub: "Ana sahne çiziliyor" },
                { icon: 'icon-paint',   title: "Renk ve Işık...", sub: "Atmosfer ayarlanıyor" },
                { icon: 'icon-paint',   title: "Detay İşleme...", sub: "Netlik ve doku artırılıyor" },
                { icon: 'icon-finish',  title: "Son Dokunuşlar...", sub: "Görsel tamamlanıyor" }
            ];

            // ikon değiştirme
            function showIconById(iconId) {
                Object.values(icons).forEach(icon => {
                    if (icon) icon.classList.remove('active');
                });
                const el = document.getElementById(iconId);
                if (el) el.classList.add('active');
            }

            // metin ve progress değiştirme
            function updateUI(step, progressPercent) {
                titleEl.style.opacity = '0';
                subtitleEl.style.opacity = '0';

                setTimeout(() => {
                    if(step) {
                        titleEl.innerText = step.title;
                        subtitleEl.innerText = step.sub;
                        showIconById(step.icon);
                    }
                    if(progressPercent !== null) {
                        progressBar.style.width = progressPercent + '%';
                    }
                    titleEl.style.opacity = '1';
                    subtitleEl.style.opacity = '1';
                }, 300);
            }


            async function handleSmartSubmit(e, scenarios) {
                e.preventDefault(); // Sayfanın aniden yenilenmesini engelle!
                const form = e.target;

                // loaderı başlat
                loader.style.display = 'flex';
                let currentStepIndex = 0;
                let progress = 5; // %5'ten başlat

                // ilk adımı göster
                updateUI(scenarios[0], progress);

                // YAVAŞ İLERLEME MODU (Zamanlayıcı)
                const timer = setInterval(() => {
                    // İlerleme mantığı: %90'a kadar yavaşça git, orada bekle
                    if (progress < 90) {
                        progress += Math.random() * 5; // Rastgele küçük artışlar

                        // Senaryo metinlerini ilerlemeye göre değiştir
                        const stepIndex = Math.floor((progress / 90) * scenarios.length);
                        if (stepIndex !== currentStepIndex && scenarios[stepIndex]) {
                            currentStepIndex = stepIndex;
                            updateUI(scenarios[currentStepIndex], null);
                        }

                        progressBar.style.width = progress + '%';
                    }
                }, 800);

                try {
                    // ARKA PLANDA VERİYİ GÖNDER (AJAX)
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData
                    });

                    // 1. Zamanlayıcıyı durdur
                    clearInterval(timer);

                    // 2. HIZLI BİTİŞ MODU: Çubuğu 1 saniyede %100 yap (BURASI DEĞİŞTİ)
                    progressBar.style.transition = 'width 1s ease-out'; // <--- SÜRE 1 SANİYE OLDU
                    progressBar.style.width = '100%';

                    // Son mesajı göster
                    updateUI({ icon: 'icon-finish', title: "İşlem Tamamlandı!", sub: "Sonuçlar Yükleniyor..." }, 100);

                    // 3. Kullanıcının %100 olduğunu görmesi için 1 saniye bekle (BURASI DEĞİŞTİ)
                    await new Promise(resolve => setTimeout(resolve, 1000)); // <--- BEKLEME SÜRESİ 1000ms (1sn)

                    // 4. Yeni Sayfayı Bas
                    const htmlResult = await response.text();
                    document.open();
                    document.write(htmlResult);
                    document.close();

                } catch (error) {
                    console.error('Hata:', error);
                    clearInterval(timer);
                    alert("Bir hata oluştu. Lütfen sayfayı yenileyip tekrar deneyin.");
                    loader.style.display = 'none'; // Loader'ı kapat
                }
            }

            // Event Listeners
            if(analyzeForm) {
                analyzeForm.addEventListener('submit', (e) => handleSmartSubmit(e, analysisScenarios));
            }

            if(imageForm) {
                imageForm.addEventListener('submit', (e) => handleSmartSubmit(e, imageScenarios));
            }
        });
    </script>
@endsection
