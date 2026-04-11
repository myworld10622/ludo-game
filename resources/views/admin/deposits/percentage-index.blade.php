@extends('admin.layouts.app')

@section('title', 'Deposit Percentage')
@section('heading', 'Deposit Percentage')
@section('subheading', 'Commission percentage by user type')

@section('content')
<div class="panel stack">
    <div class="header-row">
        <div>
            <div style="font-weight: 800; font-size: 18px;">Deposit Percentage</div>
            <div class="muted">Admin, Agent, Distributor percentage rules.</div>
        </div>
        <a class="btn" href="{{ route('admin.deposits.percentage.create') }}">Add Percentage</a>
    </div>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_deposit_percentage_master not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>User Type</th>
                <th>Percentage</th>
                <th>Added Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td data-label="ID">{{ $row->id }}</td>
                    <td data-label="User Type">
                        {{ (int) $row->user_type === 0 ? 'Admin' : ((int) $row->user_type === 2 ? 'Agent' : 'Distributor') }}
                    </td>
                    <td data-label="Percentage">{{ $row->percentage }}</td>
                    <td data-label="Added">{{ $row->added_date }}</td>
                    <td data-label="Action">
                        <a class="btn btn-secondary" href="{{ route('admin.deposits.percentage.edit', $row->id) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.deposits.percentage.delete', $row->id) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-secondary" type="submit" onclick="return confirm('Delete percentage?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No percentage rules found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
