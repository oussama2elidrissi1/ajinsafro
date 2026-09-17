@props(['hotel' => null, 'stHotel' => null, 'featuredUrl' => null])

@php
    $postTitle = old('post_title', $hotel->post_title ?? '');
    $postContent = old('post_content', $hotel->post_content ?? '');
    $postStatus = old('post_status', $hotel->post_status ?? 'publish');
    $postName = old('post_name', $hotel->post_name ?? '');
    $address = old('address', $stHotel->address ?? '');
    $hotelStar = old('hotel_star', $stHotel->hotel_star ?? '');
    $minPrice = old('min_price', $stHotel->min_price ?? '');
    $mapLat = old('map_lat', $stHotel->map_lat ?? '');
    $mapLng = old('map_lng', $stHotel->map_lng ?? '');
    $isFeatured = old('is_featured', $stHotel->is_featured ?? 'off');
@endphp

<div class="aje-stack">

    <div>
        <label for="post_title" class="aje-label">Titre <span class="aje-req">*</span></label>
        <input type="text" class="form-control @error('post_title') is-invalid @enderror" id="post_title" name="post_title" value="{{ $postTitle }}" required maxlength="255" style="max-width:640px;">
        @error('post_title')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @else
            <span class="aje-help">Tel qu'il apparaîtra dans les résultats de recherche et sur la facture.</span>
        @enderror
    </div>

    <div>
        <label for="post_content" class="aje-label">Description</label>
        <textarea class="form-control @error('post_content') is-invalid @enderror" id="post_content" name="post_content" rows="7">{{ $postContent }}</textarea>
        @error('post_content')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @else
            <span class="aje-help">Rédigez en français. Deux à quatre phrases suffisent : la fiche publique n'affiche que les deux premières lignes.</span>
        @enderror
    </div>

    <div class="aje-grid">
        <div style="min-width:0;">
            <label for="post_status" class="aje-label">Statut <span class="aje-req">*</span></label>
            <select class="form-select @error('post_status') is-invalid @enderror" id="post_status" name="post_status" required>
                <option value="publish" {{ $postStatus === 'publish' ? 'selected' : '' }}>Publié</option>
                <option value="draft" {{ $postStatus === 'draft' ? 'selected' : '' }}>Brouillon</option>
            </select>
            @error('post_status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div style="min-width:0;">
            <label for="post_name" class="aje-label">Slug</label>
            <input type="text" class="form-control aje-mono @error('post_name') is-invalid @enderror" id="post_name" name="post_name" value="{{ $postName }}" placeholder="parian-holiday-villas" maxlength="200">
            @error('post_name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @else
                <span class="aje-help">Laissez vide pour le générer depuis le titre.</span>
            @enderror
        </div>
    </div>

    <hr class="aje-rule">

    <div class="aje-grid">
        <div style="min-width:0;">
            <label for="address" class="aje-label">Adresse</label>
            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ $address }}" placeholder="Boulevard du 20 Août, quartier Founty">
            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div style="min-width:0;">
            <label for="hotel_star" class="aje-label">Catégorie</label>
            <select class="form-select @error('hotel_star') is-invalid @enderror" id="hotel_star" name="hotel_star">
                <option value="">Non renseignée</option>
                @for ($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}" {{ (string) $i === (string) $hotelStar ? 'selected' : '' }}>{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</option>
                @endfor
            </select>
            @error('hotel_star')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="aje-grid -narrow">
        <div style="min-width:0;">
            <label for="min_price" class="aje-label">Prix minimum</label>
            <span class="aje-suffix">
                <input type="number" step="0.01" min="0" class="form-control @error('min_price') is-invalid @enderror" id="min_price" name="min_price" value="{{ $minPrice }}" placeholder="185">
                <span>DH</span>
            </span>
            @error('min_price')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @else
                <span class="aje-help">Par nuit, pour la chambre la moins chère.</span>
            @enderror
        </div>
        <div style="min-width:0;">
            <span class="aje-label">Mise en avant</span>
            <input type="hidden" name="is_featured" value="off">
            <label class="aje-checkfield" for="is_featured">
                <input type="checkbox" id="is_featured" name="is_featured" value="on" {{ $isFeatured === 'on' ? 'checked' : '' }}>
                Afficher à la une
            </label>
        </div>
    </div>

    <div class="aje-subcard">
        <div class="aje-subcard-head">
            <span>Coordonnées GPS</span>
        </div>
        <div class="aje-grid" style="gap:14px;">
            <div style="min-width:0;">
                <label for="map_lat" class="aje-label">Latitude</label>
                <input type="text" class="form-control aje-mono @error('map_lat') is-invalid @enderror" id="map_lat" name="map_lat" value="{{ $mapLat }}" placeholder="30.401200">
                @error('map_lat')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div style="min-width:0;">
                <label for="map_lng" class="aje-label">Longitude</label>
                <input type="text" class="form-control aje-mono @error('map_lng') is-invalid @enderror" id="map_lng" name="map_lng" value="{{ $mapLng }}" placeholder="-9.562800">
                @error('map_lng')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <hr class="aje-rule">

    <div>
        <span class="aje-label">Image à la une</span>
        <div class="aje-media">
            <div class="aje-media-current">
                @if($featuredUrl)
                    {{-- Visuel injoignable : on retombe sur la trame plutot que sur une icone cassee. --}}
                    <img src="{{ $featuredUrl }}" alt="" onerror="this.remove();">
                @else
                    <span class="aje-media-tag">AUCUNE IMAGE</span>
                @endif
            </div>
            <div class="aje-dropzone">
                <span class="aje-dropzone-title">{{ $featuredUrl ? "Remplacer l'image" : 'Ajouter une image' }}</span>
                <span class="aje-dropzone-note">JPG, PNG ou WebP, 5 Mo maximum, 1600 × 1000 px recommandé.</span>
                <input type="file" class="form-control @error('featured_image') is-invalid @enderror" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp">
                @error('featured_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

</div>
