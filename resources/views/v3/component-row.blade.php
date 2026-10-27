<div class="component" data-component>
<label>Nama komponen<input data-field="label" name="components[{{ $i }}][label]" value="{{ $c['label'] }}" placeholder="Contoh: PPN / PPh / ongkir" maxlength="60" required>
</label>
<label>Kategori<select data-field="kategori" name="components[{{ $i }}][kategori]">
@foreach(['pajak'=>'Pajak','lainnya'=>'Komponen lain'] as $v=>$label)<option value="{{ $v }}" @selected($c['kategori']===$v)>{{ $label }}</option>
@endforeach
</select>
</label>
<label>Metode<select data-field="metode" name="components[{{ $i }}][metode]">
@foreach(['nominal'=>'Nominal','persentase'=>'Persentase'] as $v=>$label)<option value="{{ $v }}" @selected($c['metode']===$v)>{{ $label }}</option>
@endforeach
</select>
</label>
<label>Perlakuan<select data-field="perlakuan" name="components[{{ $i }}][perlakuan]">
@foreach(['termasuk'=>'Sudah termasuk harga','tambah'=>'Menambah tagihan','potong'=>'Memotong pembayaran'] as $v=>$label)<option value="{{ $v }}" @selected($c['perlakuan']===$v)>{{ $label }}</option>
@endforeach
</select>
</label>
<label data-mode="nominal">Nominal (Rp)<input data-field="nominal" name="components[{{ $i }}][nominal]" type="number" data-rupiah min="0" max="99999999999999" step="1" value="{{ $c['nominal']??0 }}">
</label>
<label data-mode="persentase">Persentase (%)<input data-field="persentase" name="components[{{ $i }}][persentase]" type="number" min="0" max="100" step="0.0001" value="{{ $c['persentase']??'' }}">
</label>
<label data-mode="persentase">Sumber dasar<select data-base-mode><option value="auto" @selected(!isset($c['dasar']))>Subtotal barang / jasa</option><option value="manual" @selected(isset($c['dasar']))>Isi dasar sendiri</option></select></label>
<label data-mode="persentase">Dasar perhitungan (Rp)<input data-field="dasar" name="components[{{ $i }}][dasar]" type="number" data-rupiah min="0" max="99999999999999" step="1" value="{{ $c['dasar']??'' }}">
</label>
<div class="actions component-result">
<strong data-component-amount>Rp0</strong>
<button class="secondary small" type="button" data-remove-component>Hapus</button>
</div>
<p class="component-explanation" data-component-help></p>
</div>
