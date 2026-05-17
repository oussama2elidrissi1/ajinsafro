<?php $__env->startSection('title', 'Messagerie'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $folders = [
            'inbox' => 'Boîte',
            'sent' => 'Envoyés',
            'drafts' => 'Brouillons',
            'trash' => 'Corbeille',
        ];
    ?>

    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0e3a5a] sm:text-3xl">Messagerie interne</h1>
            <p class="mt-1 text-sm text-gray-500">Vue Gmail intégrée au dashboard agent.</p>
        </div>
        <?php if(session('status')): ?>
            <div class="rounded-xl border border-[#0e3a5a]/10 bg-white px-4 py-2 text-sm font-medium text-[#0e3a5a] shadow-sm">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>
    </div>

    <div class="gmail-shell overflow-hidden rounded-[28px] border border-[#d7e3f4] bg-[#f8fbff] shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
        <div class="flex h-[calc(100vh-120px)] overflow-hidden">
            <aside class="hidden w-[280px] shrink-0 border-r border-[#d7e3f4] bg-[#f1f6fd] p-5 lg:flex lg:flex-col">
                <button type="button"
                        data-modal-open="modal-nouveau"
                        class="mb-6 inline-flex items-center justify-center gap-2 rounded-2xl bg-[#c2e7ff] px-5 py-4 text-sm font-semibold text-[#0b3558] shadow-sm transition hover:shadow-md">
                    <i class="fas fa-pen"></i>
                    Nouveau message
                </button>

                <nav class="space-y-1 text-sm">
                    <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $active = $folder === $key; ?>
                        <a href="<?php echo e(route($routeBase.'.index', array_filter(['folder' => $key, 'q' => $search ?: null]))); ?>"
                           class="flex items-center justify-between rounded-2xl px-4 py-3 transition <?php echo e($active ? 'bg-white font-semibold text-[#0b3558] shadow-sm' : 'text-gray-600 hover:bg-white/80 hover:text-[#0b3558]'); ?>">
                            <span><?php echo e($label); ?></span>
                            <?php if($key === 'inbox' && $unreadCount > 0): ?>
                                <span class="rounded-full bg-[#0b57d0] px-2 py-0.5 text-xs font-semibold text-white"><?php echo e($unreadCount); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>
            </aside>

            <section class="flex min-w-0 flex-1 flex-col overflow-hidden bg-white">
                <div class="border-b border-[#d7e3f4] px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <form method="GET" action="<?php echo e(route($routeBase.'.index')); ?>" class="min-w-0 flex-1">
                            <input type="hidden" name="folder" value="<?php echo e($folder); ?>">
                            <div class="relative">
                                <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                                <input type="search"
                                       name="q"
                                       value="<?php echo e($search); ?>"
                                       placeholder="Rechercher dans la messagerie"
                                       class="w-full rounded-full border border-transparent bg-[#eef3fd] py-3 pl-11 pr-4 text-sm text-gray-700 outline-none ring-0 transition focus:border-[#c2e7ff] focus:bg-white focus:shadow-[0_0_0_4px_rgba(194,231,255,0.5)]">
                            </div>
                        </form>

                        <div class="flex items-center justify-between gap-3 text-sm text-gray-500 xl:justify-end">
                            <span><?php echo e($rangeLabel); ?></span>
                            <div class="flex items-center gap-2">
                                <?php if($messages->onFirstPage()): ?>
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-300">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo e($messages->previousPageUrl()); ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#eef3fd] text-[#0b3558] transition hover:bg-[#dfe9fb]">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if($messages->hasMorePages()): ?>
                                    <a href="<?php echo e($messages->nextPageUrl()); ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#eef3fd] text-[#0b3558] transition hover:bg-[#dfe9fb]">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-300">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gmail-scroll min-h-0 flex-1 overflow-y-auto">
                    <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $senderName = $message->sender?->name ?? 'Message';
                            $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(\Illuminate\Support\Str::of($senderName)->replace(' ', ''), 0, 2));
                        ?>
                        <div class="group border-b border-[#edf2fa] px-4 py-3 transition hover:relative hover:z-[1] hover:shadow-[0_10px_26px_rgba(15,23,42,0.08)] <?php echo e($message->read ? 'bg-white' : 'bg-[#f3f8ff]'); ?> sm:px-6">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#0083c4] text-xs font-bold text-white">
                                    <?php echo e($initials); ?>

                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm <?php echo e(!$message->read ? 'font-bold text-[#0b3558]' : 'font-medium text-[#3c4043]'); ?>">
                                                <?php echo e($senderName); ?>

                                            </p>
                                            <a href="<?php echo e(route($routeBase.'.show', $message)); ?>"
                                               class="mt-1 block truncate text-sm <?php echo e(!$message->read ? 'font-bold text-[#0b3558]' : 'font-medium text-[#3c4043]'); ?>">
                                                <?php echo e($message->subject); ?>

                                                <span class="font-normal text-gray-500"> - <?php echo e($message->preview); ?></span>
                                            </a>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1 pl-0 lg:pl-4">
                                            <form method="POST" action="<?php echo e(route($routeBase.'.star', $message)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#eef3fd] hover:text-[#fbbc04]" title="Favori">
                                                    <i class="<?php echo e($message->starred ? 'fas text-[#fbbc04]' : 'far'); ?> fa-star text-xs"></i>
                                                </button>
                                            </form>

                                            <?php if(!$message->read && (int) $message->recipient_id === (int) auth()->id()): ?>
                                                <form method="POST" action="<?php echo e(route($routeBase.'.read', $message)); ?>" class="hidden group-hover:block">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>
                                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#eef3fd] hover:text-[#0b57d0]" title="Marquer comme lu">
                                                        <i class="fas fa-envelope-open-text text-xs"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" action="<?php echo e(route($routeBase.'.destroy', $message)); ?>" class="hidden group-hover:block">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#fff1f0] hover:text-[#d93025]" title="Corbeille">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </form>

                                            <span class="ml-2 text-xs <?php echo e(!$message->read ? 'font-bold text-[#0b3558]' : 'font-medium text-gray-500'); ?>">
                                                <?php echo e($message->created_at->isToday() ? $message->created_at->format('H:i') : $message->created_at->format('d M')); ?>

                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="flex h-full min-h-[260px] items-center justify-center px-6 py-12">
                            <div class="text-center">
                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#eef3fd] text-[#0b57d0]">
                                    <i class="fas fa-inbox text-xl"></i>
                                </div>
                                <h2 class="text-lg font-semibold text-[#0e3a5a]">Aucun message</h2>
                                <p class="mt-2 text-sm text-gray-500">Aucun résultat pour ce dossier ou cette recherche.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div id="modal-nouveau" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-sm">
        <div class="absolute inset-0" data-modal-close="modal-nouveau"></div>
        <div class="relative z-10 w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-bold text-[#0e3a5a]">Nouveau message</h2>
                <button type="button" data-modal-close="modal-nouveau" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="<?php echo e(route($routeBase.'.store')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>

                <div>
                    <label for="recipient_id" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Destinataire</label>
                    <select id="recipient_id" name="recipient_id" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none">
                        <option value="">Choisir un contact</option>
                        <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($contact->id); ?>" <?php if(old('recipient_id') == $contact->id): echo 'selected'; endif; ?>><?php echo e($contact->name); ?> (<?php echo e(ucfirst($contact->role)); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['recipient_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="subject" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Sujet</label>
                    <input id="subject" name="subject" type="text" value="<?php echo e(old('subject')); ?>" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none">
                    <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="body" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Message</label>
                    <textarea id="body" name="body" rows="7" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none"><?php echo e(old('body')); ?></textarea>
                    <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-modal-close="modal-nouveau" class="rounded-2xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" class="rounded-2xl bg-[#0083c4] px-5 py-3 text-sm font-semibold text-white hover:opacity-95">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        .gmail-scroll {
            scrollbar-gutter: stable;
        }

        .gmail-scroll::-webkit-scrollbar {
            width: 10px;
        }

        .gmail-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
            border: 2px solid #fff;
        }

        .gmail-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openModal = function (id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = function (id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('[data-modal-open]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(this.dataset.modalOpen);
                });
            });

            document.querySelectorAll('[data-modal-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    closeModal(this.dataset.modalClose);
                });
            });

            <?php if($errors->any()): ?>
                openModal('modal-nouveau');
            <?php endif; ?>
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.master-ajinsafro', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\messagerie\index.blade.php ENDPATH**/ ?>