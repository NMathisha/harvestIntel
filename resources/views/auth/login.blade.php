<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="HarvestIntel Admin Login">
    <meta name="author" content="HarvestIntel">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('img/icons/icon-48x48.png') }}" />
    <title>Sign In | HarvestIntel</title>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { border: 0; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); background: #0b1220; }
        .card-body { padding: 2rem 2rem; }
        .text-center.mt-4 h1 { color: #f8fafc; }
        .text-center.mt-4 p { color: #94a3b8; }
        .form-label { color: #cbd5e1; font-weight: 600; }
        .form-control { background: #0c162b; border: 1px solid #1e293b; color: #e2e8f0; }
        .form-control:focus { background: #0c162b; color: #fff; border-color: #3b82f6; box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.15); }
        .form-check-label { color: #94a3b8; }
        .btn-primary { background: linear-gradient(135deg, #2563eb, #7c3aed); border: 0; box-shadow: 0 10px 20px rgba(37,99,235,0.25); }
        .btn-primary:hover { filter: brightness(1.05); }
        .btn-primary:disabled { opacity: .7; }
        a { color: #60a5fa; }
        a:hover { color: #93c5fd; }
        #errors-list .alert { margin-bottom: .75rem; }
        .vh-100 { min-height: 100vh; }
        .m-sm-3 { padding: 1.25rem; }
    </style>

    <script>
        const dashboardStatsUrl = "{{ route('home') }}";
    </script>
</head>
<body>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">

                        <div class="text-center mt-4">
                            <h1 class="h2">Welcome back!</h1>
                            <p class="lead">Sign in to your account to continue</p>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="m-sm-3">
                                    <form id="loginForm" novalidate>
                                        <div id="errors-list"></div>

                                        <div class="mb-3">
                                            <label class="form-label" for="email">Email</label>
                                            <input class="form-control form-control-lg" id="email" type="email" name="email" placeholder="Enter your email" required autofocus />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password">Password</label>
                                            <input class="form-control form-control-lg" type="password" id="password" name="password" placeholder="Enter your password" required />
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check align-items-center">
                                                <input id="remember" type="checkbox" class="form-check-input" name="remember">
                                                <label class="form-check-label text-small" for="remember">Remember me</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100" id="loginSubmit">Login</button>

                                        <div class="mt-3 d-flex justify-content-between">
                                            @if (Route::has('password.request'))
                                                <a href="{{ route('password.request') }}">Forgot your password?</a>
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

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorsList = document.getElementById('errors-list');
            errorsList.innerHTML = '';

            if (email === 'admin@gmail.com' && password === 'admin') {
                window.location.href = dashboardStatsUrl;
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = 'Invalid email or password.';
                errorsList.appendChild(errorDiv);
            }
        });
    </script>
</body>
</html>
