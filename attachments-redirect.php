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

function replace_local_with_live_url($url)
{
    $local_url = trailingslashit(get_home_url());
    $live_url = get_option('live_url', 'https://dplugins.com/');
    return str_replace($local_url, $live_url, $url);
}

function replace_srcset_urls($sources)
{
    return array_map(function ($source) {
        $source['url'] = replace_local_with_live_url($source['url']);
        return $source;
    }, $sources);
}

function replace_content_urls($content)
{
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

    foreach (['img' => 'src', 'a' => 'href'] as $tag => $attribute) {
        foreach ($doc->getElementsByTagName($tag) as $element) {
            $element->setAttribute($attribute, replace_local_with_live_url($element->getAttribute($attribute)));
        }
    }

    return $doc->saveHTML();
}

// Admin settings page
add_action('admin_menu', function () {
    add_management_page('Attachments Redirect', 'Attachments Redirect', 'manage_options', 'redirect-settings', 'create_admin_page');
});

add_action('admin_init', function () {
    register_setting('redirect_option_group', 'live_url', 'sanitize_text_field');
    add_settings_section('setting_section_id', '', '', 'redirect-setting-admin');
    add_settings_field('live_url', '', 'live_url_callback', 'redirect-setting-admin', 'setting_section_id');
});

function live_url_callback()
{
    // This function is left empty intentionally to avoid the default WordPress table structure.
}

function create_admin_page()
{
?>

    <div class="wrap">
        <h1 class="title">Attachments Redirect</h1>
        <form method="post" action="options.php">
            <?php settings_fields('redirect_option_group'); ?>
            <label for="live_url">Live URL: </label>
            <input type="text" id="live_url" name="live_url" value="<?php echo esc_attr(get_option('live_url')); ?>" style="width:300px;" />
            <?php submit_button(); ?>
        </form>
    </div>

    <style>
        html .title {
            font-weight: 700;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            font-size: 30px;
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.3rem;
        }
    </style>

<?php
}
?>