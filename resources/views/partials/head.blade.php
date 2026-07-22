<meta charset="utf-8">
<title>@yield('judul', 'Dashboard') | {{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Sistem Informasi POS Toko Bangunan">
<meta name="author" content="HARISUIX">
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="shortcut icon" href="{{ asset('assets/admin/images/favicon.ico') }}">

{{-- Asset dan versinya dikunci dari folder template_admin. Jangan ganti dengan CDN. --}}
<script src="{{ asset('assets/admin/js/config-html.js') }}"></script>
<script src="{{ asset('assets/admin/js/config.js') }}"></script>

<link href="{{ asset('assets/admin/css/vendors.min.css') }}" rel="stylesheet" type="text/css">
<link id="app-style" href="{{ asset('assets/admin/css/app.min.css') }}" rel="stylesheet" type="text/css">

@stack('styles')
