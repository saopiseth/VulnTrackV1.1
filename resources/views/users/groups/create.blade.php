@extends('layouts.app')
@section('title', 'Create User Group')

@section('content')
@include('users.groups._form', ['group' => null, 'users' => $users, 'memberIds' => []])
@endsection
