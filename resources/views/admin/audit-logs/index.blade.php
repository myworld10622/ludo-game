@extends('admin.layouts.app')

@section('title', 'Audit Logs')
@section('heading', 'Audit Logs')
@section('subheading', 'Cross-domain operational and system event history')

@section('content')
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Actor</th>
                        <th>Target</th>
                        <th>Source</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->event }}</td>
                            <td>{{ $log->actor_type ? $log->actor_type.'#'.$log->actor_id : '-' }}</td>
                            <td>{{ $log->auditable_type }}#{{ $log->auditable_id }}</td>
                            <td>{{ $log->source }}</td>
                            <td>{{ optional($log->created_at)->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $logs->links() }}</div>
    </div>
@endsection
