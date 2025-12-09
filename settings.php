<?php
/**
 * Admin settings page for Meal Request Form
 */

if (! defined('ABSPATH')) exit;

add_action('admin_menu', function(){
    add_options_page(
        __('Meal Request Form', 'meal-request-form'),
        __('Meal Request Form', 'meal-request-form'),
        'manage_options',
        'meal-request-form',
        'mrf_options_page'
    );
});

add_action('admin_init', function(){
    register_setting('mrf_settings_group', 'mrf_settings', 'mrf_sanitize_settings');
});

function mrf_sanitize_settings($in) {
    $out = [];
    $out['insurance_api'] = isset($in['insurance_api']) ? esc_url_raw($in['insurance_api']) : '';
    $out['selfpay_api']   = isset($in['selfpay_api']) ? esc_url_raw($in['selfpay_api']) : '';
    return $out;
}

function mrf_options_page() {
    if (! current_user_can('manage_options')) return;

    $s = get_option('mrf_settings', []);
    $insurance = $s['insurance_api'] ?? 'https://stg-api.physicianmarketing.us/v2/auth/insurance-customer/account-request';
    $selfpay   = $s['selfpay_api'] ?? 'https://stg-api.physicianmarketing.us/v2/auth/payment-customer/signup';
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Meal Request Form Settings', 'meal-request-form'); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields('mrf_settings_group'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="mrf_insurance_api"><?php esc_html_e('Insurance API Endpoint', 'meal-request-form'); ?></label></th>
            <td><input id="mrf_insurance_api" style="width:100%" type="url" name="mrf_settings[insurance_api]" value="<?php echo esc_attr($insurance); ?>"></td>
          </tr>
          <tr>
            <th scope="row"><label for="mrf_selfpay_api"><?php esc_html_e('Self Pay API Endpoint', 'meal-request-form'); ?></label></th>
            <td><input id="mrf_selfpay_api" style="width:100%" type="url" name="mrf_settings[selfpay_api]" value="<?php echo esc_attr($selfpay); ?>"></td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}
