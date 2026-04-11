@extends('admin.layouts.app')

@section('title', 'Redeem List')
@section('heading', 'Redeem List')
@section('subheading', 'Legacy tbl_redeem presets')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy table tbl_redeem not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Coin</th>
                    <th>Amount</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Title">{{ $row->title ?? '-' }}</td>
                        <td data-label="Coin">{{ $row->coin ?? '-' }}</td>
                        <td data-label="Amount">{{ $row->amount ?? '-' }}</td>
                        <td data-label="Created">{{ $row->created_date ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
