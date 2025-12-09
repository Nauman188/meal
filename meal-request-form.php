<?php
/**
 * Plugin Name: Meal Request Form (AJAX)
 * Description: Server-side Meal Request form (Insurance / Self Pay). Shortcode: [meal_request_form]. AJAX loader + modal included.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: meal-request-form
 */

if (! defined('ABSPATH')) { exit; }

define('MRF_PLUGIN_FILE', __FILE__);
define('MRF_PLUGIN_DIR', plugin_dir_path(MRF_PLUGIN_FILE));
define('MRF_PLUGIN_URL', plugin_dir_url(MRF_PLUGIN_FILE));
define('MRF_VERSION', '1.0.0');

// Includes
require_once MRF_PLUGIN_DIR . 'includes/settings.php';
require_once MRF_PLUGIN_DIR . 'includes/handler-ajax.php';

/**
 * Shortcode: [meal_request_form]
 */
add_shortcode('meal_request_form', 'mrf_render_form');

function mrf_render_form($atts = []) {
    // enqueue assets
    wp_enqueue_style('mrf-style', MRF_PLUGIN_URL . 'assets/css/style.css', [], MRF_VERSION);
    wp_enqueue_script('jquery');
    wp_enqueue_script('mrf-main', MRF_PLUGIN_URL . 'assets/js/main.js', ['jquery'], MRF_VERSION, true);

    // pass settings
    $opts = get_option('mrf_settings', []);
    $selfPayValue = isset($opts['selfpay_value']) ? $opts['selfpay_value'] : 'Self Pay'; // if you later add configurable value
    wp_localize_script('mrf-main', 'mrfSettings', [
        'selfPayValue' => $selfPayValue,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'i18n' => [
            'loading' => __('Loading...', 'meal-request-form'),
            'close' => __('Close', 'meal-request-form'),
            'could_not_reach' => __('Could not reach server. Please try again.', 'meal-request-form')
        ]
    ]);

    // prepare old values for progressive enhancement if needed (AJAX path not using transient)
    $fields = [
        'name','phone','email','payment_method','package',
        'street1','street2','city','state','zip','insurance','message','came_from'
    ];
    $old = array_fill_keys($fields, '');

    ob_start();
    ?>
    <div class="mrf-wrap">
      <div class="mrf-card">
        <div class="mrf-head">
          <h2 class="mrf-title"><?php esc_html_e('Meal Plan Registration', 'meal-request-form'); ?></h2>
          <div class="mrf-sub"><?php esc_html_e('Complete the form below to get started.', 'meal-request-form'); ?></div>
        </div>

        <form method="post" action="#" novalidate class="mrf-form">
            <?php wp_nonce_field('mrf_action','mrf_nonce'); ?>
            <input type="hidden" name="action" value="meal_request_form">
            <input type="hidden" name="mrf_ajax" value="1">

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_name"><?php esc_html_e('Full Name', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_name" name="name" class="mrf-input" value="<?php echo esc_attr($old['name']); ?>" />
                </div>
                <div>
                    <label class="mrf-label" for="mrf_phone"><?php esc_html_e('Phone', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_phone" name="phone" class="mrf-input" maxlength="14" value="<?php echo esc_attr($old['phone']); ?>" />
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_email"><?php esc_html_e('Email', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_email" name="email" class="mrf-input" value="<?php echo esc_attr($old['email']); ?>" />
                </div>

                <div>
                    <label class="mrf-label" for="mrf_payment"><?php esc_html_e('Payment Method', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <select id="mrf_payment" name="payment_method" class="mrf-input">
                        <option value=""><?php esc_html_e('Select payment method', 'meal-request-form'); ?></option>
                        <option value="Insurance"><?php esc_html_e('Insurance','meal-request-form'); ?></option>
                        <option value="Self Pay"><?php esc_html_e('Self Pay','meal-request-form'); ?></option>
                    </select>
                </div>
            </div>

            <div class="mrf-grid">
                <div id="mrf_field_package">
                    <label class="mrf-label" for="mrf_package"><?php esc_html_e('Choose Package', 'meal-request-form'); ?></label>
                    <select id="mrf_package" name="package" class="mrf-input">
                        <option value=""><?php esc_html_e('Choose Package','meal-request-form'); ?></option>
                        <option value="7 meals">7 Meals</option>
                        <option value="14 meals">14 Meals</option>
                    </select>
                </div>

                <div id="mrf_field_insurance">
                    <label class="mrf-label" for="mrf_insurance"><?php esc_html_e('Insurance Provider', 'meal-request-form'); ?></label>
                    <select id="mrf_insurance" name="insurance" class="mrf-input">
                        <option value=""><?php esc_html_e('Select Insurance Provider','meal-request-form'); ?></option>
                        <option value="community">Keystone First Community Health Choice</option>
                        <option value="vip">Keystone First VIP Choice</option>
                        <option value="pa health">PA Health & Wellness</option>
                    </select>
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_street1"><?php esc_html_e('Street 1', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_street1" name="street1" class="mrf-input" value="<?php echo esc_attr($old['street1']); ?>" />
                </div>
                <div>
                    <label class="mrf-label" for="mrf_street2"><?php esc_html_e('Street 2', 'meal-request-form'); ?></label>
                    <input id="mrf_street2" name="street2" class="mrf-input" value="<?php echo esc_attr($old['street2']); ?>" />
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_city"><?php esc_html_e('City', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_city" name="city" class="mrf-input" value="<?php echo esc_attr($old['city']); ?>" />
                </div>
                <div>
                    <label class="mrf-label" for="mrf_state"><?php esc_html_e('State', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_state" name="state" class="mrf-input" maxlength="2" value="<?php echo esc_attr($old['state']); ?>" />
                </div>
            </div>

            <div class="mrf-grid">
                <div>
                    <label class="mrf-label" for="mrf_zip"><?php esc_html_e('Zip Code', 'meal-request-form'); ?> <span class="mrf-required">*</span></label>
                    <input id="mrf_zip" name="zip" class="mrf-input" pattern="[0-9]{5}" value="<?php echo esc_attr($old['zip']); ?>" />
                </div>
                 <div>
                    <label class="mrf-label" for="mrf_message"><?php esc_html_e('Message', 'meal-request-form'); ?></label>
                    <textarea id="mrf_message" name="message" class="mrf-text"><?php echo esc_textarea($old['message']); ?></textarea>
                </div>
            </div>

            <input type="hidden" name="mrf_submit" value="1" />
            <button class="mrf-submit" type="submit"><?php esc_html_e('Submit Registration', 'meal-request-form'); ?></button>
        </form>

      </div>
    </div>

   <!-- Glassmorphism Modal -->
<div id="mrf-modal" class="mrf-glass-modal hidden">
    <div class="mrf-glass-box">
        <button class="mrf-glass-close">&times;</button>

        <div class="mrf-glass-icon" id="mrf-glass-icon"></div>

        <div class="mrf-glass-message" id="mrf-modal-message"></div>

        <button class="mrf-glass-btn" id="mrf-glass-ok">Okay</button>
    </div>
</div>

<!-- Glass Loader -->
<div id="mrf-loader" class="mrf-glass-loader-wrap">
    <div class="mrf-glass-loader"></div>
</div>

    <?php
    return ob_get_clean();
}
