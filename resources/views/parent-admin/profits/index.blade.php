@extends('parent-admin.layouts.app')
@section('title','Profit management') @section('heading','Profit management')
@section('content')<x-profit-report scope="parent" :summary="$summary" :services="$services" :transactions="$transactions" />@endsection
