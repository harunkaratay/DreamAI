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
                        <form action="#" method="POST" id="analyzeForm">
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
                                Analizi Başlat(coming soon...)
                            </button>
                        </form>
                    </div>
                </div>
            </div>


@endsection
