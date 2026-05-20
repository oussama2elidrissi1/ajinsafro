<?php
    $priceRows = old('prices', $offer->prices->map(fn($item) => [
        'label' => $item->label,
        'type' => $item->type,
        'price' => $item->price,
        'old_price' => $item->old_price,
        'stock' => $item->stock,
        'condition' => $item->condition,
    ])->all());
    $departureRows = old('departures', $offer->departures->map(fn($item) => [
        'departure_date' => optional($item->departure_date)->format('Y-m-d'),
        'return_date' => optional($item->return_date)->format('Y-m-d'),
        'price_from' => $item->price_from,
        'total_places' => $item->total_places,
        'available_places' => $item->available_places,
        'reserved_places' => $item->reserved_places,
        'status' => $item->status,
        'internal_notes' => $item->internal_notes,
    ])->all());
    if ($priceRows === []) {
        $priceRows = [['label' => 'Adulte', 'type' => 'personne', 'price' => '', 'old_price' => '', 'stock' => '', 'condition' => '']];
    }
    if ($departureRows === []) {
        $departureRows = [['departure_date' => '', 'return_date' => '', 'price_from' => '', 'total_places' => '', 'available_places' => '', 'reserved_places' => '', 'status' => 'published', 'internal_notes' => '']];
    }
?>

<div class="row g-4">
    <div class="col-12">
        <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => '1. Informations générales','subtitle' => 'Structure principale de l�?Toffre économique.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '1. Informations générales','subtitle' => 'Structure principale de l�?Toffre économique.']); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Titre <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="<?php echo e(old('title', $offer->title)); ?>" class="form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" value="<?php echo e(old('slug', $offer->slug)); ?>" class="form-control <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Référence interne</label>
                    <input type="text" name="internal_reference" value="<?php echo e(old('internal_reference', $offer->internal_reference)); ?>" class="form-control <?php $__errorArgs = ['internal_reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['internal_reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Type d�?Toffre <span class="text-danger">*</span></label>
                    <select name="offer_type" class="form-select <?php $__errorArgs = ['offer_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <?php $__currentLoopData = $typeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('offer_type', $offer->offer_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['offer_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                    <select name="category" class="form-select <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <?php $__currentLoopData = $categoryOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('category', $offer->category) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('status', $offer->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordre</label>
                    <input type="number" name="sort_order" value="<?php echo e(old('sort_order', $offer->sort_order ?? 0)); ?>" class="form-control <?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mise en avant</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" <?php if(old('is_featured', $offer->is_featured)): echo 'checked'; endif; ?>>
                        <label class="form-check-label">Activer</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Description courte</label>
                    <textarea name="short_description" rows="3" class="form-control <?php $__errorArgs = ['short_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('short_description', $offer->short_description)); ?></textarea>
                    <?php $__errorArgs = ['short_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Description détaillée</label>
                    <textarea name="description" rows="6" class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('description', $offer->description)); ?></textarea>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
    </div>

    <div class="col-12">
        <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => '2. Prix & services','subtitle' => 'Tarification principale, options et services inclus.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '2. Prix & services','subtitle' => 'Tarification principale, options et services inclus.']); ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Prix à partir de</label>
                    <input type="number" step="0.01" name="price_from" value="<?php echo e(old('price_from', $offer->price_from)); ?>" class="form-control <?php $__errorArgs = ['price_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['price_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ancien prix</label>
                    <input type="number" step="0.01" name="old_price" value="<?php echo e(old('old_price', $offer->old_price)); ?>" class="form-control <?php $__errorArgs = ['old_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['old_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <input type="text" name="currency" value="<?php echo e(old('currency', $offer->currency ?: 'DH')); ?>" class="form-control <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type de prix</label>
                    <select name="price_type" class="form-select <?php $__errorArgs = ['price_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value="">Choisir</option>
                        <?php $__currentLoopData = $priceTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('price_type', $offer->price_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['price_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Acompte</label>
                    <input type="number" step="0.01" name="deposit_amount" value="<?php echo e(old('deposit_amount', $offer->deposit_amount)); ?>" class="form-control <?php $__errorArgs = ['deposit_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['deposit_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Total places</label>
                    <input type="number" min="0" name="total_places" value="<?php echo e(old('total_places', $offer->total_places)); ?>" class="form-control <?php $__errorArgs = ['total_places'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['total_places'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Places disponibles</label>
                    <input type="number" min="0" name="available_places" value="<?php echo e(old('available_places', $offer->available_places)); ?>" class="form-control <?php $__errorArgs = ['available_places'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['available_places'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Places réservées</label>
                    <input type="number" min="0" name="reserved_places" value="<?php echo e(old('reserved_places', $offer->reserved_places)); ?>" class="form-control <?php $__errorArgs = ['reserved_places'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['reserved_places'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="transport_included" value="1" id="transport_included" <?php if(old('transport_included', $offer->transport_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="transport_included">Transport inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="flight_included" value="1" id="flight_included" <?php if(old('flight_included', $offer->flight_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="flight_included">Vol inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="hotel_included" value="1" id="hotel_included" <?php if(old('hotel_included', $offer->hotel_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="hotel_included">Hôtel inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="meals_included" value="1" id="meals_included" <?php if(old('meals_included', $offer->meals_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="meals_included">Repas inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="guide_included" value="1" id="guide_included" <?php if(old('guide_included', $offer->guide_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="guide_included">Guide inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="insurance_included" value="1" id="insurance_included" <?php if(old('insurance_included', $offer->insurance_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="insurance_included">Assurance incluse</label></div></div>
                <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="transfer_included" value="1" id="transfer_included" <?php if(old('transfer_included', $offer->transfer_included)): echo 'checked'; endif; ?>><label class="form-check-label" for="transfer_included">Transfert inclus</label></div></div>
                <div class="col-md-3">
                    <label class="form-label">Repas</label>
                    <select name="meal_plan" class="form-select <?php $__errorArgs = ['meal_plan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value="">Non précisé</option>
                        <?php $__currentLoopData = $mealPlanOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('meal_plan', $offer->meal_plan) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['meal_plan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type d�?Thébergement</label>
                    <input type="text" name="accommodation_type" value="<?php echo e(old('accommodation_type', $offer->accommodation_type)); ?>" class="form-control <?php $__errorArgs = ['accommodation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['accommodation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nom hôtel</label>
                    <input type="text" name="hotel_name" value="<?php echo e(old('hotel_name', $offer->hotel_name)); ?>" class="form-control <?php $__errorArgs = ['hotel_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['hotel_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Catégorie hôtel</label>
                    <input type="text" name="hotel_category" value="<?php echo e(old('hotel_category', $offer->hotel_category)); ?>" class="form-control <?php $__errorArgs = ['hotel_category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['hotel_category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type de chambre</label>
                    <input type="text" name="room_type" value="<?php echo e(old('room_type', $offer->room_type)); ?>" class="form-control <?php $__errorArgs = ['room_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['room_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Résumé programme</label>
                    <input type="text" name="program_summary" value="<?php echo e(old('program_summary', $offer->program_summary)); ?>" class="form-control <?php $__errorArgs = ['program_summary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['program_summary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Inclus dans le prix</label>
                    <textarea name="included_items_text" rows="5" class="form-control <?php $__errorArgs = ['included_items_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('included_items_text', collect($offer->included_items ?? [])->implode("\n"))); ?></textarea>
                    <?php $__errorArgs = ['included_items_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Non inclus</label>
                    <textarea name="excluded_items_text" rows="5" class="form-control <?php $__errorArgs = ['excluded_items_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('excluded_items_text', collect($offer->excluded_items ?? [])->implode("\n"))); ?></textarea>
                    <?php $__errorArgs = ['excluded_items_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Conditions de paiement</label>
                    <textarea name="payment_conditions" rows="4" class="form-control <?php $__errorArgs = ['payment_conditions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('payment_conditions', $offer->payment_conditions)); ?></textarea>
                    <?php $__errorArgs = ['payment_conditions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Conditions d�?Tannulation</label>
                    <textarea name="cancellation_conditions" rows="4" class="form-control <?php $__errorArgs = ['cancellation_conditions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('cancellation_conditions', $offer->cancellation_conditions)); ?></textarea>
                    <?php $__errorArgs = ['cancellation_conditions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Prix variables</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="prices">Ajouter une ligne</button>
            </div>
            <div data-repeater="prices">
                <?php $__currentLoopData = $priceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Libellé</label><input type="text" name="prices[<?php echo e($index); ?>][label]" value="<?php echo e($row['label'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="prices[<?php echo e($index); ?>][type]" value="<?php echo e($row['type'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="prices[<?php echo e($index); ?>][price]" value="<?php echo e($row['price'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Ancien prix</label><input type="number" step="0.01" name="prices[<?php echo e($index); ?>][old_price]" value="<?php echo e($row['old_price'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Stock</label><input type="number" min="0" name="prices[<?php echo e($index); ?>][stock]" value="<?php echo e($row['stock'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>�-</button></div>
                            <div class="col-12"><label class="form-label">Condition</label><input type="text" name="prices[<?php echo e($index); ?>][condition]" value="<?php echo e($row['condition'] ?? ''); ?>" class="form-control"></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
    </div>

    <div class="col-12">
        <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => '3. Départs','subtitle' => 'Une offre économique peut avoir plusieurs dates et prix.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '3. Départs','subtitle' => 'Une offre économique peut avoir plusieurs dates et prix.']); ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Date départ</label><input type="date" name="departure_date" value="<?php echo e(old('departure_date', optional($offer->departure_date)->format('Y-m-d'))); ?>" class="form-control <?php $__errorArgs = ['departure_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['departure_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-3"><label class="form-label">Date retour</label><input type="date" name="return_date" value="<?php echo e(old('return_date', optional($offer->return_date)->format('Y-m-d'))); ?>" class="form-control <?php $__errorArgs = ['return_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['return_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-2"><label class="form-label">Jours</label><input type="number" min="0" name="duration_days" value="<?php echo e(old('duration_days', $offer->duration_days)); ?>" class="form-control <?php $__errorArgs = ['duration_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['duration_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-2"><label class="form-label">Nuits</label><input type="number" min="0" name="duration_nights" value="<?php echo e(old('duration_nights', $offer->duration_nights)); ?>" class="form-control <?php $__errorArgs = ['duration_nights'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['duration_nights'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-2">
                    <label class="form-label">Disponibilité</label>
                    <select class="form-select" disabled>
                        <option><?php echo e($availabilityOptions[$offer->availability_status] ?? 'Calcul automatique'); ?></option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Ville de départ</label><input type="text" name="departure_city" value="<?php echo e(old('departure_city', $offer->departure_city)); ?>" class="form-control <?php $__errorArgs = ['departure_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['departure_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-3"><label class="form-label">Destination</label><input type="text" name="destination" value="<?php echo e(old('destination', $offer->destination)); ?>" class="form-control <?php $__errorArgs = ['destination'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['destination'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-2"><label class="form-label">Pays</label><input type="text" name="country" value="<?php echo e(old('country', $offer->country)); ?>" class="form-control <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-2"><label class="form-label">Ville d�?Tarrivée</label><input type="text" name="arrival_city" value="<?php echo e(old('arrival_city', $offer->arrival_city)); ?>" class="form-control <?php $__errorArgs = ['arrival_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['arrival_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-2"><label class="form-label">Distance clé</label><input type="text" name="key_distance" value="<?php echo e(old('key_distance', $offer->key_distance)); ?>" class="form-control <?php $__errorArgs = ['key_distance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['key_distance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-12"><label class="form-label">Adresse / zone</label><input type="text" name="address_zone" value="<?php echo e(old('address_zone', $offer->address_zone)); ?>" class="form-control <?php $__errorArgs = ['address_zone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['address_zone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Départs multiples</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="departures">Ajouter un départ</button>
            </div>
            <div data-repeater="departures">
                <?php $__currentLoopData = $departureRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <div class="row g-3">
                            <div class="col-md-2"><label class="form-label">Départ</label><input type="date" name="departures[<?php echo e($index); ?>][departure_date]" value="<?php echo e($row['departure_date'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Retour</label><input type="date" name="departures[<?php echo e($index); ?>][return_date]" value="<?php echo e($row['return_date'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="departures[<?php echo e($index); ?>][price_from]" value="<?php echo e($row['price_from'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Places totales</label><input type="number" min="0" name="departures[<?php echo e($index); ?>][total_places]" value="<?php echo e($row['total_places'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Places dispo</label><input type="number" min="0" name="departures[<?php echo e($index); ?>][available_places]" value="<?php echo e($row['available_places'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-1"><label class="form-label">Réservées</label><input type="number" min="0" name="departures[<?php echo e($index); ?>][reserved_places]" value="<?php echo e($row['reserved_places'] ?? ''); ?>" class="form-control"></div>
                            <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>�-</button></div>
                            <div class="col-md-3">
                                <label class="form-label">Statut</label>
                                <select name="departures[<?php echo e($index); ?>][status]" class="form-select">
                                    <?php $__currentLoopData = $departureStatusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>" <?php if(($row['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-9"><label class="form-label">Note interne</label><input type="text" name="departures[<?php echo e($index); ?>][internal_notes]" value="<?php echo e($row['internal_notes'] ?? ''); ?>" class="form-control"></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
    </div>

    <div class="col-12">
        <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => '4. Médias','subtitle' => 'Images principales, galerie et médias SEO.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '4. Médias','subtitle' => 'Images principales, galerie et médias SEO.']); ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Image principale</label>
                    <input type="file" name="main_image_file" class="form-control <?php $__errorArgs = ['main_image_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept=".jpg,.jpeg,.png,.webp">
                    <?php $__errorArgs = ['main_image_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php if($offer->main_image_url): ?>
                        <div class="mt-2"><?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $offer->main_image_url,'alt' => $offer->title,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->main_image_url),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->title),'size' => 'sm']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $attributes = $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $component = $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?></div>
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_main_image" value="1" id="remove_main_image"><label class="form-check-label" for="remove_main_image">Supprimer</label></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Image fallback</label>
                    <input type="file" name="fallback_image_file" class="form-control <?php $__errorArgs = ['fallback_image_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept=".jpg,.jpeg,.png,.webp">
                    <?php $__errorArgs = ['fallback_image_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php if($offer->fallback_image_url): ?>
                        <div class="mt-2"><?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $offer->fallback_image_url,'alt' => $offer->title,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->fallback_image_url),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->title),'size' => 'sm']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $attributes = $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $component = $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?></div>
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_fallback_image" value="1" id="remove_fallback_image"><label class="form-check-label" for="remove_fallback_image">Supprimer</label></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vidéo</label>
                    <input type="url" name="video_url" value="<?php echo e(old('video_url', $offer->video_url)); ?>" class="form-control <?php $__errorArgs = ['video_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="https://">
                    <?php $__errorArgs = ['video_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Galerie images</label>
                    <input type="file" name="gallery_images[]" class="form-control <?php $__errorArgs = ['gallery_images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept=".jpg,.jpeg,.png,.webp" multiple>
                    <?php $__errorArgs = ['gallery_images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback d-block"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php if($offer->images->isNotEmpty()): ?>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php $__currentLoopData = $offer->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $image->image_url,'alt' => $offer->title,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($image->image_url),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offer->title),'size' => 'sm']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $attributes = $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $component = $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="replace_gallery" value="1" id="replace_gallery"><label class="form-check-label" for="replace_gallery">Remplacer toute la galerie</label></div>
                    <?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
    </div>

    <div class="col-12">
        <?php if (isset($component)) { $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.form-section','data' => ['title' => '5. SEO','subtitle' => 'Balises SEO, documents et contenu public complémentaire.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.form-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '5. SEO','subtitle' => 'Balises SEO, documents et contenu public complémentaire.']); ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Meta title</label><input type="text" name="meta_title" value="<?php echo e(old('meta_title', $offer->meta_title)); ?>" class="form-control <?php $__errorArgs = ['meta_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php $__errorArgs = ['meta_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-6"><label class="form-label">Image SEO</label><input type="file" name="seo_image_file" class="form-control <?php $__errorArgs = ['seo_image_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept=".jpg,.jpeg,.png,.webp"><?php $__errorArgs = ['seo_image_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-6"><label class="form-label">Meta description</label><textarea name="meta_description" rows="4" class="form-control <?php $__errorArgs = ['meta_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('meta_description', $offer->meta_description)); ?></textarea><?php $__errorArgs = ['meta_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-6"><label class="form-label">Keywords</label><textarea name="seo_keywords_text" rows="4" class="form-control <?php $__errorArgs = ['seo_keywords_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('seo_keywords_text', collect($offer->seo_keywords ?? [])->implode("\n"))); ?></textarea><?php $__errorArgs = ['seo_keywords_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div class="col-md-6"><label class="form-label">Documents nécessaires</label><textarea name="required_documents" rows="5" class="form-control <?php $__errorArgs = ['required_documents'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('required_documents', $offer->required_documents)); ?></textarea><?php $__errorArgs = ['required_documents'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $attributes = $__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__attributesOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3)): ?>
<?php $component = $__componentOriginalba421f08b6b43aecb09f8eebe577a4f3; ?>
<?php unset($__componentOriginalba421f08b6b43aecb09f8eebe577a4f3); ?>
<?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const createPriceRow = function (index) {
        return `
            <div class="border rounded-3 p-3 mb-3" data-row>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Libellé</label><input type="text" name="prices[${index}][label]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="prices[${index}][type]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="prices[${index}][price]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Ancien prix</label><input type="number" step="0.01" name="prices[${index}][old_price]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Stock</label><input type="number" min="0" name="prices[${index}][stock]" class="form-control"></div>
                    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>�-</button></div>
                    <div class="col-12"><label class="form-label">Condition</label><input type="text" name="prices[${index}][condition]" class="form-control"></div>
                </div>
            </div>
        `;
    };

    const createDepartureRow = function (index) {
        return `
            <div class="border rounded-3 p-3 mb-3" data-row>
                <div class="row g-3">
                    <div class="col-md-2"><label class="form-label">Départ</label><input type="date" name="departures[${index}][departure_date]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Retour</label><input type="date" name="departures[${index}][return_date]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="departures[${index}][price_from]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Places totales</label><input type="number" min="0" name="departures[${index}][total_places]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Places dispo</label><input type="number" min="0" name="departures[${index}][available_places]" class="form-control"></div>
                    <div class="col-md-1"><label class="form-label">Réservées</label><input type="number" min="0" name="departures[${index}][reserved_places]" class="form-control"></div>
                    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>�-</button></div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="departures[${index}][status]" class="form-select">
                            <?php $__currentLoopData = $departureStatusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-9"><label class="form-label">Note interne</label><input type="text" name="departures[${index}][internal_notes]" class="form-control"></div>
                </div>
            </div>
        `;
    };

    document.querySelectorAll('[data-add-row]').forEach(function (button) {
        button.addEventListener('click', function () {
            const key = button.getAttribute('data-add-row');
            const container = document.querySelector(`[data-repeater="${key}"]`);
            if (!container) {
                return;
            }
            const index = container.querySelectorAll('[data-row]').length;
            container.insertAdjacentHTML('beforeend', key === 'prices' ? createPriceRow(index) : createDepartureRow(index));
        });
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-row]');
        if (!removeButton) {
            return;
        }
        const row = removeButton.closest('[data-row]');
        if (row) {
            row.remove();
        }
    });
});
</script>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\economic-offers\_form.blade.php ENDPATH**/ ?>