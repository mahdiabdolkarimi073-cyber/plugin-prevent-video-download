<?php
/**
 * Settings management — stores options in a single WP option array.
 */

if (!defined('ABSPATH')) {
    exit;
}

class PVD_Settings {

    const OPTION_KEY = 'pvd_settings';
    const OPTION_GROUP = 'pvd_settings_group';
    const PAGE_SLUG = 'prevent-video-download';

    private $defaults = array(
        'enable_protection'      => 1,
        'disable_right_click'    => 1,
        'disable_shortcuts'      => 1,
        'disable_drag'           => 1,
        'disable_video_controls' => 1,
        'hide_context_menu'      => 1,
        'disable_console'        => 1,
        'disable_devtools'       => 1,
        'blur_on_devtools'       => 1,
        'block_video_download'   => 1,
        'watermark_text'         => '',
        'watermark_opacity'      => '0.15',
        'protect_elementor'      => 1,
        'disable_text_select'    => 0,
        'show_warning_message'   => 1,
        'warning_message'        => 'دانلود ویدیو از این سایت ممنوع است',
        'skip_for_admins'        => 0,
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('plugin_action_links_' . PVD_PLUGIN_BASENAME, array($this, 'add_action_link'));
    }

    public function get_settings() {
        $saved = get_option(self::OPTION_KEY, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        return wp_parse_args($saved, $this->defaults);
    }

    public function get($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }
        wp_enqueue_style(
            'pvd-admin',
            PVD_PLUGIN_URL . 'assets/css/pvd-admin.css',
            array(),
            PVD_VERSION
        );
        wp_enqueue_script(
            'pvd-admin',
            PVD_PLUGIN_URL . 'assets/js/pvd-admin.js',
            array(),
            PVD_VERSION,
            true
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            __('جلوگیری از دانلود ویدیو', 'prevent-video-download'),
            __('دانلود ویدیو', 'prevent-video-download'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page'),
            'dashicons-shield',
            80
        );
    }

    public function add_action_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=' . self::PAGE_SLUG) . '">'
            . __('تنظیمات', 'prevent-video-download') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_KEY,
            array($this, 'sanitize_settings')
        );
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        $checkboxes = array(
            'enable_protection', 'disable_right_click', 'disable_shortcuts',
            'disable_drag', 'disable_text_select', 'hide_context_menu',
            'disable_console', 'disable_devtools', 'blur_on_devtools',
            'disable_video_controls', 'block_video_download', 'protect_elementor',
            'show_warning_message', 'skip_for_admins',
        );

        foreach ($checkboxes as $cb) {
            $sanitized[$cb] = isset($input[$cb]) ? 1 : 0;
        }

        $sanitized['watermark_text']    = isset($input['watermark_text']) ? sanitize_text_field($input['watermark_text']) : '';
        $sanitized['watermark_opacity'] = isset($input['watermark_opacity']) ? max(0, min(1, floatval($input['watermark_opacity']))) : '0.15';
        $sanitized['warning_message']   = isset($input['warning_message']) ? sanitize_text_field($input['warning_message']) : $this->defaults['warning_message'];

        return $sanitized;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $settings = $this->get_settings();

        $active_count = 0;
        $all_toggles = array('disable_right_click', 'hide_context_menu', 'disable_devtools', 'disable_console', 'blur_on_devtools', 'disable_shortcuts', 'block_video_download', 'disable_video_controls', 'disable_drag', 'protect_elementor', 'disable_text_select', 'show_warning_message', 'skip_for_admins');
        foreach ($all_toggles as $t) {
            if (!empty($settings[$t])) $active_count++;
        }
        $total_features = count($all_toggles);
        $has_watermark = !empty($settings['watermark_text']) ? 1 : 0;
        ?>
        <div class="wrap pvd-wrap">
            <div class="pvd-header">
                <div class="pvd-header-inner">
                    <div class="pvd-logo">
                        <span class="dashicons dashicons-shield"></span>
                    </div>
                    <div class="pvd-title-area">
                        <h1>جلوگیری از دانلود ویدیو</h1>
                        <p class="pvd-subtitle">افزونه حفاظت از ویدیوهای سایت — وردپرس و المنتور</p>
                        <span class="pvd-version">نسخه <?php echo esc_html(PVD_VERSION); ?></span>
                    </div>
                    <div class="pvd-status-badge <?php echo $settings['enable_protection'] ? 'pvd-status-on' : 'pvd-status-off'; ?>">
                        <span class="pvd-status-dot"></span>
                        <?php echo $settings['enable_protection'] ? 'حفاظت فعال' : 'حفاظت غیرفعال'; ?>
                    </div>
                </div>
            </div>

            <div class="pvd-notice-bar">
                <span class="dashicons dashicons-info"></span>
                این افزونه راست‌کلیک، Inspect، میانبرهای کیبورد، Drag &amp; Drop و دانلود ویدیو را غیرفعال می‌کند. تنظیمات را زیر تغییر دهید و ذخیره کنید.
            </div>

            <div class="pvd-layout">
                <div class="pvd-main">
                    <form action="options.php" method="post" class="pvd-form">
                        <?php settings_fields(self::OPTION_GROUP); ?>

                        <!-- Master Switch -->
                        <div class="pvd-card pvd-master-card">
                            <div class="pvd-card-body">
                                <div class="pvd-toggle-row" style="border-bottom:none;">
                                    <div>
                                        <h3>فعال‌سازی حفاظت</h3>
                                        <p>وقتی فعال باشد، تمام تنظیمات زیر اعمال می‌شوند. وقتی غیرفعال باشد، افزونه هیچ تأثیری روی سایت ندارد.</p>
                                    </div>
                                    <label class="pvd-switch">
                                        <input type="checkbox" name="pvd_settings[enable_protection]" value="1" <?php checked(1, (int)$settings['enable_protection']); ?> />
                                        <span class="pvd-switch-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Right-click & Context Menu -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-warning"></span></div>
                                <h3>راست‌کلیک و منوی زمینه</h3>
                            </div>
                            <div class="pvd-card-body">
                                <?php $this->render_toggle('disable_right_click', 'غیرفعال کردن راست‌کلیک (کل صفحه)', $settings); ?>
                                <?php $this->render_toggle('hide_context_menu', 'پنهان کامل منوی راست‌کلیک مرورگر', $settings); ?>
                                <p class="pvd-hint"><span class="dashicons dashicons-info-outline"></span>وقتی فعال باشد، کاربر هیچ‌گونه راست‌کلیکی روی صفحه انجام نمی‌دهد و منوی مرورگر ظاهر نمی‌شود.</p>
                            </div>
                        </div>

                        <!-- DevTools & Inspect -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-search"></span></div>
                                <h3>Inspect و DevTools</h3>
                            </div>
                            <div class="pvd-card-body">
                                <?php $this->render_toggle('disable_devtools', 'غیرفعال کامل Inspect (F12, Ctrl+Shift+I)', $settings); ?>
                                <?php $this->render_toggle('disable_console', 'غیرفعال کردن کنسول توسعه‌دهنده', $settings); ?>
                                <?php $this->render_toggle('blur_on_devtools', 'تار کردن صفحه هنگام باز شدن DevTools', $settings); ?>
                                <p class="pvd-hint"><span class="dashicons dashicons-info-outline"></span>این تنظیمات جلوی باز شدن ابزار توسعه‌دهنده را می‌گیرد و در صورت باز شدن، صفحه را تار می‌کند.</p>
                            </div>
                        </div>

                        <!-- Keyboard Shortcuts -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-keyboard"></span></div>
                                <h3>میانبرهای کیبورد</h3>
                            </div>
                            <div class="pvd-card-body">
                                <?php $this->render_toggle('disable_shortcuts', 'مسدود کردن Ctrl+S, Ctrl+U, F12 و Ctrl+Shift+I/J/C', $settings); ?>
                                <p class="pvd-hint"><span class="dashicons dashicons-info-outline"></span>این کلیدها برای ذخیره صفحه، دیدن سورس کد و باز کردن ابزار توسعه‌دهنده استفاده می‌شوند.</p>
                            </div>
                        </div>

                        <!-- Download Prevention -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-download"></span></div>
                                <h3>جلوگیری از دانلود</h3>
                            </div>
                            <div class="pvd-card-body">
                                <?php $this->render_toggle('block_video_download', 'مسدود کردن درخواست‌های دانلود ویدیو', $settings); ?>
                                <?php $this->render_toggle('disable_video_controls', 'پنهان کردن دکمه دانلود در کنترل‌های ویدیو', $settings); ?>
                                <?php $this->render_toggle('disable_drag', 'غیرفعال کردن کشیدن (Drag &amp; Drop) ویدیو', $settings); ?>
                                <p class="pvd-hint"><span class="dashicons dashicons-info-outline"></span>دکمه دانلود بومی مرورگر پنهان می‌شود و درخواست‌های دانلود مسدود می‌گردند.</p>
                            </div>
                        </div>

                        <!-- Watermark -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-format-image"></span></div>
                                <h3>واترمارک</h3>
                            </div>
                            <div class="pvd-card-body">
                                <div class="pvd-field">
                                    <label>متن واترمارک روی ویدیو (اختیاری)</label>
                                    <input type="text" name="pvd_settings[watermark_text]" value="<?php echo esc_attr($settings['watermark_text']); ?>" class="pvd-input" placeholder="مثال: سایت من.com" />
                                </div>
                                <div class="pvd-field">
                                    <label>میزان شفافیت واترمارک (۰ تا ۱)</label>
                                    <input type="range" name="pvd_settings[watermark_opacity]" value="<?php echo esc_attr($settings['watermark_opacity']); ?>" min="0" max="1" step="0.05" class="pvd-range" />
                                    <span class="pvd-range-value"><?php echo esc_html($settings['watermark_opacity']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Message -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-megaphone"></span></div>
                                <h3>پیام هشدار</h3>
                            </div>
                            <div class="pvd-card-body">
                                <?php $this->render_toggle('show_warning_message', 'نمایش پیام هشدار هنگام تلاش برای دانلود', $settings); ?>
                                <div class="pvd-field">
                                    <label>متن پیام هشدار</label>
                                    <input type="text" name="pvd_settings[warning_message]" value="<?php echo esc_attr($settings['warning_message']); ?>" class="pvd-input" />
                                </div>
                            </div>
                        </div>

                        <!-- Misc -->
                        <div class="pvd-card">
                            <div class="pvd-card-header">
                                <div class="pvd-card-icon"><span class="dashicons dashicons-admin-generic"></span></div>
                                <h3>سایر تنظیمات</h3>
                            </div>
                            <div class="pvd-card-body">
                                <?php $this->render_toggle('protect_elementor', 'حفاظت از ویجت‌های ویدیو المنتور', $settings); ?>
                                <?php $this->render_toggle('disable_text_select', 'غیرفعال کردن انتخاب متن در صفحه', $settings); ?>
                                <?php $this->render_toggle('skip_for_admins', 'غیرفعال کردن حفاظت برای مدیران سایت (پیشنهاد می‌شود)', $settings); ?>
                                <p class="pvd-hint"><span class="dashicons dashicons-info-outline"></span>وقتی این گزینه فعال باشد، کاربرانی که وارد پیشخوان وردپرس شده‌اند، حفاظت روی سایت آن‌ها اعمال نمی‌شود. این گزینه باعث می‌شود بتوانید راحت‌تر سایت را ویرایش کنید. همچنین حفاظت در ویرایشگر المنتور و Customizer همیشه غیرفعال است.</p>
                            </div>
                        </div>

                        <div class="pvd-submit-area">
                            <div class="pvd-save-info">
                                <span class="dashicons dashicons-saved"></span>
                                پس از تغییر تنظیمات، حتماً ذخیره کنید
                            </div>
                            <?php submit_button(__('ذخیره تنظیمات', 'prevent-video-download'), 'primary pvd-save-btn', 'submit', false); ?>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="pvd-sidebar">
                    <!-- Stats -->
                    <div class="pvd-sidebar-card">
                        <div class="pvd-sidebar-card-header">
                            <span class="dashicons dashicons-chart-area"></span>
                            <h4>آمار حفاظت</h4>
                        </div>
                        <div class="pvd-sidebar-card-body">
                            <div class="pvd-stats-grid">
                                <div class="pvd-stat-item">
                                    <div class="pvd-stat-number"><?php echo esc_html($active_count); ?></div>
                                    <div class="pvd-stat-label">قابلیت فعال</div>
                                </div>
                                <div class="pvd-stat-item">
                                    <div class="pvd-stat-number"><?php echo esc_html($total_features); ?></div>
                                    <div class="pvd-stat-label">کل قابلیت‌ها</div>
                                </div>
                                <div class="pvd-stat-item">
                                    <div class="pvd-stat-number"><?php echo $has_watermark ? 'بله' : 'خیر'; ?></div>
                                    <div class="pvd-stat-label">واترمارک</div>
                                </div>
                                <div class="pvd-stat-item">
                                    <div class="pvd-stat-number"><?php echo $settings['skip_for_admins'] ? 'بله' : 'خیر'; ?></div>
                                    <div class="pvd-stat-label">استثنای مدیر</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="pvd-sidebar-card">
                        <div class="pvd-sidebar-card-header">
                            <span class="dashicons dashicons-shield-alt"></span>
                            <h4>امکانات افزونه</h4>
                        </div>
                        <div class="pvd-sidebar-card-body">
                            <ul class="pvd-feature-list">
                                <li><span class="dashicons dashicons-yes-alt"></span>غیرفعال‌سازی راست‌کلیک</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>مسدود کردن Save As</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>غیرفعال‌سازی DevTools</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>پنهان کردن دکمه دانلود</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>جلوگیری از Drag &amp; Drop</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>واترمارک روی ویدیو</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>پشتیبانی از المنتور</li>
                                <li><span class="dashicons dashicons-yes-alt"></span>پیام هشدار سفارشی</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Quick Info -->
                    <div class="pvd-sidebar-card">
                        <div class="pvd-sidebar-card-header">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <h4>نکته</h4>
                        </div>
                        <div class="pvd-sidebar-card-body">
                            <p style="font-size:13px;color:var(--pvd-neutral-600);line-height:1.7;margin:0;">هیچ روشی نمی‌تواند دانلود را ۱۰۰٪ متوقف کند، اما این افزونه تمام راه‌های رایج دانلود را مسدود می‌کند و کاربران عادی قادر به دانلود نخواهند بود.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_toggle($key, $label, $settings) {
        $value = isset($settings[$key]) ? (int)$settings[$key] : 0;
        ?>
        <div class="pvd-toggle-row">
            <span class="pvd-toggle-label"><?php echo esc_html($label); ?></span>
            <label class="pvd-switch">
                <input type="checkbox" name="pvd_settings[<?php echo esc_attr($key); ?>]" value="1" <?php checked(1, $value); ?> />
                <span class="pvd-switch-slider"></span>
            </label>
        </div>
        <?php
    }
}
