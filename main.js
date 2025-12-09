(function($){
    'use strict';

    /**
     * Toggle hide/show for Package + Insurance fields
     */
    function togglePackageInsurance() {
        var paymentSelect = document.getElementById('mrf_payment');
        if (!paymentSelect) return;

        var value = paymentSelect.value || '';
        var selfVal = (mrfSettings && mrfSettings.selfPayValue) ? mrfSettings.selfPayValue : 'Self Pay';
        var isSelfPay = (value === selfVal);

        var packageEl = document.getElementById('mrf_field_package');
        var insuranceEl = document.getElementById('mrf_field_insurance');

        if (packageEl) {
            packageEl.style.display = isSelfPay ? 'none' : '';
            var pkgSelect = document.getElementById('mrf_package');
            if (pkgSelect) pkgSelect.required = !isSelfPay;
        }
        if (insuranceEl) {
            insuranceEl.style.display = isSelfPay ? 'none' : '';
            var insSelect = document.getElementById('mrf_insurance');
            if (insSelect) insSelect.required = !isSelfPay;
        }
    }

    /**
     * Glass Loader — fade in/out
     */
    function showLoader(show) {
        if (show) {
            $('#mrf-loader').fadeIn(150);
        } else {
            $('#mrf-loader').fadeOut(150);
        }
    }

    /**
     * Glass Modal with Icons
     */
    function showGlassModal(type, message) {
        const modal = $('#mrf-modal');
        const msgBox = $('#mrf-modal-message');
        const iconBox = $('#mrf-glass-icon');

        msgBox.html(message);

        iconBox.removeClass().html('');

        if (type === 'success') {
            iconBox.addClass('mrf-glass-success').html('&#10003;');
        } else if (type === 'error') {
            iconBox.addClass('mrf-glass-error').html('&#10006;');
        } else {
            iconBox.addClass('mrf-glass-info').html('&#8505;');
        }

        modal.removeClass('hidden');
    }

    function hideGlassModal() {
        $('#mrf-modal').addClass('hidden');
    }

/**
     * INIT Phone Number Masking
     */

// US Phone Mask (231) 231-2312
function mrfPhoneMask(value) {
    let cleaned = value.replace(/\D/g, "");

    let part1 = cleaned.substring(0, 3);
    let part2 = cleaned.substring(3, 6);
    let part3 = cleaned.substring(6, 10);

    if (cleaned.length > 6) {
        return `(${part1}) ${part2}-${part3}`;
    } else if (cleaned.length > 3) {
        return `(${part1}) ${part2}`;
    } else if (cleaned.length > 0) {
        return `(${part1}`;
    }

    return "";
}

jQuery(document).on("input", "#mrf_phone", function () {
    this.value = mrfPhoneMask(this.value);
});

    /**
     * INIT
     */
    $(document).ready(function(){

        togglePackageInsurance();
        $(document).on('change', '#mrf_payment', togglePackageInsurance);

        // Close modal
        $(document).on('click', '.mrf-glass-close, #mrf-glass-ok', function(e){
            e.preventDefault();
            hideGlassModal();
        });

        // Click outside closes modal
        $(document).on('click', function(e){
            if ($(e.target).is('#mrf-modal')) {
                hideGlassModal();
            }
        });

        /**
         * AJAX Form Submit
         */
        $(document).on('submit', '.mrf-form', function(e){
            e.preventDefault();

            var form = $(this);
            var data = form.serialize();

            showLoader(true);

            $.ajax({
                url: (mrfSettings && mrfSettings.ajaxUrl) ? mrfSettings.ajaxUrl : '/wp-admin/admin-ajax.php',
                method: 'POST',
                dataType: 'json',
                data: data,

                success: function(resp){
                    showLoader(false);

                    if (resp && resp.success) {
                        // SUCCESS
                        let msg = (resp.data && resp.data.message)
                                    ? resp.data.message
                                    : 'Submission successful.';

                        showGlassModal('success', msg);
                        form[0].reset();
                        togglePackageInsurance();
                    } 
                    else {
                        // ERROR RESPONSE
                        let message = 'Submission failed.';

                        if (resp?.data?.message) {
                            message = resp.data.message;
                        } 
                        else if (resp?.data?.errors) {
                            let html = '<ul style="margin:0;padding-left:18px;">';
                            $.each(resp.data.errors, function(k,v){ 
                                html += '<li>' + v + '</li>'; 
                            });
                            html += '</ul>';
                            message = html;
                        } 
                        else if (resp?.message) {
                            message = resp.message;
                        } 
                        else {
                            message = (mrfSettings?.i18n?.could_not_reach)
                                      ? mrfSettings.i18n.could_not_reach
                                      : 'Could not reach server. Please try again.';
                        }

                        showGlassModal('error', message);
                    }
                },

                error: function(){
                    showLoader(false);

                    var msg = (mrfSettings?.i18n?.could_not_reach)
                              ? mrfSettings.i18n.could_not_reach
                              : 'Could not reach server. Please try again.';

                    showGlassModal('error', msg);
                }
            });

        });

    });

})(jQuery);
