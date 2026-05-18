(function () {
    "use strict";

    function escapeHtml(str) {
        if (!str) { return ""; }
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function formatAmount(value) {
        return Math.round(value).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    function initGuestsPicker() {
        var picker = document.getElementById("ajtb-v1-guests-picker");
        if (!picker) { return; }

        var trigger = document.getElementById("ajtb-v1-guest-trigger");
        var popover = document.getElementById("ajtb-v1-guest-popover");
        var applyBtn = document.getElementById("ajtb-v1-guest-apply");
        var summary = document.getElementById("ajtb-v1-guest-summary");
        var adultsValue = document.getElementById("ajtb-v1-guest-adults-value");
        var childrenValue = document.getElementById("ajtb-v1-guest-children-value");
        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");

        if (!trigger || !popover || !summary || !adultsValue || !childrenValue || !adultsInput || !childrenInput) {
            return;
        }

        var maxAdults = parseInt(picker.getAttribute("data-max-adults") || "20", 10);
        var maxChildren = parseInt(picker.getAttribute("data-max-children") || "8", 10);
        var maxTotal = parseInt(picker.getAttribute("data-max-total") || "28", 10);

        if (isNaN(maxAdults) || maxAdults < 1) { maxAdults = 20; }
        if (isNaN(maxChildren) || maxChildren < 0) { maxChildren = 8; }
        if (isNaN(maxTotal) || maxTotal < 1) { maxTotal = maxAdults + maxChildren; }

        var state = {
            adults: Math.max(1, parseInt(adultsInput.value || "2", 10) || 2),
            children: Math.max(0, parseInt(childrenInput.value || "0", 10) || 0),
        };

        function formatSummary() {
            var txt = state.adults + " " + (state.adults > 1 ? "adultes" : "adulte");
            if (state.children > 0) {
                txt += ", " + state.children + " " + (state.children > 1 ? "enfants" : "enfant");
            }
            return txt;
        }

        function render() {
            adultsValue.textContent = String(state.adults);
            childrenValue.textContent = String(state.children);
            adultsInput.value = String(state.adults);
            childrenInput.value = String(state.children);
            summary.textContent = formatSummary();
            document.dispatchEvent(
                new CustomEvent("ajtb:v1:travellers-changed", {
                    detail: { adults: state.adults, children: state.children },
                })
            );
        }

        function clampTotals() {
            if (state.adults > maxAdults) { state.adults = maxAdults; }
            if (state.children > maxChildren) { state.children = maxChildren; }
            if (state.adults < 1) { state.adults = 1; }
            if (state.children < 0) { state.children = 0; }
            while (state.adults + state.children > maxTotal) {
                if (state.children > 0) { state.children -= 1; }
                else if (state.adults > 1) { state.adults -= 1; }
                else { break; }
            }
        }

        function setOpen(open) {
            if (open) {
                popover.removeAttribute("hidden");
                trigger.setAttribute("aria-expanded", "true");
            } else {
                popover.setAttribute("hidden", "");
                trigger.setAttribute("aria-expanded", "false");
            }
        }

        picker.addEventListener("click", function (event) {
            var control = event.target.closest("[data-ajtb-guest-action]");
            if (!control) { return; }
            event.preventDefault();

            var action = control.getAttribute("data-ajtb-guest-action");
            var target = control.getAttribute("data-ajtb-guest-target");

            if (target === "adults") {
                if (action === "plus") { state.adults += 1; }
                else if (action === "minus") { state.adults -= 1; }
            } else if (target === "children") {
                if (action === "plus") { state.children += 1; }
                else if (action === "minus") { state.children -= 1; }
            }

            clampTotals();
            render();
        });

        trigger.addEventListener("click", function () {
            setOpen(popover.hasAttribute("hidden"));
        });

        if (applyBtn) {
            applyBtn.addEventListener("click", function () {
                setOpen(false);
            });
        }

        document.addEventListener("click", function (event) {
            if (!picker.contains(event.target)) {
                setOpen(false);
            }
        });

        clampTotals();
        render();
    }

    function initCompanions() {
        var container = document.getElementById("ajtb-recap-companions-list");
        var addAdultBtn = document.querySelector("[data-ajtb-recap-action='add-adult']");
        var addChildBtn = document.querySelector("[data-ajtb-recap-action='add-child']");
        if (!container) { return; }

        var counter = 0;

        function createRow(type) {
            counter += 1;
            var row = document.createElement("div");
            row.className = "ajtb-companion-row";
            row.setAttribute("data-companion-row", "");
            row.innerHTML =
                '<div class="ajtb-field">' +
                '<select data-companion-type>' +
                '<option value="adult"' + (type === "adult" ? " selected" : "") + '>Adulte</option>' +
                '<option value="child"' + (type === "child" ? " selected" : "") + '>Enfant</option>' +
                '</select>' +
                '</div>' +
                '<div class="ajtb-field">' +
                '<input type="text" data-companion-first placeholder="Prenom" />' +
                '</div>' +
                '<div class="ajtb-field">' +
                '<input type="text" data-companion-last placeholder="Nom" />' +
                '</div>' +
                '<button class="ajtb-remove" type="button" data-companion-remove>&times;</button>';

            row.querySelector("[data-companion-remove]").addEventListener("click", function () {
                row.remove();
                document.dispatchEvent(new CustomEvent("ajtb:v1:companions-changed"));
            });

            return row;
        }

        if (addAdultBtn) {
            addAdultBtn.addEventListener("click", function () {
                container.appendChild(createRow("adult"));
                document.dispatchEvent(new CustomEvent("ajtb:v1:companions-changed"));
            });
        }

        if (addChildBtn) {
            addChildBtn.addEventListener("click", function () {
                container.appendChild(createRow("child"));
                document.dispatchEvent(new CustomEvent("ajtb:v1:companions-changed"));
            });
        }
    }

    function initPriceCalculation() {
        var base = window.ajtbRecapBase || {};
        var baseAdultPrice = parseFloat(base.pricing && base.pricing.adult ? base.pricing.adult : 0);
        var baseChildPrice = parseFloat(base.pricing && base.pricing.child ? base.pricing.child : 0);
        var currency = base.pricing && base.pricing.currency ? base.pricing.currency : "MAD";
        var datePrices = base.datePrices || {};

        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
        var dateSelect = document.getElementById("ajtb-v1-search-date");
        var fromSelect = document.getElementById("ajtb-v1-search-from");

        var fields = {
            total: document.querySelector("[data-ajtb-recap-field='total']"),
            totalLine: document.querySelector("[data-ajtb-recap-field='totalLine']"),
            currency: document.querySelector("[data-ajtb-recap-field='currency']"),
            currencyLine: document.querySelector("[data-ajtb-recap-field='currencyLine']"),
            priceAdults: document.querySelector("[data-ajtb-recap-field='priceAdults']"),
            priceChildren: document.querySelector("[data-ajtb-recap-field='priceChildren']"),
            priceActivities: document.querySelector("[data-ajtb-recap-field='priceActivities']"),
            priceExtras: document.querySelector("[data-ajtb-recap-field='priceExtras']"),
            priceRoom: document.querySelector("[data-ajtb-recap-field='priceRoom']"),
            departure: document.querySelectorAll("[data-ajtb-recap-field='departure']"),
            date: document.querySelectorAll("[data-ajtb-recap-field='date']"),
            people: document.querySelectorAll("[data-ajtb-recap-field='people']"),
        };

        if (!fields.total) { return; }

        function getDateSpecificAdultPrice() {
            if (!dateSelect) { return null; }
            var selectedDate = dateSelect.value || "";
            if (!selectedDate || !datePrices || !datePrices[selectedDate]) { return null; }
            var info = datePrices[selectedDate];
            var selectedPlaceId = fromSelect ? parseInt(fromSelect.value || "0", 10) : 0;
            var datePlaceId = info && info.departure_place_id != null ? parseInt(info.departure_place_id, 10) : 0;
            if (isFinite(datePlaceId) && datePlaceId > 0 && isFinite(selectedPlaceId) && selectedPlaceId > 0 && datePlaceId !== selectedPlaceId) {
                return null;
            }
            if (info && info.specific_price != null) {
                var parsed = parseFloat(info.specific_price);
                if (isFinite(parsed) && parsed > 0) { return parsed; }
            }
            return null;
        }

        function getGuestsLabel(adults, children) {
            var text = adults + " " + (adults > 1 ? "adultes" : "adulte");
            if (children > 0) {
                text += ", " + children + " " + (children > 1 ? "enfants" : "enfant");
            }
            return text;
        }

        function getExtrasTotal() {
            var total = 0;
            document.querySelectorAll(".ajtb-option.is-selected").forEach(function (el) {
                var price = parseFloat(el.getAttribute("data-extra-price") || "0");
                if (isFinite(price)) { total += price; }
            });
            return total;
        }

        function recalculate() {
            var adults = adultsInput ? Math.max(1, parseInt(adultsInput.value || "2", 10) || 2) : 2;
            var children = childrenInput ? Math.max(0, parseInt(childrenInput.value || "0", 10) || 0) : 0;

            var adultUnit = baseAdultPrice;
            var dateAdult = getDateSpecificAdultPrice();
            if (dateAdult !== null) { adultUnit = dateAdult; }
            if (!isFinite(adultUnit) || adultUnit < 0) { adultUnit = 0; }

            var childUnit = baseChildPrice > 0 ? baseChildPrice : adultUnit;
            var extrasTotal = getExtrasTotal();
            var roomTotal = 0;
            var activitiesTotal = 0;

            var total = adults * adultUnit + children * childUnit + extrasTotal + roomTotal + activitiesTotal;
            if (total <= 0) { total = adultUnit; }

            var adultsTotal = adults * adultUnit;
            var childrenTotal = children * childUnit;

            if (fields.total) { fields.total.textContent = formatAmount(total); }
            if (fields.totalLine) { fields.totalLine.textContent = formatAmount(total); }
            if (fields.currency) { fields.currency.textContent = currency; }
            if (fields.currencyLine) { fields.currencyLine.textContent = currency; }
            if (fields.priceAdults) { fields.priceAdults.textContent = formatAmount(adultsTotal) + " " + currency; }
            if (fields.priceChildren) { fields.priceChildren.textContent = formatAmount(childrenTotal) + " " + currency; }
            if (fields.priceActivities) { fields.priceActivities.textContent = formatAmount(activitiesTotal) + " " + currency; }
            if (fields.priceExtras) { fields.priceExtras.textContent = formatAmount(extrasTotal) + " " + currency; }
            if (fields.priceRoom) { fields.priceRoom.textContent = formatAmount(roomTotal) + " " + currency; }

            var departureLabel = fromSelect && fromSelect.selectedIndex >= 0 ? (fromSelect.options[fromSelect.selectedIndex].getAttribute("data-place-name") || fromSelect.options[fromSelect.selectedIndex].textContent || "-") : "-";
            var dateLabel = dateSelect && dateSelect.selectedIndex >= 0 ? (dateSelect.options[dateSelect.selectedIndex].textContent || "-") : "-";
            var guestsLabel = getGuestsLabel(adults, children);

            fields.departure.forEach(function (el) { el.textContent = departureLabel; });
            fields.date.forEach(function (el) { el.textContent = dateLabel; });
            fields.people.forEach(function (el) { el.textContent = guestsLabel; });
        }

        if (dateSelect) {
            dateSelect.addEventListener("change", function () {
                recalculate();
                document.dispatchEvent(new CustomEvent("ajtb:v1:date-changed"));
                loadRoomsAndExtras();
            });
        }

        if (fromSelect) {
            fromSelect.addEventListener("change", recalculate);
        }

        document.addEventListener("ajtb:v1:travellers-changed", recalculate);
        document.addEventListener("ajtb:v1:rooms-changed", recalculate);
        document.addEventListener("ajtb:v1:extras-changed", recalculate);

        recalculate();
    }

    function loadRoomsAndExtras() {
        var base = window.ajtbRecapBase || {};
        var tourId = base.tourId || 0;
        var dateSelect = document.getElementById("ajtb-v1-search-date");
        var date = dateSelect ? dateSelect.value : "";
        if (!tourId || !date) { return; }

        var extrasContainer = document.getElementById("ajtb-v1-extras-picker");

        var formData = new FormData();
        formData.append("action", "ajtb_v1_get_rooms_extras");
        formData.append("nonce", window.ajtbData && window.ajtbData.reservationNonce ? window.ajtbData.reservationNonce : "");
        formData.append("tour_id", tourId);
        formData.append("departure_date", date);

        fetch(window.ajtbData && window.ajtbData.ajaxUrl ? window.ajtbData.ajaxUrl : "/wp-admin/admin-ajax.php", {
            method: "POST",
            body: formData,
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) { return; }
                var extras = data.data && data.data.extras ? data.data.extras : [];
                renderExtras(extras, extrasContainer);
                document.dispatchEvent(new CustomEvent("ajtb:v1:extras-changed"));
            })
            .catch(function () {
                if (extrasContainer) {
                    extrasContainer.innerHTML = "<p class='ajtb-muted'>Impossible de charger les extras. Veuillez reessayer.</p>";
                }
            });
    }

    function renderExtras(extras, container) {
        if (!container) { return; }
        if (!extras || !extras.length) {
            container.innerHTML = "<p class='ajtb-muted'>Aucun extra disponible pour ce voyage.</p>";
            return;
        }

        var html = "";
        extras.forEach(function (extra) {
            var eid = extra.id || 0;
            var name = extra.name || "Extra";
            var desc = extra.description || "";
            var priceAdult = extra.price_adult || 0;

            html +=
                '<div class="ajtb-option" data-ajtb-extra-id="' + eid + '" data-extra-price="' + priceAdult + '" title="' + escapeHtml(desc) + '">' +
                escapeHtml(name) + "<br><strong>+ " + formatAmount(priceAdult) + " MAD</strong>" +
                "</div>";
        });

        container.innerHTML = html;

        container.querySelectorAll(".ajtb-option").forEach(function (el) {
            el.addEventListener("click", function () {
                el.classList.toggle("is-selected");
                document.dispatchEvent(new CustomEvent("ajtb:v1:extras-changed"));
            });
        });
    }

    function collectPassengers() {
        var passengers = [];
        var first = document.getElementById("ajtb-client-first");
        var last = document.getElementById("ajtb-client-last");
        var phone = document.getElementById("ajtb-client-phone");
        var email = document.getElementById("ajtb-client-email");

        if (first && last && (first.value.trim() || last.value.trim())) {
            passengers.push({
                first_name: first.value.trim(),
                last_name: last.value.trim(),
                type: "adult",
                phone: phone ? phone.value.trim() : "",
                email: email ? email.value.trim() : "",
            });
        }

        document.querySelectorAll("[data-companion-row]").forEach(function (row) {
            var typeEl = row.querySelector("[data-companion-type]");
            var firstEl = row.querySelector("[data-companion-first]");
            var lastEl = row.querySelector("[data-companion-last]");
            if (!firstEl || !lastEl) { return; }
            var fn = firstEl.value.trim();
            var ln = lastEl.value.trim();
            if (fn || ln) {
                passengers.push({
                    first_name: fn,
                    last_name: ln,
                    type: typeEl ? typeEl.value : "adult",
                });
            }
        });

        return passengers;
    }

    function getSelectedExtras() {
        var extras = [];
        document.querySelectorAll(".ajtb-option.is-selected").forEach(function (el) {
            var eid = el.getAttribute("data-ajtb-extra-id");
            var price = parseFloat(el.getAttribute("data-extra-price") || "0");
            var name = el.textContent.replace(/\n/g, " ").trim();
            extras.push({ id: parseInt(eid, 10), name: name, price: price });
        });
        return extras;
    }

    function validateForm() {
        var first = document.getElementById("ajtb-client-first");
        var last = document.getElementById("ajtb-client-last");
        var phone = document.getElementById("ajtb-client-phone");

        if (!first || !last || first.value.trim() === "" || last.value.trim() === "") {
            alert("Veuillez saisir le prenom et le nom du client.");
            return false;
        }
        if (phone && phone.value.trim() === "") {
            alert("Veuillez saisir le telephone du client.");
            return false;
        }
        return true;
    }

    function showConfirmModal() {
        if (!validateForm()) { return; }

        var dateSelect = document.getElementById("ajtb-v1-search-date");
        var fromSelect = document.getElementById("ajtb-v1-search-from");
        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
        var totalEl = document.querySelector("[data-ajtb-recap-field='total']");

        var dateLabel = dateSelect && dateSelect.selectedIndex >= 0 ? dateSelect.options[dateSelect.selectedIndex].textContent : "-";
        var adults = adultsInput ? Math.max(1, parseInt(adultsInput.value || "2", 10) || 2) : 2;
        var children = childrenInput ? Math.max(0, parseInt(childrenInput.value || "0", 10) || 0) : 0;
        var peopleTxt = adults + " " + (adults > 1 ? "adultes" : "adulte");
        if (children > 0) peopleTxt += ", " + children + " " + (children > 1 ? "enfants" : "enfant");
        var totalTxt = totalEl ? totalEl.textContent + " MAD" : "-";

        var confirmDate = document.getElementById("ajtb-confirm-date");
        var confirmPeople = document.getElementById("ajtb-confirm-people");
        var confirmTotal = document.getElementById("ajtb-confirm-total");
        if (confirmDate) confirmDate.textContent = dateLabel;
        if (confirmPeople) confirmPeople.textContent = peopleTxt;
        if (confirmTotal) confirmTotal.textContent = totalTxt;

        var modalEl = document.getElementById("ajtb-confirm-modal");
        if (modalEl && typeof bootstrap !== "undefined") {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            if (window.confirm("Confirmer cette demande de reservation ?")) {
                doSubmit();
            }
        }
    }

    function doSubmit() {
        var base = window.ajtbRecapBase || {};
        var tourId = base.tourId || 0;
        var dateSelect = document.getElementById("ajtb-v1-search-date");
        var fromSelect = document.getElementById("ajtb-v1-search-from");
        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
        var first = document.getElementById("ajtb-client-first");
        var last = document.getElementById("ajtb-client-last");
        var phone = document.getElementById("ajtb-client-phone");
        var email = document.getElementById("ajtb-client-email");
        var specialReq = document.getElementById("ajtb-special-request");

        if (!first || !last || first.value.trim() === "" || last.value.trim() === "") {
            alert("Veuillez saisir le prenom et le nom du client.");
            return;
        }

        var passengers = collectPassengers();

        var formData = new FormData();
        formData.append("action", "ajtb_v1_create_reservation");
        formData.append("nonce", window.ajtbData && window.ajtbData.reservationNonce ? window.ajtbData.reservationNonce : "");
        formData.append("tour_id", tourId);
        formData.append("departure_place_id", fromSelect ? parseInt(fromSelect.value || "0", 10) : 0);
        formData.append("departure_date", dateSelect ? dateSelect.value : "");
        formData.append("adults", adultsInput ? parseInt(adultsInput.value || "2", 10) : 2);
        formData.append("children", childrenInput ? parseInt(childrenInput.value || "0", 10) : 0);
        formData.append("client_first_name", first.value.trim());
        formData.append("client_last_name", last.value.trim());
        formData.append("client_phone", phone ? phone.value.trim() : "");
        formData.append("client_email", email ? email.value.trim() : "");
        formData.append("passengers", JSON.stringify(passengers));
        formData.append("room_allocation_json", JSON.stringify([]));
        formData.append("extras_json", JSON.stringify(getSelectedExtras()));
        if (specialReq) {
            formData.append("special_request", specialReq.value.trim());
        }

        var confirmBtn = document.getElementById("ajtb-final-submit");
        var mobileBtn = document.getElementById("ajtb-mobile-submit");
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = "Envoi en cours...";
        }
        if (mobileBtn) {
            mobileBtn.disabled = true;
            mobileBtn.textContent = "Envoi...";
        }

        fetch(window.ajtbData && window.ajtbData.ajaxUrl ? window.ajtbData.ajaxUrl : "/wp-admin/admin-ajax.php", {
            method: "POST",
            body: formData,
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = "Confirmer ma reservation";
                }
                if (mobileBtn) {
                    mobileBtn.disabled = false;
                    mobileBtn.textContent = "Confirmer";
                }

                if (!data.success) {
                    alert(data.data && data.data.message ? data.data.message : "Une erreur est survenue.");
                    return;
                }

                var modalEl = document.getElementById("ajtb-account-modal");
                var loginEl = document.getElementById("ajtb-account-login");
                var passEl = document.getElementById("ajtb-account-password");

                if (loginEl) { loginEl.textContent = data.data.login || ""; }
                if (passEl) { passEl.textContent = data.data.password || ""; }

                if (modalEl && typeof bootstrap !== "undefined") {
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    alert("Reservation creee avec succes. ID : " + (data.data.reservation_id || ""));
                }
            })
            .catch(function () {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = "Confirmer ma reservation";
                }
                if (mobileBtn) {
                    mobileBtn.disabled = false;
                    mobileBtn.textContent = "Confirmer";
                }
                alert("Une erreur reseau est survenue. Veuillez reessayer.");
            });
    }

    function initMobileBar() {
        var bar = document.getElementById("ajtb-mobile-bar");
        if (!bar) { return; }

        function updateVisibility() {
            if (window.innerWidth <= 1100) {
                bar.removeAttribute("hidden");
            } else {
                bar.setAttribute("hidden", "");
            }
        }

        updateVisibility();
        window.addEventListener("resize", updateVisibility);
    }

    function initConfirmButtons() {
        document.querySelectorAll("[data-ajtb-recap-action='final-submit']").forEach(function (btn) {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                showConfirmModal();
            });
        });

        var confirmOk = document.getElementById("ajtb-confirm-ok");
        if (confirmOk) {
            confirmOk.addEventListener("click", function () {
                var modalEl = document.getElementById("ajtb-confirm-modal");
                if (modalEl && typeof bootstrap !== "undefined") {
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                doSubmit();
            });
        }
    }

    function initCopyButtons() {
        document.querySelectorAll("[data-ajtb-copy]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var target = document.querySelector(btn.getAttribute("data-ajtb-copy"));
                if (!target) { return; }
                var text = target.textContent || "";
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        btn.textContent = "Copie !";
                        setTimeout(function () { btn.textContent = "Copier"; }, 1500);
                    });
                } else {
                    var ta = document.createElement("textarea");
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand("copy");
                    document.body.removeChild(ta);
                    btn.textContent = "Copie !";
                    setTimeout(function () { btn.textContent = "Copier"; }, 1500);
                }
            });
        });
    }

    function init() {
        initGuestsPicker();
        initCompanions();
        initPriceCalculation();
        initMobileBar();
        initConfirmButtons();
        initCopyButtons();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
