@extends('layouts.app')
@section('title', 'Create SLA Policy')

@section('content')
@include('sla_policies._form', ['policy' => null])
@endsection
