{{-- Location Tree (Destination UX) - recursive, path + toggle + indeterminate support --}}
@props(['locations', 'level' => 0, 'selectedIds' => [], 'path' => []])

<ul class="wp-location-list destination-tree-list" data-level="{{ $level }}" style="padding-left: {{ $level > 0 ? '1.25rem' : '0' }}; margin: 0; list-style: none;">
    @foreach($locations as $location)
        @php
            $locPath = array_merge($path, [$location['title']]);
            $pathStr = implode(' �?� ', $locPath);
            $hasChildren = !empty($location['children']);
            $isSelected = in_array($location['id'], $selectedIds);
        @endphp
        <li class="wp-location-item destination-tree-item {{ $hasChildren ? 'has-children' : '' }}"
            data-id="{{ $location['id'] }}"
            data-title="{{ strtolower($location['title']) }}"
            data-path="{{ $pathStr }}"
            data-has-children="{{ $hasChildren ? '1' : '0' }}">
            <div class="destination-tree-row">
                @if($hasChildren)
                    <span class="destination-tree-toggle" role="button" aria-expanded="true" title="Replier / Déplier"></span>
                @else
                    <span class="destination-tree-toggle destination-tree-toggle--empty"></span>
                @endif
                <label class="destination-tree-label">
                    <input type="checkbox"
                           name="locations[]"
                           value="{{ $location['id'] }}"
                           class="location-checkbox destination-checkbox"
                           {{ $isSelected ? 'checked' : '' }}
                           data-loc-id="{{ $location['id'] }}"
                           data-loc-title="{{ e($location['title']) }}">
                    <span class="destination-tree-title">{{ $location['title'] }}</span>
                </label>
            </div>
            @if($hasChildren)
                @include('admin.circuits.voyages.partials.location-tree', [
                    'locations' => $location['children'],
                    'level' => $level + 1,
                    'selectedIds' => $selectedIds,
                    'path' => $locPath,
                ])
            @endif
        </li>
    @endforeach
</ul>

