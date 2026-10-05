<?php
/**
 * Protector class — enqueues frontend scripts and styles based on settings.
 * Also generates server-side .htaccess rules to block direct video file access.
 */

if (!defined('ABSPATH')) {
    exit;
}

class PVD_Protector {

    private $settings;

    public function __construct($settings = null) {
        if ($settings !== null) {
            $this->settings = $settings;
        }
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 999);
        add_action('wp_head', array($this, 'inject_meta_protection'), 1);
        add_action('wp_footer', array($this, 'inject_noscript_warning'), 100);
    }

    /**
     * Returns true when we are inside any builder/editor where protection
     * must NOT run (Elementor editor, preview, customizer, WP admin bar, etc.).
     */
    private function is_builder_context() {
        if (function_exists('is_admin') && is_admin()) {
            return true;
        }

        if (function_exists('elementor_preview') || (did_action('elementor/loaded') && class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->editor->is_edit_mode())) {
            return true;
        }

        if (isset($_GET['elementor-preview']) || isset($_GET['elementor'])) {
            return true;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'elementor') {
            return true;
        }

        if (function_exists('is_customize_preview') && is_customize_preview()) {
            return true;
        }
        if (isset($_GET['customize_changeset_uuid']) || isset($_GET['wp_customize'])) {
            return true;
        }

        $builder_params = array('et_fb', 'fl_builder', 'ct_builder', 'bricks-editor', 'breakdance');
        foreach ($builder_params as $p) {
            if (isset($_GET[$p])) {
                return true;
            }
        }

        $settings = $this->settings instanceof PVD_Settings
            ? $this->settings->get_settings()
            : $this->settings;

        if (!empty($settings['skip_for_admins']) && current_user_can('manage_options')) {
            return true;
        }

        return false;
    }

    public function enqueue_assets() {
        if ($this->is_builder_context()) {
            return;
        }

        $settings = $this->settings instanceof PVD_Settings
            ? $this->settings->get_settings()
            : $this->settings;

        $js_settings = array(
            'enableProtection'      => (bool) $settings['enable_protection'],
            'disableRightClick'     => (bool) $settings['disable_right_click'],
            'disableShortcuts'      => (bool) $settings['disable_shortcuts'],
            'disableDrag'           => (bool) $settings['disable_drag'],
            'disableTextSelect'     => (bool) $settings['disable_text_select'],
            'hideContextMenu'       => (bool) $settings['hide_context_menu'],
            'disableConsole'        => (bool) $settings['disable_console'],
            'disableDevtools'       => (bool) $settings['disable_devtools'],
            'blurOnDevTools'        => (bool) $settings['blur_on_devtools'],
            'disableVideoControls'  => (bool) $settings['disable_video_controls'],
            'blockVideoDownload'    => (bool) $settings['block_video_download'],
            'watermarkText'         => $settings['watermark_text'],
            'watermarkOpacity'      => $settings['watermark_opacity'],
            'protectElementor'      => (bool) $settings['protect_elementor'],
            'showWarningMessage'    => (bool) $settings['show_warning_message'],
            'warningMessage'        => $settings['warning_message'],
            'skipForAdmins'         => (bool) $settings['skip_for_admins'],
            'siteUrl'               => home_url(),
        );

        wp_register_script(
            'pvd-frontend',
            PVD_PLUGIN_URL . 'assets/js/pvd-frontend.js',
            array(),
            PVD_VERSION,
            true
        );

        wp_localize_script('pvd-frontend', 'PVD_Settings', $js_settings);
        wp_enqueue_script('pvd-frontend');

        wp_enqueue_style(
            'pvd-frontend',
            PVD_PLUGIN_URL . 'assets/css/pvd-frontend.css',
            array(),
            PVD_VERSION
        );

        $custom_css = $this->generate_custom_css($settings);
        if ($custom_css) {
            wp_add_inline_style('pvd-frontend', $custom_css);
        }

        if ($settings['disable_text_select']) {
            wp_add_inline_style('pvd-frontend', $this->no_select_css());
        }
    }

    public function inject_meta_protection() {
        if ($this->is_builder_context()) {
            return;
        }
        $settings = $this->settings instanceof PVD_Settings
            ? $this->settings->get_settings()
            : $this->settings;

        if (!$settings['enable_protection']) {
            return;
        }

        // Early inline script: blocks keys + contextmenu before any other JS loads.
        // Uses capture-phase addEventListener (not oncontextmenu assignment) so other
        // scripts cannot easily override it. Blocks Ctrl+S (Save As), Ctrl+U (View Source),
        // F12, Ctrl+Shift+I/J/C (DevTools), Ctrl+P (Print), and right-click.
        echo '<script type="text/javascript">(function(){try{var d=document;var w=window;var block=function(e){e.preventDefault();e.stopPropagation();if(e.stopImmediatePropagation)e.stopImmediatePropagation();return false;};d.addEventListener("contextmenu",function(e){return block(e);},true);d.addEventListener("keydown",function(e){var k=(e.key||"").toUpperCase();var c=e.keyCode||e.which;var ctrl=e.ctrlKey||e.metaKey;var sh=e.shiftKey;if(c===123||(ctrl&&sh&&(k==="I"||k==="J"||k==="C"||k==="K"))||(ctrl&&!sh&&(k==="U"||k==="S"||k==="P"))||(ctrl&&sh&&k==="S")||k==="PRINTSCREEN"||c===44){return block(e);}},true);d.addEventListener("mousedown",function(e){if(e.button===2){return block(e);}},true);d.addEventListener("auxclick",function(e){if(e.button===2){return block(e);}},true);}catch(e){}})();</script>' . "\n";
    }

    public function inject_noscript_warning() {
        if ($this->is_builder_context()) {
            return;
        }
        echo '<noscript><div style="position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;z-index:999999;display:flex;align-items:center;justify-content:center;font-size:20px;text-align:center;padding:40px;direction:rtl;">برای مشاهده ویدیوها باید جاوااسکریپت را در مرورگر خود فعال کنید.</div></noscript>' . "\n";
    }

    private function generate_custom_css($settings) {
        $css = '';

        if (!empty($settings['watermark_text'])) {
            $opacity = isset($settings['watermark_opacity']) ? $settings['watermark_opacity'] : '0.15';
            $escaped_text = esc_js($settings['watermark_text']);
            $css .= "video::after{content:\"{$escaped_text}\";}";
        }

        if ($settings['disable_video_controls']) {
            $css .= "video::-internal-media-controls-download-button{display:none !important;}";
            $css .= "video::-webkit-media-controls-download-button{display:none !important;}";
            $css .= "video::-webkit-media-controls-enclosure{overflow:hidden !important;}";
            $css .= "video::-webkit-media-controls-panel{width:calc(100% + 30px) !important;}";
            $css .= "video::-webkit-media-controls-picture-in-picture-button{display:none !important;}";
        }

        return $css;
    }

    private function no_select_css() {
        return 'body{-webkit-user-select:none !important;-moz-user-select:none !important;-ms-user-select:none !important;user-select:none !important;}input,textarea,[contenteditable="true"]{-webkit-user-select:text !important;-moz-user-select:text !important;-ms-user-select:text !important;user-select:text !important;}';
    }
}
