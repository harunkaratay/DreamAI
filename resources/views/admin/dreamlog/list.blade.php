@extends('admin.layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h4 class="m-0 font-weight-bold text-primary">Rüya Günlüğü</h4>
        </div>

        <div class="card-body">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Rüya Önizlemesi</th>
                                <th>Görsel Sayısı</th> <!--  yakında lazım olacak-->
                                <th class="text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ Str::limit($log->dream_text, 60) }}</td>
                                    <td>{{ count(json_decode($log->images, true) ?? []) }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('dreamlog.show', $log->id) }}"
                                           class="btn btn-sm btn-primary">
                                            Detay
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <form action="{{ route('dreamlogDelete', $log->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Bu rüyayı silmek istediğine emin misin?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">
                                                Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="alert alert-info text-center">
                    Henüz bir rüya kaydı bulunmuyor.
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
