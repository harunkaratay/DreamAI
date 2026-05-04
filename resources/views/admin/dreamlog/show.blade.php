@extends('admin.layout.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h4 class="m-0 font-weight-bold text-primary">Rüya Detayı</h4>
        </div>

        <div class="card-body">
            <h5 class="font-weight-bold">Rüya Metni</h5>
            <p class="text-muted">{{ $log->dream_text }}</p>

            <hr>

            <h5 class="font-weight-bold mt-4">Rüya Analizi</h5>
            <div id="analysisRendered" class="p-3 bg-light border rounded"></div>
            <textarea id="analysisRaw" class="d-none">{{ $log->analysis_text }}</textarea>

            <hr>

            <h5 class="font-weight-bold mt-4">Görseller(yakında)</h5>

            @if(count($images) > 0)
                <div class="row mt-3">
                    @foreach($images as $index => $path)
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm border-0">
                                <img src="{{ asset($path) }}" class="img-fluid rounded" style="width:100%; height:auto;">
                                <div class="card-footer bg-white text-center">
                                    <a href="{{ asset($path) }}" download="dream-image-{{ $index+1 }}.png" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download"></i> İndir
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">Bu rüya için görsel bulunmuyor.</p>
            @endif

            <div class="mt-4">
                <a href="{{ route('dreamlog.list') }}" class="btn btn-secondary">
                    ← Rüya Günlüğüne Dön
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const raw = document.getElementById('analysisRaw');
    const output = document.getElementById('analysisRendered');

    if (raw && raw.value.trim() !== "") {
        output.innerHTML = marked.parse(raw.value);
    }
});
</script>
@endsection
