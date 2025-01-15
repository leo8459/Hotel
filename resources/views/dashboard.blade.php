@extends('adminlte::page')
@section('title', 'Dashboard')
@section('template_title')
Sistema de EMS AGBC
@endsection

@section('content')
@livewire('dashboardgeneral')
@include('footer')
@endsection
@section('js')