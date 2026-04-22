@extends('layouts.app')
@section('title', 'Edit SLA Policy — ' . $policy->name)

@section('content')
@include('sla_policies._form', ['policy' => $policy])
@endsection
