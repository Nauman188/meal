<?php
if (! defined('ABSPATH')) exit;

// register AJAX handlers
add_action('wp_ajax_meal_request_form', 'mrf_handle_submission_ajax');
add_action('wp_ajax_nopriv_meal_request_form', 'mrf_handle_submission_ajax');

function mrf_handle_submission_ajax() {
    // expected fields
    $fields = [
        'name','phone','email','payment_method','package',
        'street1','street2','city','state','zip','insurance','message','came_from'
    ];

    // check nonce
    if (! isset($_POST['mrf_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['mrf_nonce']), 'mrf_action')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }

    // sanitize input
    $v = [];
    foreach ($fields as $f) {
        $v[$f] = isset($_POST[$f]) ? sanitize_text_field(wp_unslash($_POST[$f])) : '';
    }

    // server-side validation
    $errors = [];
    $required = ['name','phone','email','payment_method','street1','city','state','zip'];
    foreach ($required as $r) {
        if (empty($v[$r])) $errors[$r] = __('This field is required.', 'meal-request-form');
    }
    if (! isset($errors['email']) && ! is_email($v['email'])) {
        $errors['email'] = __('Enter a valid email address.', 'meal-request-form');
    }
    if ($v['payment_method'] === 'Insurance') {
        if (empty($v['package'])) $errors['package'] = __('Please choose a package.', 'meal-request-form');
        if (empty($v['insurance'])) $errors['insurance'] = __('Please choose an insurance provider.', 'meal-request-form');
    }

    if (! empty($errors)) {
        wp_send_json_error([
            'message' => __('Please correct the highlighted fields and try again.', 'meal-request-form'),
            'type' => 'invalid',
            'errors' => $errors,
        ]);
    }

    // endpoints & api key
    $opts = get_option('mrf_settings', []);
    $insurance_api = $opts['insurance_api'] ?? 'https://stg-api.physicianmarketing.us/v2/auth/insurance-customer/account-request';
    $selfpay_api   = $opts['selfpay_api'] ?? 'https://stg-api.physicianmarketing.us/v2/auth/payment-customer/signup';
    // $cleanPhone = preg_replace('/\D/', '', $v['phone']);
    // payload mapping

    if ($v['payment_method'] === 'Insurance') {
        $payload = [
            'name'       => $v['name'],
            'email'      => $v['email'],
            'phone'      => $v['phone'],
            'address' => [
                'street1' => $v['street1'],
                'street2' => $v['street2'],
                'city'    => $v['city'],
                'state'   => $v['state'],
                'zip'     => $v['zip'],
            ],
            'insuranceProvider' => $v['insurance'],
            'package' => $v['package'],
            'message' => $v['message'],
            'came_from' => 'website',
        ];
        $url = $insurance_api;
    } else {
        $payload = [
            'name' => $v['name'],
            'email' => $v['email'],
            'phone' => $v['phone'],
            'address' => [
                'street1' => $v['street1'],
                'street2' => $v['street2'],
                'city'    => $v['city'],
                'state'   => $v['state'],
                'zip'     => $v['zip'],
            ],
            'message' => $v['message'],
            'came_from' => 'website',
        ];
        $url = $selfpay_api;
    }

    // headers
    $headers = ['Content-Type' => 'application/json'];

    $args = ['headers' => $headers, 'body' => wp_json_encode($payload), 'timeout' => 30];
    $resp = wp_remote_post($url, $args);

    if (is_wp_error($resp)) {
        wp_send_json_error(['message' => __('Could not reach API: ', 'meal-request-form') . $resp->get_error_message()]);
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body_raw = wp_remote_retrieve_body($resp);
    $body = json_decode($body_raw, true);

    if (! is_array($body)) {
        if ($code >= 200 && $code < 300) {
            wp_send_json_success(['message' => $body_raw ?: __('Submission successful.', 'meal-request-form')]);
        } else {
            wp_send_json_error(['message' => $body_raw ?: __('API returned an error.', 'meal-request-form')]);
        }
    }

    $status = isset($body['status']) ? strtolower($body['status']) : ($code >=200 && $code <300 ? 'success' : 'error');
    $message = $body['message'] ?? '';

    if (! empty($body['errors']) && is_array($body['errors'])) {
        // map api error keys to our fields
        $map = [
            'first_name' => 'name',
            'last_name' => 'name',
            'email_address' => 'email',
            'zip_code' => 'zip',
            'insurance_provider' => 'insurance',
            'ins_provider' => 'insurance',
        ];
        $errout = [];
        foreach ($body['errors'] as $k => $v_err) {
            $our = $map[$k] ?? $k;
            if (in_array($our, $fields, true)) {
                $errout[$our] = is_array($v_err) ? implode(' ', $v_err) : sanitize_text_field($v_err);
            } else {
                $message .= ' ' . (is_array($v_err) ? implode(' ', $v_err) : sanitize_text_field($v_err));
            }
        }
        wp_send_json_error(['message' => trim($message ?: __('Please correct the highlighted fields.', 'meal-request-form')), 'type' => 'invalid', 'errors' => $errout]);
    } else {
        if ($status === 'success') {
            wp_send_json_success(['message' => $message ?: __('Submission successful.', 'meal-request-form')]);
        } elseif ($status === 'duplicate') {
            wp_send_json_error(['message' => $message ?: __('Duplicate submission.', 'meal-request-form'), 'type' => 'duplicate']);
        } else {
            wp_send_json_error(['message' => $message ?: __('Submission failed.', 'meal-request-form')]);
        }
    }
}
