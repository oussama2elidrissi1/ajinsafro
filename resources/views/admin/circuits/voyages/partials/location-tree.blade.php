{{-- Location Tree Component (recursive) - WordPress Traveler style --}}
@props(['locations', 'level' => 0, 'selectedIds' => []])

<ul class="wp-location-list" style="padding-left: {{ $level > 0 ? '20px' : '0' }}; margin: 0; list-style: none;">
    @foreach($locations as $location)
        <li class="wp-location-item" data-title="{{ strtolower($location['title']) }}" style="margin: 0; padding: 2px 0;">
            <label class="wp-loc-label" style="display: flex; align-items: center; cursor: pointer; font-size: 14px; line-height: 1.8; margin: 0;">
                <input 
                    type="checkbox" 
                    name="locations[]" 
                    value="{{ $location['id'] }}" 
                    class="location-checkbox"
                    style="margin: 0 8px 0 0; cursor: pointer;"
                    {{ in_array($location['id'], $selectedIds) ? 'checked' : '' }}
                >
                <span>{{ $location['title'] }}</span>
            </label>
            
            @if(!empty($location['children']))
                @include('admin.circuits.voyages.partials.location-tree', [
                    'locations' => $location['children'], 
                    'level' => $level + 1,
                    'selectedIds' => $selectedIds
                ])
            @endif
        </li>
    @endforeach
</ul>
