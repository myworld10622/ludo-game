@extends('admin.layouts.app')

@section('title', 'Agents')
@section('heading', 'Agent Management')
@section('subheading', 'Create, fund, and monitor agents')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy admin table not found.</div>
    @endif

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Agents</div>
            <a class="btn" href="{{ route('admin.agents.create') }}">Add Agent</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Distributor</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Wallet</th>
                    <th>Password</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($agents as $agent)
                    <tr>
                        <td data-label="ID">{{ $agent->id }}</td>
                        <td data-label="Name">{{ trim(($agent->first_name ?? '').' '.($agent->last_name ?? '')) }}</td>
                        <td data-label="Distributor">{{ trim(($agent->distributor_fname ?? '').' '.($agent->distributor_lname ?? '')) }}</td>
                        <td data-label="Email">{{ $agent->email_id }}</td>
                        <td data-label="Mobile">{{ $agent->mobile }}</td>
                        <td data-label="Wallet">{{ $agent->wallet }}</td>
                        <td data-label="Password">{{ $agent->password }}</td>
                        <td data-label="Created">{{ $agent->created_date }}</td>
                        <td data-label="Action">
                            <div class="mobile-actions">
                                <a class="btn btn-secondary" href="{{ route('admin.agents.users', $agent->id) }}">Users</a>
                                <a class="btn btn-secondary" href="{{ route('admin.agents.wallet.logs', $agent->id) }}">Logs</a>
                                <a class="btn btn-secondary" href="{{ route('admin.agents.payment-methods', $agent->id) }}">Methods</a>
                                <a class="btn btn-secondary" href="{{ route('admin.agents.wallet.form', [$agent->id, 'add']) }}">Add Wallet</a>
                                <a class="btn btn-secondary" href="{{ route('admin.agents.wallet.form', [$agent->id, 'deduct']) }}">Deduct Wallet</a>
                                <a class="btn btn-secondary" href="{{ route('admin.agents.edit', $agent->id) }}">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No agents found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
