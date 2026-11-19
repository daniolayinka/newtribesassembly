<?php
/** Run from the WordPress root with wp eval-file wp-content/themes/new-tribes-assembly/deployment/import-content.php apply */
if (!defined('WP_CLI') || !WP_CLI) {
    http_response_code(403);
    exit;
}
$apply = ($args[0] ?? '') === 'apply';
$version = 'nta_content_release_20261119';
if (get_option($version)) {
    WP_CLI::success('This content release has already been imported.');
    return;
}
if (!function_exists('carbon_get_post_meta') || !function_exists('carbon_set_post_meta')) {
    WP_CLI::error('Activate the New Tribes Assembly theme before importing.');
}
$page = get_page_by_path('about-us');
$home_id = (int) get_option('page_on_front');
$slides = carbon_get_post_meta($home_id, 'nta_home_slides');
$content = file_get_contents(__DIR__ . '/content/about-us.html');
$copy = json_decode(file_get_contents(__DIR__ . '/content/slides.json'), true, 512, JSON_THROW_ON_ERROR);
if (!$page || !$home_id || !is_array($slides) || count($slides) < count($copy) || !$content) {
    WP_CLI::error('An About Us page and at least two existing homepage slides are required.');
}
WP_CLI::log('Update the About Us page and the first two homepage slides with the reviewed copy.');
WP_CLI::log('Existing slide images and additional slides are retained.');
if (!$apply) {
    WP_CLI::success('Preview complete. Append apply to import this content once.');
    return;
}
$backup_key = $version . '_backup';
if (!get_option($backup_key)) {
    add_option($backup_key, ['about_page_id' => $page->ID, 'about_content' => $page->post_content, 'home_page_id' => $home_id, 'slides' => $slides], '', false);
}
$result = wp_update_post(wp_slash(['ID' => $page->ID, 'post_content' => $content]), true);
if (is_wp_error($result)) {
    WP_CLI::error($result->get_error_message());
}
foreach ($copy as $index => $fields) {
    $slides[$index] = array_merge($slides[$index], $fields);
}
carbon_set_post_meta($home_id, 'nta_home_slides', $slides);
if (carbon_get_post_meta($home_id, 'nta_home_slides') !== $slides) {
    WP_CLI::error('Slide verification failed. The original content is stored in option ' . $backup_key);
}
update_option($version, gmdate('c'), false);
clean_post_cache($page->ID);
clean_post_cache($home_id);
WP_CLI::success('Content imported. Original content is stored in option ' . $backup_key . '. Purge the Breeze cache to display the updates.');
