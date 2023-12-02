<?php
if( !class_exists( 'WP_List_Table' ) ) {
    // require_once ABSPATH . 'wp-admin/includes/template.php';
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Connection List Table class.
class Advanced_Form_Integration_Log_Table extends WP_List_Table {

    public $log;

    /**
     * Construct function
     * Set default settings.
     */
    function __construct() {
        global $status, $page;
        //Set parent defaults
        parent::__construct( array(
            'ajax'     => FALSE,
            'singular' => 'log',
            'plural'   => 'logs',
        ) );

        $this->log = new Advanced_Form_Integration_Log();
    }

    /**
     * Renders the columns.
     *
     * @since 1.0.0
     */
    function column_default( $item, $column_name ) {

        switch ( $column_name ) {
            case 'id':
                $value = $item['id'];
                break;
            case 'response_code':
                $value = $item['response_code'];
                break;
            case 'integration_id':
                $value = $item['integration_id'];
                break;
            case 'request_data':
                $value = $item['request_data'];
                break;
            case 'response_data':
                $value = $item['response_data'];
                break;
            case 'action':
                $value = $item['action'];
                break;
            default:
                $value = '';
        }

        return apply_filters( 'adfoin_log_table_column_value', $value, $item, $column_name );
    }

    /**
     * Retrieve the table columns.
     *
     * @since 1.0.0
     * @return array $columns Array of all the list table columns.
     */
    function get_columns() {
        $columns = array(
            'cb'               => '<input type="checkbox" />',
            'response_code'    => esc_html__( 'Code', 'advanced-form-integration' ),
            'integration_id'   => esc_html__( 'Integration', 'advanced-form-integration' ),
            'request_data'     => esc_html__( 'Request', 'advanced-form-integration' ),
            'response_data'    => esc_html__( 'Response', 'advanced-form-integration' ),
            'actions'          => esc_html__( 'Actions', 'advanced-form-integration' )
        );

        return apply_filters( 'adfoin_log_table_columns', $columns );
    }

    /**
     * Render the checkbox column.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function column_cb( $item ) {
        return '<input type="checkbox" name="log_id[]" value="' . absint( $item['id'] ) . '" />';
    }

    public function column_integration_id( $item ) {
        $int_id      = $item['integration_id'];
        $integration = new Advanced_Form_Integration_Integration();
        $title       = $integration->get_title( $int_id );
        return sprintf( '<span title="%s">%s</span>', $title, $int_id );
    }

    /**
     * Render the response code column
     */
    public function column_response_code( $item ) {

        $code = ! empty( $item['response_code'] ) ? $item['response_code'] : _e( 'Unknown', 'advanced-form-integration' );

        $starting = substr($code, 0, 1);
        $class    = 'code-200';

        if( 4 == $starting ) {
            $class = 'code-400';
        }

        if( 5 == $starting ) {
            $class = 'code-500';
        }

        if( isset( $item['response_message'] ) && ! empty( $item['response_message'] ) ) {
            $code .= ' ' . $item['response_message'];
        }

        $date = date_i18n( 'Y/m/d h:i a', strtotime( $item['time'] ) );

        $formatted_code = sprintf( '<mark class="log-response-code %s"><span>%s</span></mark><div class="log-date" title="%s">%s</div>', $class, esc_html__( $code ), esc_html( $item['time'] ), esc_html( $date ) );



        // Build the row action links and return the value.
        return $formatted_code;
    }

    /**
     * Render the request data column
     */
    public function column_request_data( $item ) {
        $request_data = str_replace( '"', '\'', $item['request_data'] );
        printf( '<span title="%s">%s</span>', $request_data, stripslashes( substr( $item['request_data'], 0, 60 ) ) . '...' );
    }

    /**
     * Render the response data column
     */
    public function column_response_data( $item ) {
        $response_data = str_replace( '"', '\'', $item['response_data'] );
        printf( '<span title="%s">%s</span>', $response_data, stripslashes( substr( $item['response_data'], 0, 60 ) ) . '...' );
    }

    /**
     * Render the view column.
     */
    public function column_actions( $item ) {
        $full_log = json_encode(
            array(
            'integration_id'   => $item['integration_id'],
            'response_code'    => $item['response_code'],
            'response_message' => $item['response_message'],
            'request_data'     => json_decode( $item['request_data'], true ),
            'response_data'    => json_decode( $item['response_data'], true ),
            'time'             => $item['time']
        ));
        
        $admin_url = admin_url( 'admin.php?page=advanced-form-integration-log' );
        printf( '<a href="%s&action=view&id=%s"><span class="dashicons dashicons-visibility" title="View Full Log"></span></a><div class="full-log-icon-container"><span class="dashicons dashicons-admin-page icon-copy-full-log" title="Copy Full Log" data-full-log=\'%s\'></span></div>', $admin_url, $item['id'], $full_log );
    }

    /**
     * Define bulk actions available for our table listing.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_bulk_actions() {

        $actions = array(
            'delete' => esc_html__( 'Delete', 'advanced-form-integration' ),
        );

        return $actions;
    }

    /**
     * Process the bulk actions.
     *
     * @since 1.0.0
     */
    public function process_bulk_actions() {

        $ids = isset( $_REQUEST['log_id'] ) ? $_REQUEST['log_id'] : array();

        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        $ids    = array_map( 'absint', $ids );
        $action = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;

        if ( empty( $ids ) || empty( $action ) ) {
            return;
        }

        // Delete one or multiple relations - both delete links and bulk actions.
        if ( 'delete' === $this->current_action() ) {

            if (
                wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-logs' ) ||
                wp_verify_nonce( $_REQUEST['_wpnonce'], 'adfoin_delete_log_nonce' )
            ) {

                foreach ( $ids as $id ) {
                    $this->log->delete( $id );
                }

                advanced_form_integration_redirect( admin_url( 'admin.php?page=advanced-form-integration-log' ) );

                exit;
            }
        }
    }

    /**
     * Sortable settings.
     */
    function get_sortable_columns() {
        return array(
            'integration_id' => array( 'integration_id', TRUE )
        );
    }

    public function fetch_table_data( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'number'  => 20,
            'offset'  => 0,
            'orderby' => 'id',
            'order'   => 'DESC',
            'count'   => false
        );

        $args  = wp_parse_args( $args, $defaults );
        $log   = new Advanced_Form_Integration_Log();
        $sql   = "SELECT * FROM {$log->table}";

        if( $args['count'] ) {
            $sql = "SELECT count(*) FROM {$log->table}";
        }

        // Check is the row want to search
        if ( isset( $args['s'] ) && ! empty( $args['s'] ) ) {
            $arg_s = $args['s'];

            $sql .= " WHERE (`response_message` LIKE '%{$arg_s}%' OR `request_data` LIKE '%{$arg_s}%' OR `response_data` LIKE '%{$arg_s}%')";
        }

        if ( ! empty( $args['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $args['orderby'] );
            $sql .= ! empty( $args['order'] ) ? ' ' . esc_sql( $args['order'] ) : ' ASC';
        }

        $sql .= " LIMIT {$args['number']}";
        $sql .= ' OFFSET ' . $args['offset'];

        $result = $log->get_results( $sql, 'ARRAY_A' );

        if( $args['count'] ) {
            $result = $log->get_var( $sql );
        }

        return $result;
    }

    //Query, filter data, handle sorting, pagination, and any other data-manipulation required prior to rendering
    public function prepare_items() {
        // Process bulk actions if found.
        $this->process_bulk_actions();

        // $count                 = $this->count();
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->admin_header();

        $current_page          = $this->get_pagenum();
        $per_page              = 20;
        $current_page          = $this->get_pagenum();
        $offset                = ( $current_page -1 ) * $per_page;

        $args = array(
            'offset' => $offset,
            'number' => $per_page,
        );

        if ( isset( $_REQUEST['orderby'] ) && !empty( $_REQUEST['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
        }

        if ( isset( $_REQUEST['order'] ) && !empty( $_REQUEST['order'] ) ) {
            $args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
        }

        // Filter for search
        if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
        }

        $this->items = $this->fetch_table_data( $args );
        $args['count'] = true;
        $count = $this->fetch_table_data( $args );

        $this->set_pagination_args(
            array(
                'total_items' => $count,
                'per_page'    => $per_page,
                'total_pages' => ceil( $count / $per_page ),
            )
        );
    }

    /*
     * Renders status column
     */
    public function column_status($item) {

        if ($item['status']) {
            $actions = "<span onclick='window.location=\"admin.php?page=advanced-form-integration-log&action=status&id=".$item['id']."\"'  class='span_activation_cheackbox'  ><a class='a_activation_cheackbox' href='?page=advanced-form-integration&action=edit&id=".$item['id']."'>  <input type='checkbox' name='status' checked=checked > </a></span>" ;
        }else{
            $actions = "<span onclick='window.location=\"admin.php?page=advanced-form-integration&action-log=status&id=".$item['id']." \"'  class='span_activation_cheackbox'  ><a class='a_activation_cheackbox' href='?page=advanced-form-integration&action=edit&id=".$item['id']."'>  <input type='checkbox' name='status' > </a></span>" ;
        }


        // print_r($item);

        return   $actions ;
    }

    /*
     * Handles column width
     */
    public function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'advanced-form-integration-log' != $page )
            return;

        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 10%; }';
        echo '.wp-list-table .column-response_code { width: 15%; }';
        echo '.wp-list-table .column-integration_id { width: 9%; }';
        echo '.wp-list-table .column-request_data { width: 28%; }';
        echo '.wp-list-table .column-response_data { width: 28%; }';
        echo '.wp-list-table .column-actions { width: 10%; }';
        echo '</style>';
    }
}