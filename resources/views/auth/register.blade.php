<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="HarvestIntel User Registration">
    <meta name="author" content="HarvestIntel">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('img/icons/icon-48x48.png') }}" />

    <title>Register | HarvestIntel</title>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">

                        <div class="text-center mt-4">
                            <h1 class="h2">Create your account</h1>
                            <p class="lead">
                                Join HarvestIntel to continue
                            </p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="card">
                            <div class="card-body">
                                <div class="m-sm-3">
                                    <form action="{{ route('login.post') }}" method="POST" id="registerForm">
                                        @csrf

                                        <div id="errors-list"></div>

                                        <div class="mb-3">
                                            <label class="form-label" for="name">Name</label>
                                            <input class="form-control form-control-lg" id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Enter your name" required autofocus />
                                            @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="email">Email</label>
                                            <input class="form-control form-control-lg" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required />
                                            @error('email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password">Password</label>
                                            <input class="form-control form-control-lg" type="password" id="password" name="password" placeholder="Create a password" required />
                                            @error('password')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                                            <input class="form-control form-control-lg" type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required />
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100" id="registerSubmit">Create account</button>

                                        <div class="mt-3 text-center">
                                            Already have an account?
                                            @if (Route::has('login'))
                                                <a href="{{ route('login') }}">Sign in</a>
                                            @else
                                                <a href="#" class="disabled" tabindex="-1" aria-disabled="true">Sign in</a>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- jQuery (only if you want AJAX-based submit) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJ+Y7Z6G3o5l9G1Gq4f5r5N2b6G7D9G8wZ5nE=" crossorigin="anonymous"></script>

    <script type="text/javascript">
        (function($){
            $(document).on('submit', '#registerForm', function(e){
                const form = this;
                const $btn = $('#registerSubmit');

                // If you prefer standard form submit, comment out the next 2 lines
                e.preventDefault();

                $btn.prop('disabled', true).text('Creating account...');
                $('#errors-list').empty();

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: $(form).serialize(),
                    dataType: 'json',
                }).done(function(resp){
                    $btn.prop('disabled', false).text('Create account');
                    if (resp.status || resp.success) {
                        window.location = resp.redirect ?? ('{{ url('/') }}');
                    } else if (resp.errors) {
                        Object.values(resp.errors).forEach(function(val){
                            $('#errors-list').append("<div class='alert alert-danger'>" + val + "</div>");
                        });
                    } else if (resp.message) {
                        $('#errors-list').append("<div class='alert alert-danger'>" + resp.message + "</div>");
                    } else {
                        // Fallback to full reload if unexpected response
                        form.submit();
                    }
                }).fail(function(xhr){
                    $btn.prop('disabled', false).text('Create account');
                    // If the backend returns normal HTML (non-JSON), fallback to normal submit
                    if (xhr.status === 419) {
                        $('#errors-list').append("<div class='alert alert-danger'>Session expired. Please refresh and try again.</div>");
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        Object.values(xhr.responseJSON.errors).forEach(function(val){
                            $('#errors-list').append("<div class='alert alert-danger'>" + val + "</div>");
                        });
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        $('#errors-list').append("<div class='alert alert-danger'>" + xhr.responseJSON.message + "</div>");
                    } else {
                        // Fallback to normal POST
                        form.submit();
                    }
                });
            });
        })(jQuery);
    </script>

</body>

</html>
