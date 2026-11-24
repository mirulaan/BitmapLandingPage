@extends('layouts.app')

@section('content')
  @include('components.view-portal', ['hidden' => true])
  @include('components.view-admin', ['hidden' => false])
@endsection
