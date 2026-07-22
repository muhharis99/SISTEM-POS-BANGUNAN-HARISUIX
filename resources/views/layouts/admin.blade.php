<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    <div class="wrapper">
        @include('partials.topbar')
        @include('partials.sidebar')

        <div class="content-page">
            <div class="container-fluid">
                @hasSection('breadcrumb')
                    <div class="page-title-head d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            @yield('breadcrumb')
                        </div>
                    </div>
                @endif

                @if (session('berhasil'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i data-lucide="circle-check" class="me-1"></i>
                        {{ session('berhasil') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                @if (session('gagal'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i data-lucide="circle-alert" class="me-1"></i>
                        {{ session('gagal') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            @include('partials.footer')
        </div>
    </div>

    @include('partials.theme-customizer')
    @include('partials.scripts')
</body>
</html>
