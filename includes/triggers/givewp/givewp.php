<?php
/*
 * Get GiveWP triggers
 */
function adfoin_givewp_get_forms( $form_provider ) {
    if ( $form_provider != 'givewp' ) {
        return;
    }

    $triggers = array(
        'donationViaForm'    => __( 'User makes donation via form', 'advanced-form-integration' ),
        'cancelRecurViaForm' => __( 'User cancels recurring donation via form', 'advanced-form-integration' ),
        'continueRecur'      => __( 'User continues recurring donation', 'advanced-form-integration' ),
    );

    return $triggers;
}

/*
 * Get GiveWP fields
 */
function adfoin_givewp_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'givewp' ) {
        return;
    }

    $fields = array();

    if( in_array( $form_id, array( 'donationViaForm' ) ) ) {
        $fields['title']       = __( 'Title', 'advanced-form-integration' );
        $fields['first_name']  = __( 'First Name', 'advanced-form-integration' );
        $fields['last_name']   = __( 'Last Name', 'advanced-form-integration' );
        $fields['email']       = __( 'Email', 'advanced-form-integration' );
        $fields['donor_id']    = __( 'Donor ID', 'advanced-form-integration' );
        $fields['amount']      = __( 'Amount', 'advanced-form-integration' );
        $fields['currency']    = __( 'Currency', 'advanced-form-integration' );
        $fields['comment']     = __( 'Comment', 'advanced-form-integration' );
        $fields['address1']    = __( 'Address 1', 'advanced-form-integration' );
        $fields['address2']    = __( 'Address 2', 'advanced-form-integration' );
        $fields['city']        = __( 'City', 'advanced-form-integration' );
        $fields['state']       = __( 'State', 'advanced-form-integration' );
        $fields['zip']         = __( 'Zip', 'advanced-form-integration' );
        $fields['country']     = __( 'Country', 'advanced-form-integration' );
        $fields['form_id']     = __( 'Form ID', 'advanced-form-integration' );
        $fields['form_title']  = __( 'Form Title', 'advanced-form-integration' );
        $fields['price_id']    = __( 'Price ID', 'advanced-form-integration' );
        $fields['price_title'] = __( 'Price Title', 'advanced-form-integration' );
    }

    if( in_array( $form_id, array( 'cancelRecurViaForm' ) ) ) {
        $fields['subs_id'] = __( 'Subscription ID', 'advanced-form-integration' );
        $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
        $fields['amount']  = __( 'Amount', 'advanced-form-integration' );
        $fields['donor']   = __( 'Donor', 'advanced-form-integration' );
        $fields['user_id'] = __( 'User ID', 'advanced-form-integration' );
    }

    if( in_array( $form_id, array( 'continueRecur' ) ) ) {
        $fields['form_id']       = __( 'Form ID', 'advanced-form-integration' );
        $fields['amount']        = __( 'Amount', 'advanced-form-integration' );
        $fields['total_payment'] = __( 'Total Payment', 'advanced-form-integration' );
        $fields['donor']         = __( 'Donor', 'advanced-form-integration' );
        $fields['user_id']       = __( 'User ID', 'advanced-form-integration' );
    }
    
    // if ( adfoin_fs()->is__premium_only() ) {
    //     if ( adfoin_fs()->is_plan( 'professional', true ) ) {
    //         global $wpdb;

    //         $custom_fields = array();
    //         $results       = $wpdb->get_col( "SELECT label FROM {$wpdb->prefix}xxxxxxx" );

    //         foreach ( $results as $field ){   
    //             $custom_fields[$field] = $field;
    //         }

    //         $fields = $fields + $custom_fields;
    //     }
    // }

    return $fields;
}

function adfoin_givewp_get_userdata( $user_id ) {
    $user_data = array();
    $user      = get_userdata($user_id);

    if ($user) {
        $user_data["user_id"]    = $user->ID;
        $user_data["first_name"] = $user->first_name;
        $user_data["last_name"]  = $user->last_name;
        $user_data["email"] = $user->user_email;
    }

    return $user_data;
}

function adfoin_givewp_send_data( $saved_records, $posted_data ) {
    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];
        call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
    }
}

add_action( 'give_update_payment_status', 'adfoin_update_payment_status', 10, 3 );

function adfoin_update_payment_status( $payment_id, $status, $old_status ) {
        
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'donationViaForm' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payment = new Give_Payment( $payment_id );

    if ( empty( $payment ) || !isset( $payment->ID ) ) {
        return;
    }

    $form_id = $payment->form_id;
    $user_id = $payment->user_id;

    if ( 0 === $user_id ) {
        return;
    }

    $posted_data = json_decode( wp_json_encode( $payment ), true );
    $user_info   = give_get_payment_meta_user_info( $payment_id );

    if ( $user_info ) {
        $posted_data['title'] = $user_info['title'];
        $posted_data['first_name'] = $user_info['first_name'];
        $posted_data['last_name'] = $user_info['last_name'];
        $posted_data['email'] = $user_info['email'];
        $posted_data['address1'] = $user_info['address']['line1'];
        $posted_data['address2'] = $user_info['address']['line2'];
        $posted_data['city'] = $user_info['address']['city'];
        $posted_data['state'] = $user_info['address']['state'];
        $posted_data['zip'] = $user_info['address']['zip'];
        $posted_data['country'] = $user_info['address']['country'];
        $posted_data['donar_id'] = $user_info['donor_id'];
    }

    $posted_data['form_id'] = $form_id;
    $posted_data['form_title'] = $payment->form_title;
    $posted_data['currency'] = $payment->currency;
    $posted_data['price_id'] = $payment->price_id;
    $posted_data['price'] = $payment->total;

    adfoin_givewp_send_data( $saved_records, $posted_data );
}

add_action('give_subscription_cancelled', 'adfoin_givewp_subscription_cancelled', 10, 2 );

function adfoin_givewp_subscription_cancelled( $sub_id, $subscription ) {

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'cancelRecurViaForm' );

    if (empty($saved_records)) {
        return;
    }

    $form_id = $subscription->form_id;
    $amount  = $subscription->recurring_amount;
    $donor   = $subscription->donor;
    $user_id = $donor->user_id;

    if ( 0 === absint( $user_id ) ) {
        return;
    }

    $user_data = adfoin_givewp_get_userdata( $user_id );
    $posted_data = array(
        'sub_id'     => $sub_id,
        'form_id'    => $form_id,
        'amount'     => $amount,
        'donor'      => $donor,
        'user_id'    => $user_id,
        'first_name' => $user_data['first_name'],
        'last_name'  => $user_data['last_name'],
        'email'      => $user_data['email'],
    );

    adfoin_givewp_send_data( $saved_records, $posted_data );
}

add_action('give_subscription_updated', 'adfoin_givewp_subscription_updated', 10, 4 );

function adfoin_givewp_subscription_updated( $status, $row_id, $data, $where ) {

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'continueRecur' );

    if (empty($saved_records)) {
        return;
    }

    $subscription = new \Give_Subscription( $row_id );
    $amount = $subscription->recurring_amount;
    $form_id = $subscription->form_id;
    $total_payment = $subscription->get_total_payments();
    $donor = $subscription->donor;
    $user_id = $donor->user_id;

    if( 0 === absint( $user_id ) ) {
        return;
    }

    if( $total_payment > 1 && 'active' === (string) $data['status'] ) {

        $user_data = adfoin_givewp_get_userdata( $user_id );

        $posted_data = array(
            'form_id' => $form_id,
            'amount' => $amount,
            'total_payment' => $total_payment,
            'donor' => $donor,
            'user_id' => $user_id,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'email' => $user_data['email']
        );
    }

    adfoin_givewp_send_data( $saved_records, $posted_data );
}