/**
 * Ajinsafro Package Builder JavaScript
 */

(function($) {
    'use strict';

    class AjinsafroPackageBuilder {
        constructor($container) {
            this.$container = $container;
            this.voyageId = $container.data('voyage-id');
            this.sessionId = $container.data('session-id');
            
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Day tab switching
            this.$container.on('click', '.aj-day-tab', (e) => {
                e.preventDefault();
                const dayNumber = $(e.currentTarget).data('day');
                this.switchDay(dayNumber);
            });

            // Book now button
            this.$container.on('click', '.aj-btn-book-now', (e) => {
                e.preventDefault();
                this.createCheckout();
            });

            // Item actions (stub for future)
            this.$container.on('click', '[data-action]', (e) => {
                e.preventDefault();
                const action = $(e.currentTarget).data('action');
                const itemId = $(e.currentTarget).data('item-id');
                this.performAction(action, itemId);
            });
        }

        switchDay(dayNumber) {
            // Update nav
            this.$container.find('.aj-day-tab').removeClass('active');
            this.$container.find(`.aj-day-tab[data-day="${dayNumber}"]`).addClass('active');

            // Update content
            this.$container.find('.aj-day-content').removeClass('active');
            this.$container.find(`.aj-day-content[data-day="${dayNumber}"]`).addClass('active');

            // Scroll to top
            this.$container.find('.aj-package-content')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        performAction(action, itemId) {
            // Placeholder for add/remove/modify actions
            console.log('Action:', action, 'Item ID:', itemId);

            $.ajax({
                url: ajinsafroData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aj_package_action',
                    nonce: ajinsafroData.nonce,
                    session_id: this.sessionId,
                    action_type: action,
                    action_data: {
                        item_id: itemId
                    }
                },
                beforeSend: () => {
                    this.showLoading();
                },
                success: (response) => {
                    if (response.success) {
                        this.updatePackageState(response.data);
                        this.showMessage('success', 'Package updated successfully');
                    } else {
                        this.showMessage('error', response.data.message || 'Action failed');
                    }
                },
                error: (xhr) => {
                    this.showMessage('error', 'Network error occurred');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        createCheckout() {
            if (!this.sessionId) {
                this.showMessage('error', 'Session not initialized');
                return;
            }

            const $button = this.$container.find('.aj-btn-book-now');
            const originalText = $button.text();

            $.ajax({
                url: ajinsafroData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aj_create_checkout',
                    nonce: ajinsafroData.nonce,
                    session_id: this.sessionId
                },
                beforeSend: () => {
                    $button.prop('disabled', true).text(ajinsafroData.strings.loading);
                },
                success: (response) => {
                    if (response.success && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        this.showMessage('error', response.data.message || 'Checkout creation failed');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: (xhr) => {
                    this.showMessage('error', 'Network error occurred');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }

        updatePackageState(data) {
            // Update pricing
            if (data.pricing) {
                const currency = data.pricing.currency || 'MAD';
                const symbol = this.getCurrencySymbol(currency);
                
                const perPerson = this.formatPrice(data.pricing.total_per_person, symbol);
                const totalGroup = this.formatPrice(data.pricing.total_group, symbol);

                this.$container.find('.aj-price-per-person .aj-price-amount').text(perPerson);
                this.$container.find('.aj-price-total .aj-price-amount').text(totalGroup);
            }

            // Update session ID if changed
            if (data.session && data.session.id) {
                this.sessionId = data.session.id;
                this.$container.data('session-id', this.sessionId);
            }

            // TODO: Update items if needed (full refresh for now)
        }

        formatPrice(cents, symbol) {
            const amount = (cents / 100).toFixed(2);
            return amount.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ' + symbol;
        }

        getCurrencySymbol(currency) {
            const symbols = {
                'MAD': 'DH',
                'EUR': '€',
                'USD': '$',
                'GBP': '£'
            };
            return symbols[currency.toUpperCase()] || currency;
        }

        showLoading() {
            if (this.$container.find('.aj-loading-overlay').length === 0) {
                this.$container.append('<div class="aj-loading-overlay" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;z-index:999;"><div class="aj-spinner" style="border:3px solid #f3f3f3;border-top:3px solid #667eea;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;"></div></div>');
            }
        }

        hideLoading() {
            this.$container.find('.aj-loading-overlay').remove();
        }

        showMessage(type, message) {
            const $message = $('<div class="aj-message"></div>')
                .addClass(type === 'success' ? 'aj-success' : 'aj-error')
                .text(message)
                .css({
                    padding: '1rem',
                    marginBottom: '1rem',
                    borderRadius: '6px',
                    background: type === 'success' ? '#d4edda' : '#f8d7da',
                    color: type === 'success' ? '#155724' : '#721c24',
                    border: type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb'
                });

            this.$container.find('.aj-package-content').prepend($message);

            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        $('.aj-package-builder').each(function() {
            new AjinsafroPackageBuilder($(this));
        });
    });

    // Add CSS animation for spinner
    const style = document.createElement('style');
    style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
    document.head.appendChild(style);

})(jQuery);
