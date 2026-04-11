@extends('admin.layouts.app')

@section('title', 'Distributors')
@section('heading', 'Distributor Management')
@section('subheading', 'Manage distributor accounts and wallets')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy admin table not found.</div>
    @endif

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Distributors</div>
            <a class="btn" href="{{ route('admin.distributors.create') }}">Add Distributor</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Wallet</th>
                    <th>Password</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($distributors as $distributor)
                    <tr>
                        <td data-label="ID">{{ $distributor->id }}</td>
                        <td data-label="Name">{{ trim(($distributor->first_name ?? '').' '.($distributor->last_name ?? '')) }}</td>
                        <td data-label="Email">{{ $distributor->email_id }}</td>
                        <td data-label="Mobile">{{ $distributor->mobile }}</td>
                        <td data-label="Wallet">{{ $distributor->wallet }}</td>
                        <td data-label="Password">{{ $distributor->password }}</td>
                        <td data-label="Created">{{ $distributor->created_date }}</td>
                        <td data-label="Action">
                            <div class="mobile-actions">
                                <a class="btn btn-secondary" href="{{ route('admin.distributors.users', $distributor->id) }}">Agents</a>
                                <a class="btn btn-secondary" href="{{ route('admin.distributors.wallet.logs', $distributor->id) }}">Logs</a>
                                <a class="btn btn-secondary" href="{{ route('admin.distributors.payment-history', $distributor->id) }}">Payments</a>
                                <a class="btn btn-secondary" href="{{ route('admin.distributors.wallet.form', [$distributor->id, 'add']) }}">Add Wallet</a>
                                <a class="btn btn-secondary" href="{{ route('admin.distributors.wallet.form', [$distributor->id, 'deduct']) }}">Deduct Wallet</a>
                                <a class="btn btn-secondary" href="{{ route('admin.distributors.edit', $distributor->id) }}">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No distributors found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
