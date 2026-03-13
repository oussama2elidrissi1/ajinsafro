<?php
/**
 * Part: Modern tabbed search widget
 * 5 tabs: Voyage, Billet d'avion, Hébergement, Transfert, Activité
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$search_shortcode = ! empty( $settings['search']['shortcode'] )
    ? $settings['search']['shortcode']
    : '[traveler_search]';

$shortcode_tag = 'traveler_search';
if ( preg_match( '/\[([a-zA-Z0-9_\-:]+)/', $search_shortcode, $m ) ) {
    $shortcode_tag = $m[1];
}

$voyages_page_url = function_exists( 'ajth_get_voyages_page_url' )
    ? ajth_get_voyages_page_url()
    : home_url( '/?post_type=st_tours' );
?>

<?php if ( shortcode_exists( $shortcode_tag ) && ! empty( $search_shortcode ) ) : ?>
    <div class="aj-search-native"><?php echo do_shortcode( $search_shortcode ); ?></div>
<?php else : ?>
<div class="aj-search-card" style="border-radius: var(--aj-radius); overflow: hidden; border: 1px solid #f3f4f6;">
    <div class="aj-search-tabs" id="aj-search-tabs">
        <button type="button" class="aj-search-tab aj-search-tab--active" data-target="voyage"><i class="fas fa-globe"></i> <span>Voyage</span></button>
        <button type="button" class="aj-search-tab" data-target="vol"><i class="fas fa-plane"></i> <span>Billet d'avion</span></button>
        <button type="button" class="aj-search-tab" data-target="hotel"><i class="fas fa-bed"></i> <span>Hébergement</span></button>
        <button type="button" class="aj-search-tab" data-target="transfert"><i class="fas fa-car"></i> <span>Transfert</span></button>
        <button type="button" class="aj-search-tab" data-target="activite"><i class="fas fa-camera"></i> <span>Activité</span></button>
    </div>

    <div class="aj-search-forms">
        <!-- FORM: VOYAGE -->
        <div id="aj-form-voyage" class="aj-search-form aj-search-form--active">
            <form method="get" action="<?php echo esc_url( $voyages_page_url ); ?>">
                <input type="hidden" name="s" value="">
                <input type="hidden" name="post_type" value="st_tours">
                <div class="aj-search-form__row">
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--orange"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Destination</span>
                            <input type="text" name="location_name" class="aj-search-field__input" placeholder="Où voulez-vous partir ?">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--blue"><i class="far fa-calendar-alt"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Départ - Retour</span>
                            <input type="text" name="start" class="aj-search-field__input" placeholder="Ajouter des dates">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--purple"><i class="fas fa-user-friends"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Clients</span>
                            <div class="aj-search-field__text">
                                <span>1 Adulte - 0 Enfant</span>
                                <i class="fas fa-chevron-down aj-search-field__caret"></i>
                            </div>
                        </div>
                    </div>
                    <div class="aj-search-submit">
                        <button type="submit" class="aj-search-submit__btn"><i class="fas fa-search"></i> Rechercher</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM: BILLET D'AVION -->
        <div id="aj-form-vol" class="aj-search-form">
            <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="s" value="">
                <input type="hidden" name="post_type" value="st_tours">
                <div class="aj-search-form__row">
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--blue"><i class="fas fa-plane-departure"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Départ</span>
                            <input type="text" name="departure" class="aj-search-field__input" placeholder="D'où partez-vous ?">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--orange"><i class="fas fa-plane-arrival"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Arrivée</span>
                            <input type="text" name="arrival" class="aj-search-field__input" placeholder="Où allez-vous ?">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--gray"><i class="far fa-calendar-alt"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Dates</span>
                            <input type="text" name="start" class="aj-search-field__input" placeholder="jj/mm - jj/mm">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--purple"><i class="fas fa-user"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Passagers</span>
                            <div class="aj-search-field__text"><span>1 Adulte, Éco</span></div>
                        </div>
                    </div>
                    <div class="aj-search-submit">
                        <button type="submit" class="aj-search-submit__btn">Chercher</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM: HÉBERGEMENT -->
        <div id="aj-form-hotel" class="aj-search-form">
            <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="s" value="">
                <input type="hidden" name="post_type" value="st_hotel">
                <div class="aj-search-form__row">
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon" style="background:#f0fdfa;color:#0d9488;"><i class="fas fa-bed"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Destination</span>
                            <input type="text" name="location_name" class="aj-search-field__input" placeholder="Ville ou nom d'hôtel">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--blue"><i class="far fa-calendar-check"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Arrivée - Départ</span>
                            <input type="text" name="start" class="aj-search-field__input" placeholder="Sélectionner dates">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--orange"><i class="fas fa-users"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Voyageurs</span>
                            <div class="aj-search-field__text"><span>1 Chambre, 2 Adultes</span></div>
                        </div>
                    </div>
                    <div class="aj-search-submit">
                        <button type="submit" class="aj-search-submit__btn">Chercher</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM: TRANSFERT -->
        <div id="aj-form-transfert" class="aj-search-form">
            <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="s" value="">
                <input type="hidden" name="post_type" value="st_cars">
                <div class="aj-search-form__row">
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--green"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Départ</span>
                            <input type="text" name="pickup" class="aj-search-field__input" placeholder="Lieu de prise en charge">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--red"><i class="fas fa-map-pin"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Destination</span>
                            <input type="text" name="dropoff" class="aj-search-field__input" placeholder="Lieu de dépôt">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--blue"><i class="far fa-clock"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Quand ?</span>
                            <input type="text" name="start" class="aj-search-field__input" placeholder="Date et heure">
                        </div>
                    </div>
                    <div class="aj-search-submit">
                        <button type="submit" class="aj-search-submit__btn">Trouver</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM: ACTIVITÉ -->
        <div id="aj-form-activite" class="aj-search-form">
            <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="s" value="">
                <input type="hidden" name="post_type" value="st_activity">
                <div class="aj-search-form__row">
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--pink"><i class="fas fa-map-marked-alt"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Lieu</span>
                            <input type="text" name="location_name" class="aj-search-field__input" placeholder="Où et quoi faire ?">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--blue"><i class="far fa-calendar-alt"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Date</span>
                            <input type="text" name="start" class="aj-search-field__input" placeholder="Quand ?">
                        </div>
                    </div>
                    <div class="aj-search-field">
                        <div class="aj-search-field__icon aj-search-field__icon--orange"><i class="fas fa-users"></i></div>
                        <div class="aj-search-field__content">
                            <span class="aj-search-field__label">Participants</span>
                            <div class="aj-search-field__text"><span>2 Adultes</span></div>
                        </div>
                    </div>
                    <div class="aj-search-submit">
                        <button type="submit" class="aj-search-submit__btn">Chercher</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
