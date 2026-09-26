<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name']));

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

foreach (array_filter((['name']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $strokeWidth = match ($name) {
        'plus', 'minus', 'x-mark' => '2.5',
        default => '2',
    };
?>

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo e($strokeWidth); ?>" stroke-linecap="round" stroke-linejoin="round" <?php echo e($attributes->merge(['class' => 'w-4 h-4'])); ?>>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch($name):
        case ('plus'): ?>
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
            <?php break; ?>

        <?php case ('minus'): ?>
            <line x1="5" y1="12" x2="19" y2="12" />
            <?php break; ?>

        <?php case ('pencil'): ?>
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
            <?php break; ?>

        <?php case ('trash'): ?>
            <polyline points="3 6 5 6 21 6" />
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
            <line x1="10" y1="11" x2="10" y2="17" />
            <line x1="14" y1="11" x2="14" y2="17" />
            <?php break; ?>

        <?php case ('download'): ?>
            <path d="M12 3v12" />
            <polyline points="7 10 12 15 17 10" />
            <path d="M5 19h14" />
            <?php break; ?>

        <?php case ('upload'): ?>
            <path d="M12 21V9" />
            <polyline points="7 14 12 9 17 14" />
            <path d="M5 5h14" />
            <?php break; ?>

        <?php case ('check'): ?>
            <polyline points="20 6 9 17 4 12" />
            <?php break; ?>

        <?php case ('x-mark'): ?>
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
            <?php break; ?>

        <?php case ('building'): ?>
            <rect x="4" y="3" width="16" height="18" rx="1" />
            <line x1="9" y1="7" x2="9" y2="7.01" />
            <line x1="15" y1="7" x2="15" y2="7.01" />
            <line x1="9" y1="11" x2="9" y2="11.01" />
            <line x1="15" y1="11" x2="15" y2="11.01" />
            <line x1="9" y1="15" x2="9" y2="15.01" />
            <line x1="15" y1="15" x2="15" y2="15.01" />
            <line x1="10" y1="21" x2="10" y2="17" />
            <line x1="14" y1="21" x2="14" y2="17" />
            <?php break; ?>

        <?php case ('users'): ?>
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            <?php break; ?>

        <?php case ('chart-bar'): ?>
            <line x1="12" y1="20" x2="12" y2="10" />
            <line x1="18" y1="20" x2="18" y2="4" />
            <line x1="6" y1="20" x2="6" y2="16" />
            <?php break; ?>

        <?php case ('clipboard-check'): ?>
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
            <rect x="9" y="3" width="6" height="4" rx="1" />
            <polyline points="9 14 11 16 15 12" />
            <?php break; ?>

        <?php case ('alert-circle'): ?>
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12" y2="16.01" />
            <?php break; ?>

        <?php case ('zoom-in'): ?>
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
            <line x1="11" y1="8" x2="11" y2="14" />
            <line x1="8" y1="11" x2="14" y2="11" />
            <?php break; ?>

        <?php case ('zoom-out'): ?>
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
            <line x1="8" y1="11" x2="14" y2="11" />
            <?php break; ?>

        <?php case ('filter'): ?>
            <polygon points="4 4 20 4 14 12.5 14 19 10 21 10 12.5 4 4" />
            <?php break; ?>

        <?php case ('user-plus'): ?>
            <path d="M13 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="7" cy="7" r="4" />
            <line x1="19" y1="8" x2="19" y2="14" />
            <line x1="16" y1="11" x2="22" y2="11" />
            <?php break; ?>

        
        <?php case ('cog'): ?>
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
            <?php break; ?>

        <?php case ('printer'): ?>
            <polyline points="6 9 6 2 18 2 18 9" />
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <rect x="6" y="14" width="12" height="8" />
            <?php break; ?>

        
        <?php case ('lock-closed'): ?>
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            <?php break; ?>

        
        <?php case ('search'): ?>
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
            <?php break; ?>
    <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</svg>
<?php /**PATH D:\Aplikasi SRCBD_OPS_BOSP\srcbd-ops-bosp-source\resources\views/components/icon.blade.php ENDPATH**/ ?>