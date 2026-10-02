@extends('new.layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="max-w-6xl mx-auto">
        <livewire:admin.users.user-edit :userId="$id" />
    </div>
@endsection
