@extends('admin.layouts.app')

@section('title', 'Agent Users')
@section('heading', 'Agent Users')
@section('subheading', 'Users registered under this agent')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy users table not found.</div>
    @endif

    <div class="panel">
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
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td data-label="ID">{{ $user->id }}</td>
                        <td data-label="Name">{{ $user->name }}</td>
                        <td data-label="Email">{{ $user->email }}</td>
                        <td data-label="Mobile">{{ $user->mobile }}</td>
                        <td data-label="Wallet">{{ $user->wallet }}</td>
                        <td data-label="Password">{{ $user->password }}</td>
                        <td data-label="Created">{{ $user->added_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
