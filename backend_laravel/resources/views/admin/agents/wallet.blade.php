@extends('admin.layouts.app')

@section('title', 'Agent Wallet')
@section('heading', 'Agent Wallet')
@section('subheading', ($type === 'add' ? 'Add' : 'Deduct').' wallet balance')

@section('content')
<div class="panel">
    <form method="POST" action="{{ route('admin.agents.wallet.update', $agent->id) }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="split-2">
            <div>
                <label>Agent</label>
                <input type="text" value="{{ trim(($agent->first_name ?? '').' '.($agent->last_name ?? '')) }}" readonly>
            </div>
            <div>
                <label>Current Wallet</label>
                <input type="text" value="{{ $agent->wallet }}" readonly>
            </div>
            <div>
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div>
                <label>Source Distributor ID (optional)</label>
                <input type="number" name="source_distributor_id" placeholder="Distributor ID">
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $type === 'add' ? 'Add Wallet' : 'Deduct Wallet' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.agents.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
