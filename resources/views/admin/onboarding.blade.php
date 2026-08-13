@extends('layouts.app')
@section('content')<div class="main-content"><div class="container-fluid"><div class="mb-5"><h1 class="text-2xl font-bold">Onboarding checklist</h1><p class="text-slate-500">Complete these steps to prepare your business for customers.</p></div><x-onboarding-checklist :checklist="$checklist" /></div></div>@endsection
