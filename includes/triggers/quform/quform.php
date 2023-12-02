<?php

function adfoin_quform_get_forms( $form_provider )
{
    if ( $form_provider != 'quform' ) {
        return;
    }
    global  $wpdb ;
    $query = "SELECT id, name FROM {$wpdb->prefix}quform_forms";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $forms = wp_list_pluck( $result, 'name', 'id' );
    return $forms;
}

function adfoin_quform_get_form_fields( $form_provider, $form_id )
{
    if ( $form_provider != 'quform' ) {
        return;
    }
    global  $wpdb ;
    $query = "SELECT config FROM {$wpdb->prefix}quform_forms WHERE id = {$form_id}";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $data = maybe_unserialize( base64_decode( stripslashes( $result[0]["config"] ) ) );
    $fields = array();
    if ( isset( $data['elements'] ) && is_array( $data['elements'] ) ) {
        foreach ( $data['elements'] as $element ) {
            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                foreach ( $element['elements'] as $field ) {
                    if ( 'submit' != $field['type'] ) {
                        if ( adfoin_fs()->is_not_paying() ) {
                            if ( 'text' == $field['type'] || 'email' == $field['type'] ) {
                                $fields['quform_' . $form_id . '_' . $field['id']] = $field['label'];
                            }
                        }
                    }
                }
            }
        }
    }
    return $fields;
}

function adfoin_quform_get_form_name( $form_provider, $form_id )
{
    if ( $form_provider != "quform" ) {
        return;
    }
    global  $wpdb ;
    $form_name = $wpdb->get_var( "SELECT name FROM {$wpdb->prefix}quform_forms WHERE id = " . $form_id );
    return $form_name;
}

add_filter(
    'quform_post_process',
    'adfoin_quform_post_process',
    10,
    2
);
function adfoin_quform_post_process( $result, $form )
{
    $posted_data = $form->getValues();
    $form_id = $form->getId();
    global  $wpdb, $post ;
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $saved_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = 'quform' AND form_id = %s", $form_id ), ARRAY_A );
    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
    }
    return $result;
}
