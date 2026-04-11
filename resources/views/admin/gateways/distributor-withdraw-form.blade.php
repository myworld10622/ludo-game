@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Distributor Withdraw Gateway' : 'Add Distributor Withdraw Gateway')
@section('heading', $mode === 'edit' ? 'Edit Distributor Withdraw Gateway' : 'Add Distributor Withdraw Gateway')
@section('subheading', 'Assign withdraw gateway number for a distributor')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.gateways.distributor-withdraw.update', $row->id ?? 0) : route('admin.gateways.distributor-withdraw.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>Gateway</label>
                <select name="gateway_id" required>
                    <option value="">Select Gateway</option>
                    @foreach ($gateways as $gateway)
                        <option value="{{ $gateway->id }}"
                            {{ (string) old('gateway_id', $row->gateway_id ?? '') === (string) $gateway->id ? 'selected' : '' }}>
                            {{ $gateway->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Gateway Number</label>
                <input type="number" name="number" value="{{ old('number', $row->number ?? '') }}" required>
            </div>
            <div>
                <label>Distributor</label>
                @if ($owners->isNotEmpty())
                    <select name="distributor_id">
                        <option value="">Select Distributor</option>
                        @foreach ($owners as $owner)
                            @php($label = trim(($owner->first_name ?? '').' '.($owner->last_name ?? '')) ?: ($owner->email ?? 'Distributor #'.$owner->id))
                            <option value="{{ $owner->id }}"
                                {{ (string) old('distributor_id', $row->distributor_id ?? '') === (string) $owner->id ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="number" name="distributor_id" value="{{ old('distributor_id', $row->distributor_id ?? '') }}" placeholder="Distributor ID">
                @endif
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.gateways.distributor-withdraw.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
