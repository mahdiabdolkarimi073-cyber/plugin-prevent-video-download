<?php
/**
 * .htaccess manager — generates server-side rules to block direct access
 * to video files. When protection is enabled, video files can only be
 * streamed through the HTML5 <video> tag (Referer check), not opened
 * directly in the browser or downloaded via URL.
 */

if (!defined('ABSPATH')) {
    exit;
}

class PVD_Htaccess {

    const MARKER_START = '# BEGIN Prevent Video Download';
    const MARKER_END   = '# END Prevent Video Download';

    private $settings;
    private $htaccess_path;

    public function __construct($settings) {
        $this->settings = $settings;
        $this->htaccess_path = $this->find_htaccess();

        add_action('admin_init', array($this, 'maybe_update_htaccess'));
        register_deactivation_hook(PVD_PLUGIN_FILE, array($this, 'remove_rules'));
        register_uninstall_hook(PVD_PLUGIN_FILE, array('PVD_Htaccess', 'remove_rules_static'));
    }

    private function find_htaccess() {
        if (!function_exists('get_home_path')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (function_exists('get_home_path')) {
            $path = get_home_path() . '.htaccess';
            if (file_exists($path)) {
                return $path;
            }
        }
        return ABSPATH . '.htaccess';
    }

    public function get_settings() {
        if ($this->settings instanceof PVD_Settings) {
            return $this->settings->get_settings();
        }
        return $this->settings;
    }

    public function maybe_update_htaccess() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        $should_have_rules = !empty($settings['enable_protection']) && !empty($settings['block_videoDownload'] ?? $settings['block_video_download'] ?? 0);

        if ($should_have_rules) {
            $this->write_rules();
        } else {
            $this->remove_rules();
        }
    }

    private function generate_rules() {
        $site_url = home_url();
        $host = parse_url($site_url, PHP_URL_HOST);
        $scheme = is_ssl() ? 'https' : 'http';

        $rules = self::MARKER_START . "\n";
        $rules .= "<IfModule mod_rewrite.c>\n";
        $rules .= "RewriteEngine On\n";
        $rules .= "RewriteCond %{HTTP_REFERER} !^" . preg_quote($scheme . '://' . $host) . " [NC]\n";
        $rules .= "RewriteCond %{HTTP_REFERER} !^" . preg_quote($scheme . '://www.' . $host) . " [NC]\n";
        $rules .= "RewriteRule \.(mp4|webm|ogg|ogv|avi|mov|mkv|flv|wmv|m4v|m3u8|ts)$ - [F]\n";
        $rules .= "</IfModule>\n";

        // Also block direct access without a Referer for common video types
        $rules .= "<IfModule mod_headers.c>\n";
        $rules .= "<FilesMatch \"\\.(mp4|webm|ogg|ogv|avi|mov|mkv|flv|wmv|m4v|m3u8|ts)$\">\n";
        $rules .= "Header set Content-Disposition inline\n";
        $rules .= "Header set X-Content-Type-Options nosniff\n";
        $rules .= "</FilesMatch>\n";
        $rules .= "</IfModule>\n";

        $rules .= self::MARKER_END;

        return $rules;
    }

    public function write_rules() {
        if (empty($this->htaccess_path) || !is_writable($this->htaccess_path)) {
            return false;
        }

        $new_rules = $this->generate_rules();
        $existing = file_get_contents($this->htaccess_path);

        // Remove old rules first
        $existing = $this->strip_old_rules($existing);

        // Prepend new rules
        $content = $new_rules . "\n\n" . $existing;

        return file_put_contents($this->htaccess_path, $content) !== false;
    }

    private function strip_old_rules($content) {
        $start = self::MARKER_START;
        $end = self::MARKER_END;
        $pattern = '/' . preg_quote($start, '/') . '.*?' . preg_quote($end, '/') . '/s';
        $content = preg_replace($pattern, '', $content);
        // Clean up extra newlines left behind
        $content = preg_replace("/\n{3,}/", "\n\n", $content);
        return trim($content) . "\n";
    }

    public function remove_rules() {
        if (empty($this->htaccess_path) || !file_exists($this->htaccess_path)) {
            return false;
        }
        if (!is_writable($this->htaccess_path)) {
            return false;
        }
        $existing = file_get_contents($this->htaccess_path);
        $content = $this->strip_old_rules($existing);
        return file_put_contents($this->htaccess_path, $content) !== false;
    }

    public static function remove_rules_static() {
        $instance = new self(new PVD_Settings());
        $instance->remove_rules();
    }
}
