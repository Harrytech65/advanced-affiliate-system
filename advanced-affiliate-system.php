<?php
/**
 * Plugin Name: Advanced Affiliate System
 * Plugin URI: https://example.com/affiliate-system
 * Description: Complete affiliate marketing system with tracking, commissions, and payouts
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: advanced-affiliate
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AAS_VERSION', '1.0.0');
define('AAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAS_PLUGIN_FILE', __FILE__);

class Advanced_Affiliate_System {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-install.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-database.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-affiliate.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-tracking.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-commission.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-payout.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-reports.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-admin.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-dashboard.php';
        require_once AAS_PLUGIN_DIR . 'includes/class-aas-woocommerce.php';
    }
    
    private function init_hooks() {
        register_activation_hook(AAS_PLUGIN_FILE, array('AAS_Install', 'activate'));
        register_deactivation_hook(AAS_PLUGIN_FILE, array('AAS_Install', 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'register_post_types'));
    }
    
    public function init() {
        load_plugin_textdomain('advanced-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        new AAS_Tracking();
        new AAS_Commission();
        new AAS_Admin();
        new AAS_Dashboard();
        new AAS_WooCommerce();
    }
    
    public function register_post_types() {
        // Register custom post types if needed
    }
}

function AAS() {
    return Advanced_Affiliate_System::instance();
}

AAS();