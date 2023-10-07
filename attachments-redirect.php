<?php
/*
Plugin Name: Attachments Redirect
Description: Redirects local image URLs to the live site.
Version: 1.0
Author: Marko Krstic
*/

// Filters for replacing URLs
add_filter('wp_get_attachment_url', 'replace_local_with_live_url');
add_filter('wp_calculate_image_srcset', 'replace_srcset_urls', 10, 5);
add_filter('the_content', 'replace_content_urls');

function replace_local_with_live_url($url) {
    $local_url = trailingslashit(get_home_url());
    $live_url = get_option('live_url', 'https://dplugins.com/');
    return str_replace($local_url, $live_url, $url);
}

function replace_srcset_urls($sources) {
    foreach ($sources as $key => $source) {
        $sources[$key]['url'] = replace_local_with_live_url($source['url']);
    }
    return $sources;
}

function replace_content_urls($content) {
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

    $tags = ['img' => 'src', 'a' => 'href'];

    foreach ($tags as $tag => $attribute) {
        $elements = $doc->getElementsByTagName($tag);
        foreach ($elements as $element) {
            $value = $element->getAttribute($attribute);
            $element->setAttribute($attribute, replace_local_with_live_url($value));
        }
    }

    return $doc->saveHTML();
}

// Admin settings page
add_action('admin_menu', 'add_plugin_page');
add_action('admin_init', 'page_init');

function add_plugin_page() {
    add_management_page('Attachments Redirect', 'Attachments Redirect', 'manage_options', 'redirect-settings', 'create_admin_page');
}

function create_admin_page() {
    echo '<div class="wrap">
        <h1>Attachments Redirect</h1>
        <form method="post" action="options.php">';
    settings_fields('redirect_option_group');
    do_settings_sections('redirect-setting-admin');
    submit_button();
    echo '</form></div>';
}

function page_init() {
    register_setting('redirect_option_group', 'live_url', 'sanitize_text_field');
    add_settings_section('setting_section_id', '', '', 'redirect-setting-admin');
    add_settings_field('live_url', 'Live URL', 'live_url_callback', 'redirect-setting-admin', 'setting_section_id');
}

function live_url_callback() {
    printf('<input type="text" id="live_url" name="live_url" value="%s" style="width:300px;" />', esc_attr(get_option('live_url')));
}
