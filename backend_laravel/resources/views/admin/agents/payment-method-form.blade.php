@extends('admin.layouts.app')

@section('title', 'Add Payment Method')
@section('heading', 'Add Payment Method')
@section('subheading', 'Create a payout option for the agent')

@section('content')
<div class="panel">
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.agents.payment-methods.store', $userId) }}">
        @csrf
        <div class="split-2">
            <div>
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Image</label>
                <input type="file" name="image" accept="image/*">
            </div>
        </div>
        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">Save</button>
            <a class="btn btn-secondary" href="{{ route('admin.agents.payment-methods', $userId) }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
