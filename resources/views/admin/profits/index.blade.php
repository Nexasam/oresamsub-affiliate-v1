@extends('layouts.app')
@section('content')<div class="main-content p-4 sm:p-6"><div class="mb-5"><h1 class="text-2xl font-bold">Profit management</h1><p class="text-sm text-slate-500">Realised earnings from successful customer purchases.</p></div><x-profit-report scope="affiliate" :summary="$summary" :services="$services" :transactions="$transactions" /></div>@endsection
