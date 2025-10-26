@extends('layout.app')

@section('content')
    @include('components.opecost_ope')
    {{-- @include('components.opecost_cost') --}}
    <div id="costsTableContainer">
        @include('components.opecost_cost', ['costs' => $costs])
    </div>
    <script>
        $(document).on('click', '#operationsTableContainer .pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const $container = $('#operationsTableContainer');
            $container.css('opacity', 0.6);
            $.get(url).done(function(data) {
                $container.html($(data).find('#operationsTableContainer').html());
                feather.replace();
            }).always(() => $container.css('opacity', 1));
        });

        $(document).on('click', '#costsTableContainer .pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const $container = $('#costsTableContainer');
            $container.css('opacity', 0.6);
            $.get(url).done(function(data) {
                $container.html($(data).find('#costsTableContainer').html());
                feather.replace();
            }).always(() => $container.css('opacity', 1));
        });
    </script>
@endsection
