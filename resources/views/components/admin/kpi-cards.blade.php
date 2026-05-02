@props(['kpis' => []])

@if(!empty($kpis))
    <section class="aj-kpis">
        @foreach($kpis as $kpi)
            <article class="aj-kpi">
                <div class="aj-kpi-head">
                    <div class="aj-kpi-icon {{ $kpi['color'] ?? '-blue' }}">
                        <i class="{{ $kpi['icon'] ?? 'bx bx-buildings' }}"></i>
                    </div>
                    <div>
                        <span class="aj-kpi-label">{{ $kpi['label'] ?? '' }}</span>
                        <strong class="aj-kpi-value">{{ $kpi['value'] ?? '0' }}</strong>
                        @if(!empty($kpi['note']))
                            <span class="aj-kpi-note">{{ $kpi['note'] }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </section>
@endif
