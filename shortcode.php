<?php
/**
 * Shortcode renders the form and enqueues assets.
 */

if (! defined('ABSPATH')) exit;

add_shortcode('meal_request_form','mrf_render_form');

/**
 * Render the form (shortcode). Enqueue front-end assets for this plugin.
 *
 * @param array $atts
 * @return string HTML
 */
function mrf_render_form($atts = []) {
    // Enqueue assets
    wp_register_style('mrf-style', MRF_PLUGIN_URL . 'assets/css/style.css', [], MRF_VERSION);
    wp_enqueue_style('mrf-style');

    wp_register_script('mrf-main', MRF_PLUGIN_URL . 'assets/js/main.js', ['jquery'], MRF_VERSION, true);
    wp_enqueue_script('mrf-main');

    // Localize script with selectors / strings
    wp_localize_script('mrf-main', 'mrfSettings', [
    'selfPayValue' => 'Self Pay',
    'ajaxUrl' => admin_url('admin-ajax.php')
]);

    // Prepare fields & read transient if present
    $fields = [
        'name','phone','email','payment_method','package',
        'street1','street2','city','state','zip','insurance','message','came_from'
    ];

    $key = isset($_GET['mrf_key']) ? sanitize_text_field(wp_unslash($_GET['mrf_key'])) : '';
    $result = null;
    if ($key) {
        $result = get_transient('mrf_result_' . $key);
        delete_transient('mrf_result_' . $key);
    }
    $old = $result['old'] ?? array_fill_keys($fields, '');

    // Output is similar to previous version; keep structural IDs for JS
    ob_start();
    ?>
    <div class="mrf-wrap">
      <div class="mrf-card">
        <div class="mrf-head">
          <h2 class="mrf-title"><?php esc_html_e('Meal Plan Registration', 'meal-request-form'); ?></h2>
          <div class="mrf-sub"><?php esc_html_e('Complete the form below to get started.', 'meal-request-form'); ?></div>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate class="mrf-form">
            <?php wp_nonce_field('mrf_action','mrf_nonce'); ?>
            <input type="hidden" name="action" value="meal_request_form">
            <input type="hidden" name="mrf_ref" value="<?php echo esc_attr( wp_get_referer() ? wp_get_referer() : home_url() ); ?>">

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_name"><?php esc_html_e('Full Name', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_name" name="name" class="mrf-input" value="<?php echo esc_attr($old['name'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['name'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['name']); ?></div><?php endif; ?>
                </div>
                <div>
                    <label class="mrf-label" for="mrf_phone"><?php esc_html_e('Phone', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_phone" name="phone" class="mrf-input" value="<?php echo esc_attr($old['phone'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['phone'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['phone']); ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_email"><?php esc_html_e('Email', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_email" name="email" class="mrf-input" value="<?php echo esc_attr($old['email'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['email'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['email']); ?></div><?php endif; ?>
                </div>

                <div>
                    <label class="mrf-label" for="mrf_payment"><?php esc_html_e('Payment Method', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <select id="mrf_payment" name="payment_method" class="mrf-input">
                        <option value=""><?php esc_html_e('Select payment method', 'meal-request-form'); ?></option>
                        <option value="Insurance" <?php selected($old['payment_method'] ?? '','Insurance'); ?>><?php esc_html_e('Insurance','meal-request-form'); ?></option>
                        <option value="Self Pay" <?php selected($old['payment_method'] ?? '','Self Pay'); ?>><?php esc_html_e('Self Pay','meal-request-form'); ?></option>
                    </select>
                    <?php if (!empty($result['errors']['payment_method'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['payment_method']); ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mrf-grid">
                <div id="mrf_field_package">
                    <!-- <span class="mrf-required">*</span> -->
                    <label class="mrf-label" for="mrf_package"><?php esc_html_e('Choose Package', 'meal-request-form'); ?> </label>
                    <select id="mrf_package" name="package" class="mrf-input">
                        <option value=""><?php esc_html_e('Choose Package','meal-request-form'); ?></option>
                        <option value="7 meals" <?php selected($old['package'] ?? '','7 meals'); ?>>7 Meals</option>
                        <option value="14 meals" <?php selected($old['package'] ?? '','14 meals'); ?>>14 Meals</option>
                    </select>
                    <?php if (!empty($result['errors']['package'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['package']); ?></div><?php endif; ?>
                </div>

                <div id="mrf_field_insurance">
                    <label class="mrf-label" for="mrf_insurance"><?php esc_html_e('Insurance Provider', 'meal-request-form'); ?></label>
                    <select id="mrf_insurance" name="insurance" class="mrf-input">
                        <option value=""><?php esc_html_e('Select Insurance Provider','meal-request-form'); ?></option>
                        <option value="community" <?php selected($old['insurance'] ?? '','community'); ?>>Keystone First Community Health Choice</option>
                        <option value="vip" <?php selected($old['insurance'] ?? '','vip'); ?>>Keystone First VIP Choice</option>
                        <option value="pa health" <?php selected($old['insurance'] ?? '','pa health'); ?>>PA Health & Wellness</option>
                    </select>
                    <?php if (!empty($result['errors']['insurance'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['insurance']); ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_street1"><?php esc_html_e('Street 1', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_street1" name="street1" class="mrf-input" value="<?php echo esc_attr($old['street1'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['street1'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['street1']); ?></div><?php endif; ?>
                </div>
                <div>
                    <label class="mrf-label" for="mrf_street2"><?php esc_html_e('Street 2', 'meal-request-form'); ?></label>
                    <input id="mrf_street2" name="street2" class="mrf-input" value="<?php echo esc_attr($old['street2'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['street2'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['street2']); ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_city"><?php esc_html_e('City', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_city" name="city" class="mrf-input" value="<?php echo esc_attr($old['city'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['city'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['city']); ?></div><?php endif; ?>
                </div>
                <div>
                    <label class="mrf-label" for="mrf_state"><?php esc_html_e('State', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_state" name="state" class="mrf-input" value="<?php echo esc_attr($old['state'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['state'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['state']); ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_zip"><?php esc_html_e('Zip Code', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_zip" name="zip" class="mrf-input" pattern="[0-9]{5}" value="<?php echo esc_attr($old['zip'] ?? ''); ?>" />
                    <?php if (!empty($result['errors']['zip'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['zip']); ?></div><?php endif; ?>
                </div>
                 <div>
                    <label class="mrf-label" for="mrf_message"><?php esc_html_e('Message', 'meal-request-form'); ?></label>
                    <textarea id="mrf_message" name="message" class="mrf-text"><?php echo esc_textarea($old['message'] ?? ''); ?></textarea>
                    <?php if (!empty($result['errors']['message'])): ?><div class="mrf-error"><?php echo esc_html($result['errors']['message']); ?></div><?php endif; ?>
                </div>
            </div>

            <!-- <div class="mrf-grid single" style="margin-top:8px;">
              
            </div> -->

            <input type="hidden" name="mrf_submit" value="1" />
            <button class="mrf-submit" type="submit"><?php esc_html_e('Submit Registration', 'meal-request-form'); ?></button>
        </form>

        <!-- result below form -->
        <?php if ($result && isset($result['message'])): ?>
            <div id="mrf-result" class="mrf-msg <?php echo esc_attr($result['type']); ?>"><?php echo esc_html($result['message']); ?></div>
            <script>
            (function(){
                // remove mrf_key from URL to avoid re-showing message on refresh
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('mrf_key');
                    window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
                } catch(e) { /* ignore */ }
            })();
            </script>
        <?php endif; ?>

      </div>
    </div>
    <?php
    return ob_get_clean();
}
