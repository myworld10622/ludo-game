@extends('admin.layouts.app')

@section('title', 'Distributor Wallet Logs')
@section('heading', 'Wallet Logs')
@section('subheading', 'Distributor wallet transactions')

@section('content')
<div class="panel">
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
            @forelse ($logs as $log)
                <tr>
                    <td data-label="ID">{{ $log->id }}</td>
                    <td data-label="User ID">{{ $log->user_id }}</td>
                    <td data-label="Coin">{{ $log->coin }}</td>
                    <td data-label="Created">{{ $log->created_at ?? $log->added_date ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No logs found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
