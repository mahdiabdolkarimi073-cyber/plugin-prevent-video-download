<?php
/**
 * Plugin Name:       Prevent Video Download
 * Plugin URI:        https://example.com/prevent-video-download
 * Description:       جلوگیری از دانلود ویدیوها در سایت وردپرسی و المنتور — غیرفعال کردن راست‌کلیک، میانبرهای کیبورد، Drag & Drop، کنسول و ابزارهای دانلود روی ویدیوها.
 * Version:           1.2.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Prevent Video Download
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       prevent-video-download
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PVD_VERSION', '1.2.0');
define('PVD_PLUGIN_FILE', __FILE__);
define('PVD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PVD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PVD_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once PVD_PLUGIN_DIR . 'includes/class-pvd-settings.php';
require_once PVD_PLUGIN_DIR . 'includes/class-pvd-protector.php';
require_once PVD_PLUGIN_DIR . 'includes/class-pvd-htaccess.php';

/**
 * Main plugin class.
 */
final class Prevent_Video_Download {

    private static $instance = null;

    public $settings;
    public $protector;
    public $htaccess;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings  = new PVD_Settings();
        $this->protector = new PVD_Protector($this->settings);
        $this->htaccess  = new PVD_Htaccess($this->settings);
    }
}

function pvd() {
    return Prevent_Video_Download::instance();
}

pvd();
