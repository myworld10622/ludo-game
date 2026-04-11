@extends('admin.layouts.app')

@section('title', 'Agent Withdraw Gateways')
@section('heading', 'Agent Withdraw Gateways')
@section('subheading', 'Assign gateway numbers for agent withdrawals')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy agent withdraw gateway tables not found.</div>
    @endif

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Withdraw Gateway Numbers</div>
            <a class="btn" href="{{ route('admin.gateways.agent-withdraw.create') }}">Add Agent Withdraw Gateway</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Agent</th>
                    <th>Gateway</th>
                    <th>Number</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    @php
                        $ownerLabel = trim(($row->owner_first_name ?? '').' '.($row->owner_last_name ?? ''));
                        if ($ownerLabel === '') {
                            $ownerLabel = $row->owner_email ?? ('#'.$row->agent_id);
                        }
                    @endphp
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Agent">{{ $ownerLabel }}</td>
                        <td data-label="Gateway">{{ $row->gateway_name }}</td>
                        <td data-label="Number">{{ $row->number }}</td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                        <td data-label="Action">
                            <a class="btn btn-secondary" href="{{ route('admin.gateways.agent-withdraw.edit', $row->id) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No agent withdraw gateways found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
