@extends('parent-admin.layouts.app')
@section('title','Onboarding checklist') @section('heading','Onboarding checklist')
@section('content') <x-onboarding-checklist :checklist="$checklist" /> @endsection
