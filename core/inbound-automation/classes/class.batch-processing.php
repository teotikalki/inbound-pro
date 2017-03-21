<?php

class Automation_Batch_Processing {

    static $rules;

    /**
     * Automation_Batch_Processing constructor.
     */
    public function __construct(){
        self::load_hooks();
    }

    /**
     * Load hooks and filters
     */
    public static function load_hooks(){
        add_action( 'admin_menu' , array( __CLASS__ , 'init_listener') , 30);
    }


    /**
     * Listens for batch processing
     */
    public static function init_listener() {

        /* check if batch processing event is flagged */
        if ( !get_option('automation_batch_processing' , false )) {
            return;
        }

        /* Temporarily create admin page for visualizing batch processing */
        add_submenu_page(
            'edit.php?post_type=automation',
            __( 'RESUME DATA MIGRATION', 'inbound-pro' ),
            __( 'RESUME DATA MIGRATION', 'inbound-pro' ),
            'manage_options',
            'automation-batch-processing',
            array( __CLASS__ , 'process_batches' )
        );

        /* Do not let user escape until all leads have been processed */
        if ( ( !isset($_GET['page']) || $_GET['page'] != 'automation-batch-processing' ) && !get_transient('automation_batch_processing_started') ) {
            set_transient('automation_batch_processing_started' , true , 1 * HOUR_IN_SECONDS );
            header('Location: ' . admin_url('edit.php?post_type=automation&page=automation-batch-processing'));
            exit;
        }

    }


    /**
     * Run the batch processing method stored in leads_batch_processing option
     */
    public static function process_batches() {

        /* load batch processing data into variable */
        $jobs = get_option('automation_batch_processing');

        echo '<h1>' . __( 'Processing Batches!' , 'inbound-pro' ) .'</h1>';
        echo '<div class="wrap">';

        /* run the method */
        $args = array_shift($jobs);
        call_user_func(
            array(__ClASS__, $args['method']),
            $args
        );

        echo '</div>';

    }


    /**
     * Removes complete job and deletes leads_batch_processing if all jobs are complete else updates and returns true.
     * @return bool
     */
    public static function delete_flag( $args ) {
        $jobs = get_option('automation_batch_processing');
        unset($jobs[$args['method']]);

        if ($jobs) {
            update_option('automation_batch_processing', $jobs);
            return true;
        } else {
            delete_option('automation_batch_processing');
            return false;
        }
    }

}

new Automation_Batch_Processing();