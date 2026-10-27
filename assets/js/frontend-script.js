/**
 * Awesome Car Rental - Complete Unified Frontend Engine (EXTENDED)
 * Version: 2.5.0
 * Features: Advanced Validation, Date Constraints, Live Multi-Filters, 
 * AJAX Auth, Touch Gallery, Currency Logic (£), and Receipt Generation.
 */
(function($) {
    'use strict';

    $(function() {
        // --- 1. GLOBAL SELECTORS & STATE ---
        const $window      = $(window);
        const $doc         = $(document);
        const $container   = $('.acrb-main-container');
        const $hero        = $('#acrb-hero-view');
        const $searchInput = $('#acrb-car-search');
        const $noResults   = $('#acrb-no-results');
        const $pDate       = $('#acrb_pdate_in');
        const $rDate       = $('#acrb_rdate_in');
        
        // Configuration
        const dayRate      = parseFloat($container.data('day-rate')) || 0;
        const currency     = '£';
        const transition   = 250; // ms

        // --- 2. ADVANCED DATE & PRICING ENGINE ---
        function updatePricingUI() {
            if (!$pDate.val() || !$rDate.val()) return;

            // Fix for Safari Date parsing (ISO to Slash)
            const pickup = new Date($pDate.val().replace(/-/g, '/'));
            const returnD = new Date($rDate.val().replace(/-/g, '/'));
            
            const timeDiff = returnD.getTime() - pickup.getTime();
            const dayCount = Math.ceil(timeDiff / (1000 * 3600 * 24));

            const $summaryTotal = $('#acrb_sum_total');
            const $hiddenTotal  = $('#acrb_hidden_total');
            const $dayBadge     = $('.acrb-day-count-badge');

            if (dayCount > 0) {
                const total = (dayCount * dayRate).toFixed(2);
                
                // Animate value change
                $summaryTotal.prop('Counter', parseFloat($summaryTotal.text() || 0)).animate({
                    Counter: total
                }, {
                    duration: 500,
                    step: function (now) {
                        $(this).text(now.toFixed(2));
                    }
                });

                $hiddenTotal.val(total);
                if ($dayBadge.length) $dayBadge.text(dayCount + ' Days').fadeIn();
                $('#acrb-submit-booking').prop('disabled', false).css('opacity', '1');
            } else {
                $summaryTotal.text("0.00");
                $hiddenTotal.val("0.00");
                if ($dayBadge.length) $dayBadge.hide();
                // Disable booking if dates are invalid
                $('#acrb-submit-booking').prop('disabled', true).css('opacity', '0.5');
            }
        }

        if ($pDate.length && $rDate.length) {
            // Set minimum pickup date to today
            const today = new Date().toISOString().split('T')[0];
            $pDate.attr('min', today);

            $pDate.on('change', function() {
                const minReturn = $(this).val();
                $rDate.attr('min', minReturn);
                if ($rDate.val() && $rDate.val() < minReturn) {
                    $rDate.val(minReturn);
                }
                updatePricingUI();
            });

            $rDate.on('change', updatePricingUI);
        }

        // --- 3. THE HIGH-PERFORMANCE FILTERING SYSTEM ---
        /**
         * Uses a small delay (debounce) to prevent UI lag while typing
         */
        let filterTimeout;
        function debouncedFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(runUnifiedFilter, 150);
        }

        function runUnifiedFilter() {
            const query = $searchInput.val() ? $searchInput.val().toLowerCase().trim() : '';
            const activeCats = $('.acrb-cat-check:checked').map(function() { return $(this).val(); }).get();
            const activeSpecs = $('.acrb-spec-check:checked').map(function() { return $(this).val(); }).get();
            
            let visibleCount = 0;
            const $allCards = $('.acrb-card');

            $allCards.each(function() {
                const $card = $(this);
                const name  = ($card.data('name') || '').toString().toLowerCase();
                const specs = ($card.data('specs') || '').toString().toLowerCase();
                
                const matchSearch = query === '' || name.includes(query) || specs.includes(query);
                const matchCat    = activeCats.length === 0 || activeCats.some(cat => $card.hasClass(cat));
                const matchSpec   = activeSpecs.length === 0 || activeSpecs.every(s => specs.includes(s.toLowerCase()));

                if (matchSearch && matchCat && matchSpec) {
                    $card.removeClass('acrb-hidden').stop().fadeIn(transition);
                    visibleCount++;
                } else {
                    $card.addClass('acrb-hidden').stop().hide();
                }
            });

            if ($noResults.length) {
                visibleCount === 0 ? $noResults.fadeIn(transition) : $noResults.hide();
            }
        }

        $searchInput.on('keyup input', debouncedFilter);
        $doc.on('change', '.acrb-cat-check, .acrb-spec-check', runUnifiedFilter);

        // --- 4. INTERACTIVE GALLERY & MODALS ---
        // Smooth Image Switcher with Loader
        $doc.on('click', '.acrb-t-node', function(e) {
            e.preventDefault();
            const $node = $(this);
            const fullImg = $node.data('full');

            if (!fullImg || $hero.attr('src') === fullImg) return;

            $('.acrb-t-node').removeClass('active');
            $node.addClass('active');

            $hero.stop().animate({ opacity: 0.3 }, 150, function() {
                const tempImg = new Image();
                tempImg.src = fullImg;
                tempImg.onload = function() {
                    $hero.attr('src', fullImg).animate({ opacity: 1 }, 150);
                };
            });
        });

        // Tab System with URL Hash Support
        $doc.on('click', '.acrb-tab-trigger', function(e) {
            e.preventDefault();
            const target = $(this).data('tab');
            if (!target) return;

            window.location.hash = target;
            switchTab(target);
        });

        function switchTab(id) {
            $('.acrb-tab-panel').removeClass('active').hide();
            $('#' + id).addClass('active').fadeIn(transition);
            
            $('.acrb-acc-nav li').removeClass('active');
            $(`[data-tab="${id}"]`).parent().addClass('active');
        }

        // Initialize Tab from URL
        if (window.location.hash) {
            const hash = window.location.hash.substring(1);
            if ($('#' + hash).length) switchTab(hash);
        }

        // Modal Management
        $doc.on('click', '.open-receipt', function() {
            const data = $(this).data();
            $('#acrb-m-car').text(data.car);
            $('#acrb-m-date').text(data.date);
            $('#acrb-m-total').text(currency + (parseFloat(data.total).toFixed(2)));
            $('#acrb-m-id').text('REFERENCE: #' + data.id);
            
            $('.acrb-modal-overlay').fadeIn(transition);
            $('#acrb-receipt-modal').addClass('is-visible').fadeIn(transition);
        });

        $doc.on('click', '.acrb-modal-close, .acrb-modal-overlay', function() {
            $('.acrb-modal, .acrb-modal-overlay').fadeOut(transition).removeClass('is-visible');
        });

        // --- 5. SECURE AJAX AUTHENTICATION ---
        /**
         * Form Validation + AJAX Submission
         */
        function validateAndSubmitAuth(e, action, btnId, statusId) {
            e.preventDefault();
            const $form = $(e.target);
            const $btn  = $(btnId);
            const $msg  = $(statusId);
            const originalText = $btn.html();

            // Basic Client-Side Validation
            let valid = true;
            $form.find('input[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('acrb-input-error');
                    valid = false;
                } else {
                    $(this).removeClass('acrb-input-error');
                }
            });

            if (!valid) {
                showStatus($msg, 'Please fill in all required fields.', 'error');
                return;
            }

            // Prep Data
            $btn.prop('disabled', true).addClass('is-loading').html('<span class="acrb-loader"></span> Verifying...');
            $msg.fadeOut();

            const formData = new FormData(e.target);
            formData.append('action', action);

            fetch(acrb_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showStatus($msg, res.data || 'Success! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = acrb_vars.home_url + '/acrb-account/';
                    }, 1500);
                } else {
                    throw new Error(res.data || 'Authentication failed');
                }
            })
            .catch(err => {
                $btn.prop('disabled', false).removeClass('is-loading').html(originalText);
                showStatus($msg, '✕ ' + err.message, 'error');
            });
        }

        function showStatus($el, text, type) {
            $el.stop().hide().removeClass('success error')
               .addClass(type).text(text).fadeIn(transition);
        }

        $('#acrb-register-form').on('submit', (e) => validateAndSubmitAuth(e, 'acrb_ajax_register', '#acrb-reg-submit', '#acrb-reg-msg'));
        $('#acrb-login-form').on('submit', (e) => validateAndSubmitAuth(e, 'acrb_ajax_login', '#acrb-login-submit', '#acrb-login-status'));

        // --- 6. PRINT & EXPORT ---
        $doc.on('click', '.js-acrb-print', function(e) {
            e.preventDefault();
            window.print();
        });

    });
})(jQuery);