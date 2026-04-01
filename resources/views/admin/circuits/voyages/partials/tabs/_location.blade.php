<div class="tab-pane" id="location" role="tabpanel">
<div class="card ve-pane-card destination-ux-card">
                    <div class="card-body destination-ux-body">
                        <div class="destination-ux-header">
                            <h4 class="destination-ux-title">Tour location</h4>
                            <p class="destination-ux-helper">SÃ©lectionnez une ou plusieurs locations pour ce circuit.</p>
                            <div class="destination-ux-badge-wrap">
                                <span class="badge bg-primary destination-ux-badge" id="locationCountBadge">
                                    <span id="locationCountText">{{ count($selectedLocationIds ?? []) }} location(s) sÃ©lectionnÃ©e(s)</span>
                                </span>
                            </div>
                        </div>

                        {{-- SÃ©lections actuelles (chips) --}}
                        <div class="destination-ux-chips-section">
                            <div class="destination-ux-chips-label">SÃ©lections actuelles</div>
                            <div class="destination-ux-chips" id="locationChipsContainer"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary destination-ux-chips-clear" id="locationChipsClear" style="display: none;">Effacer tout</button>
                        </div>

                        {{-- Tous les pays du monde + catalogue villes (world_cities + WP, crÃ©ation ÃƒÂ  la volÃ©e) --}}
                        <div id="locationTreeContainer">
                            @include('admin.circuits.voyages.partials.location-country-cities', [
                                'worldCountries' => $worldCountries ?? [],
                                'countryCitiesData' => $countryCitiesData ?? [],
                                'mergedCitiesByCode' => $mergedCitiesByCode ?? [],
                                'selectedLocationIds' => $selectedLocationIds ?? []
                            ])
                        </div>
                    </div>
                </div>
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Localisation & Carte</h4>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresse complÃ¨te</label>
                                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $meta['address'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="id_location" class="form-label">ID Location</label>
                                    <input type="number" class="form-control" id="id_location" name="id_location" value="{{ old('id_location', $meta['id_location'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location_id" class="form-label">Location ID (alias)</label>
                                    <input type="number" class="form-control" id="location_id" name="location_id" value="{{ old('location_id', $meta['location_id'] ?? '') }}">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="map_lat" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="map_lat" name="map_lat" value="{{ old('map_lat', $meta['map_lat'] ?? '') }}" placeholder="Ex: 33.5731">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="map_lng" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="map_lng" name="map_lng" value="{{ old('map_lng', $meta['map_lng'] ?? '') }}" placeholder="Ex: -7.5898">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="map_zoom" class="form-label">Zoom carte</label>
                                    <input type="number" class="form-control" id="map_zoom" name="map_zoom" value="{{ old('map_zoom', $meta['map_zoom'] ?? '14') }}" min="1" max="20">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="map_type" class="form-label">Type de carte</label>
                                    <select class="form-select" id="map_type" name="map_type">
                                        <option value="roadmap" {{ old('map_type', $meta['map_type'] ?? '') === 'roadmap' ? 'selected' : '' }}>Roadmap</option>
                                        <option value="satellite" {{ old('map_type', $meta['map_type'] ?? '') === 'satellite' ? 'selected' : '' }}>Satellite</option>
                                        <option value="hybrid" {{ old('map_type', $meta['map_type'] ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                        <option value="terrain" {{ old('map_type', $meta['map_type'] ?? '') === 'terrain' ? 'selected' : '' }}>Terrain</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="st_google_map" class="form-label">Google Map (iframe code)</label>
                            <textarea class="form-control rich-editor" id="st_google_map" name="st_google_map" rows="4">{{ old('st_google_map', $meta['st_google_map'] ?? '') }}</textarea>
                        </div>
                        
                        <h5 class="mb-3 mt-4">Informations de contact</h5>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email de contact</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ old('contact_email', $meta['contact_email'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">TÃ©lÃ©phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $meta['phone'] ?? '') }}">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="fax" class="form-label">Fax</label>
                                    <input type="text" class="form-control" id="fax" name="fax" value="{{ old('fax', $meta['fax'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="website" class="form-label">Site web</label>
                                    <input type="text" class="form-control" id="website" name="website" value="{{ old('website', $meta['website'] ?? '') }}" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: PRICE --}}



