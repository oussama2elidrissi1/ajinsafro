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

    function initStepper() {
        var stepButtons = document.querySelectorAll(".ajtb-step-btn");
        var stepContents = document.querySelectorAll(".ajtb-step-content");
        var prevBtn = document.getElementById("ajtb-prev-btn");
        var nextBtn = document.getElementById("ajtb-next-btn");
        var currentStepText = document.getElementById("ajtb-current-step-text");
        var bottomNavigation = document.getElementById("ajtb-bottom-navigation");

        if (!stepButtons.length || !stepContents.length || !nextBtn) {
            return;
        }

        var currentStep = 1;
        var totalSteps = 4;

        function updateStep(step) {
            currentStep = step;

            stepButtons.forEach(function (btn) {
                var target = parseInt(btn.getAttribute("data-step-target") || "0", 10);
                var isActive = target === currentStep;
                btn.classList.toggle("is-active", isActive);
                btn.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            stepContents.forEach(function (content) {
                var target = parseInt(content.getAttribute("data-step") || "0", 10);
                content.classList.toggle("is-active", target === currentStep);
            });

            if (currentStepText) {
                currentStepText.textContent = currentStep;
            }

            if (prevBtn) {
                if (currentStep === 1) {
                    prevBtn.style.display = "none";
                    bottomNavigation.classList.add("is-first-step");
                } else {
                    prevBtn.style.display = "inline-flex";
                    bottomNavigation.classList.remove("is-first-step");
                }
            }

            if (nextBtn) {
                if (currentStep === totalSteps) {
                    nextBtn.textContent = "Confirmer la reservation →";
                } else {
                    nextBtn.textContent = "Suivant →";
                }
            }

            var stepper = document.querySelector(".ajtb-stepper");
            if (stepper) {
                window.scrollTo({
                    top: stepper.offsetTop - 20,
                    behavior: "smooth"
                });
            }
        }

        stepButtons.forEach(function (btn) {
            btn.addEventListener("click", function () {
                var target = parseInt(btn.getAttribute("data-step-target") || "0", 10);
                if (target > 0) {
                    updateStep(target);
                }
            });
        });

        if (prevBtn) {
            prevBtn.addEventListener("click", function () {
                if (currentStep > 1) {
                    updateStep(currentStep - 1);
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", function () {
                if (currentStep < totalSteps) {
                    updateStep(currentStep + 1);
                } else {
                    submitReservation();
                }
            });
        }

        updateStep(currentStep);
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

        function getRoomSupplementTotal() {
            var total = 0;
            document.querySelectorAll("[data-ajtb-room-id]").forEach(function (el) {
                var qty = parseInt(el.querySelector("[data-room-qty]") ? el.querySelector("[data-room-qty]").textContent : "0", 10);
                var supplement = parseFloat(el.getAttribute("data-room-supplement") || "0");
                var capacity = parseInt(el.getAttribute("data-room-capacity") || "1", 10);
                if (isFinite(qty) && isFinite(supplement) && isFinite(capacity)) {
                    total += qty * supplement * capacity;
                }
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
            var roomTotal = getRoomSupplementTotal();
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

        var roomContainer = document.getElementById("ajtb-v1-room-picker");
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
                var rooms = data.data && data.data.rooms ? data.data.rooms : [];
                var extras = data.data && data.data.extras ? data.data.extras : [];
                renderRooms(rooms, roomContainer);
                renderExtras(extras, extrasContainer);
                document.dispatchEvent(new CustomEvent("ajtb:v1:rooms-changed"));
                document.dispatchEvent(new CustomEvent("ajtb:v1:extras-changed"));
            })
            .catch(function () {
                if (roomContainer) {
                    roomContainer.innerHTML = "<p class='ajtb-muted'>Impossible de charger les chambres. Veuillez reessayer.</p>";
                }
            });
    }

    function renderRooms(rooms, container) {
        if (!container) { return; }
        if (!rooms || !rooms.length) {
            container.innerHTML = "<p class='ajtb-muted'>Aucune chambre disponible pour cette date.</p>";
            return;
        }

        var html = "";
        rooms.forEach(function (room) {
            var rid = room.id || 0;
            var type = room.room_type || "Chambre";
            var qty = room.quantity || 0;
            var cap = room.capacity_per_room || 1;
            var supplement = room.supplement || 0;
            var availableRooms = room.available_rooms || 0;

            html +=
                '<div class="ajtb-room-card" data-ajtb-room-id="' + rid + '" data-room-supplement="' + supplement + '" data-room-capacity="' + cap + '">' +
                '<div>' +
                '<div class="ajtb-room-title">' + escapeHtml(type) + '</div>' +
                '<div class="ajtb-room-meta">' +
                "Pour " + cap + " personne(s) · Stock disponible : " + availableRooms + " chambre(s)<br>" +
                (supplement > 0 ? "Supplement : +" + formatAmount(supplement) + " MAD/personne" : "Prix : Inclus") +
                "</div>" +
                (type.toLowerCase().indexOf("demi") !== -1 || type.toLowerCase().indexOf("half") !== -1
                    ? '<span class="ajtb-badge-orange">En attente de jumelage</span>'
                    : "") +
                "</div>" +
                '<div class="ajtb-qty">' +
                '<button type="button" data-ajtb-room-action="minus">-</button>' +
                '<span data-room-qty>0</span>' +
                '<button type="button" data-ajtb-room-action="plus">+</button>' +
                "</div>" +
                "</div>";
        });

        container.innerHTML = html;

        container.querySelectorAll("[data-ajtb-room-action]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var card = btn.closest("[data-ajtb-room-id]");
                var qtyEl = card.querySelector("[data-room-qty]");
                var action = btn.getAttribute("data-ajtb-room-action");
                var current = parseInt(qtyEl.textContent || "0", 10);
                var max = parseInt(card.querySelector(".ajtb-room-meta").textContent.match(/Stock disponible : (\d+)/) ? RegExp.$1 : "99", 10);

                if (action === "plus") {
                    if (current < max) { current += 1; }
                } else if (action === "minus") {
                    if (current > 0) { current -= 1; }
                }

                qtyEl.textContent = String(current);
                document.dispatchEvent(new CustomEvent("ajtb:v1:rooms-changed"));
            });
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
            var priceChild = extra.price_child || 0;

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

    function getRoomAllocation() {
        var allocation = [];
        document.querySelectorAll("[data-ajtb-room-id]").forEach(function (card) {
            var rid = card.getAttribute("data-ajtb-room-id");
            var qtyEl = card.querySelector("[data-room-qty]");
            var qty = qtyEl ? parseInt(qtyEl.textContent || "0", 10) : 0;
            if (qty > 0) {
                allocation.push({ room_id: parseInt(rid, 10), room_count: qty });
            }
        });
        return allocation;
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

    function submitReservation() {
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

        if (!first || !last || first.value.trim() === "" || last.value.trim() === "") {
            alert("Veuillez saisir le prenom et le nom du client.");
            return;
        }

        var passengers = collectPassengers();
        var allocation = getRoomAllocation();

        if (allocation.length === 0) {
            alert("Veuillez choisir au moins une chambre.");
            return;
        }

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
        formData.append("room_allocation_json", JSON.stringify(allocation));
        formData.append("extras_json", JSON.stringify(getSelectedExtras()));

        var confirmBtn = document.getElementById("ajtb-final-submit");
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = "Envoi en cours...";
        }

        fetch(window.ajtbData && window.ajtbData.ajaxUrl ? window.ajtbData.ajaxUrl : "/wp-admin/admin-ajax.php", {
            method: "POST",
            body: formData,
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = "Confirmer la reservation";
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
                    confirmBtn.textContent = "Confirmer la reservation";
                }
                alert("Une erreur reseau est survenue. Veuillez reessayer.");
            });
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
        initStepper();
        initGuestsPicker();
        initCompanions();
        initPriceCalculation();
        initCopyButtons();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
