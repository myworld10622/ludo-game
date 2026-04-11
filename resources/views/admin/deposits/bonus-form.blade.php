@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Deposit Bonus' : 'Add Deposit Bonus')
@section('heading', $mode === 'edit' ? 'Edit Deposit Bonus' : 'Add Deposit Bonus')
@section('subheading', 'Configure deposit bonus slab')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.deposits.bonus.update', $bonus->id ?? 0) : route('admin.deposits.bonus.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>Min</label>
                <input type="number" step="0.01" name="min" value="{{ old('min', $bonus->min ?? '') }}" required>
            </div>
            <div>
                <label>Max</label>
                <input type="number" step="0.01" name="max" value="{{ old('max', $bonus->max ?? '') }}" required>
            </div>
            <div>
                <label>Self Bonus</label>
                <input type="number" step="0.01" name="self_bonus" value="{{ old('self_bonus', $bonus->self_bonus ?? '') }}" required>
            </div>
            <div>
                <label>Upline Bonus</label>
                <input type="number" step="0.01" name="upline_bonus" value="{{ old('upline_bonus', $bonus->upline_bonus ?? '') }}" required>
            </div>
            <div>
                <label>Deposit Count</label>
                <input type="number" name="deposit_count" value="{{ old('deposit_count', $bonus->deposit_count ?? '') }}" required>
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.deposits.bonus.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
