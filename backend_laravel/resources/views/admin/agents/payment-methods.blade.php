@extends('admin.layouts.app')

@section('title', 'Payment Methods')
@section('heading', 'Payment Methods')
@section('subheading', 'Agent payout methods')

@section('content')
<div class="panel stack">
    <div class="header-row">
        <div style="font-weight: 800;">Methods</div>
        <a class="btn" href="{{ route('admin.agents.payment-methods.create', $userId) }}">Add Method</a>
    </div>
    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Image</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($methods as $method)
                <tr>
                    <td data-label="ID">{{ $method->id }}</td>
                    <td data-label="Name">{{ $method->name }}</td>
                    <td data-label="Image">
                        @if (!empty($method->image))
                            <img src="{{ url('data/PaymentMethods/'.$method->image) }}" style="width: 80px; border-radius: 8px;">
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td data-label="Action">
                        <form method="POST" action="{{ route('admin.agents.payment-methods.delete', [$userId, $method->id]) }}" onsubmit="return confirm('Delete this method?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-secondary" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No methods found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
