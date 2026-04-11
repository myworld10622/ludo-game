@extends('admin.layouts.app')

@section('title', 'Redeem Presets')
@section('heading', 'Redeem Presets')
@section('subheading', 'Manage legacy redeem cards')

@section('content')
<div class="panel stack">
    <div class="header-row">
        <div>
            <div style="font-weight: 800; font-size: 18px;">Redeem Cards</div>
            <div class="muted">These are used in the app withdraw screen.</div>
        </div>
        <a class="btn" href="{{ route('admin.withdrawals.redeem.create') }}">Add Redeem</a>
    </div>

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
                <th>Image</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td data-label="ID">{{ $row->id }}</td>
                    <td data-label="Title">{{ $row->title }}</td>
                    <td data-label="Coin">{{ $row->coin }}</td>
                    <td data-label="Amount">{{ $row->amount }}</td>
                    <td data-label="Image">
                        @if (!empty($row->img))
                            <img src="{{ url('data/Redeem/'.$row->img) }}" style="width: 80px; border-radius: 8px;">
                        @else
                            -
                        @endif
                    </td>
                    <td data-label="Created">{{ $row->created_date }}</td>
                    <td data-label="Action">
                        <a class="btn btn-secondary" href="{{ route('admin.withdrawals.redeem.edit', $row->id) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.withdrawals.redeem.delete', $row->id) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-secondary" type="submit" onclick="return confirm('Remove this redeem?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No redeem presets found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
