@extends('adminlte::page')
@section('title', 'Usuarios')
@section('template_title')
    Eventos
@endsection

@section('content')
@livewire('evento')
@include('footer')
@endsection
