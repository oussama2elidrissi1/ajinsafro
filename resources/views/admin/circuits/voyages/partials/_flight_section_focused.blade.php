{{--
Section de vol focalisée pour modal - affiche une seule section selon contexte
@param string $type - 'outbound', 'return', ou 'segment'
@param string $title - Titre de la section
@param array $flightOptionsWithIndex - Options existantes
@param Collection $airlines - Compagnies aériennes
@param callable $fmtDate - Fonction formatage date
@param string $dash - Caractère tiret
@param int $dayNumber - Numéro du jour
@param bool $isModal - Mode modal
--}}

@php
    $type = $type ?? 'outbound';
    $title = $title ?? 'Vol';
    $flightOptionsWithIndex = $flightOptionsWithIndex ?? [];
    $airlines = $airlines ?? collect();
    $dayNumber = $dayNumber ?? 1;
    $isModal = $isModal ?? false;
    
    // Filtrer les options pour le type concerné
    $relevantOptions = collect($flightOptionsWithIndex)->filter(function($entry) use ($type, $dayNumber) {
        if ($type === 'segment') {
            return $entry['type'] === 'segment' && ($entry['option']->day_number ?? 0) == $dayNumber;
        }
        return $entry['type'] === $type;
    });
    
    $sectionId = $isModal ? "modal-flight-section-{$type}" : "flight-section-{$type}";
@endphp

<div class="flight-section-focused" 
     data-type="{{ $type }}" 
     data-day="{{ $dayNumber }}"
     id="{{ $sectionId }}">
    
    <div class="flight-section-header d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0">
            <i class="bx bx-trip me-1"></i>{{ $title }}
        </h6>
        @if($relevantOptions->isEmpty())
            <span class="badge bg-light text-muted">Aucun vol configuré</span>
        @else
            <span class="badge bg-success">{{ $relevantOptions->count() }} vol(s)</span>
        @endif
    </div>

    {{-- Cards des vols existants --}}
    <div class="flight-cards-container flight-opt-cards-{{ $type }}" data-type="{{ $type }}">
        @foreach($relevantOptions as $entry)
            @include('admin.circuits.voyages.partials._flight_option_card', [
                'index' => $entry['index'], 
                'option' => $entry['option'], 
                'type' => $type, 
                'dayLabel' => $type === 'segment' ? "Jour {$dayNumber}" : ($type === 'outbound' ? 'Jour 1' : "Jour {$dayNumber}"),
                'airlines' => $airlines, 
                'fmtDate' => $fmtDate, 
                'dash' => $dash,
                'isModal' => $isModal
            ])
        @endforeach
    </div>

    {{-- Bouton d'ajout --}}
    <div class="flight-add-section mt-3">
        @if($type === 'outbound')
            <button type="button" class="btn btn-sm btn-primary btn-add-flight-opt" data-type="outbound" data-day="1">
                <i class="bx bx-plus me-1"></i>Ajouter un vol Aller
            </button>
        @elseif($type === 'return')
            <button type="button" class="btn btn-sm btn-primary btn-add-flight-opt" data-type="return" data-day="{{ $dayNumber }}">
                <i class="bx bx-plus me-1"></i>Ajouter un vol Retour  
            </button>
        @else
            <button type="button" class="btn btn-sm btn-primary btn-add-flight-opt" data-type="segment" data-day="{{ $dayNumber }}">
                <i class="bx bx-plus me-1"></i>Ajouter un vol interne
            </button>
        @endif
        
        @if($isModal && $relevantOptions->isEmpty())
            <div class="text-muted small mt-2">
                @if($type === 'outbound')
                    Configurez le vol aller pour le premier jour du circuit
                @elseif($type === 'return')  
                    Configurez le vol retour pour le dernier jour du circuit
                @else
                    Configurez les vols internes pour ce jour du circuit
                @endif
            </div>
        @endif
    </div>

    {{-- Template pour nouveaux vols (caché) --}}
    @if($isModal)
        <div class="flight-templates" style="display: none;" data-templates="{{ $type }}">
            <div data-flight-tpl="{{ $type }}">
                @include('admin.circuits.voyages.partials._flight_option_card', [
                    'index' => -1, 
                    'option' => null, 
                    'type' => $type, 
                    'dayLabel' => $type === 'segment' ? "Jour {$dayNumber}" : ($type === 'outbound' ? 'Jour 1' : "Jour {$dayNumber}"),
                    'airlines' => $airlines, 
                    'fmtDate' => $fmtDate, 
                    'dash' => $dash,
                    'isModal' => $isModal
                ])
            </div>
        </div>
    @endif
</div>

{{-- CSS spécifique à cette section --}}
<style>
.flight-section-focused {
    border-radius: 8px;
    border: 1px solid #e9ecef;
    padding: 16px;
    background: #fff;
}
.flight-section-focused .flight-section-header h6 {
    color: #495057;
    font-weight: 600;
}
.flight-section-focused .flight-cards-container:empty + .flight-add-section {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 2px dashed #dee2e6;
}
.flight-section-focused .flight-cards-container:empty + .flight-add-section .btn {
    font-weight: 600;
}
</style>

@if($isModal)
    {{-- JavaScript pour gestion des interactions dans le modal --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const section = document.getElementById('{{ $sectionId }}');
        if (!section || section.dataset.focusedInitialized) return;
        section.dataset.focusedInitialized = 'true';

        const container = section.querySelector('.flight-cards-container');
        const addBtn = section.querySelector('.btn-add-flight-opt');
        const templates = section.querySelector('.flight-templates');
        
        if (!container || !addBtn || !templates) return;

        // Fonction pour obtenir le prochain index
        function getNextModalIndex() {
            const existingCards = document.querySelectorAll('.flight-opt-card[data-flight-opt-index]');
            let maxIndex = -1;
            existingCards.forEach(card => {
                const idx = parseInt(card.dataset.flightOptIndex);
                if (!isNaN(idx) && idx > maxIndex) maxIndex = idx;
            });
            return maxIndex + 1;
        }

        // Gestion du bouton d'ajout
        addBtn.addEventListener('click', function() {
            const type = this.dataset.type;
            const dayNum = this.dataset.day;
            
            const template = templates.querySelector(`[data-flight-tpl="${type}"]`);
            if (!template) return;
            
            const cardTemplate = template.querySelector('.flight-opt-card');
            if (!cardTemplate) return;
            
            const newIndex = getNextModalIndex();
            const clone = cardTemplate.cloneNode(true);
            
            // Mise à jour des attributs
            clone.setAttribute('data-flight-opt-index', newIndex);
            
            // Mise à jour des noms d'inputs
            clone.querySelectorAll('[name^="flight_options["]').forEach(input => {
                input.name = input.name.replace(/flight_options\[-1\]/, `flight_options[${newIndex}]`);
                input.removeAttribute('disabled');
                
                // Pré-remplir le jour pour les segments
                if (type === 'segment' && input.name.includes('[day_number]')) {
                    input.value = dayNum;
                }
            });

            // Réinitialiser l'affichage
            const viewPanel = clone.querySelector('.flight-opt-view');
            const editPanel = clone.querySelector('.flight-opt-edit');
            
            if (viewPanel) viewPanel.style.display = 'none';
            if (editPanel) editPanel.style.display = 'block';
            
            // Ajouter au container
            container.appendChild(clone);
            
            // Focus sur le premier input
            const firstInput = editPanel.querySelector('select, input[type="text"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }

            // Mettre à jour le badge du header
            updateSectionBadge();
        });

        // Fonction pour mettre à jour le badge
        function updateSectionBadge() {
            const badge = section.querySelector('.flight-section-header .badge');
            const cards = container.querySelectorAll('.flight-opt-card');
            
            if (badge) {
                if (cards.length === 0) {
                    badge.className = 'badge bg-light text-muted';
                    badge.textContent = 'Aucun vol configuré';
                } else {
                    badge.className = 'badge bg-success';
                    badge.textContent = `${cards.length} vol(s)`;
                }
            }
        }

        // Observer les changements dans le container
        if (window.MutationObserver) {
            const observer = new MutationObserver(() => updateSectionBadge());
            observer.observe(container, { childList: true });
        }
    });
    </script>
@endif