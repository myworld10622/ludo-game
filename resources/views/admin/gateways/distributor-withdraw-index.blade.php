@extends('admin.layouts.app')

@section('title', 'Distributor Withdraw Gateways')
@section('heading', 'Distributor Withdraw Gateways')
@section('subheading', 'Assign gateway numbers for distributor withdrawals')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy distributor withdraw gateway tables not found.</div>
    @endif

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Withdraw Gateway Numbers</div>
            <a class="btn" href="{{ route('admin.gateways.distributor-withdraw.create') }}">Add Distributor Withdraw Gateway</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Distributor</th>
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
                            $ownerLabel = $row->owner_email ?? ('#'.$row->distributor_id);
                        }
                    @endphp
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Distributor">{{ $ownerLabel }}</td>
                        <td data-label="Gateway">{{ $row->gateway_name }}</td>
                        <td data-label="Number">{{ $row->number }}</td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                        <td data-label="Action">
                            <a class="btn btn-secondary" href="{{ route('admin.gateways.distributor-withdraw.edit', $row->id) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No distributor withdraw gateways found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
