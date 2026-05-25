{{-- Package Builder Items Section --}}
<div class="card mt-4">
    <div class="card-body">
        <h4 class="card-title mb-4">
            <i class="bx bx-package text-primary me-2"></i>
            Package Builder - Items par jour
        </h4>
        <p class="text-muted">Gérez les items (vols, hôtels, transferts, activités, repas, options) pour chaque jour du programme.</p>

        @if($voyage->programDays->isEmpty())
            <div class="alert alert-warning">
                <i class="bx bx-info-circle me-2"></i>
                Veuillez d'abord créer les jours du programme ci-dessus avant d'ajouter des items.
            </div>
        @else
            <div class="accordion" id="accordionItems">
                @foreach($voyage->programDays as $programDay)
                    @php
                        $dayItems = $voyage->dayItems->where('day_number', $programDay->day_number)->sortBy('sort_order');
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDay{{ $programDay->day_number }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapseDay{{ $programDay->day_number }}" aria-expanded="false" 
                                aria-controls="collapseDay{{ $programDay->day_number }}">
                                <strong>Jour {{ $programDay->day_number }} : {{ $programDay->title }}</strong>
                                <span class="badge bg-soft-primary text-primary ms-2">{{ $dayItems->count() }} items</span>
                            </button>
                        </h2>
                        <div id="collapseDay{{ $programDay->day_number }}" class="accordion-collapse collapse" 
                            aria-labelledby="headingDay{{ $programDay->day_number }}" data-bs-parent="#accordionItems">
                            <div class="accordion-body">
                                {{-- Items list for this day --}}
                                @if($dayItems->isEmpty())
                                    <div class="text-muted mb-3">
                                        <i class="bx bx-info-circle"></i> Aucun item pour ce jour.
                                    </div>
                                @else
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40px;">#</th>
                                                    <th>Type</th>
                                                    <th>Titre</th>
                                                    <th>Jours</th>
                                                    <th>Inclus</th>
                                                    <th>Prix delta</th>
                                                    <th style="width: 100px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($dayItems as $item)
                                                    <tr>
                                                        <td>{{ $item->sort_order }}</td>
                                                        <td>
                                                            <span class="badge bg-soft-info text-info">
                                                                {{ $item->type_label }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <strong>{{ $item->title }}</strong>
                                                            @if($item->details)
                                                                <br><small class="text-muted">{{ Str::limit($item->details, 60) }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($item->isMultiDay())
                                                                J{{ $item->start_day }}-{{ $item->end_day }}
                                                                @if($item->nights > 0)
                                                                    <small class="text-muted">({{ $item->nights }}n)</small>
                                                                @endif
                                                            @else
                                                                J{{ $item->day_number }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($item->included)
                                                                <span class="badge bg-success">Inclus</span>
                                                            @else
                                                                <span class="badge bg-warning">Optionnel</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($item->price_delta_per_person == 0)
                                                                <span class="text-muted">?</span>
                                                            @else
                                                                {{ $item->formatted_price_delta }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-soft-primary" 
                                                                onclick="editItem({{ $item->id }})" title="Modifier">
                                                                <i class="bx bx-edit"></i>
                                                            </button>
                                                            <form action="{{ route('admin.circuits.voyages.items.destroy', [$voyage, $item]) }}" 
                                                                method="POST" class="d-inline" 
                                                                onsubmit="return confirm('Supprimer cet item ?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-soft-danger" title="Supprimer">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                {{-- Add item button for this day --}}
                                <button type="button" class="btn btn-sm btn-soft-success" 
                                    onclick="addItemForDay({{ $programDay->day_number }})">
                                    <i class="bx bx-plus me-1"></i> Ajouter un item pour ce jour
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Modal for Add/Edit Item --}}
@include('admin.circuits.voyages.partials._item_modal')

@push('scripts')
<script>
    let itemModalEl = document.getElementById('itemModal');
    let itemForm = document.getElementById('itemForm');

    function getItemModal() {
        if (!itemModalEl || !window.bootstrap || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(itemModalEl);
    }

    function addItemForDay(dayNumber) {
        // Reset form
        itemForm.reset();
        itemForm.action = "{{ route('admin.circuits.voyages.items.store', $voyage) }}";
        itemForm.querySelector('input[name="_method"]')?.remove();
        
        // Set day_number
        document.getElementById('item_day_number').value = dayNumber;
        document.getElementById('item_start_day').value = dayNumber;
        document.getElementById('itemModalLabel').textContent = 'Ajouter un item - Jour ' + dayNumber;
        
        // Show modal
        let itemModal = getItemModal();
        if (itemModal) {
            itemModal.show();
        }
    }

    function editItem(itemId) {
        // Fetch item data via AJAX and populate form
        fetch(`/admin/circuits/voyages/{{ $voyage->id }}/items/${itemId}/edit`)
            .then(response => response.json())
            .then(data => {
                itemForm.action = `/admin/circuits/voyages/{{ $voyage->id }}/items/${itemId}`;
                
                // Add PUT method
                let methodInput = itemForm.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    itemForm.appendChild(methodInput);
                }
                methodInput.value = 'PUT';
                
                // Populate fields
                document.getElementById('item_day_number').value = data.day_number;
                document.getElementById('item_start_day').value = data.start_day;
                document.getElementById('item_end_day').value = data.end_day || '';
                document.getElementById('item_nights').value = data.nights;
                document.getElementById('item_type').value = data.type;
                document.getElementById('item_title').value = data.title;
                document.getElementById('item_details').value = data.details || '';
                document.getElementById('item_included').checked = data.included;
                document.getElementById('item_price_delta').value = (data.price_delta_per_person / 100).toFixed(2);
                document.getElementById('item_sort_order').value = data.sort_order;
                
                document.getElementById('itemModalLabel').textContent = 'Modifier l\'item';
                let itemModal = getItemModal();
                if (itemModal) {
                    itemModal.show();
                }
            })
            .catch(error => {
                console.error('Error fetching item:', error);
                alert('Erreur lors du chargement de l\'item');
            });
    }

    // Toggle end_day and nights based on type
    document.getElementById('item_type').addEventListener('change', function() {
        let isHotel = this.value === 'hotel_stay';
        document.getElementById('multiDayFields').style.display = isHotel ? 'block' : 'none';
    });
</script>
@endpush

