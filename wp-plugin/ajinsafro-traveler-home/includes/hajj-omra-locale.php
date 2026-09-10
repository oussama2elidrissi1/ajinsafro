<?php
/** Locale is applied after reading the shared bilingual API cache. */
function ajth_ho_locale() {
    $requested = $_GET['lang'] ?? null;
    if (is_string($requested) && in_array($requested, array('fr', 'ar'), true)) { return $requested; }
    return function_exists('get_locale') && strpos(get_locale(), 'ar') === 0 ? 'ar' : 'fr';
}

function ajth_ho_translations() {
    return array(
        'Accueil' => 'الرئيسية', 'Hajj & Omra' => 'الحج والعمرة', 'Hajj & Omra avec Ajinsafro' => 'الحج والعمرة مع أجينسافرو',
        'Omra' => 'العمرة', 'Hajj' => 'الحج', 'Présentation' => 'نظرة عامة', 'Départs' => 'المواعيد', 'Tarifs' => 'الأسعار',
        'Programme' => 'البرنامج', 'Inclus / Exclus' => 'المشمول وغير المشمول', 'Conditions' => 'الشروط',
        'Formules & hébergements' => 'الباقات والإقامة', 'Formule' => 'الباقة', 'Choisir une formule' => 'اختر باقة',
        'Votre offre Hajj & Omra' => 'عرض الحج والعمرة', 'Départ' => 'المغادرة', 'Durée' => 'المدة', 'Places' => 'المقاعد',
        'restantes' => 'متبقية', 'Prix à partir de' => 'السعر ابتداءً من', 'À partir de' => 'ابتداءً من',
        'par personne' => 'للفرد', '/ pers.' => '/ للفرد', 'Meilleur prix' => 'أفضل سعر',
        'Prochain départ' => 'الموعد المقبل', 'Hôtel Makkah' => 'فندق مكة', 'Hôtel Madinah' => 'فندق المدينة',
        'Repas' => 'الوجبات', 'Selon offre' => 'حسب العرض', 'Demander une réservation' => 'طلب حجز',
        'Partager' => 'مشاركة', 'Fil d’Ariane' => 'مسار التصفح', 'Photos de l’offre' => 'صور العرض',
        'Agrandir la photo principale' => 'تكبير الصورة الرئيسية', 'Choisir une photo' => 'اختيار صورة', 'Afficher la photo' => 'عرض الصورة',
        'Sections de l’offre' => 'أقسام العرض', 'Hôtels' => 'الفنادق', 'Makkah' => 'مكة المكرمة', 'Madinah' => 'المدينة المنورة',
        'Distance Haram' => 'المسافة من الحرم', 'Services inclus' => 'الخدمات المشمولة',
        'Transport inclus' => 'النقل مشمول', 'Visa inclus' => 'التأشيرة مشمولة', 'Encadrement Ajinsafro' => 'تأطير أجينسافرو',
        'Services à confirmer avec votre conseiller.' => 'يؤكد مستشاركم الخدمات المتاحة.',
        'Ce que comprend l’offre' => 'ما يشمله العرض', 'Prestations sur demande.' => 'الخدمات عند الطلب.',
        'Dates disponibles' => 'المواعيد المتاحة', 'Choisir un départ pour la demande de réservation' => 'اختر موعداً لطلب الحجز',
        'Disponible' => 'متاح', 'Indisponible' => 'غير متاح', 'Complet' => 'مكتمل', 'Places limitees' => 'مقاعد محدودة', 'Offre expiree' => 'عرض منتهي',
        'Contactez-nous pour connaître les prochains départs disponibles.' => 'تواصلوا معنا لمعرفة المواعيد المقبلة المتاحة.',
        'Prix par chambre' => 'الأسعار حسب الغرفة', 'Chambre' => 'الغرفة', 'Dernières' => 'المقاعد الأخيرة', 'Stock' => 'المتاح', 'dispo.' => 'متاح',
        'Les tarifs par chambre sont disponibles sur demande.' => 'أسعار الغرف متاحة عند الطلب.',
        'Jour par jour' => 'يوماً بيوم', 'Jour' => 'اليوم', 'Étape' => 'مرحلة',
        'Le programme détaillé sera confirmé par votre conseiller.' => 'سيؤكد مستشاركم البرنامج التفصيلي.',
        'Prestations' => 'الخدمات', 'Inclus' => 'المشمول', 'Ce qui est inclus' => 'ما يشمله السعر', 'Prestations à confirmer.' => 'تُؤكد الخدمات لاحقاً.',
        'Exclusions' => 'غير المشمول', 'Ce qui n’est pas inclus' => 'ما لا يشمله السعر', 'Exclusions à confirmer.' => 'تُؤكد الاستثناءات لاحقاً.',
        'Documents et conditions' => 'الوثائق والشروط', 'Documents' => 'الوثائق', 'Documents nécessaires' => 'الوثائق المطلوبة',
        'La liste des documents vous sera communiquée par votre conseiller.' => 'سيوافيكم مستشاركم بقائمة الوثائق.',
        'Conditions de réservation' => 'شروط الحجز', 'Les conditions vous seront précisées avant confirmation.' => 'سنوضح لكم الشروط قبل التأكيد.',
        'Résumé et réservation' => 'الملخص والحجز', 'Résumé de l’offre' => 'ملخص العرض', 'Type' => 'النوع', 'Ville de départ' => 'مدينة المغادرة', 'Date' => 'التاريخ',
        'Places restantes' => 'المقاعد المتبقية', 'Réservation' => 'الحجز', 'Notre équipe vous accompagne à chaque étape.' => 'يرافقكم فريقنا في كل خطوة.',
        'Nom complet' => 'الاسم الكامل', 'Votre nom et prénom' => 'اسمكم الكامل', 'Téléphone' => 'الهاتف', 'Email' => 'البريد الإلكتروني',
        'Départ sélectionné' => 'الموعد المختار', 'Choisir un départ' => 'اختر موعداً', 'Type de chambre' => 'نوع الغرفة', 'Choisir une chambre' => 'اختر غرفة',
        'Adultes' => 'البالغون', 'Enfants' => 'الأطفال', 'Message' => 'الرسالة', 'Vos demandes, préférences, questions…' => 'طلباتكم وتفضيلاتكم وأسئلتكم…',
        'Estimation' => 'التكلفة التقديرية', 'Estimation indicative, confirmée par votre conseiller.' => 'تقدير أولي يؤكده مستشاركم.',
        'Tarif enfants à confirmer en complément. ' => 'يُؤكد سعر الأطفال بشكل إضافي. ', 'Envoyer la demande' => 'إرسال الطلب',
        'Vos coordonnées permettent à notre équipe de vous recontacter au sujet de cette demande.' => 'نستخدم بيانات الاتصال للتواصل معكم بشأن هذا الطلب.',
        'Une question sur cette offre ?' => 'لديكم سؤال عن هذا العرض؟', 'Nos conseillers vous accompagnent et répondent à vos questions sur WhatsApp.' => 'يرافقكم مستشارونا ويجيبون عن أسئلتكم عبر واتساب.',
        'Écrire sur WhatsApp' => 'تواصل عبر واتساب', 'Sur demande' => 'عند الطلب', 'À confirmer' => 'يُؤكد لاحقاً', 'A confirmer' => 'يُؤكد لاحقاً',
        'Date sur demande' => 'الموعد عند الطلب', 'Renseignez de 1 à 20 adultes et de 0 à 20 enfants.' => 'أدخل من 1 إلى 20 بالغاً ومن 0 إلى 20 طفلاً.',
        'Lien copié dans le presse-papiers.' => 'تم نسخ الرابط.', 'Lien copié' => 'تم نسخ الرابط', 'Copiez ce lien :' => 'انسخوا هذا الرابط:',
        'Votre session a expire. Merci de renvoyer votre demande.' => 'انتهت الجلسة. يرجى إعادة إرسال الطلب.',
        'Merci de renseigner votre nom, telephone et email.' => 'يرجى إدخال الاسم والهاتف والبريد الإلكتروني.',
        'Aucune formule disponible pour le moment.' => 'لا توجد باقة متاحة حالياً.',
        'Choisissez une formule et un tarif disponibles.' => 'اختر باقة وسعراً متاحين.',
        'Sans repas' => 'بدون وجبات', 'Petit-déjeuner' => 'الإفطار', 'Demi-pension' => 'نصف إقامة', 'Pension complète' => 'إقامة كاملة',
        'Petit dejeuner' => 'الإفطار', 'Pension complete' => 'إقامة كاملة', 'Publie' => 'منشور', 'Expire' => 'منتهي', 'Ramadan' => 'رمضان', 'Premium' => 'ممتاز', 'Low Cost' => 'اقتصادي',
        'Offre introuvable' => 'العرض غير موجود', 'Offre Hajj & Omra introuvable' => 'عرض الحج والعمرة غير موجود',
        'Cette offre n est plus disponible ou n a pas encore ete publiee.' => 'هذا العرض لم يعد متاحاً أو لم يُنشر بعد.', 'Retour au catalogue' => 'العودة إلى العروض',
        'Selection Ajinsafro' => 'اختيارات أجينسافرو',
        'Retrouvez nos offres Omra, Hajj, Ramadan, Low Cost et Premium avec un affichage clair, des prix dynamiques et des departs mis a jour depuis notre base.' => 'اكتشفوا عروض العمرة والحج ورمضان والباقات الاقتصادية والممتازة بأسعار ومواعيد محدّثة.',
        'offres dynamiques' => 'عروض محدّثة', 'Catalogue synchronise avec le back-office Ajinsafro.' => 'عروض أجينسافرو المحدّثة.',
        'Tous les types' => 'جميع الأنواع', 'Ville de depart' => 'مدينة المغادرة', 'Toutes les villes' => 'جميع المدن',
        'Budget max' => 'الميزانية القصوى', 'Date de depart' => 'تاريخ المغادرة', 'Filtrer' => 'تصفية', 'Reinitialiser' => 'إعادة الضبط',
        'offres visibles' => 'عروض متاحة', 'offres a la une' => 'عروض مميزة', 'villes de depart' => 'مدن المغادرة',
        'Catalogue officiel' => 'العروض الرسمية', 'Offres Hajj & Omra disponibles' => 'عروض الحج والعمرة المتاحة',
        'Des offres Ajinsafro pensees pour une lecture rapide: image, hotels, depart, places restantes, prix et acces direct a la reservation.' => 'تعرفوا على الفنادق والمواعيد والمقاعد والأسعار، واطلبوا حجزكم مباشرة.',
        'Aucune offre ne correspond a vos filtres' => 'لا توجد عروض تطابق اختياراتكم',
        'Essayez une autre ville de depart, un autre budget ou reinitialisez vos criteres.' => 'جرّبوا مدينة مغادرة أو ميزانية أخرى، أو أعيدوا ضبط الاختيارات.',
        'Reinitialiser les filtres' => 'إعادة ضبط التصفية', 'Duree' => 'المدة', 'Depart' => 'المغادرة', 'Prix a partir de' => 'السعر ابتداءً من',
        'Voir details' => 'عرض التفاصيل', 'Demander reservation' => 'طلب حجز', 'Offre' => 'العرض'
    );
}

function ajth_ho_t($text) {
    return ajth_ho_locale() === 'ar' ? (ajth_ho_translations()[$text] ?? $text) : $text;
}

function ajth_ho_localized(array $row, $field) {
    $fr = $row[$field.'_fr'] ?? $row[$field] ?? '';
    $ar = $row[$field.'_ar'] ?? '';
    return ajth_ho_locale() === 'ar' ? ($ar ?: $fr) : ($fr ?: $ar);
}

function ajth_ho_localize_package(array $package) {
    foreach (array('title', 'short_description', 'description', 'booking_conditions', 'required_documents', 'meta_title', 'meta_description') as $field) {
        $package[$field] = ajth_ho_localized($package, $field);
    }
    foreach (array('included_items', 'excluded_items') as $field) {
        $package[$field] = ajth_ho_localized($package, $field) ?: array();
    }
    foreach (array('program_days', 'hotels', 'room_prices') as $collection) {
        foreach (($package[$collection] ?? array()) as $i => $row) {
            foreach (array('title', 'name', 'description', 'location', 'room_type_label') as $field) {
                if (array_key_exists($field, $row) || array_key_exists($field.'_ar', $row)) $package[$collection][$i][$field] = ajth_ho_localized($row, $field);
            }
        }
    }
    foreach (array('makkah', 'madinah') as $city) {
        $names = array();
        foreach (($package['hotels'] ?? array()) as $hotel) if (($hotel['city'] ?? '') === $city && !empty($hotel['name'])) $names[] = $hotel['name'];
        if ($names) $package[$city.'_hotel'] = implode(' / ', $names);
    }
    if (ajth_ho_locale() === 'ar' && !empty($package['duration_days'])) {
        $package['duration_label'] = $package['duration_days'].' أيام'.(!empty($package['duration_nights']) ? ' / '.$package['duration_nights'].' ليالٍ' : '');
    }
    foreach (array('type_label', 'status_label', 'meal_plan_label') as $field) if (!empty($package[$field])) $package[$field] = ajth_ho_t($package[$field]);
    return $package;
}
