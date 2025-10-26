@extends('layout.app')

@section('content')

    @include('components.ana_cost')

    <div id="predictionContainer" class="mt-4"></div>
<canvas id="costChart" height="100"></canvas>
@endsection
