@extends('admin.layout.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-dark text-white text-center py-4">
                        <h3 class="m-0"><i class="fas fa-film mr-2"></i> Rüya Sahneleri</h3>
                        <p class="m-0 text-white-50">Rüyanızın yapay zeka tarafından yorumlanan anları</p>
                    </div>

                    <div class="card-body bg-light p-4">
                        @if(isset($images) && count($images) > 0)

                            <div class="row">
                                @foreach($images as $index => $imgPath)
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm border-0">
                                            <div class="card-header bg-white font-weight-bold">
                                                Sahne {{ $index + 1 }}
                                            </div>
                                            <div class="card-body p-2 text-center">
                                                <img src="{{ asset($imgPath) }}"
                                                     class="img-fluid rounded"
                                                     style="width: 100%; height: auto;">
                                            </div>
                                            <div class="card-footer bg-white text-center">
                                                <a href="{{ asset($imgPath) }}"
                                                   download="ruya-sahne-{{ $index+1 }}.png"
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-download"></i> İndir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-center gap-2 mt-4">
                                <a href="{{ route('dreamIndex') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-redo mr-2"></i> Yeni Rüya Yorumla
                                </a>
                            </div>

                        @else
                            <div class="alert alert-warning py-5 text-center">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                                <h4>Görseller Oluşturulamadı</h4>
                                <p>Stable Diffusion bağlantısında bir sorun oluşmuş olabilir veya süre zaman aşımına uğramış olabilir.</p>
                                <a href="{{ route('dreamIndex') }}" class="btn btn-secondary mt-3">Geri Dön</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
