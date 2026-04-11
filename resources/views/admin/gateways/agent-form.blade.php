@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Agent Gateway' : 'Add Agent Gateway')
@section('heading', $mode === 'edit' ? 'Edit Agent Gateway' : 'Add Agent Gateway')
@section('subheading', 'Assign gateway number for an agent')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.gateways.agent.update', $row->id ?? 0) : route('admin.gateways.agent.store') }}">
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
                <label>Agent</label>
                @if ($owners->isNotEmpty())
                    <select name="agent_id">
                        <option value="">Select Agent</option>
                        @foreach ($owners as $owner)
                            @php($label = trim(($owner->first_name ?? '').' '.($owner->last_name ?? '')) ?: ($owner->email ?? 'Agent #'.$owner->id))
                            <option value="{{ $owner->id }}"
                                {{ (string) old('agent_id', $row->agent_id ?? '') === (string) $owner->id ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="number" name="agent_id" value="{{ old('agent_id', $row->agent_id ?? '') }}" placeholder="Agent ID">
                @endif
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.gateways.agent.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
