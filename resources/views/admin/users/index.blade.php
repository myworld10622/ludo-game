@extends('admin.layouts.app')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', 'Monitor registered users and account state')

@section('content')
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email ?: '-' }}</td>
                            <td>{{ $user->mobile ?: '-' }}</td>
                            <td><span class="badge {{ $user->is_active && ! $user->is_banned ? '' : 'off' }}">{{ $user->is_active && ! $user->is_banned ? 'Active' : 'Restricted' }}</span></td>
                            <td>{{ optional($user->last_login_at)->toDateTimeString() ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $users->links() }}</div>
    </div>
@endsection
