<?php
/**
 * Uninstall — clean up plugin settings on removal.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('pvd_settings');
