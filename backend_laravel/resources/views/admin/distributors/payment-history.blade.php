@extends('admin.layouts.app')

@section('title', 'Distributor Payment History')
@section('heading', 'Payment History')
@section('subheading', 'Distributor and agent wallet movements')

@section('content')
<div class="panel stack">
    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Distributor Wallet Logs</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Coin</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($distributorLogs as $log)
                    <tr>
                        <td data-label="ID">{{ $log->id }}</td>
                        <td data-label="User ID">{{ $log->user_id }}</td>
                        <td data-label="Coin">{{ $log->coin }}</td>
                        <td data-label="Created">{{ $log->created_at ?? $log->added_date ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No distributor logs.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Agent Wallet Logs</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Agent</th>
                    <th>Coin</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($agentLogs as $log)
                    <tr>
                        <td data-label="ID">{{ $log->id }}</td>
                        <td data-label="Agent">{{ trim(($log->Username ?? '').' '.($log->UserLastName ?? '')) }}</td>
                        <td data-label="Coin">{{ $log->coin }}</td>
                        <td data-label="Created">{{ $log->created_at ?? $log->added_date ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No agent logs.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
