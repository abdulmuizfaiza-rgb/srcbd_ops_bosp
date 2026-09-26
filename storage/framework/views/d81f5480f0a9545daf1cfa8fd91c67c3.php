<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['active' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['active' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
// Warna huruf menu (non-aktif) bisa diatur Superadmin lewat menu Tampilan
// (--warna-huruf-menu, lihat layouts/app.blade.php) - dipasang lewat CSS
// variable class (BUKAN inline style) supaya class "hover:text-white" di
// bawah tetap menang saat kursor hover (spesifisitas class+pseudo lebih
// tinggi dari class warna dasar ini). Link yang sedang aktif (bg biru)
// SENGAJA tidak ikut diubah warnanya - dianggap gaya "badge/tombol aktif",
// bukan teks label biasa.
$classes = ($active ?? false)
            ? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold bg-blue-600 text-white shadow-sm shadow-blue-900/30'
            : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[color:var(--warna-huruf-menu)] hover:bg-slate-800/80 hover:text-white transition duration-150 ease-in-out';
?>

<a <?php echo e($attributes->merge(['class' => $classes])); ?>>
    <?php echo e($slot); ?>

</a>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/components/sidebar-link.blade.php ENDPATH**/ ?>