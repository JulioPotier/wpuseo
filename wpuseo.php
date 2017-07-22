<?php

/*
Plugin Name: WPU SEO
Plugin URI: https://github.com/WordPressUtilities/wpuseo
Description: Enhance SEO : Clean title, nice metas.
Version: 1.23.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Contributor: @boiteaweb
*/

class WPUSEO {

    public function init() {

        add_action('init', array(&$this,
            'check_config'
        ));
        add_action('plugins_loaded', array(&$this,
            'load_translation'
        ));

        // Filter
        add_filter('wp_title', array(&$this,
            'wp_title'
        ), 999, 2);
        add_filter('pre_get_document_title', array(&$this,
            'wp_title'
        ), 999, 2);
        add_filter('wp_title_rss', array(&$this,
            'rss_wp_title'
        ), 999, 2);

        // Actions
        add_action('wp_head', array(&$this,
            'add_metas'
        ), 1);
        add_action('wp_head', array(&$this,
            'add_metas_robots'
        ), 1, 0);
        add_action('wp_head', array(&$this,
            'display_google_analytics_code'
        ), 99, 0);
        add_action('wp_head', array(&$this,
            'display_facebook_pixel_code'
        ), 99, 0);

        // Clean WP Head
        add_action('template_redirect', array(&$this,
            'clean_wordpress_head'
        ));

        // Admin boxes
        add_filter('wpu_options_tabs', array(&$this,
            'add_tabs'
        ), 99, 1);
        add_filter('wpu_options_boxes', array(&$this,
            'add_boxes'
        ), 99, 1);
        add_filter('wpu_options_fields', array(&$this,
            'add_fields'
        ), 99, 1);
        add_filter('admin_head', array(&$this,
            'remove_boxes'
        ), 99);

        // User boxes
        add_filter('wpu_usermetas_sections', array(&$this,
            'add_user_sections'
        ), 10, 3);
        add_filter('wpu_usermetas_fields', array(&$this,
            'add_user_fields'
        ), 10, 3);

        // Post box
        add_filter('wputh_post_metas_boxes', array(&$this,
            'post_meta_boxes'
        ), 10, 3);
        add_filter('wputh_post_metas_fields', array(&$this,
            'post_meta_fields'
        ), 10, 3);

        // Taxo fields
        add_filter('wputaxometas_fields', array(&$this,
            'taxo_fields'
        ), 10, 3);

        $this->thumbnail_size = apply_filters('wpuseo_thumbnail_size', 'full');

        $this->enable_twitter_metas = (get_option('wpu_seo_user_twitter_enable') != '0');
        $this->enable_facebook_metas = (get_option('wpu_seo_user_facebook_enable') != '0');
        $this->boxes_pt = apply_filters('wpuseo_post_types', array(
            'product',
            'post',
            'page'
        ));
        $this->twitter_cards = apply_filters('wpuseo_twitter_cards', array(
            'summary' => 'summary',
            'summary_large_image' => 'summary_large_image'
        ));
    }

    public function load_translation() {

        // Load lang
        load_plugin_textdomain('wpuseo', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    /* ----------------------------------------------------------
      Check config
    ---------------------------------------------------------- */

    public function check_config() {

        if (!is_admin()) {
            return;
        }
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Check if WPU Options is active
        if (!is_plugin_active('wpuoptions/wpuoptions.php')) {
            add_action('admin_notices', array(&$this,
                'set_error_missing_wpuoptions'
            ));
        }
    }

    public function set_error_missing_wpuoptions() {
        echo '<div class="error"><p>' . sprintf($this->__('The plugin <b>%s</b> depends on the <b>WPU Options</b> plugin. Please install and activate it.'), 'WPU SEO') . '</p></div>';
    }

    /* ----------------------------------------------------------
      Clean WordPress head
    ---------------------------------------------------------- */

    public function clean_wordpress_head() {
        if (!is_single()) {
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        }
    }

    /* ----------------------------------------------------------
      Metas
    ---------------------------------------------------------- */

    public function post_meta_boxes($boxes) {
        $boxes['wpuseo_box'] = array(
            'name' => $this->__('SEO Details'),
            'post_type' => $this->boxes_pt
        );
        if ($this->enable_twitter_metas) {
            $boxes['wpuseo_box_twitter'] = array(
                'name' => $this->__('SEO Details - Twitter'),
                'post_type' => $this->boxes_pt
            );
        }
        if ($this->enable_facebook_metas) {
            $boxes['wpuseo_box_facebook'] = array(
                'name' => $this->__('SEO Details - Facebook'),
                'post_type' => $this->boxes_pt
            );
        }
        return $boxes;
    }

    /* Meta fields
     -------------------------- */

    public function post_meta_fields($fields) {
        $fields['wpuseo_post_title'] = array(
            'box' => 'wpuseo_box',
            'name' => $this->__('Page title'),
            'lang' => true
        );
        $fields['wpuseo_post_description'] = array(
            'box' => 'wpuseo_box',
            'name' => $this->__('Page description'),
            'type' => 'textarea',
            'lang' => true
        );
        /* Twitter */
        $fields['wpuseo_post_image_twitter'] = array(
            'box' => 'wpuseo_box_twitter',
            'name' => $this->__('Image'),
            'type' => 'image',
            'lang' => true
        );
        $fields['wpuseo_post_title_twitter'] = array(
            'box' => 'wpuseo_box_twitter',
            'name' => $this->__('Page title'),
            'lang' => true
        );
        $fields['wpuseo_post_description_twitter'] = array(
            'box' => 'wpuseo_box_twitter',
            'name' => $this->__('Page description'),
            'type' => 'textarea',
            'lang' => true
        );
        /* Facebook */
        $fields['wpuseo_post_image_facebook'] = array(
            'box' => 'wpuseo_box_facebook',
            'name' => $this->__('Image'),
            'type' => 'attachment',
            'lang' => true
        );
        $fields['wpuseo_post_title_facebook'] = array(
            'box' => 'wpuseo_box_facebook',
            'name' => $this->__('Page title'),
            'lang' => true
        );
        $fields['wpuseo_post_description_facebook'] = array(
            'box' => 'wpuseo_box_facebook',
            'name' => $this->__('Page description'),
            'type' => 'textarea',
            'lang' => true
        );

        global $WPUPostMetas;
        /* Old version of wpupostmetas : use type attachment */
        if (is_object($WPUPostMetas) && isset($WPUPostMetas->version)) {
            $wpupostmetas_version = floatval($WPUPostMetas->version);
            if (is_float($wpupostmetas_version) && $wpupostmetas_version < 0.26) {
                $fields['wpuseo_post_image_twitter']['type'] = 'attachment';
                $fields['wpuseo_post_image_facebook']['type'] = 'attachment';
            }
        }

        return $fields;
    }

    /* Taxo fields
     -------------------------- */

    public function taxo_fields($fields) {
        $taxonomies = apply_filters('wpuseo_taxo_list', array(
            'category',
            'post_tag'
        ));
        $fields['wpuseo_title'] = array(
            'label' => $this->__('SEO Details'),
            'type' => 'title',
            'taxonomies' => $taxonomies
        );
        $fields['wpuseo_taxo_title'] = array(
            'label' => $this->__('Page title'),
            'taxonomies' => $taxonomies,
            'lang' => 1
        );
        $fields['wpuseo_taxo_description'] = array(
            'label' => $this->__('Page description'),
            'taxonomies' => $taxonomies,
            'type' => 'textarea',
            'lang' => 1
        );
        if ($this->enable_twitter_metas) {
            $fields['wpuseo_title_twitter'] = array(
                'label' => $this->__('SEO Details - Twitter'),
                'type' => 'title',
                'taxonomies' => $taxonomies
            );
            $fields['wpuseo_taxo_title_twitter'] = array(
                'label' => $this->__('Twitter:title'),
                'taxonomies' => $taxonomies,
                'lang' => 1
            );
            $fields['wpuseo_taxo_description_twitter'] = array(
                'label' => $this->__('Twitter:description'),
                'type' => 'textarea',
                'taxonomies' => $taxonomies,
                'lang' => 1
            );
            $fields['wpuseo_taxo_image_twitter'] = array(
                'label' => $this->__('Twitter:Image'),
                'type' => 'attachment',
                'taxonomies' => $taxonomies
            );

        }
        if ($this->enable_facebook_metas) {
            $fields['wpuseo_title_facebook'] = array(
                'label' => $this->__('SEO Details - Facebook'),
                'type' => 'title',
                'taxonomies' => $taxonomies
            );
            $fields['wpuseo_taxo_title_facebook'] = array(
                'label' => $this->__('OG:Title'),
                'taxonomies' => $taxonomies,
                'lang' => 1
            );
            $fields['wpuseo_taxo_description_facebook'] = array(
                'label' => $this->__('OG:Description'),
                'type' => 'textarea',
                'taxonomies' => $taxonomies,
                'lang' => 1
            );
            $fields['wpuseo_taxo_image_facebook'] = array(
                'label' => $this->__('Og:Image'),
                'type' => 'attachment',
                'taxonomies' => $taxonomies
            );
        }
        return $fields;
    }

    /* ----------------------------------------------------------
      Admin Options
    ---------------------------------------------------------- */

    public function add_tabs($tabs) {
        $tabs['wpu_seo'] = array(
            'name' => 'Options SEO'
        );
        return $tabs;
    }

    public function add_boxes($boxes) {
        $boxes['wpu_seo'] = array(
            'name' => $this->__('Main'),
            'tab' => 'wpu_seo'
        );
        $boxes['wpu_seo_home'] = array(
            'name' => $this->__('Homepage'),
            'tab' => 'wpu_seo'
        );
        $boxes['wpu_seo_bing'] = array(
            'name' => 'Bing',
            'tab' => 'wpu_seo'
        );
        $boxes['wpu_seo_google'] = array(
            'name' => 'Google',
            'tab' => 'wpu_seo'
        );
        $boxes['wpu_seo_facebook'] = array(
            'name' => 'Facebook',
            'tab' => 'wpu_seo'
        );
        $boxes['wpu_seo_twitter'] = array(
            'name' => 'Twitter',
            'tab' => 'wpu_seo'
        );
        return $boxes;
    }

    public function add_fields($options) {
        $is_multi = $this->is_site_multilingual() !== false;

        // Various
        $options['wpu_home_title_separator'] = array(
            'label' => $this->__('Title separator'),
            'box' => 'wpu_seo'
        );

        // Home
        $options['wpu_home_title_separator_hide_prefix'] = array(
            'label' => $this->__('Hide title prefix'),
            'type' => 'select',
            'box' => 'wpu_seo_home'
        );
        $options['wpu_home_page_title'] = array(
            'label' => $this->__('Page title'),
            'box' => 'wpu_seo_home'
        );
        $options['wpu_home_meta_description'] = array(
            'label' => $this->__('Meta description'),
            'type' => 'textarea',
            'box' => 'wpu_seo_home'
        );
        $options['wpu_home_meta_keywords'] = array(
            'label' => $this->__('Meta keywords'),
            'type' => 'textarea',
            'box' => 'wpu_seo_home'
        );

        // Bing
        $options['wpu_bing_site_verification'] = array(
            'label' => $this->__('Site verification ID'),
            'box' => 'wpu_seo_bing',
            'help' => $this->__('Use the content attribute of the validation meta tag') . ' (&lt;meta name="msvalidate.01" content="THECODE" /&gt;)'
        );

        // Google
        $options['wpu_google_site_verification'] = array(
            'label' => $this->__('Site verification ID'),
            'box' => 'wpu_seo_google',
            'help' => $this->__('Use the content attribute of the validation meta tag') . ' (&lt;meta name="google-site-verification" content="THECODE" /&gt;)'
        );
        $options['wputh_ua_analytics'] = array(
            'label' => $this->__('Google Analytics ID'),
            'box' => 'wpu_seo_google'
        );
        $options['wputh_analytics_enableloggedin'] = array(
            'label' => $this->__('Enable Analytics for logged-in users'),
            'box' => 'wpu_seo_google',
            'type' => 'select'
        );

        // Facebook
        $options['wpu_seo_user_facebook_enable'] = array(
            'label' => $this->__('Enable Facebook metas'),
            'type' => 'select',
            'box' => 'wpu_seo_facebook'
        );
        $options['wputh_fb_admins'] = array(
            'label' => $this->__('FB:Admins ID'),
            'box' => 'wpu_seo_facebook'
        );
        $options['wputh_fb_app'] = array(
            'label' => $this->__('FB:App ID'),
            'box' => 'wpu_seo_facebook'
        );
        $options['wputh_fb_pixel'] = array(
            'label' => $this->__('FB:Pixel ID'),
            'box' => 'wpu_seo_facebook'
        );
        $options['wputh_fb_image'] = array(
            'label' => $this->__('OG:Image'),
            'type' => 'media',
            'box' => 'wpu_seo_facebook'
        );
        $options['wpu_homefb_page_title'] = array(
            'label' => $this->__('OG:Title Home'),
            'box' => 'wpu_seo_facebook'
        );
        $options['wpu_homefb_meta_description'] = array(
            'label' => $this->__('OG:Description Home'),
            'type' => 'textarea',
            'box' => 'wpu_seo_facebook'
        );

        // Twitter
        $options['wpu_seo_user_twitter_enable'] = array(
            'label' => $this->__('Enable Twitter metas'),
            'type' => 'select',
            'box' => 'wpu_seo_twitter'
        );
        $options['wpu_seo_user_twitter_site_username'] = array(
            'label' => $this->__('Twitter site @username'),
            'box' => 'wpu_seo_twitter'
        );
        $options['wpu_seo_user_twitter_account_id'] = array(
            'label' => $this->__('Twitter ads ID'),
            'box' => 'wpu_seo_twitter'
        );
        $options['wputh_twitter_card'] = array(
            'label' => $this->__('Twitter:Card format'),
            'type' => 'select',
            'datas' => $this->twitter_cards,
            'box' => 'wpu_seo_twitter'
        );
        $options['wputh_twitter_image'] = array(
            'label' => $this->__('Twitter:Image'),
            'type' => 'media',
            'box' => 'wpu_seo_twitter'
        );
        $options['wpu_hometwitter_page_title'] = array(
            'label' => $this->__('Title Home'),
            'box' => 'wpu_seo_twitter'
        );
        $options['wpu_hometwitter_meta_description'] = array(
            'label' => $this->__('Description Home'),
            'type' => 'textarea',
            'box' => 'wpu_seo_twitter'
        );

        if ($is_multi) {
            $options['wpu_home_page_title']['lang'] = 1;
            $options['wpu_home_meta_description']['lang'] = 1;
            $options['wpu_home_meta_keywords']['lang'] = 1;
            $options['wpu_homefb_page_title']['lang'] = 1;
            $options['wpu_homefb_meta_description']['lang'] = 1;
            $options['wpu_seo_user_twitter_site_username']['lang'] = 1;
            $options['wpu_hometwitter_page_title']['lang'] = 1;
            $options['wpu_hometwitter_meta_description']['lang'] = 1;
        }

        return $options;
    }

    public function remove_boxes() {
        $screen = get_current_screen();
        /* Only on post page */
        if (!isset($_GET['post']) || !is_object($screen) || $screen->base != 'post' || $screen->id != 'page') {
            return;
        }
        $show_on_front = get_option('show_on_front');
        if ($show_on_front != 'page') {
            return;
        }
        $page_on_front = get_option('page_on_front');
        if ($page_on_front != $_GET['post']) {
            return;
        }
        echo '<style>#wputh_box_wpuseo_box, #wputh_box_wpuseo_box_twitter, #wputh_box_wpuseo_box_facebook{display:none;}</style>';
    }

    /* ----------------------------------------------------------
      User Options
    ---------------------------------------------------------- */

    public function add_user_sections($sections) {
        $sections['wpu-seo'] = array(
            'name' => 'WPU SEO'
        );
        return $sections;
    }

    public function add_user_fields($fields) {
        $fields['wpu_seo_user_google_profile'] = array(
            'name' => 'Google+ URL',
            'section' => 'wpu-seo'
        );
        $fields['wpu_seo_user_twitter_account'] = array(
            'name' => '@TwitterUsername',
            'section' => 'wpu-seo'
        );
        return $fields;
    }

    /* ----------------------------------------------------------
      Taxo metas
    ---------------------------------------------------------- */

    public function get_taxo_meta($type) {
        global $q_config;
        $queried_object = get_queried_object();
        $term_id = $queried_object->term_id;
        $metas = array();
        if (function_exists('get_taxonomy_metas')) {
            $metas = get_taxonomy_metas($term_id);
        }
        $seo_title = '';
        if (is_array($metas)) {
            if (isset($metas['wpuseo_taxo_' . $type])) {
                $seo_title = $metas['wpuseo_taxo_' . $type];
            }
            if (isset($q_config['language']) && isset($metas[$q_config['language'] . '__wpuseo_taxo_' . $type]) && !empty($metas[$q_config['language'] . '__wpuseo_taxo_' . $type])) {
                $seo_title = $metas[$q_config['language'] . '__wpuseo_taxo_' . $type];
            }
        }
        return $seo_title;
    }

    /* ----------------------------------------------------------
      Page Title
    ---------------------------------------------------------- */

    public function rss_wp_title($content, $sep) {
        return $this->wp_title($content, $sep);
    }

    public function wp_title($title, $sep = '|') {

        $wpu_home_title_separator = trim(get_option('wpu_home_title_separator'));
        if (!empty($wpu_home_title_separator)) {
            $sep = htmlentities($wpu_home_title_separator);
        }

        $spaced_sep = ' ' . $sep . ' ';
        $new_title = '';

        // Home : Exception for order
        if (is_home() || is_front_page() || is_feed()) {
            $wpu_title = trim($this->get_option_custom('wpu_home_page_title'));
            if (empty($wpu_title)) {
                $wpu_title = get_bloginfo('description');
            }
            $hide_prefix = get_option('wpu_home_title_separator_hide_prefix');
            if ($hide_prefix != '1') {
                $wpu_title = get_bloginfo('name') . $spaced_sep . $wpu_title;
            }

            return apply_filters('wpuseo_title_after_settings', $wpu_title);
        }

        $new_title = $this->get_displayed_title();

        if (is_singular()) {
            $wpuseo_post_title = trim($this->get_post_meta_custom(get_the_ID(), 'wpuseo_post_title', 1));

            if (!empty($wpuseo_post_title)) {
                $new_title = $wpuseo_post_title;
            }
        }

        if (is_category() || is_tax() || is_tag()) {
            $taxo_meta_title = $this->get_taxo_meta('title');
            if (!empty($taxo_meta_title)) {
                $new_title = $taxo_meta_title;
            }
        }

        // Return new title with site name at the end
        $wpu_title = $new_title . $spaced_sep . get_bloginfo('name');

        return apply_filters('wpuseo_title_after_settings', $wpu_title);
    }

    public function get_displayed_title($prefix = true) {
        global $post;
        $displayed_title = $this->__('404 Error');
        if (is_search()) {
            $displayed_title = sprintf($this->__('Search results for "%s"'), get_search_query());
        }
        if (is_archive()) {
            $displayed_title = $this->__('Archive');
        }
        if (is_tax()) {
            $displayed_title = single_cat_title("", false);
        }
        if (is_tag()) {
            $displayed_title = ($prefix ? $this->__('Tag:') . ' ' : '') . single_tag_title("", false);
        }
        if (is_category()) {
            $displayed_title = ($prefix ? $this->__('Category:') . ' ' : '') . single_cat_title("", false);
        }
        if (is_post_type_archive()) {
            $displayed_title = post_type_archive_title('', false);
        }
        if (is_author()) {
            global $author;
            $author_name = get_query_var('author_name');
            $curauth = !empty($author_name) ? get_user_by('slug', $author_name) : get_userdata(intval($author));
            $displayed_title = $this->__('Author:') . ' ' . $curauth->nickname;
        }
        if (is_year()) {
            $displayed_title = ($prefix ? $this->__('Year:') . ' ' : '') . get_the_time($this->__('Y'));
        }
        if (is_month()) {
            $displayed_title = ($prefix ? $this->__('Month:') . ' ' : '') . get_the_time($this->__('F Y'));
        }
        if (is_day()) {
            $displayed_title = ($prefix ? $this->__('Day:') . ' ' : '') . get_the_time($this->__('F j, Y'));
        }
        if (is_singular()) {
            $displayed_title = get_the_title();
            if (property_exists($post, 'post_password') && !empty($post->post_password)) {
                if (post_password_required()) {
                    $displayed_title = apply_filters('wpuseo_protectedposthidden_title', $displayed_title, $post);
                } else {
                    $displayed_title = apply_filters('wpuseo_protectedpostvisible_title', $displayed_title, $post);
                }
            }
        }
        if (is_404()) {
            $displayed_title = $this->__('404 Error');
        }
        return $displayed_title;
    }

    /* ----------------------------------------------------------
      Meta content & open graph
    ---------------------------------------------------------- */

    public function add_metas() {
        global $post;
        $metas = apply_filters('wpuseo_metas_before_settings', array());
        $metas_json = array();
        $links = array();

        if ($this->enable_facebook_metas) {
            $metas['og_sitename'] = array(
                'property' => 'og:site_name',
                'content' => get_bloginfo('name')
            );

            $metas['og_type'] = array(
                'property' => 'og:type',
                'content' => 'website'
            );
        }

        $og_image = false;
        $themes = wp_get_themes();
        foreach ($themes as $theme) {
            $screenshot = $theme->get_screenshot();
            if ($screenshot) {
                $og_image = $screenshot;
            }
        }

        // Default image
        $og_image = apply_filters('wpuseo_default_image', $og_image);

        // Default Facebook og:image
        if ($this->enable_facebook_metas) {
            $opt_wputh_fb_image = get_option('wputh_fb_image');
            $wputh_fb_image = wp_get_attachment_image_src($opt_wputh_fb_image, $this->thumbnail_size, true);
            if ($opt_wputh_fb_image != false && isset($wputh_fb_image[0])) {
                $og_image = $wputh_fb_image[0];
            }
            $metas['og_image'] = array(
                'property' => 'og:image',
                'content' => $og_image
            );
        }

        // Default Twitter twitter:image
        if ($this->enable_twitter_metas) {
            $opt_wputh_twitter_image = get_option('wputh_twitter_image');
            $wputh_twitter_image = wp_get_attachment_image_src($opt_wputh_twitter_image, $this->thumbnail_size, true);
            if ($opt_wputh_twitter_image != false && isset($wputh_twitter_image[0])) {
                $og_image = $wputh_twitter_image[0];
            }
            $metas['twitter_image'] = array(
                'name' => 'twitter:image',
                'content' => $og_image
            );
        }
        $metas['image'] = array(
            'hidden' => 1,
            'content' => $og_image
        );

        if ($this->enable_twitter_metas) {
            $twitter_card_format = get_option('wputh_twitter_card');
            if (!$twitter_card_format) {
                $twitter_card_format = 'summary';
            }

            /* Twitter : Summary card */
            $metas['twitter_card'] = array(
                'name' => 'twitter:card',
                'content' => $twitter_card_format
            );
        }

        $wpu_seo_user_twitter_site_username = trim($this->get_option_custom('wpu_seo_user_twitter_site_username'));
        if (!empty($wpu_seo_user_twitter_site_username) && $this->testTwitterUsername($wpu_seo_user_twitter_site_username) && $this->enable_twitter_metas) {
            $metas['twitter_site'] = array(
                'name' => 'twitter:site',
                'content' => $wpu_seo_user_twitter_site_username
            );
            $metas['twitter_creator'] = array(
                'name' => 'twitter:creator',
                'content' => $wpu_seo_user_twitter_site_username
            );
        }

        $wpu_seo_user_twitter_account_id = trim(get_option('wpu_seo_user_twitter_account_id'));
        if (!empty($wpu_seo_user_twitter_account_id) && $this->enable_twitter_metas) {
            $metas['twitter_account_id'] = array(
                'property' => 'twitter:account_id',
                'content' => $wpu_seo_user_twitter_account_id
            );
        }

        if (is_category() || is_tax() || is_tag()) {
            $queried_object = get_queried_object();
            $description = $queried_object->description;
            $taxo_meta_title = $this->get_taxo_meta('title');
            if (empty($taxo_meta_title)) {
                $taxo_meta_title = $queried_object->name;
            }
            $taxo_meta_description = $this->get_taxo_meta('description');
            if (!empty($taxo_meta_description)) {
                $description = $taxo_meta_description;
            }
            if (empty($description)) {
                $description = $taxo_meta_title;
            }

            $metas['description'] = array(
                'name' => 'description',
                'content' => $this->prepare_text($description)
            );

            if ($this->enable_twitter_metas) {

                /* Title */
                $twitter_title = trim($this->get_taxo_meta('title_twitter'));
                if (empty($twitter_title)) {
                    $twitter_title = $taxo_meta_title;
                }
                $metas['twitter_title'] = array(
                    'name' => 'twitter:title',
                    'content' => $twitter_title
                );
                /* Description */
                $twitter_description = $this->prepare_text($this->get_taxo_meta('description_twitter'));
                if (empty($twitter_description)) {
                    $twitter_description = $description;
                }
                $metas['twitter_description'] = array(
                    'name' => 'twitter:description',
                    'content' => $twitter_description
                );
                $custom_twitter_image = $this->get_taxo_meta('image_twitter');
                if (is_numeric($custom_twitter_image)) {
                    $thumb_url = wp_get_attachment_image_src($custom_twitter_image, $this->thumbnail_size, true);
                    if (isset($thumb_url[0])) {
                        $metas['twitter_image'] = array(
                            'name' => 'twitter:image',
                            'content' => $thumb_url[0]
                        );
                    }
                }
            }

            if ($this->enable_facebook_metas) {
                $facebook_title = $this->prepare_text($this->get_taxo_meta('title_facebook'));
                if (empty($facebook_title)) {
                    $facebook_title = $taxo_meta_title;
                }
                $metas['og_title'] = array(
                    'property' => 'og:title',
                    'content' => $facebook_title
                );
                /* Description */
                $facebook_description = trim($this->get_taxo_meta('description_facebook'));
                if (empty($facebook_description)) {
                    $facebook_description = $description;
                }
                $metas['og_description'] = array(
                    'property' => 'og:description',
                    'content' => $facebook_description
                );

                $custom_og_image = $this->get_taxo_meta('image_facebook');
                if (is_numeric($custom_og_image)) {
                    $thumb_url = wp_get_attachment_image_src($custom_og_image, $this->thumbnail_size, true);
                    if (isset($thumb_url[0])) {
                        $metas['og_image'] = array(
                            'property' => 'og:image',
                            'content' => $thumb_url[0]
                        );
                    }
                }
            }

        }

        if (!is_home() && !is_front_page() && (is_single() || is_page() || is_singular())) {

            $post_type = get_post_type($post);

            $description = apply_filters('wpuseo_post_description_main', $post->post_excerpt);
            if (empty($description)) {
                $description = $post->post_content;
            }

            $wpuseo_post_description = trim($this->get_post_meta_custom(get_the_ID(), 'wpuseo_post_description', 1));

            if (!empty($wpuseo_post_description)) {
                $description = $wpuseo_post_description;
            }

            $description = $this->prepare_text($description);

            if ($this->enable_twitter_metas) {
                /* Title */
                $twitter_title = trim($this->get_post_meta_custom(get_the_ID(), 'wpuseo_post_title_twitter', 1));
                if (empty($twitter_title)) {
                    $twitter_title = get_the_title();
                }
                $metas['twitter_title'] = array(
                    'name' => 'twitter:title',
                    'content' => $twitter_title
                );
                /* Description */
                $twitter_description = $this->prepare_text($this->get_post_meta_custom(get_the_ID(), 'wpuseo_post_description_twitter', 1));
                if (empty($twitter_description)) {
                    $twitter_description = $description;
                }
                $metas['twitter_description'] = array(
                    'name' => 'twitter:description',
                    'content' => $twitter_description
                );
            }

            if ($this->enable_facebook_metas) {

                $og_type = 'article';
                if ($post_type == 'product') {
                    $og_type = 'product';
                }

                /* Facebook : Open Graph */
                $metas['og_type']['content'] = $og_type;

            }

            // Description
            $metas['description'] = array(
                'name' => 'description',
                'content' => $description
            );

            $keywords = $this->get_post_keywords(get_the_ID());
            if (!empty($keywords)) {
                $keywords_txt = implode(', ', $keywords);
                $metas['keywords'] = array(
                    'name' => 'keywords',
                    'content' => $this->prepare_text($keywords_txt)
                );
            }

            if ($this->enable_facebook_metas) {
                $facebook_title = $this->prepare_text($this->get_post_meta_custom(get_the_ID(), 'wpuseo_post_title_facebook', 1));
                if (empty($facebook_title)) {
                    $facebook_title = get_the_title();
                }
                $metas['og_title'] = array(
                    'property' => 'og:title',
                    'content' => $facebook_title
                );
                /* Description */
                $facebook_description = trim($this->get_post_meta_custom(get_the_ID(), 'wpuseo_post_description_facebook', 1));
                if (empty($facebook_description)) {
                    $facebook_description = $description;
                }
                $metas['og_description'] = array(
                    'property' => 'og:description',
                    'content' => $facebook_description
                );
                $metas['og_url'] = array(
                    'property' => 'og:url',
                    'content' => get_permalink()
                );
            }
            $post_thumbnail_id = apply_filters('wpuseo_post_image_main', get_post_thumbnail_id(), $post);
            $thumb_url = wp_get_attachment_image_src($post_thumbnail_id, $this->thumbnail_size, true);
            if (isset($thumb_url[0])) {
                $metas['image'] = array(
                    'hidden' => 1,
                    'content' => $thumb_url[0]
                );
                if ($this->enable_facebook_metas) {
                    $metas['og_image'] = array(
                        'property' => 'og:image',
                        'content' => $thumb_url[0]
                    );
                }
                if ($this->enable_twitter_metas) {
                    $metas['twitter_image'] = array(
                        'name' => 'twitter:image',
                        'content' => $thumb_url[0]
                    );
                }
            }

            // Product details
            if ($this->enable_facebook_metas && $post_type == 'product' && function_exists('wc_get_product')) {
                $metas['product_price_currency'] = array(
                    'property' => 'product:price:currency',
                    'content' => get_woocommerce_currency()
                );
                $_product = wc_get_product($post->ID);
                if (is_object($_product)) {
                    $metas['product_price_amount'] = array(
                        'property' => 'product:price:amount',
                        'content' => $_product->get_price()
                    );
                }
            }

            // Custom og:image
            if ($this->enable_facebook_metas) {
                $custom_og_image = get_post_meta(get_the_ID(), 'wpuseo_post_image_facebook', 1);
                if (is_numeric($custom_og_image)) {
                    $thumb_url = wp_get_attachment_image_src($custom_og_image, $this->thumbnail_size, true);
                    if (isset($thumb_url[0])) {
                        $metas['og_image'] = array(
                            'property' => 'og:image',
                            'content' => $thumb_url[0]
                        );
                    }
                }
            }

            // Custom twitter:image
            if ($this->enable_twitter_metas) {
                $custom_twitter_image = get_post_meta(get_the_ID(), 'wpuseo_post_image_twitter', 1);
                if (is_numeric($custom_twitter_image)) {
                    $thumb_url = wp_get_attachment_image_src($custom_twitter_image, $this->thumbnail_size, true);
                    if (isset($thumb_url[0])) {
                        $metas['twitter_image'] = array(
                            'name' => 'twitter:image',
                            'content' => $thumb_url[0]
                        );
                    }
                }
            }

            // Author informations
            $wpu_seo_user_google_profile = get_user_meta($post->post_author, 'wpu_seo_user_google_profile', 1);
            if (filter_var($wpu_seo_user_google_profile, FILTER_VALIDATE_URL)) {
                $links['google_author'] = array(
                    'rel' => 'author',
                    'href' => $wpu_seo_user_google_profile
                );
            }

            $wpu_seo_user_twitter_account = get_user_meta($post->post_author, 'wpu_seo_user_twitter_account', 1);
            if (!empty($wpu_seo_user_twitter_account) && preg_match('/^@([A-Za-z0-9_]+)$/', $wpu_seo_user_twitter_account) && $this->enable_twitter_metas) {
                $metas['twitter_creator'] = array(
                    'name' => 'twitter:creator',
                    'content' => $wpu_seo_user_twitter_account
                );
            }
        }

        if (is_home() || is_front_page()) {

            // Main values
            $wpu_description = trim($this->get_option_custom('wpu_home_meta_description'));
            $wpu_keywords = trim($this->get_option_custom('wpu_home_meta_keywords'));

            // Meta description
            $home_meta_description = trim(get_bloginfo('description'));
            if (!empty($wpu_description)) {
                $home_meta_description = $wpu_description;
            }
            $metas['description'] = array(
                'name' => 'description',
                'content' => $this->prepare_text($home_meta_description, 200)
            );

            // Meta keywords
            if (empty($wpu_keywords)) {
                $categories = get_categories();
                if (count($categories) > 1) {
                    $tmp_keywords = array();
                    foreach ($categories as $category) {
                        $tmp_keywords[] = $category->name;
                    }
                    $wpu_keywords = implode(', ', $tmp_keywords);
                }
            }
            if (!empty($wpu_keywords)) {
                $metas['keywords'] = array(
                    'name' => 'keywords',
                    'content' => $this->prepare_text($wpu_keywords, 200)
                );
            }

            // Twitter
            if ($this->enable_twitter_metas) {
                $wpu_hometwitter_page_title = trim($this->get_option_custom('wpu_hometwitter_page_title'));
                if (empty($wpu_hometwitter_page_title)) {
                    $wpu_hometwitter_page_title = get_bloginfo('name');
                }
                $wpu_hometwitter_meta_description = trim($this->get_option_custom('wpu_hometwitter_meta_description'));
                if (empty($wpu_hometwitter_meta_description)) {
                    $wpu_hometwitter_meta_description = $home_meta_description;
                }

                $metas['twitter_title'] = array(
                    'name' => 'twitter:title',
                    'content' => $wpu_hometwitter_page_title
                );
                $metas['twitter_description'] = array(
                    'name' => 'twitter:description',
                    'content' => $this->prepare_text($wpu_hometwitter_meta_description, 200)
                );
            }

            // Facebook
            if ($this->enable_facebook_metas) {
                $homefb_page_title = trim($this->get_option_custom('wpu_homefb_page_title'));
                if (empty($homefb_page_title)) {
                    $homefb_page_title = get_bloginfo('name');
                }
                $homefb_meta_description = trim($this->get_option_custom('wpu_homefb_meta_description'));
                if (empty($homefb_meta_description)) {
                    $homefb_meta_description = $home_meta_description;
                }
                $metas['og_title'] = array(
                    'property' => 'og:title',
                    'content' => $homefb_page_title
                );
                $metas['og_description'] = array(
                    'property' => 'og:description',
                    'content' => $homefb_meta_description
                );
                $metas['og_url'] = array(
                    'property' => 'og:url',
                    'content' => home_url()
                );
            }

            // Metas JSON
            $metas_json["@type"] = "WebSite";
            $metas_json["url"] = site_url();
            $metas_json["name"] = get_bloginfo('name');

        }

        // Bing Site
        $wpu_bing_site_verification = trim(get_option('wpu_bing_site_verification'));
        if (!empty($wpu_bing_site_verification)) {
            $metas['bing_site_verification'] = array(
                'name' => 'msvalidate.01',
                'content' => $wpu_bing_site_verification
            );
        }

        // Google Site
        $wpu_google_site_verification = trim(get_option('wpu_google_site_verification'));
        if (!empty($wpu_google_site_verification)) {
            $metas['google_site_verification'] = array(
                'name' => 'google-site-verification',
                'content' => $wpu_google_site_verification
            );
        }

        // FB Admins
        $wputh_fb_admins = trim(get_option('wputh_fb_admins'));
        if ($this->enable_facebook_metas && !empty($wputh_fb_admins)) {
            $wputh_fb_admins_multiple = explode(',', $wputh_fb_admins);
            foreach ($wputh_fb_admins_multiple as $k => $admin) {
                $metas['fb_admins_' . $k] = array(
                    'property' => 'fb:admins',
                    'content' => trim($admin)
                );
            }
        }

        // FB App
        $wputh_fb_app = trim(get_option('wputh_fb_app'));
        if ($this->enable_facebook_metas && !empty($wputh_fb_app)) {
            $metas['fb_app'] = array(
                'property' => 'fb:app',
                'content' => $wputh_fb_app
            );
        }

        // Metas JSON : Single
        if ((is_single() || is_singular()) && !is_singular('product') && !is_front_page() && !is_home()) {
            $queried_object = get_queried_object();
            $metas_json = array(
                "@type" => "NewsArticle",
                "author" => get_the_author_meta('display_name', $queried_object->post_author),
                "datePublished" => get_the_time('Y-m-d', $queried_object->ID),
                "dateModified" => get_the_modified_time('Y-m-d', $queried_object->ID),
                "mainEntityOfPage" => get_permalink(),
                "url" => get_permalink(),
                "headline" => get_the_title()
            );

            $metas_keywords = '';
            if (isset($metas_json['keywords'])) {
                $metas_keywords = $metas['keywords']['content'];
            }
            $metas_json['keywords'] = apply_filters('wpuseo_metasldjson_single', $metas_keywords);
        }

        $metas = apply_filters('wpuseo_metas_after_settings', $metas);

        echo $this->special_convert_array_html($metas);
        echo $this->special_convert_array_html($links, 'link');

        if (!empty($metas_json)) {
            echo $this->set_metas_ld_json($metas, $metas_json);
        }

    }

    public function set_metas_ld_json($metas, $metas_json) {

        $metas_json = array_merge(array("@context" => "http://schema.org"), $metas_json);

        if (!empty($metas['description'])) {
            $metas_json['description'] = $metas['description']['content'];
        }

        if (isset($metas['image'])) {
            $metas_json['image'] = array(
                "@type" => "ImageObject",
                "url" => $metas['image']['content']
            );
            $image_id = $this->get_att_from_url($metas['image']['content']);
            if (is_numeric($image_id)) {
                $image_obj = wp_get_attachment_image_src($image_id, $this->thumbnail_size);
                if (is_array($image_obj) && $image_obj[0] == $metas['image']['content']) {
                    $metas_json['image']['height'] = $image_obj[1];
                    $metas_json['image']['width'] = $image_obj[2];
                }
            }
        }

        $metas_json['publisher'] = array(
            "@type" => "Organization",
            "name" => get_bloginfo('name'),
            "url" => site_url()
        );

        if (has_header_image()) {
            $header = get_custom_header();
            if (is_object($header) && property_exists($header, 'url')) {
                $metas_json['publisher']['logo'] = array(
                    "@type" => "ImageObject",
                    "url" => $header->url,
                    "width" => $header->width,
                    "height" => $header->height
                );
            }
        }

        $json = str_replace('\/', '/', json_encode($metas_json));

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /* ----------------------------------------------------------
      Robots tag
    ---------------------------------------------------------- */

    public function add_metas_robots() {

        // Disable indexation for archives pages after page 1 OR 404 page OR paginated comments
        if ((is_paged() && (is_category() || is_tag() || is_author() || is_tax())) || is_404() || (is_single() && comments_open() && (int) get_query_var('cpage') > 0)) {
            wp_no_robots();
        }
    }

    /* ----------------------------------------------------------
      Google Analytics
    ---------------------------------------------------------- */

    public function display_google_analytics_code() {
        $ua_analytics = get_option('wputh_ua_analytics');
        // Invalid ID
        if ($ua_analytics === false || empty($ua_analytics) || in_array($ua_analytics, array(
            'UA-XXXXX-X'
        ))) {
            return;
        }

        // Tracked logged in users
        $analytics_enableloggedin = (get_option('wputh_analytics_enableloggedin') == '1');
        if (is_user_logged_in() && !$analytics_enableloggedin) {
            return;
        }
        $hook_ajaxready = apply_filters('wpuseo_ajaxready_hook', '');

        echo '<script type="text/javascript">';
        echo "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');";
        echo "\nga('create','" . $ua_analytics . "','auto');";
        if (is_user_logged_in() && apply_filters('wpuseo_analytics_track_user_id', true)) {
            $user = wp_get_current_user();
            if (is_object($user)) {
                echo "\nga('set','userId','" . $user->ID . "');";
            }
        }
        echo "\nga('set','dimension1','visitorloggedin-" . (is_user_logged_in() ? '1' : '0') . "');";
        echo "\nga('send','pageview');";

        if (!empty($hook_ajaxready)) {
            echo "function wpuseo_callback_ajaxready(){ga('send','pageview');}";
            echo "if (typeof(jQuery) == 'undefined') {document.addEventListener('" . esc_attr($hook_ajaxready) . "',wpuseo_callback_ajaxready,1);}";
            echo "else {jQuery(document).on('" . esc_attr($hook_ajaxready) . "',wpuseo_callback_ajaxready);}";
        }
        echo '</script>';
    }

    /* ----------------------------------------------------------
      Facebook Pixel code
    ---------------------------------------------------------- */

    public function display_facebook_pixel_code() {
        $fb_pixel = get_option('wputh_fb_pixel');
        // Invalid ID
        if (empty($fb_pixel)) {
            return;
        }

        echo '<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');
fbq(\'init\', \'' . $fb_pixel . '\'); // Insert your pixel ID here.
fbq(\'track\', \'PageView\');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . $fb_pixel . '&ev=PageView&noscript=1"/></noscript>
<!-- DO NOT MODIFY -->
<!-- End Facebook Pixel Code -->';
    }

    /* ----------------------------------------------------------
      Get post keywords
    ---------------------------------------------------------- */

    public function get_post_keywords($id) {
        global $post;

        // Keywords
        $keywords_raw = array();

        $title = explode(' ', strtolower(get_the_title($id)));
        foreach ($title as $word) {
            if (strlen($word) > 3) {
                $keywords_raw = $this->check_keywords_value(sanitize_title($word), $word, $keywords_raw);
            }
        }

        $keywords_raw = $this->add_terms_to_keywords(get_the_category($id), $keywords_raw);
        $keywords_raw = $this->add_terms_to_keywords(get_the_tags($id), $keywords_raw);

        // Sort keywords by score
        usort($keywords_raw, array(
            'WPUSEO',
            'order_keywords_values'
        ));

        // Set keywords
        $keywords = array();
        foreach ($keywords_raw as $keyword) {
            $keywords[] = $keyword[1];
        }
        return $keywords;
    }

    public function add_terms_to_keywords($terms, $keywords_raw) {
        if (is_array($terms)) {
            foreach ($terms as $term) {
                $keywords_raw = $this->check_keywords_value($term->slug, $term->name, $keywords_raw);
            }
        }

        return $keywords_raw;
    }

    public function check_keywords_value($slug, $word, $keywords_raw) {
        $word = str_replace(array(
            ','
        ), ' ', $word);
        if (array_key_exists($slug, $keywords_raw)) {
            $keywords_raw[$slug][0]++;
        } else {
            $keywords_raw[$slug] = array(
                1,
                $word
            );
        }
        return $keywords_raw;
    }

    public function order_keywords_values($a, $b) {
        return $a[0] < $b[0];
    }

    /* ----------------------------------------------------------
      Utilities
    ---------------------------------------------------------- */

    /* Translate
     -------------------------- */

    private function __($string) {
        return __($string, 'wpuseo');
    }

    private function get_option_custom($name) {
        $is_multi = $this->is_site_multilingual() !== false;
        $opt = get_option($name);
        if ($is_multi && function_exists('wputh_l18n_get_option')) {
            $opt = wputh_l18n_get_option($name);
        }
        return $opt;
    }

    private function get_post_meta_custom($post_id, $name, $single = 1) {
        $is_multi = $this->is_site_multilingual() !== false;
        $post_meta = trim(get_post_meta($post_id, $name, $single));
        if (function_exists('wputh_l10n_get_post_meta')) {
            $post_meta = trim(wputh_l10n_get_post_meta($post_id, $name, $single));
        }
        return $post_meta;
    }

    /* Get an attachment from image URL
    -------------------------- */

    public function get_att_from_url($image_url) {
        $cache_id = 'wpuseo_att_from_url_' . md5($image_url);

        // GET CACHED VALUE
        $att_id = wp_cache_get($cache_id);
        if ($att_id !== false) {
            return $att_id;
        }

        // COMPUTE RESULT
        $att_id = attachment_url_to_postid($image_url);

        // CACHE RESULT
        wp_cache_set($cache_id, $att_id, '', is_numeric($att_id) ? 3600 : 60);

        return $att_id;

    }

    /* Test a twitter username
     -------------------------- */

    public function testTwitterUsername($username) {
        return preg_match('/^\@([a-zA_Z_0-9]+)$/', $username) !== false;
    }

    /* Prepare meta description
     -------------------------- */

    public function prepare_text($text, $max_length = 200) {
        $text = strip_shortcodes($text);
        $text = strip_tags($text);
        $text = preg_replace("/\s+/", ' ', $text);
        $text = trim($text);
        $words = explode(' ', $text);
        $final_text = '';
        foreach ($words as $word) {
            if ((strlen($final_text) + strlen(' ' . $word)) > $max_length) {
                return trim($final_text) . ' ...';
            } else {
                $final_text .= ' ' . $word;
            }
        }
        return trim($final_text);
    }

    /* Convert an array of metas to HTML
     -------------------------- */

    public function special_convert_array_html($metas, $tag = 'meta') {
        $html = '';
        foreach ($metas as $values) {
            if (isset($values['hidden'])) {
                continue;
            }
            $html .= '<' . $tag;
            foreach ($values as $name => $value) {
                $_value = esc_attr(trim($value));
                $html .= sprintf(' %s="%s"', $name, $_value);
            }
            $html .= ' />' . "\n";
        }
        return $html;
    }

    /* Check if site is multilingual
     -------------------------- */

    public function is_site_multilingual() {
        $is_multi = false;
        if (function_exists('qtrans_getSortedLanguages')) {
            $is_multi = qtrans_getSortedLanguages();
        }
        if (function_exists('qtranxf_getSortedLanguages')) {
            $is_multi = qtranxf_getSortedLanguages();
        }
        return $is_multi;
    }

    /* Get languages
     -------------------------- */

    private function get_languages() {
        global $q_config, $polylang;
        $languages = array();

        // Obtaining from Qtranslate
        if (isset($q_config['enabled_languages'])) {
            foreach ($q_config['enabled_languages'] as $lang) {
                if (!in_array($lang, $languages) && isset($q_config['language_name'][$lang])) {
                    $languages[$lang] = $q_config['language_name'][$lang];
                }
            }
        }

        // Obtaining from Polylang
        if (function_exists('pll_the_languages') && is_object($polylang)) {
            $poly_langs = $polylang->model->get_languages_list();
            foreach ($poly_langs as $lang) {
                $languages[$lang->slug] = $lang->name;
            }
        }
        return $languages;
    }
}

$WPUSEO = new WPUSEO();
$WPUSEO->init();
