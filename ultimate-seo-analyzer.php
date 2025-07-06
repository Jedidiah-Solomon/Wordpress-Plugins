<?php
/**
 * Plugin Name: Ultimate SEO Analyzer
 * Description: Basic SEO analysis tool for titles, meta, images, and favicon.
 * Version: 1.0
 * Author: Solomon HDS
 */

class UltimateSEOAnalyzer {
    private $post;
    private $post_id;
    private $content;
    private $meta_tags = [];
    private $images = [];
    private $headings = [];
    private $links = [];
    private $favicon = false;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        $this->check_favicon();
    }

    public function add_admin_menu() {
        add_menu_page(
            'SEO Analyzer',
            'SEO Analyzer',
            'manage_options',
            'ultimate-seo-analyzer',
            [$this, 'render_admin_page'],
            'dashicons-search',
            80
        );
    }

    private function check_favicon() {
        $favicon_path = ABSPATH . 'favicon.ico';
        $this->favicon = file_exists($favicon_path);
    }

    public function render_admin_page() {
        // Inline CSS
        echo '<style>
        /* Main Container */
        .ultimate-seo-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Post Selector */
        .seo-post-selector {
            margin: 20px 0;
            padding: 20px;
            background: #f6f7f7;
            border-radius: 4px;
        }
        
        #seo-post-select {
            min-width: 300px;
            height: 32px;
            padding: 5px;
        }
        
        /* Notices */
        .seo-notice {
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid;
        }
        
        .notice-info {
            border-color: #00a0d2;
            background: #f7fcfe;
        }
        
        .notice-error {
            border-color: #dc3232;
            background: #fcf0f1;
        }
        
        /* Results Container */
        .seo-results {
            margin-top: 30px;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 20px;
            background: #fff;
        }
        
        /* Score Header */
        .seo-score-header {
            margin-bottom: 25px;
        }
        
        .seo-score-header h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .score-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .score-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        /* Details Rows */
        .seo-details {
            font-size: 15px;
            line-height: 1.6;
        }
        
        .seo-detail-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .seo-detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .seo-detail-label {
            font-weight: 600;
            width: 150px;
            flex-shrink: 0;
        }
        
        .seo-detail-value {
            flex-grow: 1;
        }
        
        /* Status Colors */
        .good {
            color: #46b450;
            font-weight: 500;
        }
        
        .warning {
            color: #ffb900;
            font-weight: 500;
        }
        
        .bad {
            color: #dc3232;
            font-weight: 500;
        }
        </style>';

        echo '<div class="ultimate-seo-container">';
        echo '<h1>SEO Analyzer</h1>';

        echo '<form method="get" class="seo-post-selector">';
        echo '<input type="hidden" name="page" value="ultimate-seo-analyzer">';
        echo '<label for="post">Select a Post:</label> ';
        echo '<select name="post" id="seo-post-select" onchange="this.form.submit()">';

        $posts = get_posts(['numberposts' => 50, 'post_type' => ['post', 'page']]);
        echo '<option value="">-- Select a post --</option>';
        foreach ($posts as $p) {
            $selected = (isset($_GET['post']) && $_GET['post'] == $p->ID) ? 'selected' : '';
            echo '<option value="'.$p->ID.'" '.$selected.'>'.esc_html($p->post_title).'</option>';
        }

        echo '</select>';
        echo '</form>';

        if (isset($_GET['post']) && !empty($_GET['post'])) {
            $this->post_id = intval($_GET['post']);
            $this->post = get_post($this->post_id);
            if ($this->post) {
                $this->content = $this->post->post_content;
                $this->analyze_all();
                $this->display_results();
            } else {
                echo '<div class="seo-notice notice-error">Post not found!</div>';
            }
        } else {
            echo '<div class="seo-notice notice-info">Select a post to analyze its SEO elements</div>';
        }

        echo '</div>';
    }

    private function analyze_all() {
        $this->analyze_meta_tags();
        $this->analyze_title();
        $this->analyze_images();
        $this->analyze_headings();
        $this->analyze_links();
    }

    private function analyze_meta_tags() {
        $desc = $this->get_meta_description();
        $this->meta_tags['description'] = [
            'value' => $desc,
            'present' => !empty($desc),
            'length' => strlen($desc),
            'optimal' => '120–160 chars'
        ];
    }

    private function analyze_title() {
        $title = get_the_title($this->post_id);
        $this->meta_tags['title'] = [
            'value' => $title,
            'length' => strlen($title),
            'optimal' => '30–65 chars',
            'present' => strlen($title) > 0
        ];
    }

    private function analyze_images() {
        preg_match_all('/<img[^>]+>/i', $this->content, $matches);
        $this->images = [
            'total' => count($matches[0]),
            'with_alt' => 0,
            'with_empty_alt' => 0,
            'missing_alt' => 0
        ];

        foreach ($matches[0] as $img) {
            if (preg_match('/alt=["\']([^"\']*)["\']/', $img, $alt)) {
                if ($alt[1] === '') {
                    $this->images['with_empty_alt']++;
                } else {
                    $this->images['with_alt']++;
                }
            } else {
                $this->images['missing_alt']++;
            }
        }
    }

    private function analyze_headings() {
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $this->content, $matches);
        $this->headings = array_fill_keys(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], 0);
        foreach ($matches[1] as $level) {
            $this->headings['h'.$level]++;
        }
    }

    private function analyze_links() {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $this->content, $matches);
        $this->links = [
            'total' => count($matches[0]),
            'internal' => 0,
            'external' => 0
        ];
        foreach ($matches[1] as $href) {
            if (strpos($href, home_url()) === 0 || strpos($href, '/') === 0) {
                $this->links['internal']++;
            } else {
                $this->links['external']++;
            }
        }
    }

    private function get_meta_description() {
        $desc = get_post_field('post_excerpt', $this->post_id);
        if (empty($desc)) {
            $desc = substr(strip_tags($this->content), 0, 160);
        }
        return $desc ?: '';
    }

    private function calculate_score() {
        $score = 100;

        // Title (30 points)
        $title = $this->meta_tags['title'];
        if (!$title['present']) $score -= 30;
        elseif ($title['length'] < 30 || $title['length'] > 65) $score -= 15;

        // Meta Description (20 points)
        $desc = $this->meta_tags['description'];
        if (!$desc['present']) $score -= 20;
        elseif ($desc['length'] < 120 || $desc['length'] > 160) $score -= 10;

        // Images (20 points)
        if ($this->images['total'] > 0) {
            $missing = $this->images['missing_alt'] + $this->images['with_empty_alt'];
            $score -= round(($missing / $this->images['total']) * 20);
        }

        // Headings (15 points)
        if ($this->headings['h1'] !== 1) $score -= 10;
        if ($this->headings['h2'] < 2) $score -= 5;

        // Favicon (15 points)
        if (!$this->favicon) $score -= 15;

        return max(0, $score);
    }

    private function get_score_color($score) {
        if ($score >= 80) return '#46b450';
        if ($score >= 50) return '#ffb900';
        return '#dc3232';
    }

    private function display_results() {
        $score = $this->calculate_score();
        $color = $this->get_score_color($score);

        echo '<div class="seo-results">';
        echo '<div class="seo-score-header">';
        echo '<h2>SEO Score: <span style="color:'.$color.'">'.$score.' / 100</span></h2>';
        echo '<div class="score-bar"><div class="score-fill" style="width:'.$score.'%;background:'.$color.';"></div></div>';
        echo '</div>';

        echo '<div class="seo-details">';
        
        // Title
        $title = $this->meta_tags['title'];
        echo '<div class="seo-detail-row">';
        echo '<div class="seo-detail-label">Title:</div>';
        echo '<div class="seo-detail-value '.($title['present'] ? 'good' : 'bad').'">';
        echo $title['present'] ? esc_html($title['value']) : 'Missing';
        echo ' ('.$title['length'].' chars, optimal: '.$title['optimal'].')';
        echo '</div>';
        echo '</div>';

        // Meta Description
        $desc = $this->meta_tags['description'];
        echo '<div class="seo-detail-row">';
        echo '<div class="seo-detail-label">Meta Description:</div>';
        echo '<div class="seo-detail-value '.($desc['present'] ? 'good' : 'bad').'">';
        echo $desc['present'] ? esc_html(substr($desc['value'], 0, 100)).(strlen($desc['value']) > 100 ? '...' : '') : 'Missing';
        echo ' ('.$desc['length'].' chars, optimal: '.$desc['optimal'].')';
        echo '</div>';
        echo '</div>';

        // Images
        echo '<div class="seo-detail-row">';
        echo '<div class="seo-detail-label">Images:</div>';
        echo '<div class="seo-detail-value">';
        echo $this->images['total'].' total, ';
        echo '<span class="good">'.$this->images['with_alt'].' with alt</span>, ';
        echo '<span class="warning">'.$this->images['with_empty_alt'].' empty alt</span>, ';
        echo '<span class="bad">'.$this->images['missing_alt'].' missing alt</span>';
        echo '</div>';
        echo '</div>';

        // Headings
        echo '<div class="seo-detail-row">';
        echo '<div class="seo-detail-label">Headings:</div>';
        echo '<div class="seo-detail-value">';
        foreach ($this->headings as $tag => $count) {
            if ($count > 0) {
                echo strtoupper($tag).': '.$count.' ';
            }
        }
        echo '</div>';
        echo '</div>';

        // Links
        echo '<div class="seo-detail-row">';
        echo '<div class="seo-detail-label">Links:</div>';
        echo '<div class="seo-detail-value">';
        echo $this->links['total'].' total, ';
        echo $this->links['internal'].' internal, ';
        echo $this->links['external'].' external';
        echo '</div>';
        echo '</div>';

        // Favicon
        echo '<div class="seo-detail-row">';
        echo '<div class="seo-detail-label">Favicon:</div>';
        echo '<div class="seo-detail-value '.($this->favicon ? 'good' : 'bad').'">';
        echo $this->favicon ? '✔ Present' : '✖ Missing';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .seo-details
        echo '</div>'; // .seo-results
    }
}

new UltimateSEOAnalyzer();