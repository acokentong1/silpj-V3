<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — SiLPJ</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f3f6fb;color:#172033}main{max-width:780px;margin:36px auto;padding:0 20px 40px}.brand{color:#1d4ed8;font-weight:800;letter-spacing:.08em}h1{font-size:28px;margin:12px 0}h2{font-size:18px;margin:0 0 18px}p{line-height:1.6;color:#586579}.card{padding:26px;background:white;border:1px solid #dce3ee;border-radius:16px;margin:20px 0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.wide{grid-column:1/-1}label{display:block;font-weight:600;font-size:14px;margin-bottom:8px}input,select,textarea{width:100%;padding:12px;border:1px solid #bac5d5;border-radius:8px;font:inherit;background:white}input:focus,select:focus,textarea:focus{outline:2px solid #2563eb;outline-offset:2px}button,.button{display:inline-block;background:#1d4ed8;color:white;border:0;border-radius:8px;padding:13px 20px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none}a{color:#1d4ed8}.actions{display:flex;align-items:center;gap:20px;margin-top:22px}.notice{padding:14px 18px;border-radius:10px;background:#eaf2ff;line-height:1.6}.errors{background:#fff0ef;color:#9b1c1c}.hint{font-size:13px}ul{padding-left:20px}@media(max-width:560px){.grid{grid-template-columns:1fr}.card{padding:20px}main{margin-top:24px}}
    </style>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20261024"><link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=20261024"><link rel="stylesheet" href="{{ asset('minimal.css') }}?v=20261024"></head>
<body>
<main>
    <div class="brand">SiLPJ · PENGELOLAAN AKUN</div>
    <h1>@yield('title')</h1>
    @if(session('success'))
        <div class="notice" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="notice errors" role="alert">
            <strong>Periksa kembali isian berikut:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
