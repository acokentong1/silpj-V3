<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SILPJ') }}</title>
    <style>
        :root{--primary:#146452;--primary-dark:#123c33;--text:#183330;--muted:#647873;--border:#dfe7e3;--background:#f5f7f4;--danger:#b91c1c}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:var(--background);color:var(--text)}button,input,a{font:inherit}.auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px}.auth-shell{width:min(1000px,100%);display:grid;grid-template-columns:minmax(0,.95fr) minmax(420px,1.05fr);overflow:hidden;background:#fff;border:1px solid #dfe7e3;border-radius:20px;box-shadow:0 24px 60px rgba(16,24,40,.12)}.auth-brand{position:relative;overflow:hidden;padding:46px 42px;background:linear-gradient(145deg,#123c33,#18745d 55%,#28886c);color:#fff}.auth-brand:before,.auth-brand:after{content:"";position:absolute;border-radius:999px;background:rgba(255,255,255,.08)}.auth-brand:before{width:280px;height:280px;right:-120px;top:-90px}.auth-brand:after{width:210px;height:210px;left:-90px;bottom:-100px}.auth-brand-content{position:relative;z-index:1}.auth-brand-head{display:flex;align-items:center;gap:13px;color:#fff;text-decoration:none}.auth-brand-logo{width:48px;height:48px;color:#fff}.auth-brand-name strong{display:block;font-size:22px;letter-spacing:.2px}.auth-brand-name span{display:block;margin-top:3px;font-size:11px;opacity:.82}.auth-brand h1{margin:48px 0 12px;font-size:31px;line-height:1.18;letter-spacing:-.6px}.auth-brand>div>p{margin:0;max-width:400px;font-size:14px;line-height:1.7;opacity:.9}.auth-feature-list{display:grid;gap:12px;margin-top:34px}.auth-feature{display:flex;gap:10px;align-items:flex-start;font-size:12px;line-height:1.5}.auth-feature-icon{width:24px;height:24px;flex:0 0 24px;display:flex;align-items:center;justify-content:center;border-radius:999px;background:rgba(255,255,255,.16);font-size:11px;font-weight:900}.auth-form-side{display:flex;align-items:center;padding:44px 48px}.auth-form-wrap{width:100%;max-width:420px;margin:auto}.auth-title{margin:0;font-size:25px;letter-spacing:-.35px}.auth-subtitle{margin:7px 0 24px;color:var(--muted);font-size:13px;line-height:1.6}.auth-alert{padding:12px 13px;margin-bottom:16px;border-radius:9px;font-size:12px;line-height:1.55}.auth-alert.success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}.auth-alert.info{background:#edf5ef;color:#123c33;border:1px solid #cce2d4}.auth-alert.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.auth-form{display:grid;gap:15px}.auth-field label{display:block;margin-bottom:7px;color:#294b40;font-size:12px;font-weight:800}.auth-field input{width:100%;height:44px;padding:0 12px;border:1px solid #cbd8d0;border-radius:9px;background:#fff;color:#183330;outline:none}.auth-field input:focus{border-color:#7abda3;box-shadow:0 0 0 3px rgba(20,100,82,.12)}.auth-error{margin-top:6px;color:#b91c1c;font-size:11px;font-weight:700}.auth-row{display:flex;align-items:center;justify-content:space-between;gap:12px}.auth-check{display:inline-flex;align-items:center;gap:7px;color:#647873;font-size:12px}.auth-check input{width:15px;height:15px;accent-color:#18745d}.auth-link{color:#146452;text-decoration:none;font-size:12px;font-weight:800}.auth-link:hover{text-decoration:underline}.auth-button{width:100%;min-height:44px;border:0;border-radius:9px;background:#146452;color:#fff;font-size:13px;font-weight:900;cursor:pointer;box-shadow:0 7px 16px rgba(20,100,82,.17);transition:.16s ease}.auth-button:hover{background:#123c33;transform:translateY(-1px)}.auth-secondary-button{width:100%;min-height:42px;border:1px solid #cbd8d0;border-radius:9px;background:#fff;color:#294b40;font-size:12px;font-weight:800;cursor:pointer}.auth-footer{margin-top:20px;padding-top:17px;border-top:1px solid #e8eee9;text-align:center;color:#647873;font-size:12px}.auth-footer a{color:#146452;text-decoration:none;font-weight:900}.auth-back{display:inline-flex;align-items:center;gap:5px;margin-bottom:20px;color:#647873;text-decoration:none;font-size:11px;font-weight:800}.auth-back:hover{color:#146452}.auth-help{color:#7d9487;font-size:11px;line-height:1.5;margin-top:5px}
        @media(max-width:820px){.auth-page{padding:16px}.auth-shell{grid-template-columns:1fr;max-width:560px}.auth-brand{padding:25px 26px}.auth-brand h1{margin:24px 0 8px;font-size:24px}.auth-brand>div>p{font-size:12px}.auth-feature-list{display:none}.auth-form-side{padding:30px 26px 34px}}
        @media(max-width:460px){.auth-page{padding:0;align-items:stretch}.auth-shell{min-height:100vh;border:0;border-radius:0}.auth-brand{padding:21px 20px}.auth-brand-logo{width:40px;height:40px}.auth-brand h1{font-size:21px}.auth-form-side{padding:27px 20px}.auth-title{font-size:22px}.auth-row{align-items:flex-start;flex-direction:column}}
        a:focus-visible,button:focus-visible{outline:3px solid #7abda3;outline-offset:3px}
        @media(prefers-reduced-motion:reduce){.auth-button{transition:none}.auth-button:hover{transform:none}}
    </style>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=20261024"><link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=20261024"><link rel="stylesheet" href="{{ asset('minimal.css') }}?v=20261024"></head>
<body>
<div class="auth-page">
    <div class="auth-shell">
        <aside class="auth-brand">
            <div class="auth-brand-content">
                <a href="{{ url('/') }}" class="auth-brand-head">
                    <x-application-logo class="auth-brand-logo" />
                    <span class="auth-brand-name">
                        <strong>SILPJ V3</strong>
                        <span>Sistem Informasi Laporan Pertanggungjawaban Sekolah</span>
                    </span>
                </a>
                <h1>Dokumen LPJ lebih rapi dalam satu alur kerja.</h1>
                <p>Kelola transaksi belanja, Nota Pesanan, Kuitansi, BAPB, dan laporan sekolah dari satu aplikasi.</p>
                <div class="auth-feature-list">
                    <div class="auth-feature"><span class="auth-feature-icon">✓</span><span>Data transaksi dan dokumen LPJ saling terhubung.</span></div>
                    <div class="auth-feature"><span class="auth-feature-icon">✓</span><span>Status kelengkapan dapat dipantau dari Dashboard.</span></div>
                    <div class="auth-feature"><span class="auth-feature-icon">✓</span><span>Dokumen siap dicetak dan laporan dapat diekspor.</span></div>
                </div>
            </div>
        </aside>
        <section class="auth-form-side">
            <div class="auth-form-wrap">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
</body>
</html>
