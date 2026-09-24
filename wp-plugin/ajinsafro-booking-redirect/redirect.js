jQuery(function ($) {

  function getVal(selector) {
    const el = $(selector);
    if (!el.length) return null;
    return el.val();
  }

  // Intercepter le clic sur le bouton BOOK NOW (TravelerWP)
  $(document).on('click', '.btn-book-ajax, .btn-book-now, button.btn-book-now, a.btn-book-now', function (e) {

    // si le bouton fait un submit/form ajax, on bloque
    e.preventDefault();
    e.stopPropagation();

    // Récupérer slug depuis l’URL
    const slug = window.location.pathname.split('/').filter(Boolean).pop();

    // Récupérer inputs usuels de TravelerWP (selectors peuvent varier)
    const date =
      getVal('input[name="start"]') ||
      getVal('input[name="check_in"]') ||
      getVal('input[name="date"]');

    const adults =
      getVal('input[name="adult_number"]') ||
      getVal('input[name="adults"]');

    const children =
      getVal('input[name="child_number"]') ||
      getVal('input[name="children"]');

    const infant =
      getVal('input[name="infant_number"]') ||
      getVal('input[name="infants"]');

    // Déduire type depuis post_type dans URL (ou body class)
    let type = 'tour';
    if ($('body').hasClass('single-st_hotel')) type = 'hotel';
    if ($('body').hasClass('single-st_tours')) type = 'tour';

    // Construire URL
    const params = new URLSearchParams();
    params.set('type', type);
    params.set('slug', slug);
    if (date) params.set('date', date);
    if (adults) params.set('adults', adults);
    if (children) params.set('children', children);
    if (infant) params.set('infant', infant);

    window.location.href = AJIN_BOOKING.base + '?' + params.toString();
  });

});
