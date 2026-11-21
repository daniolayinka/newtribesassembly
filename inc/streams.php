<?php
/** Scheduled YouTube gatherings. API credentials never leave the server. */
add_action('init', function () {
    register_post_type('nta_stream', [
        'labels' => ['name' => 'Streams', 'singular_name' => 'Stream', 'add_new_item' => 'Add Stream', 'edit_item' => 'Edit Stream', 'enter_title_here' => 'Stream title'],
        'public' => false, 'show_ui' => true, 'show_in_rest' => true,
        'menu_icon' => 'dashicons-video-alt3', 'supports' => ['title', 'thumbnail', 'revisions'],
    ]);
});
add_action('carbon_fields_register_fields', function () {
    \Carbon_Fields\Container::make('post_meta', 'Stream schedule')->where('post_type', '=', 'nta_stream')->add_fields([
        \Carbon_Fields\Field::make('html', 'nta_stream_guide')->set_html('<p>Add the <strong>Stream title</strong>, featured image, and scheduled time, then publish this entry now. Start your public broadcast from OBS; the website detects your channel automatically. You do not need to schedule it in YouTube or paste a new video link each time. The date below controls the countdown, not the WordPress Publish date. Times use <strong>the timezone selected below</strong>.</p>'),
        \Carbon_Fields\Field::make('text', 'nta_stream_url', 'YouTube video override (optional)')->set_attribute('type', 'url')->set_help_text('Leave empty to detect the channel set in Meeting Settings. For a specific broadcast, paste its video link here. This override also supports unlisted broadcasts when embedding is enabled.'),
        \Carbon_Fields\Field::make('select', 'nta_stream_timezone', 'Schedule timezone')->set_options(['Africa/Lagos' => 'West Africa Time (Africa/Lagos, UTC+1)', 'UTC' => 'UTC'])->set_default_value('Africa/Lagos'),
        \Carbon_Fields\Field::make('date_time', 'nta_stream_start', 'Scheduled start date and time')->set_required(true)->set_storage_format('Y-m-d H:i:s'),
    ]);
});
function nta_stream_video_id($url) {
    $host = strtolower(wp_parse_url($url, PHP_URL_HOST) ?: '');
    $path = wp_parse_url($url, PHP_URL_PATH) ?: '';
    $id = '';
    if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
        parse_str(wp_parse_url($url, PHP_URL_QUERY) ?: '', $query);
        $id = $query['v'] ?? '';
        if (!$id && preg_match('~^/(?:live|embed)/([^/]+)~', $path, $match)) $id = $match[1];
    } elseif ($host === 'youtu.be') {
        $id = trim($path, '/');
    }
    return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : '';
}
function nta_stream_timestamp($value, $timezone = '') {
    try { $zone = $timezone ? new DateTimeZone($timezone) : wp_timezone(); } catch (Exception $e) { return 0; }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $value, $zone);
    $errors = DateTimeImmutable::getLastErrors();
    return $date && (!$errors || (!$errors['warning_count'] && !$errors['error_count'])) ? $date->getTimestamp() : 0;
}
function nta_stream_api_key() {
    return defined('NTA_YOUTUBE_API_KEY') ? NTA_YOUTUBE_API_KEY : nta_theme_option('nta_youtube_api_key');
}
function nta_stream_youtube_state($video_id, $refresh = false) {
    $key = nta_stream_api_key();
    if (!$key || !$video_id) return 'unknown';
    $cache_key = 'nta_yt_' . md5($video_id . $key);
    if ($refresh) delete_transient($cache_key);
    $cached = get_transient($cache_key);
    if (is_string($cached)) return $cached;
    $data = nta_stream_api_request('videos', ['part' => 'snippet,liveStreamingDetails,status', 'id' => $video_id]);
    $state = 'unknown';
    if (is_array($data)) {
        $item = $data['items'][0] ?? [];
        $live = $item['liveStreamingDetails'] ?? [];
        if (!empty($live['actualEndTime'])) $state = 'ended';
        elseif (!empty($item['status']['embeddable']) && ($item['snippet']['liveBroadcastContent'] ?? '') === 'live' && !empty($live['actualStartTime'])) $state = 'live';
        elseif (($item['snippet']['liveBroadcastContent'] ?? '') === 'upcoming') $state = 'upcoming';
    }
    set_transient($cache_key, $state, $state === 'ended' ? 5 * MINUTE_IN_SECONDS : 45);
    return $state;
}
function nta_stream_payload($refresh = false) {
    $now = time();
    $events = [];
    foreach (get_posts(['post_type' => 'nta_stream', 'post_status' => 'publish', 'numberposts' => -1]) as $post) {
        $start = nta_stream_timestamp(nta_meta('nta_stream_start', $post->ID), nta_meta('nta_stream_timezone', $post->ID) ?: wp_timezone_string());
        $video = nta_stream_video_id(nta_meta('nta_stream_url', $post->ID));
        if (!$start || $post->post_password) continue;
        $events[] = ['id' => $post->ID, 'title' => $post->post_title, 'start' => $start, 'video' => $video, 'thumbnail' => get_the_post_thumbnail_url($post->ID, 'full') ?: ''];
    }
    usort($events, static fn($a, $b) => $a['start'] <=> $b['start']);
    $past = null; $future = null;
    foreach ($events as $event) {
        if ($event['start'] <= $now) $past = $event;
        elseif (!$future) $future = $event;
    }
    // Keep the current gathering through its service window, including its ended message.
    $event = $past && (!$future || $now - $past['start'] < 12 * HOUR_IN_SECONDS) ? $past : $future;
    if (!$event) return ['state' => 'empty', 'serverTime' => $now];
    $channel = nta_theme_option('nta_youtube_channel', 'https://www.youtube.com/@NewtribesAssembly');
    $channel_hash = md5($channel);
    if (!$event['video'] && get_post_meta($event['id'], '_nta_stream_detected_channel', true) === $channel_hash) {
        $event['video'] = get_post_meta($event['id'], '_nta_stream_detected_video', true);
    }
    // Search only around this entry's service window; cached once per channel for all visitors.
    if (!$event['video'] && $now >= $event['start'] - 30 * MINUTE_IN_SECONDS && $now <= $event['start'] + 4 * HOUR_IN_SECONDS) {
        $detected = nta_stream_discover_live($channel, $refresh);
        if ($detected) {
            $event['video'] = $detected;
            update_post_meta($event['id'], '_nta_stream_detected_video', $detected);
            update_post_meta($event['id'], '_nta_stream_detected_channel', $channel_hash);
        }
    }
    $youtube = $event['video'] && get_post_meta($event['id'], '_nta_stream_ended_video', true) === $event['video'] ? 'ended' : nta_stream_youtube_state($event['video'], $refresh);
    if ($youtube === 'ended') update_post_meta($event['id'], '_nta_stream_ended_video', $event['video']);
    $event['state'] = in_array($youtube, ['live', 'ended'], true) ? $youtube : ($event['start'] > $now ? 'countdown' : 'waiting');
    $event['confirmed'] = $youtube !== 'unknown';
    $event['serverTime'] = $now;
    return $event;
}
add_action('rest_api_init', function () {
    register_rest_route('nta/v1', '/stream', ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function () {
        $response = new WP_REST_Response(nta_stream_payload());
        $response->header('Cache-Control', 'no-store, max-age=0');
        return $response;
    }]);
});
add_action('wp_enqueue_scripts', function () {
    if (is_page('meetings') || is_page_template('page-templates/meetings.php')) {
        wp_enqueue_script('nta-stream', get_theme_file_uri('assets/streams.js'), [], filemtime(get_theme_file_path('assets/streams.js')), true);
    }
});
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (($screen->post_type ?? '') === 'nta_stream' && !nta_stream_api_key()) {
        echo '<div class="notice notice-warning"><p>Automatic YouTube detection needs an API key. Add it under <a href="' . esc_url(admin_url('admin.php?page=crb_carbon_fields_container_meeting_settings.php')) . '">Meeting Settings</a>. Until connected, published streams show the countdown and Starting soon state.</p></div>';
    }
});

add_filter('enter_title_here', function ($placeholder, $post) {
    return $post->post_type === 'nta_stream' ? 'Stream title' : $placeholder;
}, 10, 2);

function nta_stream_api_request($endpoint, $parameters) {
    if (!in_array($endpoint, ['channels', 'search', 'videos'], true) || !nta_stream_api_key()) return false;
    $parameters['key'] = nta_stream_api_key();
    $response = wp_remote_get(add_query_arg($parameters, 'https://www.googleapis.com/youtube/v3/' . $endpoint), ['timeout' => 7, 'redirection' => 0]);
    if (is_wp_error($response)) {
        set_transient('nta_youtube_connection_error', 'Network connection failed', 5 * MINUTE_IN_SECONDS);
        return false;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (wp_remote_retrieve_response_code($response) !== 200 || !is_array($data)) {
        $reason = $data['error']['errors'][0]['reason'] ?? $data['error']['status'] ?? 'API request failed';
        set_transient('nta_youtube_connection_error', sanitize_text_field($reason), 5 * MINUTE_IN_SECONDS);
        return false;
    }
    delete_transient('nta_youtube_connection_error');
    return $data;
}
function nta_stream_channel_id($input, $refresh = false) {
    $input = trim($input);
    if (preg_match('/^UC[A-Za-z0-9_-]{22}$/D', $input)) return $input;
    if (str_contains($input, '://')) {
        if (!in_array(strtolower(wp_parse_url($input, PHP_URL_HOST) ?: ''), ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) return '';
        $path = trim(wp_parse_url($input, PHP_URL_PATH) ?: '', '/');
        if (preg_match('~^channel/(UC[A-Za-z0-9_-]{22})(?:/|$)~', $path, $match)) return $match[1];
        $input = explode('/', $path)[0];
    }
    if (!preg_match('/^@[A-Za-z0-9_.-]{3,30}$/D', $input) || !nta_stream_api_key()) return '';
    $cache = 'nta_channel_' . md5($input . nta_stream_api_key());
    if ($refresh) delete_transient($cache);
    $cached = get_transient($cache);
    if (is_string($cached)) return $cached;
    $data = nta_stream_api_request('channels', ['part' => 'id', 'forHandle' => $input]);
    $id = $data['items'][0]['id'] ?? '';
    $id = preg_match('/^UC[A-Za-z0-9_-]{22}$/D', $id) ? $id : '';
    set_transient($cache, $id, $id ? DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
    return $id;
}
function nta_stream_discover_live($channel, $refresh = false) {
    $channel_id = nta_stream_channel_id($channel, $refresh);
    if (!$channel_id || !nta_stream_api_key()) return '';
    $cache = 'nta_discovery_' . md5($channel_id . nta_stream_api_key());
    if ($refresh) delete_transient($cache);
    $cached = get_transient($cache);
    if (is_string($cached)) return $cached && nta_stream_youtube_state($cached) === 'live' ? $cached : '';
    // A cross-request lock avoids duplicate searches from simultaneous page visits.
    $lock = $cache . '_lock';
    if (get_transient($lock)) return '';
    set_transient($lock, 1, 15);
    if (!nta_stream_search_budget()) { delete_transient($lock); return ''; }
    $data = nta_stream_api_request('search', ['part' => 'snippet', 'channelId' => $channel_id, 'eventType' => 'live', 'type' => 'video', 'videoEmbeddable' => 'true', 'order' => 'date', 'maxResults' => 5]);
    $video = '';
    foreach (($data['items'] ?? []) as $item) {
        $candidate = $item['id']['videoId'] ?? '';
        if (preg_match('/^[A-Za-z0-9_-]{11}$/D', $candidate) && nta_stream_youtube_state($candidate, $refresh) === 'live') { $video = $candidate; break; }
    }
    set_transient($cache, $video, 3 * MINUTE_IN_SECONDS);
    delete_transient($lock);
    return $video;
}
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (($screen->post_type ?? '') !== 'nta_stream' && ($screen->id ?? '') !== 'toplevel_page_crb_carbon_fields_container_meeting_settings.php') return;
    $error = get_transient('nta_youtube_connection_error');
    if ($error) echo '<div class="notice notice-error"><p>YouTube connection: ' . esc_html($error) . '. Check the API key, enabled API, quota, and allowed outbound IP in Google Cloud.</p></div>';
});

/** Shared daily ceiling: manual clicks cannot exhaust the channel-search quota. */
function nta_stream_search_budget() {
    $lock = 'nta_search_budget_lock';
    if ((int) get_option($lock) < time() - 20) delete_option($lock);
    if (!add_option($lock, time(), '', false)) return false;
    try {
        $day = wp_date('Y-m-d', time(), new DateTimeZone('America/Los_Angeles'));
        $budget = get_option('nta_search_budget', []);
        if (($budget['day'] ?? '') !== $day) $budget = ['day' => $day, 'count' => 0];
        if ($budget['count'] >= 90) {
            set_transient('nta_youtube_connection_error', 'Daily channel-discovery limit reached', HOUR_IN_SECONDS);
            return false;
        }
        $budget['count']++;
        update_option('nta_search_budget', $budget, false);
        return true;
    } finally { delete_option($lock); }
}
add_action('rest_api_init', function () {
    register_rest_route('nta/v1', '/stream/refresh', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function () {
        // A single shared refresh per minute, regardless of visitor count.
        $lock = 'nta_stream_refresh_lock';
        if ((int) get_option($lock) < time() - 60) delete_option($lock);
        $fresh = add_option($lock, time(), '', false);
        $data = nta_stream_payload($fresh);
        $data['refreshNotice'] = !$fresh ? 'A recent check is already available. Please wait a minute before checking again.' : ($data['state'] === 'live' ? 'Live broadcast found. Connecting…' : ($data['state'] === 'empty' ? 'No stream is scheduled yet. Please see the meeting details.' : ($data['state'] === 'ended' ? 'This gathering has ended. Explore the messages below.' : 'No live broadcast detected yet. We’ll keep checking automatically.')));
        if ($fresh && get_transient('nta_youtube_connection_error')) $data['refreshNotice'] = 'YouTube could not be checked right now. Please try again shortly.';
        $response = new WP_REST_Response($data);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        return $response;
    }]);
});
add_action('template_redirect', function () {
    if (is_page('meetings') || is_page_template('page-templates/meetings.php')) {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
}, 0);
add_filter('breeze_filter_js_exclude', function ($scripts) { $scripts[] = 'assets/streams.js'; return $scripts; });
