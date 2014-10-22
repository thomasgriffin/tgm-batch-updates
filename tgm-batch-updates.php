<?php
/**
 * Plugin Name: TGM Batch Updates
 * Plugin URI:  https://github.com/thomasgriffin/tgm-batch-updates
 * Description: A batch updating utility for handling large amounts of data in WordPress.
 * Author:      Thomas Griffin
 * Author URI:  http://thomasgriffin.io
 * Version:     1.0.0
 * Text Domain: tgm-batch-updates
 * Domain Path: languages
 *
 * TGM Batch Updates is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * TGM Batch Updates is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TGM Batch Updates. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 *
 * @package TGM_Batch_Updates
 * @author  Thomas Griffin
 */
class TGM_Batch_Updates {

	/**
     * Number of posts to check per batch.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public $num = 10;

    /**
     * Main plugin method for querying data. The default order is oldest items first,
     * since that's typically where batch updating needs to happen. Change the 'order'
     * param to 'DESC' to start with newest first.
     *
     * @since 1.0.0
     *
     * @param int $ppp 	  The posts_per_page number to use in the query.
     * @param int $offset The offset to use for querying data.
     * @return mixed 	  An array of data to be processed in bulk fashion.
     */
    public function get_query_data( $ppp, $offset ) {

	    return get_posts(
            array(
				'post_type' 	 => 'post',
				'post_status'    => 'any',
				'orderby'		 => 'date',
				'order'			 => 'ASC',
				'cache_results'  => false,	 // Do not modify.
				'posts_per_page' => $ppp,    // Do not modify.
                'offset'         => $offset, // Do not modify.
            )
        );

    }

    /**
     * Loops through the array of data and processes it as necessary.
     *
     * @since 1.0.0
     *
     * @param array $data An array of data to process.
     */
    public function process_query_data( $data ) {

		// Loop through each post and add a custom field.
        foreach ( (array) $data as $post ) {
	        // Checks if the custom field exists. If not, add it with the title of the post.
	        $field = get_post_meta( $post->ID, 'my_custom_field', true );
	        if ( empty( $field ) ) {
		        update_post_meta( $post->ID, 'my_custom_field', $post->post_title );
	        }
        }

    }

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Unique plugin slug identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'tgm-batch-updates';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Plugin menu hook.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $hook = false;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'wp_ajax_tgm_batch_updates', array( $this, 'process_bulk_routine' ) );

    }

    /**
     * Processes the bulk editing routine experience.
     *
     * @since 1.0.0
     */
    public function process_bulk_routine() {

        // Run a security check first to ensure we initiated this action.
        check_ajax_referer( $this->plugin_slug, 'nonce' );

        // Prepare variables.
        $step   = absint( $_POST['step'] );
        $steps  = absint( $_POST['steps'] );
        $ppp    = $this->num;
        $offset = 1 == $step ? 0 : $ppp * ($step - 1);
        $done   = false;

        // Possibly return early if the offset exceeds the total steps and the $ppp is equal to the difference.
        if ( $offset > ($steps - ($this->num * 2)) && $ppp == ($offset - $steps) ) {
            die( json_encode( array( 'success' => true ) ) );
        }

        // If our offset is greater than our steps but $ppp is different, set $ppp to the difference.
        if ( $offset > ($steps - ($this->num * 2)) ) {
            $ppp    = $offset - $steps;
            $offset = $offset + 1;
            $done   = true;
        }

        // Ignore the user aborting and set the time limit to maximum (if allowed) for processing.
        @ignore_user_abort( true );
        if ( ! ini_get( 'safe_mode' ) ) {
            @set_time_limit( 0 );
        }

        // Grab all of our data.
        $data = $this->get_query_data( $ppp, $offset );

        // If we have no data or it returns false, we are done!
        if ( empty( $data ) || ! $data ) {
            die( json_encode( array( 'done' => true ) ) );
        }

        // Process our query data.
        $this->process_query_data( $data );

        // Send back our response to say we need to process more items.
        die( json_encode( array( 'done' => $done ) ) );

    }

    /**
     * Loads the plugin into WordPress.
     *
     * @since 1.0.0
     */
    public function init() {

        add_action( 'admin_menu', array( $this, 'menu' ) );

    }

    /**
     * Loads the admin menu item under the Tools menu.
     *
     * @since 1.0.0
     */
    public function menu() {

        $this->hook = add_management_page(
            __( 'TGM Batch Updates', 'tgm-batch-updates' ),
            __( 'Batch Updates', 'tgm-batch-updates' ),
            'manage_options',
            $this->plugin_slug,
            array( $this, 'menu_cb' )
        );

    }

    /**
     * Outputs the menu view.
     *
     * @since 1.0.0
     */
    public function menu_cb() {

        $processing = isset( $_GET['tgm-batch-updates'] ) || isset( $_GET['tgm-batch-step'] ) ? true : false;
        $step       = isset( $_GET['tgm-batch-step'] ) ? absint( $_GET['tgm-batch-step'] ) : 1;
        $steps      = isset( $_GET['tgm-batch-limit'] ) ? round( ( absint( $_GET['tgm-batch-limit'] ) / $this->num ), 0 ) : 0;
        $nonce      = wp_create_nonce( $this->plugin_slug );
        ?>
        <div class="wrap">
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
            <?php if ( $processing ) : ?>
            <p><?php _e( 'The batch update routine has started. Please be patient as this may take several minutes to complete.', 'tgm-batch-updates' ); ?> <img class="tgm-batch-loading" src="<?php echo includes_url( 'images/spinner-2x.gif' ); ?>" alt="<?php esc_attr_e( 'Loading...', 'tgm-batch-updates' ); ?>" width="20px" height="20px" style="vertical-align:bottom" /></p>
            <p class="tgm-batch-step"><strong><?php printf( __( 'Currently on step %d of a possible %d (steps may be less if your limit exceeds available items).', 'optin-monster-ga' ), (int) $step, (int) $steps ); ?></strong></p>
            <script type="text/javascript">
				jQuery(document).ready(function($){
					// Trigger the bulk upgrades to continue to processing.
					$.post( ajaxurl, { action: 'tgm_batch_updates', step: '<?php echo $step; ?>', steps: '<?php echo absint( $_GET['tgm-batch-limit'] ); ?>', nonce: '<?php echo $nonce; ?>' }, function(res){
						if ( res && res.success || res && res.done ) {
							$('.tgm-batch-step').after('<?php echo $this->get_success_message(); ?>');
							$('.tgm-batch-loading').remove();
							return;
						} else {
    						document.location.href = '<?php echo add_query_arg( array( 'page' => $this->plugin_slug, 'tgm-batch-updates' => 1, 'tgm-batch-step' => (int) $step + 1, 'tgm-batch-limit' => absint( $_GET['tgm-batch-limit'] ) ), admin_url( 'tools.php' ) ); ?>';
						}
					}, 'json');
				});
			</script>
            <?php else : ?>
            <p><?php printf( __( 'Once submitted, the form below will start the batch update routine. It will upgrade items in groups of <strong>%d</strong> until the limit is hit. You can manually enter a limit below.', 'tgm-batch-updates' ), $this->num ); ?>
            <form id="tgm-batch-updates" method="get" action="<?php echo add_query_arg( 'page', $this->plugin_slug, admin_url( 'tools.php' ) ); ?>">
                <input type="hidden" name="page" value="<?php echo $this->plugin_slug; ?>" />
                <input type="hidden" name="tgm-batch-updates" value="1" />
                <input type="hidden" name="tgm-batch-step" value="1" />
                <input type="number" name="tgm-batch-limit" value="1000" /> <span class="description"><?php _e( 'The maximum number of items to update during this routine.', 'tgm-batch-updates' ); ?></span>
                <p>
                    <input class="button button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Start Batch Updates', 'tgm-batch-updates' ); ?>" />
                </p>
            </form>
            <?php endif; ?>
        </div>
        <?php

    }

    /**
     * Returns the batch update completed message.
     *
     * @since 1.0.0
     *
     * @return string $message The batch update completed message.
     */
    public function get_success_message() {

	    $message  = '<div class="updated"><p>' . __( 'The batch update routine has been completed!', 'tgm-batch-updates' ) . '</p></div>';
	    $message .= '<p><a class="button button-secondary" href="' . add_query_arg( array( 'page' => $this->plugin_slug ), admin_url( 'tools.php' ) ) . '" title="' . esc_attr__( 'Reset Batch Updates Page', 'tgm-batch-updates' ) . '">' . __( 'Reset Batch Updates Page', 'tgm-batch-updates' ) . '</a></p>';
	    return $message;

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The TGM_Batch_Updates object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof TGM_Batch_Updates ) ) {
            self::$instance = new TGM_Batch_Updates();
        }

        return self::$instance;

    }

}

// Load the main plugin class.
$tgm_batch_updates = TGM_Batch_Updates::get_instance();