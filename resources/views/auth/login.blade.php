<x-guest-layout>
    <a href="{{ url('/') }}" class="auth-back">← Kembali ke halaman awal</a>
    <h2 class="auth-title">Masuk ke SILPJ</h2>
    <p class="auth-subtitle">Gunakan email dan kata sandi akun Anda untuk melanjutkan.</p>

    @if(session('status'))<div class="auth-alert success">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf
        <div class="auth-field">
            <label for="email">Alamat Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@sekolah.sch.id">
            @foreach($errors->get('email') as $message)<div class="auth-error">{{ $message }}</div>@endforeach
        </div>
        <div class="auth-field">
            <label for="password">Kata Sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
            @foreach($errors->get('password') as $message)<div class="auth-error">{{ $message }}</div>@endforeach
        </div>
        <div class="auth-row">
            <label class="auth-check"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            @if(Route::has('password.request'))<a class="auth-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>@endif
        </div>
        <button type="submit" class="auth-button">Masuk</button>
    </form>

    @if(Route::has('register'))
        <div class="auth-footer">Belum memiliki akun? <a href="{{ route('register') }}">Daftar sekarang</a></div>
    @endif
</x-guest-layout>
