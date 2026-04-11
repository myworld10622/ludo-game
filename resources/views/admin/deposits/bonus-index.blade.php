@extends('admin.layouts.app')

@section('title', 'Deposit बोनस')
@section('heading', 'Deposit Bonus')
@section('subheading', 'Bonus slabs for deposits')

@section('content')
<div class="panel stack">
    <div class="header-row">
        <div>
            <div style="font-weight: 800; font-size: 18px;">Deposit Bonus Slabs</div>
            <div class="muted">Min/Max amount based bonus rules.</div>
        </div>
        <a class="btn" href="{{ route('admin.deposits.bonus.create') }}">Add Bonus</a>
    </div>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_deposit_bonus_master not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Min</th>
                <th>Max</th>
                <th>Self Bonus</th>
                <th>Upline Bonus</th>
                <th>Deposit Count</th>
                <th>Added Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td data-label="ID">{{ $row->id }}</td>
                    <td data-label="Min">{{ $row->min }}</td>
                    <td data-label="Max">{{ $row->max }}</td>
                    <td data-label="Self Bonus">{{ $row->self_bonus }}</td>
                    <td data-label="Upline Bonus">{{ $row->upline_bonus }}</td>
                    <td data-label="Deposit Count">{{ $row->deposit_count }}</td>
                    <td data-label="Added">{{ $row->added_date }}</td>
                    <td data-label="Action">
                        <a class="btn btn-secondary" href="{{ route('admin.deposits.bonus.edit', $row->id) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.deposits.bonus.delete', $row->id) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-secondary" type="submit" onclick="return confirm('Delete bonus slab?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No bonus slabs found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
