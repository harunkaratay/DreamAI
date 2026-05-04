@extends('admin.layout.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">

            <!-- Rüya Girme Formu -->
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
                                placeholder="Gece vakti karanlık bir ormanda yürüyordum...">{{ $original_dream ?? '' }}</textarea>
                                <small class="form-text text-muted mt-2">
                                    Daha iyi bir sonuç almak için rüyanızdaki hislerinizi detaylıca yazın.
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold">
                                Analizi Başlat
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Analiz Sonucu Ekranı -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 font-weight-bold text-primary">Analiz Sonuçları</h6>
                    </div>
                    <div class="card-body overflow-auto mt-2" style="max-height: 650px;">
                        @if(isset($analysis))
                            <div class="mb-4">
                                <div class="p-3 bg-light rounded border" id="markdownResult"></div>
                                <textarea id="rawAnalysisData" class="d-none">{{ $analysis }}</textarea>
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

    <!-- Yükleme (Loading) Ekranı -->
    <div id="dream-loader" class="dream-loader-overlay" style="display: none;">
        <div class="loader-content">
            <div class="spinner-container">
                <div class="spinner-outer"></div>
                <div class="spinner-inner"></div>
                <div class="icon-wrapper">
                    <!-- Sadece analiz ikonu bırakıldı -->
                    <svg id="icon-analyze" class="loader-icon active" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 60px; height: 60px; color: #7e22ce;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-area">
                <h3 id="loader-title">Rüya Yorumlanıyor...</h3>
                <p id="loader-subtitle">Lütfen bekleyiniz</p>
            </div>
            <div class="progress-container">
                <div id="progress-bar" class="progress-fill"></div>
            </div>
        </div>
    </div>

    <style>
        .dream-loader-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.96); backdrop-filter: blur(8px); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .spinner-container { position: relative; width: 150px; height: 150px; margin-bottom: 2rem; }
        .spinner-outer { position: absolute; inset: 0; border-radius: 50%; border: 4px solid #f3e8ff; border-top-color: #9333ea; animation: spin 1s linear infinite; }
        .spinner-inner { position: absolute; inset: 15px; border-radius: 50%; border: 4px solid #faf5ff; border-bottom-color: #c084fc; animation: spin 3s linear infinite reverse; }
        .icon-wrapper { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
        .text-area h3 { font-size: 1.75rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; }
        .text-area p { font-size: 1.1rem; color: #9333ea; font-weight: 500; }
        .progress-container { width: 320px; height: 8px; background-color: #f3e8ff; border-radius: 999px; margin-top: 2rem; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #c084fc, #9333ea, #4f46e5); width: 0%; transition: width 0.5s ease-out; border-radius: 999px; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Markdown Render
            const rawData = document.getElementById('rawAnalysisData');
            const resultArea = document.getElementById('markdownResult');
            if (rawData && resultArea && rawData.value.trim() !== "") {
                resultArea.innerHTML = marked.parse(rawData.value);
            }

            // Loader Mantığı
            const analyzeForm = document.getElementById('analyzeForm');
            const loader = document.getElementById('dream-loader');
            const progressBar = document.getElementById('progress-bar');

            if(analyzeForm) {
                analyzeForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    loader.style.display = 'flex';
                    let progress = 5;

                    const timer = setInterval(() => {
                        if (progress < 90) {
                            progress += Math.random() * 5;
                            progressBar.style.width = progress + '%';
                        }
                    }, 800);

                    try {
                        const formData = new FormData(this);
                        const response = await fetch(this.action, { method: 'POST', body: formData });
                        clearInterval(timer);
                        progressBar.style.transition = 'width 1s ease-out';
                        progressBar.style.width = '100%';

                        await new Promise(resolve => setTimeout(resolve, 1000));

                        const htmlResult = await response.text();
                        document.open(); document.write(htmlResult); document.close();
                    } catch (error) {
                        clearInterval(timer);
                        alert("Hata oluştu.");
                        loader.style.display = 'none';
                    }
                });
            }
        });
    </script>
@endsection
