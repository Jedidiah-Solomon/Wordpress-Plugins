<?php
/**
 * Plugin Name: Advanced Dashboard Info
 * Description: Enhanced dashboard widget with detailed site information and quick tools.
 * Version: 3.0
 * Author: Solomon HDS
 */

// Add the dashboard widget
add_action('wp_dashboard_setup', 'advanced_dashboard_widget');

function advanced_dashboard_widget() {
    wp_add_dashboard_widget(
        'advanced_dashboard_widget',           
        'Site Health & Quick Tools',                   
        'advanced_dashboard_widget_content'    
    );
}

// Widget content
function advanced_dashboard_widget_content() {
    // Basic site information
    $post_count = wp_count_posts()->publish;
    $page_count = wp_count_posts('page')->publish;
    $user_count = count_users();
    $theme = wp_get_theme();
    $plugins = get_plugins();
    
    // Server information
    $php_version = phpversion();
    $mysql_version = $GLOBALS['wpdb']->db_version();
    $server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
    $memory_usage = round(memory_get_usage() / 1024 / 1024, 2);
    $memory_limit = ini_get('memory_limit');
    
    // WordPress information
    $wp_version = get_bloginfo('version');
    $cron = get_option('cron');
    $last_cron = $cron ? date('Y-m-d H:i:s', end($cron)) : 'Never';
    
    // Security information
    $updates = get_site_transient('update_core');
    $wp_update_needed = !empty($updates->updates) ? ' Update Available' : 'Up to date';
    
    echo '<div class="advanced-dashboard-widget">';
    
    // System Status
    echo '<div class="dashboard-section">';
    echo '<h3><span class="dashicons dashicons-dashboard"></span> System Status</h3>';
    echo '<div class="system-health">';
    echo '<p><strong>WordPress:</strong> v' . $wp_version . ' (' . $wp_update_needed . ')</p>';
    echo '<p><strong>PHP:</strong> ' . $php_version . ' (Limit: ' . $memory_limit . ')</p>';
    echo '<p><strong>MySQL:</strong> ' . $mysql_version . '</p>';
    echo '<p><strong>Server:</strong> ' . $server_software . '</p>';
    echo '<p><strong>Memory Usage:</strong> ' . $memory_usage . 'MB</p>';
    echo '</div></div>';
    
    // Content Overview
    echo '<div class="dashboard-section">';
    echo '<h3><span class="dashicons dashicons-admin-post"></span> Content Overview</h3>';
    echo '<div class="content-summary">';
    echo '<p><strong>Posts:</strong> ' . number_format($post_count) . '</p>';
    echo '<p><strong>Pages:</strong> ' . number_format($page_count) . '</p>';
    echo '<p><strong>Users:</strong> ' . number_format($user_count['total_users']) . ' (';
    foreach($user_count['avail_roles'] as $role => $count) {
        echo ucfirst($role) . ': ' . $count . ' ';
    }
    echo ')</p>';
    echo '<p><strong>Comments:</strong> ' . wp_count_comments()->approved . ' approved</p>';
    echo '</div></div>';
    
    // Theme & Plugins
    echo '<div class="dashboard-section">';
    echo '<h3><span class="dashicons dashicons-admin-appearance"></span> Theme & Plugins</h3>';
    echo '<div class="theme-plugins">';
    echo '<p><strong>Theme:</strong> ' . esc_html($theme->get('Name')) . ' v' . esc_html($theme->get('Version')) . '</p>';
    echo '<p><strong>Active Plugins:</strong> ' . count(get_option('active_plugins')) . '/' . count($plugins) . '</p>';
    echo '</div></div>';
    
    // Quick Tools
    echo '<div class="dashboard-section">';
    echo '<h3><span class="dashicons dashicons-admin-tools"></span> Quick Tools</h3>';
    echo '<div class="quick-tools">';
    echo '<p><a href="' . admin_url('post-new.php') . '" class="button button-small"><span class="dashicons dashicons-edit"></span> New Post</a> ';
    echo '<a href="' . admin_url('edit-comments.php') . '" class="button button-small"><span class="dashicons dashicons-admin-comments"></span> Comments</a> ';
    echo '<a href="' . admin_url('users.php') . '" class="button button-small"><span class="dashicons dashicons-admin-users"></span> Users</a></p>';
    echo '<p><a href="' . admin_url('themes.php') . '" class="button button-small"><span class="dashicons dashicons-admin-appearance"></span> Themes</a> ';
    echo '<a href="' . admin_url('plugins.php') . '" class="button button-small"><span class="dashicons dashicons-admin-plugins"></span> Plugins</a> ';
    echo '<a href="' . admin_url('options-general.php') . '" class="button button-small"><span class="dashicons dashicons-admin-settings"></span> Settings</a></p>';
    echo '</div></div>';
    
    // Maintenance
    echo '<div class="dashboard-section">';
    echo '<h3><span class="dashicons dashicons-admin-generic"></span> Maintenance</h3>';
    echo '<div class="maintenance-tools">';
    echo '<p><a href="' . wp_nonce_url(admin_url('admin-post.php?action=clear_cache'), 'clear_cache') . '" class="button button-small"><span class="dashicons dashicons-update"></span> Clear Cache</a> ';
    echo '<a href="' . admin_url('tools.php?page=site-health') . '" class="button button-small"><span class="dashicons dashicons-heart"></span> Site Health</a> ';
    echo '<a href="' . admin_url('options-permalink.php') . '" class="button button-small"><span class="dashicons dashicons-admin-links"></span> Permalinks</a></p>';
    echo '</div></div>';
    
    echo '</div>'; 
}

// Custom styling
add_action('admin_head', 'advanced_dashboard_widget_styles');
function advanced_dashboard_widget_styles() {
    echo '<style>
        #advanced_dashboard_widget .dashboard-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        #advanced_dashboard_widget .dashboard-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        #advanced_dashboard_widget h3 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
        }
        #advanced_dashboard_widget h3 .dashicons {
            margin-right: 5px;
            color: #2271b1;
        }
        #advanced_dashboard_widget p {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        #advanced_dashboard_widget .button-small {
            padding: 0 8px;
            height: 28px;
            line-height: 26px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-flex;
            align-items: center;
        }
        #advanced_dashboard_widget .button-small .dashicons {
            margin-right: 3px;
            font-size: 16px;
        }
        #advanced_dashboard_widget .system-health p strong {
            display: inline-block;
            width: 120px;
        }
    </style>';
}

// Add cache clearing functionality
add_action('admin_post_clear_cache', 'handle_clear_cache');
function handle_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_admin_referer('clear_cache');
    
    // Clear common caches
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    
    // Clear transients
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
    
    wp_redirect(admin_url('index.php'));
    exit;
}