@extends('adminlte::page')
@section('title', 'Usuarios')
@section('template_title')
    Alquiler
@endsection

@section('content')
@livewire('alquileres')
@include('footer')
@endsection
