@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Redeem' : 'Add Redeem')
@section('heading', $mode === 'edit' ? 'Edit Redeem' : 'Add Redeem')
@section('subheading', 'Configure redeem preset card')

@section('content')
<div class="panel">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $mode === 'edit' ? route('admin.withdrawals.redeem.update', $redeem->id ?? 0) : route('admin.withdrawals.redeem.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>Title</label>
                <input type="text" name="title" value="{{ old('title', $redeem->title ?? '') }}" required>
            </div>
            <div>
                <label>Coin</label>
                <input type="number" step="0.01" name="coin" value="{{ old('coin', $redeem->coin ?? '') }}" required>
            </div>
            <div>
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount', $redeem->amount ?? '') }}" required>
            </div>
            <div>
                <label>Image</label>
                <input type="file" name="img" accept="image/*">
                @if (!empty($redeem->img))
                    <div class="muted" style="margin-top:8px;">Current: {{ $redeem->img }}</div>
                    <img src="{{ url('data/Redeem/'.$redeem->img) }}" style="width: 120px; border-radius: 12px; margin-top:8px;">
                @endif
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.withdrawals.redeem.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
