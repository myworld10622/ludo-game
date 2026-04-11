@extends('admin.layouts.app')

@section('title', 'Manual Gateways')
@section('heading', 'Manual Gateways')
@section('subheading', 'Manage base gateway definitions')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy gateway table not found.</div>
    @endif

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Gateway List</div>
            <a class="btn" href="{{ route('admin.gateways.manual.create') }}">Add Manual Gateway</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($gateways as $gateway)
                    <tr>
                        <td data-label="ID">{{ $gateway->id }}</td>
                        <td data-label="Name">{{ $gateway->name }}</td>
                        <td data-label="Roles">{{ $gateway->role }}</td>
                        <td data-label="Status">
                            <span class="badge {{ (int) $gateway->status === 1 ? '' : 'off' }}">
                                {{ (int) $gateway->status === 1 ? 'Enabled' : 'Disabled' }}
                            </span>
                        </td>
                        <td data-label="Created">{{ $gateway->created_date }}</td>
                        <td data-label="Action">
                            <div class="mobile-actions">
                                <a class="btn btn-secondary" href="{{ route('admin.gateways.manual.edit', $gateway->id) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.gateways.manual.toggle', $gateway->id) }}" onsubmit="return confirm('Toggle this gateway status?');">
                                    @csrf
                                    <button type="submit" class="btn">{{ (int) $gateway->status === 1 ? 'Disable' : 'Enable' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No manual gateways found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
