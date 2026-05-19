<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title' => 'Destination',
    'image' => 'destinations/placeholder.jpg',
    'slug' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title' => 'Destination',
    'image' => 'destinations/placeholder.jpg',
    'slug' => null,
]); ?>
<?php foreach (array_filter(([
    'title' => 'Destination',
    'image' => 'destinations/placeholder.jpg',
    'slug' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $url = $slug ? route('front.search', ['location' => $title]) : '#';
    $imgSrc = str_starts_with($image, 'http') ? $image : asset('front/images/' . $image);
?>

<a href="<?php echo e($url); ?>" class="group block rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-shadow bg-gray-100">
    <div class="aspect-[4/3] relative overflow-hidden">
        <img src="<?php echo e($imgSrc); ?>" alt="<?php echo e($title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#667eea 0%,#764ba2 100%)'; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23667eea%22 width=%22400%22 height=%22300%22/%3E%3C/svg%3E';">
    </div>
    <div class="p-4">
        <h3 class="font-semibold text-gray-900 group-hover:text-brand transition"><?php echo e($title); ?></h3>
    </div>
</a>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\front\destination-card.blade.php ENDPATH**/ ?>