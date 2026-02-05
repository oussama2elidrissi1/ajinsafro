{{-- Location Tree Component (recursive) --}}
@props(['locations', 'level' => 0, 'selectedIds' => []])

@foreach($locations as $location)
    <div class="location-item" style="padding-left: {{ $level * 20 }}px;" data-title="{{ strtolower($location['title']) }}">
        <div class="form-check">
            <input 
                class="form-check-input location-checkbox" 
                type="checkbox" 
                name="multi_location[]" 
                value="{{ $location['id'] }}" 
                id="location_{{ $location['id'] }}"
                {{ in_array($location['id'], $selectedIds) ? 'checked' : '' }}
            >
            <label class="form-check-label" for="location_{{ $location['id'] }}">
                {{ $location['title'] }}
            </label>
        </div>
        
        @if(!empty($location['children']))
            @include('admin.circuits.voyages.partials.location-tree', [
                'locations' => $location['children'], 
                'level' => $level + 1,
                'selectedIds' => $selectedIds
            ])
        @endif
    </div>
@endforeach
