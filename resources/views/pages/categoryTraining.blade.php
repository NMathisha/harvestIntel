@extends('layout.app')

@section('content')
    {{-- @include('components.ope_form') --}}
    {{-- @include('components.cost_form')
    @include('components.cost_table') --}}
    @include('components.category')

    <div id="training-summary" class="container mt-4" style="display:none;">
    <h3 id="training-message" class="mb-3 text-success"></h3>

    <table class="table table-bordered" id="training-metrics">
        <tbody></tbody>
    </table>

    <div id="recommendations-section">
        <h4>📌 Recommendations</h4>
        <ul id="recommendations-list" class="list-group"></ul>
    </div>
</div>

@endsection
