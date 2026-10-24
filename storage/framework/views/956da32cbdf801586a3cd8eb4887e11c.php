<section class="card">
<h2>Pemeriksaan LPJ</h2>
<div class="actions">
<?php if($edit): ?><form method="post" action="<?php echo e(route('v3.transition',$p->id)); ?>">
<?php echo csrf_field(); ?>
<input type="hidden" name="action" value="ajukan">
<button>Ajukan pemeriksaan</button>
</form>
<form method="post" action="<?php echo e(route('v3.transition',$p->id)); ?>" data-confirm="Batalkan transaksi dan seluruh dokumennya?">
<?php echo csrf_field(); ?>
<input type="hidden" name="action" value="batalkan">
<button class="secondary">Batalkan transaksi</button>
</form>
<?php endif; ?>
<?php if(auth()->user()->isKepalaSekolah()&&$p->status==='diajukan'): ?>
<?php if(auth()->id()!==$p->created_by&&auth()->id()!==$p->submitted_by): ?><form method="post" action="<?php echo e(route('v3.transition',$p->id)); ?>">
<?php echo csrf_field(); ?>
<input type="hidden" name="action" value="setujui">
<details>
<summary>Setujui LPJ</summary>
<p>Nomor: <strong><?php echo e($p->nomor_bukti); ?></strong>
<br>Kegiatan: <?php echo e($p->activity->nama); ?><br>Rekanan: <?php echo e($p->vendor->nama); ?><br>Nilai bruto: <strong>Rp<?php echo e(number_format($p->bruto,2,',','.')); ?></strong>
</p>
<label>
<input type="checkbox" name="confirmation" value="1" required> Saya sudah memeriksa transaksi ini dan menyetujui LPJ.</label>
<button>Konfirmasi persetujuan</button>
</details>
</form>
<?php endif; ?>
<form method="post" action="<?php echo e(route('v3.transition',$p->id)); ?>" data-confirm="Kembalikan untuk revisi?">
<?php echo csrf_field(); ?>
<input type="hidden" name="action" value="revisi">
<button class="secondary">Minta revisi</button>
</form>
<?php endif; ?>
<?php if(auth()->user()->isKepalaSekolah()&&$p->status==='disetujui'): ?><form method="post" action="<?php echo e(route('v3.transition',$p->id)); ?>" data-confirm="Buka kembali untuk revisi?">
<?php echo csrf_field(); ?>
<input type="hidden" name="action" value="buka">
<button class="secondary">Buka untuk revisi</button>
</form>
<?php endif; ?>
</div>
</section>
<?php /**PATH D:\04 DATA ACO\S8\Capstone Project\silpj-V3\resources\views/v3/partials/review-actions.blade.php ENDPATH**/ ?>