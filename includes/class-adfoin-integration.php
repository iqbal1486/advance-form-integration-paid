<?php

class Advanced_Form_Integration_Integration extends Advanced_Form_Integration_DB {

    public $integrations;

    /*
    * The constructor function
    */
    public function __construct() {
        global $wpdb;

        $this->db           = $wpdb;
        $this->table        = $this->db->prefix . 'adfoin_integration';
        $this->integrations = $this->all();
    }

    public function all() {
        $all = $this->db->get_results( "SELECT * FROM {$this->table}", 'ARRAY_A' );
        return $all;
    }

    public function get( $id ) {
        foreach( $this->integrations as $single_integration ) {
            if( $id == $single_integration['id'] ) {
                return $single_integration;
            }
        }
    }

    public function get_title( $id ) {
        $integration = $this->get( $id );

        if( $integration &&  isset( $integration['title'] ) ) {
            return $integration['title'];
        }
    }

    public function get_by_trigger( $trigger_platform, $form = false ) {
        $sql = "SELECT * FROM {$this->table} WHERE status = 1 AND form_provider = '{$trigger_platform}'";
        // $sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE status = 1 AND form_provider = %s", $trigger_platform );

        if( $form ) {
            $sql .= " AND form_id = '{$form}'";
        }
        
        $saved_records = $this->db->get_results( $sql, ARRAY_A );

        return $saved_records;
    }
}