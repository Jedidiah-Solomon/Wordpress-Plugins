<?php
 /**
 * Plugin Name: SEO Audit Lite
 * Description: A simple on-page SEO checker for titles, meta descriptions, H1 tags, and missing image alts.
 * Version: 1.1
 * Author: Solomon HDS
 * Author URI: https://jedidiahsolomon.name.ng
 * Plugin URI: https://github.com/Jedidiah-Solomon/Wordpress-Plugins/blob/main/ultimate-seo-analyzer.zip
 *  Text Domain: ultimate-seo-analyzer
 */

add_action('admin_notices', 'seo_audit_lite_notice');

function seo_audit_lite_notice() {
    // Only show on post edit screens
    $screen = get_current_screen();
    if ($screen->base != 'post' || !isset($_GET['post'])) return;

    $post_id = intval($_GET['post']);
    $post = get_post($post_id);

    // Only show for published posts
    if (!$post || $post->post_status !== 'publish') return;

    $content = $post->post_content;
    $score = 0;
    $total_checks = 4;

    // Check Title
    $title = get_the_title($post_id);
    if (strlen($title) > 0) {
        $title_check = '✅ Title exists';
        $score++;
    } else {
        $title_check = '❌ Title missing';
    }

    // Check Meta Description (Yoast or others)
    $meta_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    if (!$meta_desc) {
        $meta_desc = get_post_meta($post_id, '_aioseo_description', true); 
    }
    if ($meta_desc) {
        $meta_check = '✅ Meta description exists';
        $score++;
    } else {
        $meta_check = '❌ Meta description missing';
    }

    // Check H1s
    preg_match_all('/<h1[^>]*>.*?<\/h1>/i', $content, $h1s);
    $h1_count = count($h1s[0]);
    if ($h1_count === 1) {
        $h1_check = '✅ One H1 tag found';
        $score++;
    } else {
        $h1_check = "❌ $h1_count H1 tags found (should be exactly 1)";
    }

    // Check image alts
    preg_match_all('/<img[^>]*>/i', $content, $imgs);
    $missing_alts = 0;
    foreach ($imgs[0] as $img) {
        if (!preg_match('/alt=["\'].*?["\']/', $img)) {
            $missing_alts++;
        }
    }
    if ($missing_alts === 0 && count($imgs[0]) > 0) {
        $alt_check = '✅ All images have alt text';
        $score++;
    } else if (count($imgs[0]) === 0) {
        $alt_check = '⚠️ No images found';
        $score++; // Give point if no images
    } else {
        $alt_check = "❌ $missing_alts image(s) missing alt text";
    }

    // Display result
    echo '<div class="notice notice-info"><h3>SEO Audit Lite Results</h3>';
    echo "<p><strong>Score: $score / $total_checks</strong></p><ul style='list-style-type:none;padding-left:0;'>";
    echo "<li>$title_check</li>";
    echo "<li>$meta_check</li>";
    echo "<li>$h1_check</li>";
    echo "<li>$alt_check</li>";
    echo '</ul></div>';
}