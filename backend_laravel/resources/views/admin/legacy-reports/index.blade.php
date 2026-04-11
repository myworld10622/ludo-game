@extends('admin.layouts.app')

@section('title', 'Legacy Reports')
@section('heading', 'Legacy Reports')
@section('subheading', 'Quick access to migrated legacy data')

@section('content')
<div class="panel">
    <div class="header-row">
        <div>
            <div style="font-weight: 800; font-size: 18px;">Legacy Data Panels</div>
            <div class="muted">Use these pages to verify legacy tables during migration.</div>
        </div>
    </div>
    <div class="highlight-grid">
        <a class="highlight-card live-card" href="{{ route('admin.legacy-reports.purchase-history') }}">
            <div class="highlight-label">Purchase History</div>
            <div class="highlight-sub">User purchases from tbl_purchase.</div>
        </a>
        <a class="highlight-card live-card" href="{{ route('admin.legacy-reports.deposit-bonus') }}">
            <div class="highlight-label">Deposit Bonus</div>
            <div class="highlight-sub">Activation list from tbl_purcharse_ref.</div>
        </a>
        <a class="highlight-card running-card" href="{{ route('admin.legacy-reports.bet-commission') }}">
            <div class="highlight-label">Bet Commission</div>
            <div class="highlight-sub">Commission logs from tbl_bet_income_log.</div>
        </a>
        <a class="highlight-card running-card" href="{{ route('admin.legacy-reports.rebate-history') }}">
            <div class="highlight-label">Rebate History</div>
            <div class="highlight-sub">Rebate logs from tbl_rebate_income.</div>
        </a>
        <a class="highlight-card live-card" href="{{ route('admin.legacy-reports.welcome-bonus') }}">
            <div class="highlight-label">Welcome Bonus</div>
            <div class="highlight-sub">Rewards + logs from tbl_welcome_reward/log.</div>
        </a>
        <a class="highlight-card running-card" href="{{ route('admin.legacy-reports.withdrawal-logs') }}">
            <div class="highlight-label">Withdrawal Logs</div>
            <div class="highlight-sub">Withdraw requests from tbl_withdrawal_log.</div>
        </a>
        <a class="highlight-card live-card" href="{{ route('admin.legacy-reports.redeem-list') }}">
            <div class="highlight-label">Redeem List</div>
            <div class="highlight-sub">Redeem presets from tbl_redeem.</div>
        </a>
    </div>
</div>
@endsection
