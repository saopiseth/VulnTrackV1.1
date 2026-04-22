@extends('layouts.app')
@section('title', 'Edit Group — ' . $userGroup->name)

@section('content')
@include('users.groups._form', ['group' => $userGroup, 'users' => $users, 'memberIds' => $memberIds])
@endsection
