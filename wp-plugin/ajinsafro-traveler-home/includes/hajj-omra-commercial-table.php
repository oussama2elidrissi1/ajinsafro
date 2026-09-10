<?php
/** Shared, framework-independent renderer used by WordPress and Laravel preview. */
namespace Ajinsafro\HajjOmra;

final class CommercialTable
{
    public static function localized(array $row, string $field, string $locale): string
    {
        $fr = $row[$field.'_fr'] ?? $row[$field] ?? '';
        $ar = $row[$field.'_ar'] ?? '';
        return (string) ($locale === 'ar' ? ($ar ?: $fr) : ($fr ?: $ar));
    }

    public static function render(array $formulas, string $locale = 'fr', string $currency = 'DH'): string
    {
        $isAr = $locale === 'ar';
        $e = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $t = static fn ($fr, $ar) => $isAr ? $ar : $fr;
        $cities = ['madinah' => $t('Médine', 'الإقامة بالمدينة المنورة'), 'makkah' => $t('Makkah', 'الإقامة بمكة المكرمة'), 'other' => $t('Autres étapes', 'إقامات أخرى')];
        $meals = ['none' => $t('Sans repas', 'بدون وجبات'), 'breakfast' => $t('Petit-déjeuner', 'الإفطار'), 'half_board' => $t('Demi-pension', 'نصف إقامة'), 'full_board' => $t('Pension complète', 'إقامة كاملة')];
        ob_start();
        ?>
        <div class="ajho-formulas" lang="<?= $isAr ? 'ar' : 'fr' ?>" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
        <?php foreach ($formulas as $formula):
            $groups = [];
            foreach (($formula['accommodations'] ?? []) as $hotel) { $groups[$hotel['city'] ?? 'other'][] = $hotel; }
            $ordered = array_intersect_key($cities, $groups);
            foreach (array_diff_key($groups, $ordered) as $city => $unused) { $ordered[$city] = $city; }
            $prices = $formula['prices'] ?? [];
        ?>
            <section class="ajho-formula" data-formula-id="<?= $e($formula['id']) ?>">
                <div class="ajho-formula__heading">
                    <h3 dir="auto"><?= $e(self::localized($formula, 'name', $locale)) ?></h3>
                    <?php if (!empty($formula['departure_date'])): ?><span><?= $t('Départ', 'المغادرة') ?> <bdi dir="ltr"><?= $e($formula['departure_date']) ?></bdi></span><?php endif ?>
                    <?php if (self::localized($formula, 'description', $locale)): ?><p dir="auto"><?= nl2br($e(self::localized($formula, 'description', $locale))) ?></p><?php endif ?>
                </div>
                <table class="ajho-formula__table">
                    <caption><?= $e(self::localized($formula, 'name', $locale).' — '.$t('Hébergements et prix par personne', 'الإقامة والأسعار للفرد')) ?></caption>
                    <thead><tr>
                        <?php foreach ($ordered as $city => $label): ?><th scope="col"><?= $e($label) ?></th><?php endforeach ?>
                        <?php foreach ($prices as $price): ?><th scope="col"><?= $e(self::localized($price, 'room_type_label', $locale)) ?></th><?php endforeach ?>
                    </tr></thead>
                    <tbody><tr>
                        <?php foreach ($ordered as $city => $label): ?>
                            <td class="ajho-formula__stay"><span class="ajho-formula__mobile-label" aria-hidden="true"><?= $e($label) ?></span>
                                <?php foreach ($groups[$city] as $hotel): ?>
                                    <div class="ajho-formula__hotel" data-hotel-id="<?= $e($hotel['id']) ?>">
                                        <?php if (!empty($hotel['image_url']) && preg_match('~^https?://~i', $hotel['image_url'])): ?><img src="<?= $e($hotel['image_url']) ?>" alt="" loading="lazy"><?php endif ?>
                                        <strong dir="auto"><?= $e(self::localized($hotel, 'name', $locale)) ?></strong>
                                        <?php if (!empty($hotel['stars'])): ?><span class="ajho-formula__stars" aria-label="<?= $e($hotel['stars'].' '.$t('étoiles', 'نجوم')) ?>"><?= str_repeat('★', min(5, (int) $hotel['stars'])) ?></span><?php endif ?>
                                        <?php if (isset($hotel['nights'])): ?><span><bdi dir="ltr"><?= $e($hotel['nights']) ?></bdi> <?= $t('nuits', 'ليالٍ') ?></span><?php endif ?>
                                        <?php if (!empty($hotel['haram_distance'])): ?><span><bdi dir="auto"><?= $e($hotel['haram_distance']) ?></bdi> <?= $t('du Haram', 'من الحرم') ?></span><?php endif ?>
                                        <?php if (self::localized($hotel, 'location', $locale)): ?><span dir="auto"><?= $e(self::localized($hotel, 'location', $locale)) ?></span><?php endif ?>
                                        <?php if (!empty($hotel['meal_plan'])): ?><span><?= $e($meals[$hotel['meal_plan']] ?? $hotel['meal_plan']) ?></span><?php endif ?>
                                        <?php if (!empty($hotel['stay_start_date'])): ?><span class="ajho-formula__dates"><?= $t('Du', 'من') ?> <bdi dir="ltr"><?= $e($hotel['stay_start_date']) ?></bdi><?php if (!empty($hotel['stay_end_date'])): ?> <?= $t('au', 'إلى') ?> <bdi dir="ltr"><?= $e($hotel['stay_end_date']) ?></bdi><?php endif ?></span><?php endif ?>
                                        <?php if (self::localized($hotel, 'description', $locale)): ?><p dir="auto"><?= nl2br($e(self::localized($hotel, 'description', $locale))) ?></p><?php endif ?>
                                    </div>
                                <?php endforeach ?>
                            </td>
                        <?php endforeach ?>
                        <?php foreach ($prices as $price): ?>
                            <td class="ajho-formula__price" data-tariff-id="<?= $e($price['tariff_id']) ?>">
                                <span class="ajho-formula__mobile-label" aria-hidden="true"><?= $e(self::localized($price, 'room_type_label', $locale)) ?></span>
                                <div><?php if (isset($price['old_price']) && $price['old_price'] > $price['price']): ?><del><bdi dir="ltr"><?= $e(number_format($price['old_price'], 0, ',', ' ').' '.$currency) ?></bdi></del><?php endif ?>
                                <strong><bdi dir="ltr"><?= $e(number_format($price['price'], 0, ',', ' ').' '.$currency) ?></bdi></strong><small><?= $t('par personne', 'للفرد') ?></small></div>
                            </td>
                        <?php endforeach ?>
                    </tr></tbody>
                </table>
            </section>
        <?php endforeach ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
