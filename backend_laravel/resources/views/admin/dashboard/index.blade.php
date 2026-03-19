@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Operational overview for the gaming backend')

@section('content')
    <div class="stats">
        @foreach ($stats as $label => $value)
            <div class="stat-card">
                <div class="stat-label">{{ str($label)->replace('_', ' ')->title() }}</div>
                <div class="stat-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Recent Audit Logs</strong>
            <a class="muted" href="{{ route('admin.audit-logs.index') }}">View all</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Source</th>
                        <th>Target</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent_audits as $log)
                        <tr>
                            <td>{{ $log->event }}</td>
                            <td>{{ $log->source }}</td>
                            <td>{{ $log->auditable_type }}#{{ $log->auditable_id }}</td>
                            <td>{{ optional($log->created_at)->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">No audit activity available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
