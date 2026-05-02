@props([
    'src' => null,
    'alt' => '',
    'size' => 'md',
    'placeholder' => 'images/admin-placeholder.svg',
])

@php
    $sizeClass = match($size) {
        'sm' => '--sm',
        'md' => '--md',
        'lg' => '--lg',
        'tour' => '--tour',
        'card-cover' => '--card-cover',
        default => '--md',
    };
@endphp

<div class="aj-thumb {{ $sizeClass }}">
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
        <div class="aj-thumb-placeholder" style="display:none;">
            <img src="{{ asset($placeholder) }}" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
        </div>
    @else
        <div class="aj-thumb-placeholder" style="display:grid;">
            <img src="{{ asset($placeholder) }}" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
        </div>
    @endif
</div>
