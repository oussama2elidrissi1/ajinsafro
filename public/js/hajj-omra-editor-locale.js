(function () {
    'use strict';
    const editor = document.querySelector('[data-ho-editor]');
    if (!editor) return;
    const labels = {
        'Offre': 'العرض', 'Tarifs': 'الأسعار', 'Départs': 'المواعيد', 'Hébergement': 'الإقامة',
        'Programme': 'البرنامج', 'Prestations': 'الخدمات', 'Médias': 'الصور', 'Publication': 'النشر',
        'Type de chambre': 'نوع الغرفة', 'Prix': 'السعر', 'Ancien prix': 'السعر السابق', 'Capacité': 'السعة',
        'Places': 'المقاعد', 'Actif': 'مفعّل', 'Action': 'الإجراء', 'Retirer': 'إزالة',
        '+ Ajouter un tarif': '+ إضافة سعر', 'Tarifs & chambres': 'الأسعار والغرف',
        'Chambre quintuple': 'الخماسي', 'Chambre quadruple': 'الرباعي', 'Chambre triple': 'الثلاثي',
        'Chambre double': 'الثنائي', 'Chambre single': 'الفردي',
        'Liez ces tarifs aux formules ci-dessous. Le prix actif le plus bas devient le « à partir de ».': 'اربط هذه الأسعار بالباقات أدناه. يظهر أدنى سعر مفعّل كسعر ابتدائي.',
        'Ville': 'المدينة', 'Catégorie': 'الفئة', 'Distance du Haram': 'المسافة من الحرم',
        'Nombre de nuits': 'عدد الليالي', 'Pension': 'الوجبات', 'Photo de l’hôtel': 'صورة الفندق',
        "Photo de l'hôtel": 'صورة الفندق', '+ Ajouter une étape': '+ إضافة إقامة',
        'Makkah': 'مكة المكرمة', 'Madinah': 'المدينة المنورة', 'Autre ville': 'مدينة أخرى',
        '5 étoiles': '5 نجوم', '4 étoiles': '4 نجوم', '3 étoiles': '3 نجوم', '2 étoiles': '2 نجوم', '1 étoiles': 'نجمة واحدة',
        'Sans repas': 'بدون وجبات', 'Petit-déjeuner': 'الإفطار', 'Demi-pension': 'نصف إقامة', 'Pension complète': 'إقامة كاملة',
        'Annuler': 'إلغاء', 'Prévisualiser': 'معاينة', 'Enregistrer le brouillon': 'حفظ المسودة',
        'Enregistrer': 'حفظ', "Publier l'offre": 'نشر العرض', 'Statut': 'الحالة', 'Brouillon': 'مسودة',
        'Publié': 'منشور', 'Complet': 'مكتمل', 'Expiré': 'منتهي', 'Archivé': 'مؤرشف',
        'Date de départ': 'تاريخ المغادرة', 'Date de retour': 'تاريخ العودة', 'Ville de départ': 'مدينة المغادرة',
        'Places disponibles': 'المقاعد المتاحة', 'Places réservées': 'المقاعد المحجوزة', 'Prix à partir de': 'السعر ابتداءً من',
        '+ Ajouter un départ': '+ إضافة موعد', 'Notes internes': 'ملاحظات داخلية',
        'Destination': 'الوجهة', 'Type': 'النوع', 'Durée (jours)': 'المدة (أيام)', 'Durée (nuits)': 'المدة (ليالٍ)',
        'Informations principales': 'المعلومات الأساسية', "Type d'offre": 'نوع العرض', 'Nombre de jours': 'عدد الأيام',
        'Pilote la génération automatique du programme.': 'يحدد إنشاء أيام البرنامج تلقائياً.', 'Date principale de départ': 'تاريخ المغادرة الرئيسي',
        'Date principale de retour': 'تاريخ العودة الرئيسي', 'Prix principal': 'السعر الرئيسي', 'Ancien prix / valeur': 'السعر السابق',
        'Affiché barré sur le site public.': 'يظهر مشطوباً في الموقع العام.', 'Économie / remise': 'التوفير / الخصم',
        'Calculée automatiquement si laissée vide.': 'تُحسب تلقائياً إذا تُركت فارغة.', 'Description': 'الوصف',
        'Occupation': 'الإشغال', 'Pension par défaut': 'الوجبات الافتراضية', '+ Ajouter': '+ إضافة',
        'Programme du voyage': 'برنامج الرحلة', 'Générer les jours manquants': 'إنشاء الأيام الناقصة', '+ Ajouter un jour': '+ إضافة يوم',
        'Slug': 'رابط العرض', 'Référencement (SEO)': 'الظهور في محركات البحث', 'Récapitulatif avant publication': 'ملخص قبل النشر',
        'Laissez vide pour générer automatiquement depuis le titre français.': 'اتركه فارغاً لإنشاء الرابط تلقائياً.',
        'Publie': 'منشور', 'Expire': 'منتهي', 'Ramadan': 'رمضان', 'Premium': 'ممتاز', 'Low Cost': 'اقتصادي',
        'Petit dejeuner': 'الإفطار', 'Pension complete': 'إقامة كاملة', 'Autre': 'أخرى',
        'Devise': 'العملة', 'Prix adulte': 'سعر البالغ', 'Prix enfant': 'سعر الطفل', 'Prix bébé': 'سعر الرضيع',
        'Ordre d’affichage': 'ترتيب العرض', "Ordre d'affichage": 'ترتيب العرض',
        'Services inclus': 'الخدمات المشمولة', 'Services exclus': 'الخدمات غير المشمولة',
        'Inclus': 'المشمول', 'Exclus': 'غير المشمول', 'Ajouter': 'إضافة', 'Image principale': 'الصورة الرئيسية',
        'Galerie': 'معرض الصور', 'Choisir une image': 'اختيار صورة', 'Supprimer': 'حذف',
        'Traduction arabe non completee.': 'لم تُضف الترجمة العربية بعد.',
        'Un bloc par hébergement. Plusieurs hôtels par ville sont possibles. Un bloc laissé entièrement vide n’est pas enregistré.': 'إقامة لكل قسم. يمكن إضافة عدة فنادق في المدينة نفسها. لا يُحفظ القسم الفارغ.'
    };
    const originals = new WeakMap();
    function translate() {
        const lang = editor.classList.contains('lang-ar') ? 'ar' : 'fr';
        editor.dir = lang === 'ar' ? 'rtl' : 'ltr'; editor.lang = lang;
        editor.querySelectorAll('[data-ho-fr]').forEach(node => {
            const value = lang === 'ar' ? node.dataset.hoAr : node.dataset.hoFr;
            if (node.textContent !== value) node.textContent = value;
        });
        const walker = document.createTreeWalker(editor, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            if (node.parentElement.closest('script,style,textarea,[data-ho-fr],[data-lang-pane]')) continue;
            if (!originals.has(node)) originals.set(node, node.textContent);
            const original = originals.get(node), translated = labels[original.trim()];
            if (translated) node.textContent = lang === 'ar' ? original.replace(original.trim(), translated) : original;
        }
        editor.querySelectorAll('input[type=number],input[type=date],input[type=email],input[type=url],input[type=tel]').forEach(input => { input.dir = 'ltr'; });
        editor.querySelectorAll('[data-formula-preview]').forEach(link => {
            const url = new URL(link.href); url.searchParams.set('locale', lang); link.href = url.toString();
        });
    }
    editor.addEventListener('click', event => {
        if (!event.target.closest('button')) return;
        translate();
        if (event.target.closest('[data-lang-switch]')) editor.dispatchEvent(new CustomEvent('ho:language'));
    });
    translate();
}());
