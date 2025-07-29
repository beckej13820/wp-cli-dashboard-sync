<?php
/**
 * Plugin Name: WP-CLI Dashboard Sync
 * Description: Adds a WP-CLI command to sync dashboard widget order across all sites/users in a multisite.
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Dashboard_Sync_Command {
        /**
         * Synchronize dashboard widget order and hidden widgets from a master user/site
         *
         * ## OPTIONS
         *
         * [--master_site=<id>]
         * : Site ID to use as the master. Default is 1.
         *
         * [--master_user=<id>]
         * : User ID to use as the master. Default is 1.
         *
         * ## EXAMPLES
         *     wp dashboard sync --master_site=1 --master_user=1
         *
         * @when after_wp_load
         */
        public function sync( $args, $assoc_args ) {
            $master_site_id = isset( $assoc_args['master_site'] ) ? intval( $assoc_args['master_site'] ) : 1;
            $master_user_id = isset( $assoc_args['master_user'] ) ? intval( $assoc_args['master_user'] ) : 1;

            // Switch to master site
            switch_to_blog( $master_site_id );
            $master_order = get_user_meta( $master_user_id, 'meta-box-order_dashboard', true );
            $master_hidden = get_user_meta( $master_user_id, 'metaboxhidden_dashboard', true );
            restore_current_blog();

            if ( empty( $master_order ) ) {
                WP_CLI::error( "No dashboard order found for master user {$master_user_id} on site {$master_site_id}." );
                return;
            }

            $sites = get_sites( [ 'fields' => 'ids' ] );
            foreach ( $sites as $site_id ) {
                switch_to_blog( $site_id );

                WP_CLI::log( "Syncing dashboard for site ID: $site_id" );

                $users = get_users( [ 'fields' => 'ID' ] );
                foreach ( $users as $user_id ) {
                    update_user_meta( $user_id, 'meta-box-order_dashboard', $master_order );
                    update_user_meta( $user_id, 'metaboxhidden_dashboard', $master_hidden );
                    WP_CLI::log( " - Updated user ID: $user_id" );
                }

                restore_current_blog();
            }

            WP_CLI::success( "Dashboard sync completed across all sites." );
        }
    }

    WP_CLI::add_command( 'dashboard', 'Dashboard_Sync_Command' );
}
