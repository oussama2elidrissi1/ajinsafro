<?php
/**
 * Part: Hébergements mis en avant.
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$accom_settings = isset( $settings['accommodations'] ) && is_array( $settings['accommodations'] )
    ? $settings['accommodations']
    : array();

$section_title = ! empty( $accom_settings['title'] ) ? $accom_settings['title'] : 'Hébergements sélectionnés';
$items_count   = ! empty( $accom_settings['count'] ) ? max( 1, intval( $accom_settings['count'] ) ) : 4;
$hebergements = function_exists( 'getAjinsafroHebergements' ) ? getAjinsafroHebergements( $items_count ) : array();
if ( empty( $hebergements ) ) return;
?>

<section class="aj-accom" id="aj-sejours">
    <div class="aj-container">
        <div class="aj-section-head">
            <h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>
            <div class="aj-section-arrows">
                <button type="button" class="aj-section-arrow aj-accom-prev" aria-label="Précédent"><i class="fas fa-angle-left"></i></button>
                <button type="button" class="aj-section-arrow aj-accom-next" aria-label="Suivant"><i class="fas fa-angle-right"></i></button>
            </div>
        </div>

        <div class="aj-slider-v2" id="aj-accom-track">
            <?php foreach ( $hebergements as $hebergement ) :
                $star_count = isset( $hebergement['stars'] ) ? max( 0, (int) $hebergement['stars'] ) : 0;
                $price_value = isset( $hebergement['price'] ) ? $hebergement['price'] : null;
                $price_label = $price_value !== null && $price_value !== '' ? number_format_i18n( (float) $price_value, 0 ) : '';
            ?>
            <div class="aj-slider-v2__item">
                <a href="<?php echo esc_url( $hebergement['url'] ); ?>" class="aj-card2 aj-hover-glass" style="text-decoration:none;" aria-label="<?php echo esc_attr( $hebergement['title'] ); ?>">
                    <div class="aj-card2__image">
                        <?php
                        // Les cartes suivent .aj-slider-v2__item, comme le carrousel de last-minute.php :
                        // 80vw jusqu'a 768px, 50vw jusqu'a 1024px, puis 25% d'un conteneur de 1280px.
                        // Le helper choisit une famille de tailles pour laquelle WordPress sait calculer
                        // un srcset (le `large` carre de Traveler n'en a pas) ; src suit cette famille.
                        $image_id   = isset( $hebergement['image_id'] ) ? (int) $hebergement['image_id'] : 0;
                        $image_size = ! empty( $hebergement['image_size'] ) ? (string) $hebergement['image_size'] : 'large';
                        $responsive = ( $image_id > 0 && function_exists( 'ajth_hebergement_card_responsive_image' ) )
                            ? ajth_hebergement_card_responsive_image( $image_id, $image_size )
                            : array( 'src' => '', 'srcset' => '', 'size' => '' );
                        $srcset = $responsive['srcset'];
                        $src    = $srcset !== '' ? $responsive['src'] : $hebergement['image_url'];
                        ?>
                        <img loading="lazy" decoding="async" width="900" height="600" src="<?php echo esc_url( $src ); ?>"<?php if ( $srcset !== '' ) : ?> srcset="<?php echo esc_attr( $srcset ); ?>" sizes="(max-width: 768px) 80vw, (max-width: 1024px) 50vw, 300px"<?php endif; ?> alt="<?php echo esc_attr( $hebergement['title'] ); ?>">
                        <?php if ( ! empty( $hebergement['category'] ) ) : ?>
                        <span class="aj-card2__badge aj-card2__badge--info"><?php echo esc_html( $hebergement['category'] ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="aj-card2__body">
                        <h3 class="aj-card2__title"><?php echo esc_html( $hebergement['title'] ); ?></h3>
                        <?php if ( ! empty( $hebergement['location'] ) ) : ?>
                        <div class="aj-card2__location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( $hebergement['location'] ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $hebergement['excerpt'] ) ) : ?>
                        <p class="aj-card2__desc"><?php echo esc_html( $hebergement['excerpt'] ); ?></p>
                        <?php endif; ?>
                        <div class="aj-card2__meta">
                            <span class="aj-card2__category"><?php echo esc_html( $hebergement['category'] ?? 'Hôtel' ); ?></span>
                            <?php if ( $star_count > 0 ) : ?>
                            <div class="aj-card2__stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d étoile', '%d étoiles', $star_count, 'ajinsafro-traveler-home' ), $star_count ) ); ?>">
                                <?php for ( $s = 0; $s < 5; $s++ ) : ?>
                                    <i class="<?php echo $s < $star_count ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="aj-card2__footer">
                            <div>
                                <?php if ( $price_label !== '' ) : ?>
                                <span class="aj-card2__price-label">À partir de</span>
                                <div class="aj-card2__price">
                                    <?php echo esc_html( $price_label ); ?>
                                    <span class="aj-card2__price-currency">DHS</span>
                                </div>
                                <span class="aj-card2__price-note">prix indicatif par nuit</span>
                                <?php endif; ?>
                            </div>
                            <span class="aj-card2__cta">Découvrir</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
