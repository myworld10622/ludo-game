@extends('admin.layouts.app')

@section('title', 'Agent Wallet Logs')
@section('heading', 'Wallet Logs')
@section('subheading', 'Agent wallet transactions')

@section('content')
<div class="panel">
    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Coin</th>
                <th>Added By</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td data-label="ID">{{ $log->id }}</td>
                    <td data-label="User ID">{{ $log->user_id }}</td>
                    <td data-label="Coin">{{ $log->coin }}</td>
                    <td data-label="Added By">{{ $log->added_by ?? '-' }}</td>
                    <td data-label="Created">{{ $log->created_at ?? $log->added_date ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No logs found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
