@extends('admin.layouts.app')

@section('title', 'Distributor Wallet')
@section('heading', 'Distributor Wallet')
@section('subheading', ($type === 'add' ? 'Add' : 'Deduct').' wallet balance')

@section('content')
<div class="panel">
    <form method="POST" action="{{ route('admin.distributors.wallet.update', $distributor->id) }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="split-2">
            <div>
                <label>Distributor</label>
                <input type="text" value="{{ trim(($distributor->first_name ?? '').' '.($distributor->last_name ?? '')) }}" readonly>
            </div>
            <div>
                <label>Current Wallet</label>
                <input type="text" value="{{ $distributor->wallet }}" readonly>
            </div>
            <div>
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $type === 'add' ? 'Add Wallet' : 'Deduct Wallet' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.distributors.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
