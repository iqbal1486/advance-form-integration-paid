<?php

function adfoin_liveforms_get_forms( $form_provider )
{
    if ( $form_provider != 'liveforms' ) {
        return;
    }
    global  $wpdb ;
    $form_data = get_posts( array(
        'post_type'           => 'form',
        'ignore_sticky_posts' => true,
        'nopaging'            => true,
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
    ) );
    $forms = wp_list_pluck( $form_data, 'post_title', 'ID' );
    return $forms;
}

function adfoin_liveforms_get_form_fields( $form_provider, $form_id )
{
    if ( $form_provider != 'liveforms' ) {
        return;
    }
    if ( !$form_id ) {
        return;
    }
    $form = maybe_unserialize( get_post_meta( $form_id, 'form_data' ) );
    $fields = array();
    if ( isset( $form[0], $form[0]['fields'] ) ) {
        foreach ( $form[0]['fields'] as $key => $value ) {
            if ( adfoin_fs()->is_not_paying() ) {
                if ( 'Name_' == substr( $key, 0, 5 ) || 'Email_' == substr( $key, 0, 6 ) ) {
                    $fields[$key] = $value;
                }
            }
        }
    }
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

/*
 * Get Form name by form id
 */
function adfoin_liveforms_get_form_name( $form_provider, $form_id )
{
    if ( $form_provider != 'liveforms' ) {
        return;
    }
    $form = get_post( $form_id );
    $form_name = $form->post_title;
    return $form_name;
}

add_action(
    'liveform_after_form_submitted',
    'adfoin_liveforms_submission',
    10,
    2
);
function adfoin_liveforms_submission( $form_entry, $submission_id )
{
    global  $wpdb, $post ;
    $form_id = $form_entry['fid'];
    $saved_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = 'liveforms' AND form_id = %s", $form_id ), ARRAY_A );
    $data = maybe_unserialize( $form_entry['data'] );
    $posted_data = array();
    foreach ( $data as $key => $value ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'Name_' == substr( $key, 0, 5 ) || 'Email_' == substr( $key, 0, 6 ) ) {
                $posted_data[$key] = $value;
            }
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
    }
    return;
}
