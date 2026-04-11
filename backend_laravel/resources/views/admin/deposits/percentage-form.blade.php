@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Deposit Percentage' : 'Add Deposit Percentage')
@section('heading', $mode === 'edit' ? 'Edit Deposit Percentage' : 'Add Deposit Percentage')
@section('subheading', 'Configure percentage rule')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.deposits.percentage.update', $percent->id ?? 0) : route('admin.deposits.percentage.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>User Type</label>
                <select name="user_type" required>
                    <option value="">-- Select --</option>
                    <option value="0" {{ old('user_type', $percent->user_type ?? '') == '0' ? 'selected' : '' }}>Admin</option>
                    <option value="2" {{ old('user_type', $percent->user_type ?? '') == '2' ? 'selected' : '' }}>Agent</option>
                    <option value="3" {{ old('user_type', $percent->user_type ?? '') == '3' ? 'selected' : '' }}>Distributor</option>
                </select>
            </div>
            <div>
                <label>Percentage</label>
                <input type="number" step="0.01" name="percentage" value="{{ old('percentage', $percent->percentage ?? '') }}" required>
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.deposits.percentage.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
