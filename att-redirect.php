<?php
/*
Plugin Name: Attachments Redirect Local Images to Live
Description: Redirects local image URLs to the live site.
Version: 1.0
Author: Marko Krstic
*/

// Filters for replacing URLs
add_filter('wp_get_attachment_url', 'replace_attachment_url');
add_filter('wp_calculate_image_srcset', 'replace_srcset_urls', 10, 5);
add_filter('the_content', 'replace_content_urls');

function replace_attachment_url($url)
{
    return replace_local_with_live_url($url);
}

function replace_local_with_live_url($url)
{
    $local_url = trailingslashit(get_home_url());
    $live_url = get_option('live_url', 'https://dplugins.com/'); // Default to 'https://dplugins.com/' if not set

    $new_url = str_replace($local_url, $live_url, $url);
    return $new_url;
}

function replace_srcset_urls($sources, $size_array, $image_src, $image_meta, $attachment_id)
{
    foreach ($sources as $key => $source) {
        $sources[$key]['url'] = replace_local_with_live_url($source['url']);
    }

    return $sources;
}

function replace_content_urls($content)
{
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

    $images = $doc->getElementsByTagName('img');
    foreach ($images as $image) {
        $src = $image->getAttribute('src');
        $image->setAttribute('src', replace_local_with_live_url($src));
    }

    $anchors = $doc->getElementsByTagName('a');
    foreach ($anchors as $anchor) {
        $href = $anchor->getAttribute('href');
        $anchor->setAttribute('href', replace_local_with_live_url($href));
    }

    $content = $doc->saveHTML();
    return $content;
}

// Admin settings page
add_action('admin_menu', 'add_plugin_page');
add_action('admin_init', 'page_init');

function add_plugin_page()
{
    add_management_page(
        'Attachments Redirect',
        'Attachments Redirect',
        'manage_options',
        'redirect-settings',
        'create_admin_page'
    );
}

function create_admin_page()
{
?>
    <div class="wrap">
        <h1>Attachments Redirect</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('redirect_option_group');
            do_settings_sections('redirect-setting-admin');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

function page_init()
{
    register_setting(
        'redirect_option_group',
        'live_url',
        'sanitize'
    );

    add_settings_section(
        'setting_section_id',
        '', // Removed title
        '', // Removed callback
        'redirect-setting-admin'
    );

    add_settings_field(
        'live_url',
        'Live URL',
        'live_url_callback',
        'redirect-setting-admin',
        'setting_section_id'
    );
}

function sanitize($input)
{
    return sanitize_text_field($input);
}

function live_url_callback()
{
    printf(
        '<input type="text" id="live_url" name="live_url" value="%s" style="width:300px;" />',
        esc_attr(get_option('live_url'))
    );
}
?>
