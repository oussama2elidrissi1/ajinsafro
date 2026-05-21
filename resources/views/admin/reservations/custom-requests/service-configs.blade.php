@php
    $s = fn (string $path, $default = '') => old('services.'.str_replace('.', '.', $path), data_get($services ?? [], $path, $default));
    $isActive = fn (string $key) => array_key_exists($key, $services ?? []);
@endphp

<div class="crq-service-config {{ $isActive('flights') ? 'is-active' : '' }}" data-service-config="flights">
    <h4>Vols</h4>
    <div class="crq-grid-3">
        <input name="services[flights][departure_city]" class="crq-input" placeholder="Ville départ" value="{{ $s('flights.departure_city') }}">
        <input name="services[flights][destination]" class="crq-input" placeholder="Destination" value="{{ $s('flights.destination') }}">
        <select name="services[flights][trip_type]" class="crq-select"><option value="">Type trajet</option><option value="one_way" @selected($s('flights.trip_type') === 'one_way')>Aller simple</option><option value="round_trip" @selected($s('flights.trip_type') === 'round_trip')>Aller-retour</option></select>
        <select name="services[flights][stops]" class="crq-select"><option value="">Escale</option><option value="direct" @selected($s('flights.stops') === 'direct')>Direct</option><option value="stopover_ok" @selected($s('flights.stops') === 'stopover_ok')>Escale acceptée</option></select>
        <select name="services[flights][baggage]" class="crq-select"><option value="">Bagage inclus</option><option value="yes" @selected($s('flights.baggage') === 'yes')>Oui</option><option value="no" @selected($s('flights.baggage') === 'no')>Non</option></select>
        <select name="services[flights][class]" class="crq-select"><option value="">Classe</option><option value="economy" @selected($s('flights.class') === 'economy')>Economique</option><option value="business" @selected($s('flights.class') === 'business')>Business</option></select>
        <input name="services[flights][time_preference]" class="crq-input" placeholder="Préférence horaire" value="{{ $s('flights.time_preference') }}">
    </div>
</div>

<div class="crq-service-config {{ $isActive('accommodation') ? 'is-active' : '' }}" data-service-config="accommodation">
    <h4>Hébergement</h4>
    <div class="crq-grid-3">
        <select name="services[accommodation][type]" class="crq-select"><option value="">Type</option>@foreach(['hotel'=>'Hôtel','apartment'=>'Appartement','villa'=>'Villa','riad'=>'Riad'] as $v=>$l)<option value="{{ $v }}" @selected($s('accommodation.type') === $v)>{{ $l }}</option>@endforeach</select>
        <select name="services[accommodation][category]" class="crq-select"><option value="">Catégorie</option>@foreach(['2'=>'2*','3'=>'3*','4'=>'4*','5'=>'5*','luxury'=>'Luxe'] as $v=>$l)<option value="{{ $v }}" @selected($s('accommodation.category') === $v)>{{ $l }}</option>@endforeach</select>
        <input type="number" min="1" name="services[accommodation][nights]" class="crq-input" placeholder="Nombre de nuits" value="{{ $s('accommodation.nights') }}">
        <input type="number" min="1" name="services[accommodation][rooms]" class="crq-input" placeholder="Nombre de chambres" value="{{ $s('accommodation.rooms') }}">
        <select name="services[accommodation][room_type]" class="crq-select"><option value="">Type chambre</option>@foreach(['single'=>'Simple','double'=>'Double','twin'=>'Twin','triple'=>'Triple','quad'=>'Quad'] as $v=>$l)<option value="{{ $v }}" @selected($s('accommodation.room_type') === $v)>{{ $l }}</option>@endforeach</select>
        <select name="services[accommodation][board]" class="crq-select"><option value="">Pension</option>@foreach(['room_only'=>'Logement seul','breakfast'=>'Petit-déjeuner','half_board'=>'Demi-pension','full_board'=>'Pension complète','all_inclusive'=>'All inclusive'] as $v=>$l)<option value="{{ $v }}" @selected($s('accommodation.board') === $v)>{{ $l }}</option>@endforeach</select>
    </div>
</div>

<div class="crq-service-config {{ $isActive('transfers') ? 'is-active' : '' }}" data-service-config="transfers">
    <h4>Transferts</h4>
    <div class="crq-grid-3">
        <input name="services[transfers][from]" class="crq-input" placeholder="De" value="{{ $s('transfers.from') }}">
        <input name="services[transfers][to]" class="crq-input" placeholder="À" value="{{ $s('transfers.to') }}">
        <select name="services[transfers][trip_type]" class="crq-select"><option value="">Trajet</option><option value="one_way" @selected($s('transfers.trip_type') === 'one_way')>Aller simple</option><option value="round_trip" @selected($s('transfers.trip_type') === 'round_trip')>Aller-retour</option></select>
        <select name="services[transfers][vehicle_type]" class="crq-select"><option value="">Véhicule</option><option value="standard" @selected($s('transfers.vehicle_type') === 'standard')>Standard</option><option value="van" @selected($s('transfers.vehicle_type') === 'van')>Van</option><option value="luxury" @selected($s('transfers.vehicle_type') === 'luxury')>Luxe</option></select>
        <textarea name="services[transfers][routes_note]" class="crq-textarea" placeholder="Plusieurs trajets / remarques">{{ $s('transfers.routes_note') }}</textarea>
    </div>
</div>

<div class="crq-service-config {{ $isActive('excursions') ? 'is-active' : '' }}" data-service-config="excursions">
    <h4>Excursions</h4>
    <div class="crq-grid-3">
        <input name="services[excursions][themes]" class="crq-input" placeholder="Culturel, aventure, détente..." value="{{ $s('excursions.themes') }}">
        <input name="services[excursions][region]" class="crq-input" placeholder="Ville / région" value="{{ $s('excursions.region') }}">
        <input type="number" min="1" name="services[excursions][days]" class="crq-input" placeholder="Nombre de jours" value="{{ $s('excursions.days') }}">
        <select name="services[excursions][guide]" class="crq-select"><option value="">Guide demandé</option><option value="yes" @selected($s('excursions.guide') === 'yes')>Oui</option><option value="no" @selected($s('excursions.guide') === 'no')>Non</option></select>
    </div>
</div>

<div class="crq-service-config {{ $isActive('omra') ? 'is-active' : '' }}" data-service-config="omra">
    <h4>Omra</h4>
    <div class="crq-grid-3">
        <input name="services[omra][duration]" class="crq-input" placeholder="Durée" value="{{ $s('omra.duration') }}">
        <input type="number" min="0" name="services[omra][budget_per_person]" class="crq-input" placeholder="Budget / personne" value="{{ $s('omra.budget_per_person') }}">
        <select name="services[omra][room_type]" class="crq-select"><option value="">Chambre</option>@foreach(['double'=>'Double','triple'=>'Triple','quad'=>'Quad','quintuple'=>'Quintuple'] as $v=>$l)<option value="{{ $v }}" @selected($s('omra.room_type') === $v)>{{ $l }}</option>@endforeach</select>
        <input name="services[omra][board]" class="crq-input" placeholder="Pension" value="{{ $s('omra.board') }}">
        <input name="services[omra][haram_proximity]" class="crq-input" placeholder="Proximité Haram Makkah / Médine" value="{{ $s('omra.haram_proximity') }}">
        <select name="services[omra][under_1km]" class="crq-select"><option value="">Moins de 1km</option><option value="yes" @selected($s('omra.under_1km') === 'yes')>Oui</option><option value="no" @selected($s('omra.under_1km') === 'no')>Non</option></select>
    </div>
</div>

<div class="crq-service-config {{ $isActive('visa') ? 'is-active' : '' }}" data-service-config="visa">
    <h4>Visa</h4>
    <div class="crq-grid-3">
        <input name="services[visa][country]" class="crq-input" placeholder="Pays" value="{{ $s('visa.country') }}">
        <input type="number" min="1" name="services[visa][people]" class="crq-input" placeholder="Nombre de personnes" value="{{ $s('visa.people') }}">
        <input name="services[visa][type]" class="crq-input" placeholder="Type visa" value="{{ $s('visa.type') }}">
        <input type="date" name="services[visa][desired_date]" class="crq-input" value="{{ $s('visa.desired_date') }}">
    </div>
</div>

<div class="crq-service-config {{ $isActive('insurance') ? 'is-active' : '' }}" data-service-config="insurance">
    <h4>Assurance</h4>
    <div class="crq-grid-3">
        <input type="number" min="1" name="services[insurance][people]" class="crq-input" placeholder="Nombre de personnes" value="{{ $s('insurance.people') }}">
        <input name="services[insurance][duration]" class="crq-input" placeholder="Durée" value="{{ $s('insurance.duration') }}">
        <input name="services[insurance][coverage]" class="crq-input" placeholder="Type couverture" value="{{ $s('insurance.coverage') }}">
    </div>
</div>

<div class="crq-service-config {{ $isActive('other') ? 'is-active' : '' }}" data-service-config="other">
    <h4>Autre</h4>
    <textarea name="services[other][description]" class="crq-textarea" placeholder="Description libre">{{ $s('other.description') }}</textarea>
</div>
