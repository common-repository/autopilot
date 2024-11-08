<?php

/**
 * @since             1.0.0
 * @package           Autopilot
 *
 * @wordpress-plugin
 * Plugin Name:       Ortto
 * Plugin URI:        https://wordpress.org/plugins/autopilot
 * Description:       Customer data and marketing automation platform.
 * Version:           1.0.22
 * Author:            Ortto
 * Author URI:        https://ortto.app
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       autopilot
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('AUTOPILOT_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-autopilot-activator.php
 */
function activate_autopilot()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-autopilot-activator.php';
    Autopilot_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-autopilot-deactivator.php
 */
function deactivate_autopilot()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-autopilot-deactivator.php';
    Autopilot_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_autopilot');
register_deactivation_hook(__FILE__, 'deactivate_autopilot');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-autopilot.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_autopilot()
{
    $plugin = new Autopilot();
    $plugin->run();
}

run_autopilot();
