<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Alle Optionen löschen
delete_option('lobster_posts_switch_options');