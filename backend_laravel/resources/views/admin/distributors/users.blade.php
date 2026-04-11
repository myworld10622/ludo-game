@extends('admin.layouts.app')

@section('title', 'Distributor Agents')
@section('heading', 'Distributor Agents')
@section('subheading', 'Agents under this distributor')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy admin table not found.</div>
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
                @forelse ($agents as $agent)
                    <tr>
                        <td data-label="ID">{{ $agent->id }}</td>
                        <td data-label="Name">{{ trim(($agent->first_name ?? '').' '.($agent->last_name ?? '')) }}</td>
                        <td data-label="Email">{{ $agent->email_id }}</td>
                        <td data-label="Mobile">{{ $agent->mobile }}</td>
                        <td data-label="Wallet">{{ $agent->wallet }}</td>
                        <td data-label="Password">{{ $agent->password }}</td>
                        <td data-label="Created">{{ $agent->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No agents found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
