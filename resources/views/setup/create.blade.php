@extends('accounts.layout')
@section('title', 'Pengaturan Awal SiLPJ')
@section('content')
<p>Lengkapi identitas sekolah dan buat akun admin pertama. Admin akan langsung terhubung ke sekolah ini.</p>
<form method="POST" action="{{ route('setup.store') }}">
    @csrf
    <section class="card">
        <h2>1. Identitas sekolah</h2>
        <div class="grid">
            <div class="wide">
                <label for="nama_sekolah">Nama sekolah</label>
                <input id="nama_sekolah" name="nama_sekolah" value="{{ old('nama_sekolah') }}" required maxlength="255" autofocus>
            </div>
            <div>
                <label for="npsn">NPSN (8 digit)</label>
                <input id="npsn" name="npsn" value="{{ old('npsn') }}" required inputmode="numeric" pattern="[0-9]{8}" maxlength="8">
            </div>
            <div class="wide">
                <label for="alamat">Alamat sekolah</label>
                <textarea id="alamat" name="alamat" rows="3" required maxlength="2000">{{ old('alamat') }}</textarea>
            </div>
        </div>
    </section>
    <section class="card">
        <h2>2. Admin pertama</h2>
        <div class="grid">
            <div>
                <label for="name">Nama pengelola</label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
            </div>
            <div>
                <label for="email">Email untuk login</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="username">
            </div>
            @include('accounts.password-fields')
        </div>
    </section>
    <p class="notice">Setelah disimpan, pengaturan awal ditutup. Anda bisa menambahkan akun bendahara dan kepala sekolah dari menu admin.</p>
    <button type="submit">Simpan dan Mulai</button>
</form>
@endsection
