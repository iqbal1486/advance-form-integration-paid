<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_klaviyo_actions',
    10,
    1
);
function adfoin_klaviyo_actions( $actions )
{
    $actions['klaviyo'] = array(
        'title' => __( 'Klaviyo', 'advanced-form-integration' ),
        'tasks' => array(
        'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
    ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_klaviyo_settings_tab',
    10,
    1
);
function adfoin_klaviyo_settings_tab( $providers )
{
    $providers['klaviyo'] = __( 'Klaviyo', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_klaviyo_settings_view',
    10,
    1
);
function adfoin_klaviyo_settings_view( $current_tab )
{
    if ( $current_tab != 'klaviyo' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_klaviyo_settings' );
    $pub_api_key = ( get_option( 'adfoin_klaviyo_public_api_key' ) ? get_option( 'adfoin_klaviyo_public_api_key' ) : '' );
    $api_token = ( get_option( 'adfoin_klaviyo_api_token' ) ? get_option( 'adfoin_klaviyo_api_token' ) : '' );
    ?>

    <form name="klaviyo_save_form" action="<?php 
    echo  esc_url( admin_url( 'admin-post.php' ) ) ;
    ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_klaviyo_save_api_token">
        <input type="hidden" name="_nonce" value="<?php 
    echo  $nonce ;
    ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php 
    _e( 'Public API Key', 'advanced-form-integration' );
    ?></th>
                <td>
                    <input type="text" name="adfoin_klaviyo_public_api_key"
                           value="<?php 
    echo  esc_attr( $pub_api_key ) ;
    ?>" placeholder="<?php 
    _e( 'Enter Public API Key', 'advanced-form-integration' );
    ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description"><a
                            href="https://www.klaviyo.com/account#api-keys-tab"
                            target="_blank" rel="noopener noreferrer"><?php 
    _e( 'Click here to get the API Keys', 'advanced-form-integration' );
    ?></a></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"> <?php 
    _e( 'Private API Key', 'advanced-form-integration' );
    ?></th>
                <td>
                    <input type="text" name="adfoin_klaviyo_api_token"
                           value="<?php 
    echo  esc_attr( $api_token ) ;
    ?>" placeholder="<?php 
    _e( 'Enter Private API Key', 'advanced-form-integration' );
    ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description"><?php 
    _e( 'Create a private API key with full access', 'advanced-form-integration' );
    ?></p>
                </td>
            </tr>
        </table>
        <?php 
    submit_button();
    ?>
    </form>

    <?php 
}

add_action(
    'admin_post_adfoin_klaviyo_save_api_token',
    'adfoin_save_klaviyo_api_token',
    10,
    0
);
function adfoin_save_klaviyo_api_token()
{
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_klaviyo_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $pub_api_key = sanitize_text_field( $_POST["adfoin_klaviyo_public_api_key"] );
    $api_token = sanitize_text_field( $_POST["adfoin_klaviyo_api_token"] );
    // Save tokens
    update_option( "adfoin_klaviyo_public_api_key", $pub_api_key );
    update_option( "adfoin_klaviyo_api_token", $api_token );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=klaviyo" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_klaviyo_js_fields',
    10,
    1
);
function adfoin_klaviyo_js_fields( $field_data )
{
}

add_action( 'adfoin_action_fields', 'adfoin_klaviyo_action_fields' );
function adfoin_klaviyo_action_fields()
{
    ?>
    <script type="text/template" id="klaviyo-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Klaviyo List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php 
    
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'subscribe'">
                        <th scope="row">
                            <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                        </th>
                        <td scope="row">
                            <span><?php 
        printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                        </td>
                    </tr>
                    <?php 
    }
    
    ?>
            
        </table>
    </script>
    <?php 
}

/*
 * Klaviyo API Private Request
 */
function adfoin_klaviyo_private_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array()
)
{
    $api_token = get_option( 'adfoin_klaviyo_api_token' );
    $base_url = 'https://a.klaviyo.com/api/v2/';
    $url = $base_url . $endpoint;
    $url = add_query_arg( 'api_key', $api_token, $url );
    $args = array(
        'method'  => $method,
        'headers' => array(
        'Content-Type' => 'application/json',
    ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}

/*
 * Klaviyo API Private Request revision 2023-02-22
 */
function adfoin_klaviyo_private_request_20230222(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array()
)
{
    $api_key = get_option( 'adfoin_klaviyo_api_token' );
    $base_url = 'https://a.klaviyo.com/api/';
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
        'Content-Type'  => 'application/json',
        'Accpet'        => 'application/json',
        'revision'      => '2023-02-22',
        'Authorization' => 'Klaviyo-API-Key ' . $api_key,
    ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}

add_action(
    'wp_ajax_adfoin_get_klaviyo_list',
    'adfoin_get_klaviyo_list',
    10,
    0
);
/*
 * Get Kalviyo subscriber lists
 */
function adfoin_get_klaviyo_list()
{
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $data = adfoin_klaviyo_private_request( 'lists' );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $body = json_decode( wp_remote_retrieve_body( $data ) );
    $lists = wp_list_pluck( $body, 'list_name', 'list_id' );
    wp_send_json_success( $lists );
}

/*
 * Handles sending data to Klaviyo API
 */
function adfoin_klaviyo_send_data( $record, $posted_data )
{
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $list_id = $data['listId'];
    $task = $record['task'];
    
    if ( $task == 'subscribe' ) {
        $email = ( empty($data['email']) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $req_data = array(
            'profiles' => array(
            'email' => $email,
        ),
        );
        if ( isset( $data['firstName'] ) && $data['firstName'] ) {
            $req_data['profiles']['first_name'] = adfoin_get_parsed_values( $data['firstName'], $posted_data );
        }
        if ( isset( $data['lastName'] ) && $data['lastName'] ) {
            $req_data['profiles']['last_name'] = adfoin_get_parsed_values( $data['lastName'], $posted_data );
        }
        if ( isset( $data['title'] ) && $data['title'] ) {
            $req_data['profiles']['$title'] = adfoin_get_parsed_values( $data['title'], $posted_data );
        }
        if ( isset( $data['organization'] ) && $data['organization'] ) {
            $req_data['profiles']['$organization'] = adfoin_get_parsed_values( $data['organization'], $posted_data );
        }
        if ( isset( $data['address1'] ) && $data['address1'] ) {
            $req_data['profiles']['$address1'] = adfoin_get_parsed_values( $data['address1'], $posted_data );
        }
        if ( isset( $data['address2'] ) && $data['address2'] ) {
            $req_data['profiles']['$address2'] = adfoin_get_parsed_values( $data['address2'], $posted_data );
        }
        if ( isset( $data['city'] ) && $data['city'] ) {
            $req_data['profiles']['$city'] = adfoin_get_parsed_values( $data['city'], $posted_data );
        }
        if ( isset( $data['region'] ) && $data['region'] ) {
            $req_data['profiles']['$region'] = adfoin_get_parsed_values( $data['region'], $posted_data );
        }
        if ( isset( $data['zip'] ) && $data['zip'] ) {
            $req_data['profiles']['$zip'] = adfoin_get_parsed_values( $data['zip'], $posted_data );
        }
        if ( isset( $data['country'] ) && $data['country'] ) {
            $req_data['profiles']['$country'] = adfoin_get_parsed_values( $data['country'], $posted_data );
        }
        if ( isset( $data['latitude'] ) && $data['latitude'] ) {
            $req_data['profiles']['$latitude'] = adfoin_get_parsed_values( $data['latitude'], $posted_data );
        }
        if ( isset( $data['longitude'] ) && $data['longitude'] ) {
            $req_data['profiles']['$longitude'] = adfoin_get_parsed_values( $data['longitude'], $posted_data );
        }
        if ( isset( $data['timezone'] ) && $data['timezone'] ) {
            $req_data['profiles']['$timezone'] = adfoin_get_parsed_values( $data['timezone'], $posted_data );
        }
        if ( isset( $data['externalId'] ) && $data['externalId'] ) {
            $req_data['profiles']['$id'] = adfoin_get_parsed_values( $data['externalId'], $posted_data );
        }
        if ( isset( $data['source'] ) && $data['source'] ) {
            $req_data['profiles']['$source'] = adfoin_get_parsed_values( $data['source'], $posted_data );
        }
        
        if ( isset( $data['phoneNumber'] ) && $data['phoneNumber'] ) {
            $phone_number = preg_replace( '/[^0-9+]/', '', adfoin_get_parsed_values( $data['phoneNumber'], $posted_data ) );
            if ( strlen( $phone_number ) > 7 ) {
                $req_data['profiles']['phone_number'] = $phone_number;
            }
        }
        
        $sub_endpoint = "list/{$list_id}/subscribe";
        $return = adfoin_klaviyo_private_request(
            $sub_endpoint,
            'POST',
            $req_data,
            $record
        );
    }
    
    return;
}
