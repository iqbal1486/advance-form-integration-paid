<?php

function adfoin_register_api_routes() {
    register_rest_route('afi2/v1', '/credentials', array(
        'methods' => 'GET',
        'callback' => 'adfoin_get_credentials',
        'permission_callback' => 'adfoin_credentials_permission',
    ));
    register_rest_route('afi2/v1', '/credentials', array(
        'methods' => 'POST',
        'callback' => 'adfoin_save_credentials',
        'permission_callback' => 'adfoin_credentials_permission',
    ));
    // register_rest_route('afi2/v1', '/credentials/(?P<id>\d+)', array(
    //     'methods' => 'PUT',
    //     'callback' => 'adfoin_update_credentials',
    //     'permission_callback' => 'current_user_can',
    // ));
    // register_rest_route('afi2/v1', '/credentials/(?P<id>\d+)', array(
    //     'methods' => 'DELETE',
    //     'callback' => 'adfoin_delete_credentials',
    //     'permission_callback' => 'current_user_can',
    // ));
}

add_action('rest_api_init', 'adfoin_register_api_routes');

function adfoin_credentials_permission() {
    return true;
    $permission = current_user_can( 'manage_options' );

    return $permission;
}

function adfoin_save_credentials( WP_REST_Request $request ) {
    $params = $request->get_json_params();

    if ( isset( $params['platform'] ) && isset( $params['data'] ) ) {
        // $platform = sanitize_text_field($params['platform']);
        // $data = sanitize_text_field($params['data']);
        $platform = $params['platform'];
        $data = $params['data'];
        $all_credentials = (array) maybe_unserialize( get_option( 'adfoin_credentials' ) );

        if( $platform && $data ) {
            $all_credentials[$platform] = $data;

            update_option( 'adfoin_credentials', maybe_serialize( $all_credentials ) );
        }


        // Insert 'Title' and 'API Key' into your custom database table.
        // Make sure to validate and sanitize the data.

        $response = array('message' => 'Data created successfully');
        return new WP_REST_Response($response, 201);
    } else {
        return new WP_Error('missing_data', 'Both "Title" and "API Key" fields are required.', array('status' => 400));
    }
}

function adfoin_get_credentials(WP_REST_Request $request) {
    $platform = $request->get_param( 'platform' );
    $data = adfoin_read_credentials( $platform );

    return new WP_REST_Response($data, 200);
}

function adfoin_read_credentials( $platform ) {
    $all_credentials = (array) maybe_unserialize( get_option( 'adfoin_credentials' ) );
    $credentials = array();

    if( isset( $all_credentials[$platform] ) && sizeof( $all_credentials[$platform] ) > 0 ) {
        $credentials = $all_credentials[$platform];
    }

    return $credentials;
}