@extends('layouts.app')

@section('content')
  @include('components.view-portal', ['hidden' => false])
  @auth
    @include('components.view-admin', ['hidden' => true])
  @endauth
@endsection
