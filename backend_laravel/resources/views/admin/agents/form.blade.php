@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Agent' : 'Add Agent')
@section('heading', $mode === 'edit' ? 'Edit Agent' : 'Add Agent')
@section('subheading', 'Manage agent profile')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.agents.update', $agent->id ?? 0) : route('admin.agents.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name', $agent->first_name ?? '') }}" required>
            </div>
            <div>
                <label>Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $agent->last_name ?? '') }}">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email_id" value="{{ old('email_id', $agent->email_id ?? '') }}" required>
            </div>
            <div>
                <label>Mobile</label>
                <input type="text" name="mobile" value="{{ old('mobile', $agent->mobile ?? '') }}">
            </div>
            <div>
                <label>Password</label>
                <input type="text" name="password" value="{{ old('password', $agent->password ?? '') }}" {{ $mode === 'create' ? 'required' : '' }}>
            </div>
            <div>
                <label>Added By (Distributor ID)</label>
                <input type="number" name="addedby" value="{{ old('addedby', $agent->addedby ?? '') }}">
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.agents.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
