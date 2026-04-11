@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Distributor' : 'Add Distributor')
@section('heading', $mode === 'edit' ? 'Edit Distributor' : 'Add Distributor')
@section('subheading', 'Manage distributor profile')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.distributors.update', $distributor->id ?? 0) : route('admin.distributors.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name', $distributor->first_name ?? '') }}" required>
            </div>
            <div>
                <label>Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $distributor->last_name ?? '') }}">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email_id" value="{{ old('email_id', $distributor->email_id ?? '') }}" required>
            </div>
            <div>
                <label>Mobile</label>
                <input type="text" name="mobile" value="{{ old('mobile', $distributor->mobile ?? '') }}">
            </div>
            <div>
                <label>Password</label>
                <input type="text" name="password" value="{{ old('password', $distributor->password ?? '') }}" {{ $mode === 'create' ? 'required' : '' }}>
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.distributors.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
