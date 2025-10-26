@extends('layout.app')

@section('content')

    @include('components.opePredict')

    <div id="prediction-container">
    <p>Loading prediction data...</p>
</div>
@endsection
