@php
    $footerDefaults = [
        'col1_heading' => 'En savoir plus',
        'col2_heading' => 'Société',
        'legal_text' => "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU",
    ];

    $messageUrl = Route::has('partner.messages.index')
        ? route('partner.messages.index')
        : (Route::has('agent.messagerie.index')
            ? route('agent.messagerie.index')
            : (Route::has('admin.messagerie.index') ? route('admin.messagerie.index') : '#'));

    $footerCols = [
        [
            'heading' => $footerDefaults['col1_heading'],
            'links' => [
                ['label' => 'À propos', 'url' => '#'],
                ['label' => 'FAQ', 'url' => '#'],
                ['label' => "Conditions d'utilisation", 'url' => '#'],
                ['label' => 'Blog', 'url' => '#'],
            ],
        ],
        [
            'heading' => $footerDefaults['col2_heading'],
            'links' => array_values(array_filter([
                ['label' => 'Emplois', 'url' => '#'],
                ['label' => 'Forum', 'url' => '#'],
                ['label' => 'Devenez-Partenaire', 'url' => url('/devenir-partenaire')],
                $messageUrl !== '#' ? ['label' => 'Laissez-nous un message', 'url' => $messageUrl] : null,
                ['label' => 'Contact', 'url' => '#'],
            ])),
        ],
    ];

    $legalLines = $footerDefaults['legal_text'];

    $paymentImages = [
        ['name' => 'Mastercard', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png', 'h' => '24px'],
        ['name' => 'Visa', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png', 'h' => '20px'],
        ['name' => 'PayPal', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/2560px-PayPal.svg.png', 'h' => '20px'],
        ['name' => 'Western Union', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/ba/Western_Union_logo.svg/1280px-Western_Union_logo.svg.png', 'h' => '20px'],
        ['name' => 'Wafacash', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1b/Wafacash_logo.svg/2560px-Wafacash_logo.svg.png', 'h' => '16px'],
    ];
@endphp

<footer class="aj-footer-v2">
    <div class="aj-container">
        <div class="aj-footer-v2__cols" style="position:relative;z-index:10;">
            @foreach($footerCols as $col)
                <div>
                    <h4 class="aj-footer-v2__heading">{{ $col['heading'] }}</h4>
                    <ul class="aj-footer-v2__list">
                        @foreach($col['links'] as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div>
                <h4 class="aj-footer-v2__heading">Mentions Légales</h4>
                <div class="aj-footer-v2__legal">
                    @foreach(explode("\n", $legalLines) as $line)
                        <p style="margin:0 0 8px;">{{ trim($line) }}</p>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="aj-footer-v2__nl-header">
                    <i class="far fa-envelope"></i>
                    <div>
                        <h4 class="aj-footer-v2__nl-title">Recevez en avant-première :</h4>
                        <p class="aj-footer-v2__nl-desc">Réductions, codes promo, offres exclusives ...</p>
                    </div>
                </div>
                <form class="aj-footer-v2__nl-form" method="post" action="#">
                    <input type="email" name="ajth_nl_email" placeholder="Saisissez votre email" required>
                    <button type="submit">S'INSCRIRE</button>
                </form>
            </div>
        </div>
    </div>

    <div class="aj-payments-v2">
        <p class="aj-payments-v2__label">Moyens de paiement acceptés</p>
        <div class="aj-payments-v2__icons">
            @foreach($paymentImages as $pm)
                <img src="{{ $pm['url'] }}" alt="{{ $pm['name'] }}" style="height:{{ $pm['h'] }};" loading="lazy">
            @endforeach
            <span class="aj-payments-v2__text-badge">CASH PLUS</span>
        </div>
    </div>

    <div style="height:128px;"></div>
</footer>
