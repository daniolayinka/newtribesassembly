<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;
require_once get_theme_file_path('vendor/autoload.php');
add_action('after_setup_theme', function () { add_theme_support('title-tag'); add_theme_support('post-thumbnails'); register_nav_menus(['primary' => 'Primary menu']); });
add_action('after_setup_theme', function () { \Carbon_Fields\Carbon_Fields::boot(); }, 20);
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('nta', get_stylesheet_uri(), [], '1.0.2');
  wp_enqueue_style('nta-tailwind', get_theme_file_uri('assets/tailwind.css'), [], filemtime(get_theme_file_path('assets/tailwind.css')));
  wp_enqueue_style('nta-aos', get_theme_file_uri('assets/interactions.css'), [], filemtime(get_theme_file_path('assets/interactions.css')));
  wp_enqueue_style('nta-header-overrides', get_theme_file_uri('assets/header-overrides.css'), [], filemtime(get_theme_file_path('assets/header-overrides.css')));
  wp_enqueue_style('nta-home-overrides', get_theme_file_uri('assets/home-overrides.css'), [], filemtime(get_theme_file_path('assets/home-overrides.css')));
  if (is_page('resources') || is_page_template('page-templates/resources.php')) {
    wp_enqueue_style('nta-resources', get_theme_file_uri('assets/resources.css'), [], filemtime(get_theme_file_path('assets/resources.css')));
  }
  wp_enqueue_style('nta-experience', get_theme_file_uri('assets/experience.css'), [], filemtime(get_theme_file_path('assets/experience.css')));
  if (is_page('about-us') || is_page_template('page-templates/about-us.php')) {
    wp_enqueue_style('dashicons');
    wp_enqueue_script('nta-about-motion', get_theme_file_uri('assets/about-motion.js'), [], filemtime(get_theme_file_path('assets/about-motion.js')), true);
  }
  wp_enqueue_script('nta-interactions', get_theme_file_uri('assets/interactions.js'), [], filemtime(get_theme_file_path('assets/interactions.js')), true);
});
function nta_register_media_type($type, $plural, $singular, $slug) { register_post_type($type, ['labels'=>['name'=>$plural,'singular_name'=>$singular,'add_new_item'=>"Add $singular",'edit_item'=>"Edit $singular"], 'public'=>true, 'show_in_menu'=>!in_array($type, ['nta_message','nta_video','nta_audio'], true), 'show_in_admin_bar'=>!in_array($type, ['nta_message','nta_video','nta_audio'], true), 'show_in_rest'=>true, 'has_archive'=>$slug, 'rewrite'=>['slug'=>$slug,'with_front'=>false], 'menu_icon'=>'dashicons-media-document', 'supports'=>['title','editor','excerpt','thumbnail','revisions']]); }
add_action('init', function () { nta_register_media_type('nta_download','Downloads','Download','downloads'); nta_register_media_type('nta_message','Messages','Message','messages'); nta_register_media_type('nta_video','Videos','Video','videos'); nta_register_media_type('nta_audio','Audio','Audio item','audio'); });
add_action('carbon_fields_register_fields', function () {
  $menu_options=[]; foreach (wp_get_nav_menus() as $menu) { $menu_options[$menu->term_id]=$menu->name; }
  Container::make('theme_options','Header Settings')->set_page_menu_title('Header Settings')->add_fields([
    Field::make('image','nta_logo','Header logo')->set_value_type('id'),
    Field::make('select','nta_header_menu','Header menu')->set_options($menu_options),
    Field::make('text','nta_header_cta_label','Header button label')->set_default_value('Join Us'),
    Field::make('text','nta_header_cta_url','Header button URL')->set_attribute('type','url')->set_default_value('/meetings/#sunday-worship'),
  ]);
  Container::make('theme_options', 'Meeting Settings')->add_fields([
    Field::make('text', 'nta_youtube_api_key', 'YouTube Data API key')->set_attribute('type', 'password')->set_help_text('Enable YouTube Data API v3 in Google Cloud. Restrict this key to that API and your server IP. Used only on the server to detect published streams. Create broadcasts under Streams → Add Stream.'),
    Field::make('text', 'nta_youtube_channel', 'YouTube channel')->set_default_value('https://www.youtube.com/@NewtribesAssembly')->set_help_text('Set once: paste the channel URL, @handle, or UC channel ID. Streams with no video override detect this channel’s public live broadcast near their scheduled start.'),
    Field::make('complex', 'nta_meetings', 'Meeting listings')
      ->setup_labels(['singular_name' => 'Meeting', 'plural_name' => 'Meetings'])
      ->set_help_text('Add a meeting, then add its sessions and times. Drag entries to change the display order.')
      ->set_default_value(nta_default_meetings())
      ->add_fields([
        Field::make('text', 'title', 'Meeting name')->set_required(true),
        Field::make('text', 'day', 'Day or schedule')->set_help_text('For example, Every Sunday or First Saturday of each month.'),
        Field::make('textarea', 'description', 'Introduction'),
        Field::make('text', 'location', 'Venue or online location'),
        Field::make('complex', 'sessions', 'Sessions')
          ->setup_labels(['singular_name' => 'Session', 'plural_name' => 'Sessions'])
          ->add_fields([
          Field::make('text', 'name', 'Session name')->set_required(true),
          Field::make('text', 'time', 'Time')->set_required(true)->set_help_text('Include AM or PM, and a timezone if needed.'),
        ])->set_header_template('<%- name %> · <%- time %>'),
        Field::make('text', 'url', 'Meeting link')->set_attribute('type', 'url')->set_help_text('The joining URL for this meeting, such as its YouTube, Zoom, or other online meeting link.'),
        Field::make('text', 'button_label', 'Joining button text')->set_default_value('Join this meeting'),
      ])->set_header_template('<%- title %>'),
    Field::make('text', 'nta_contact_email', 'Visitor and people group contact email')->set_attribute('type', 'email'),
  ]);
  Container::make('theme_options','Leadership Settings')->set_page_menu_title('Leadership Settings')->add_fields([
    Field::make('image','nta_pastor_image','Pastor image')->set_value_type('id'),
  ]);
  $homepage_fields = Container::make('post_meta','Homepage imagery')->where('post_template','=','page-templates/homepage.php');
  $front_page_id = (int) get_option('page_on_front');
  if ($front_page_id) {
    $homepage_fields->or_where('post_id','=',$front_page_id);
  }
  $homepage_fields->add_fields([
    Field::make('complex','nta_home_slides','Homepage slider')
      ->setup_labels(['singular_name' => 'Slide', 'plural_name' => 'Slides'])
      ->set_help_text('Click Add Slide to create a slide. Only the image is required. Leave text and buttons empty for an image without an overlay. Use the trash icon to remove a slide, or drag its header to change the order.')
      ->add_fields([
      Field::make('image','image','Slide image')->set_value_type('id')->set_required(true),
      Field::make('text','eyebrow','Introductory label'),
      Field::make('text','heading','Main heading'),
      Field::make('textarea','body','Supporting text'),
      Field::make('text','primary_label','First button label'),
      Field::make('text','primary_url','First button link')->set_help_text('Use a page path such as /about-us/ or a full https:// URL.'),
      Field::make('text','secondary_label','Second button label'),
      Field::make('text','secondary_url','Second button link')->set_help_text('Leave the button text and link empty to hide this button.'),
    ])->set_header_template('<%- heading %>'),
    Field::make('image','nta_home_leadership_image','Leadership image')->set_value_type('id')->set_help_text('Upload the portrait shown beside the Senior Elder’s introduction. It displays in a 4:5 frame.'),
  ]);
  $content = [Field::make('date','nta_date','Message date'),Field::make('text','nta_speaker','Speaker'),Field::make('text','nta_series','Series'),Field::make('complex','nta_scriptures','Scripture references')->add_fields([Field::make('text','reference','Reference')]),Field::make('rich_text','nta_summary','Summary')];
  Container::make('post_meta','Download sections')->where('post_type','=','nta_download')
    ->add_tab('Message details', array_merge([Field::make('html','nta_download_editor_guide')->set_html('<p><strong>Write the message in the Gutenberg editor above.</strong> Use Heading, Paragraph, List, and Quote blocks. Add optional media and a PDF below; empty sections stay hidden.</p>')], array_slice($content, 0, 4)))
    ->add_tab('Video', [Field::make('textarea','nta_embed_code','Video embed code')->set_help_text('Paste iframe or HTML embed markup. Scripts are removed. Leave empty to use the featured image above the message.')])
    ->add_tab('Audio', [Field::make('textarea','nta_podcast_embed','Audio embed code')->set_help_text('Paste the Spotify or other podcast iframe embed. Leave empty to hide this section.'),Field::make('file','nta_audio_file','Audio file (optional alternative)')->set_type(['audio']),Field::make('text','nta_spotify_url','Spotify episode URL')->set_attribute('type','url'),Field::make('text','nta_anchor_url','Other audio URL')->set_attribute('type','url')])
    ->add_tab('PDF download', [Field::make('checkbox','nta_generate_pdf','Offer a PDF of the message content')->set_default_value(true)->set_help_text('Creates a branded PDF from the Gutenberg text. Video and audio players are excluded. An uploaded PDF takes precedence.'),Field::make('file','nta_pdf','Upload an existing PDF (optional)')->set_type(['application/pdf'])]);
  Container::make('post_meta','Message details')->where('post_type','=','nta_message')->add_fields(array_merge($content,[Field::make('rich_text','nta_transcript','Transcript / notes'),Field::make('association','nta_download','Related download')->set_max(1)->set_types([['type'=>'post','post_type'=>'nta_download']]),Field::make('association','nta_audio','Related audio')->set_types([['type'=>'post','post_type'=>'nta_audio']]),Field::make('association','nta_videos','Related videos')->set_types([['type'=>'post','post_type'=>'nta_video']]),Field::make('checkbox','nta_homepage','Show on homepage')]));
  Container::make('post_meta','Video details')->where('post_type','=','nta_video')->add_fields([Field::make('date','nta_video_date','Video date'),Field::make('text','nta_duration','Duration'),Field::make('select','nta_source_type','Source type')->set_options(['upload'=>'Upload','youtube'=>'YouTube','tiktok'=>'TikTok','facebook'=>'Facebook','twitter'=>'X / Twitter','spotify_video'=>'Spotify Video','other_embed'=>'Other embed']),Field::make('file','nta_video_file','Video file')->set_type(['video']),Field::make('text','nta_external_url','External video URL')->set_attribute('type','url'),Field::make('textarea','nta_embed_code','Embed code'),Field::make('association','nta_parent_download','Parent download')->set_max(1)->set_types([['type'=>'post','post_type'=>'nta_download']]),Field::make('association','nta_parent_message','Parent message')->set_max(1)->set_types([['type'=>'post','post_type'=>'nta_message']]),Field::make('text','nta_speaker','Speaker'),Field::make('text','nta_series','Series'),Field::make('checkbox','nta_featured','Featured video'),Field::make('checkbox','nta_homepage','Show on homepage')]);
  Container::make('post_meta','Audio details')->where('post_type','=','nta_audio')->add_fields([Field::make('date','nta_audio_date','Audio date'),Field::make('text','nta_duration','Duration'),Field::make('file','nta_audio_file','Audio file')->set_type(['audio']),Field::make('text','nta_external_url','External audio URL')->set_attribute('type','url'),Field::make('text','nta_spotify_url','Spotify episode URL')->set_attribute('type','url'),Field::make('text','nta_anchor_url','Anchor episode URL')->set_attribute('type','url'),Field::make('textarea','nta_podcast_embed','Podcast embed code'),Field::make('association','nta_parent_download','Parent download')->set_max(1)->set_types([['type'=>'post','post_type'=>'nta_download']]),Field::make('association','nta_parent_message','Parent message')->set_max(1)->set_types([['type'=>'post','post_type'=>'nta_message']]),Field::make('checkbox','nta_featured','Featured audio'),Field::make('checkbox','nta_homepage','Show on homepage')]);
});
function nta_theme_option($key,$fallback=''){ $value=function_exists('carbon_get_theme_option') ? carbon_get_theme_option($key) : ''; return $value !== '' && $value !== null ? $value : $fallback; }
function nta_menu(){ $menu=(int) nta_theme_option('nta_header_menu'); wp_nav_menu(['theme_location'=>$menu ? '' : 'primary','menu'=>$menu ?: 0,'container'=>false,'menu_id'=>'primary-navigation','menu_class'=>'nav','fallback_cb'=>false]); }
add_filter('wp_nav_menu_items', function ($items, $args) {
  if (($args->menu_id ?? '') !== 'primary-navigation') {
    return $items;
  }
  $label = nta_theme_option('nta_header_cta_label', 'Join Us');
  $url = nta_theme_option('nta_header_cta_url', '/meetings/#sunday-worship');
  return $items . '<li class="mobile-nav-cta"><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
}, 10, 2);
function nta_page_intro($slug){$copy=['about-us'=>'A kingdom community growing in truth, shared life, and the present purpose of God.','our-foundations'=>'The convictions that shape our worship, our relationships, and the way we serve.','meetings'=>'Gathered worship, the word, prayer, and life together.','people-groups'=>'People growing in shared responsibility, prayer, maturity, and meaningful participation in church life.','audio'=>'Listen to published audio messages and recordings.','videos'=>'Watch published video messages from New Tribes Assembly.'];return $copy[$slug]??'';}
function nta_meta($key,$id=0){ $id=$id ?: get_the_ID(); $value=function_exists('carbon_get_post_meta') ? carbon_get_post_meta($id,$key) : ''; return $value !== '' && $value !== null ? $value : get_post_meta($id,$key,true); }

function nta_resource_date() {
  $keys = ['nta_audio' => 'nta_audio_date', 'nta_video' => 'nta_video_date'];
  $date = nta_meta($keys[get_post_type()] ?? 'nta_date');
  $parsed = $date ? date_create_immutable($date, wp_timezone()) : false;
  return $parsed ? wp_date(get_option('date_format'), $parsed->getTimestamp()) : get_the_date();
}

function nta_embed_allowed_html() {
  $allowed = wp_kses_allowed_html('post');
  $allowed['iframe'] = array_fill_keys(['src', 'title', 'width', 'height', 'allow', 'allowfullscreen', 'loading', 'referrerpolicy', 'frameborder'], true);
  return $allowed;
}

add_filter('wp_nav_menu_objects', function ($items, $args) {
  if (($args->menu_id ?? '') !== 'primary-navigation') {
    return $items;
  }
  foreach ($items as $parent) {
    if (untrailingslashit(wp_parse_url($parent->url, PHP_URL_PATH) ?: '') !== '/resources') {
      continue;
    }
    foreach (['nta_audio' => 'Audio Messages', 'nta_video' => 'Video Messages', 'nta_message' => 'Message Notes', 'nta_download' => 'Downloads'] as $type => $label) {
      $url = get_post_type_archive_link($type);
      $exists = false;
      foreach ($items as $child) {
        if ((int) $child->menu_item_parent === (int) $parent->ID && untrailingslashit(wp_parse_url($child->url, PHP_URL_PATH) ?: '') === untrailingslashit(wp_parse_url($url, PHP_URL_PATH) ?: '')) {
          $exists = true;
          break;
        }
      }
      if ($exists) {
        continue;
      }
      $child = clone $parent;
      $child->ID = -100 - count($items);
      $child->db_id = $child->ID;
      $child->menu_item_parent = $parent->ID;
      $child->title = $label;
      $child->url = $url;
      $child->classes = [];
      $child->current = false;
      $child->current_item_parent = false;
      $child->current_item_ancestor = false;
      $items[] = $child;
    }
    break;
  }
  return $items;
}, 10, 2);

function nta_youtube_live_url($embed) {
  if (!preg_match('/<iframe\b[^>]*\bsrc\s*=\s*([\'"])(.*?)\1/is', $embed, $matches)) {
    return '';
  }
  $url = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $parts = wp_parse_url($url);
  if (!$parts || ($parts['scheme'] ?? '') !== 'https' || !in_array(strtolower($parts['host'] ?? ''), ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)) {
    return '';
  }
  $path = $parts['path'] ?? '';
  if (!preg_match('~^/embed/(?:[A-Za-z0-9_-]{11}|live_stream)$~', $path)) {
    return '';
  }
  return add_query_arg(['controls' => '1', 'fs' => '1', 'playsinline' => '1', 'autoplay' => '0'], $url);
}

function nta_default_meetings() {
  return [
    [
      'title' => 'Sunday Service',
      'day' => 'Every Sunday',
      'description' => 'Gather with us for worship and teaching as we grow in the Word and in our life together.',
      'location' => '',
      'sessions' => [
        ['name' => 'Gadites Worship Service', 'time' => '9:00 AM'],
        ['name' => 'Quarry Session', 'time' => '10:00 AM'],
      ],
      'url' => '',
      'button_label' => 'Join Sunday service',
    ],
    [
      'title' => 'Friday Prayer Meeting',
      'day' => 'Every Friday',
      'description' => 'Join us in prayer as we seek God together and share responsibility for the life of the community.',
      'location' => 'Online',
      'sessions' => [['name' => 'Online prayer', 'time' => '8:00 PM']],
      'url' => '',
      'button_label' => 'Join Friday prayer',
    ],
  ];
}

function nta_meetings() {
  $meetings = nta_theme_option('nta_meetings', []);
  if (is_array($meetings) && $meetings) {
    return $meetings;
  }
  $meetings = nta_default_meetings();
  $meetings[0]['url'] = nta_theme_option('nta_sunday_url');
  $meetings[0]['location'] = nta_theme_option('nta_meeting_address');
  $meetings[1]['url'] = nta_theme_option('nta_friday_url');
  return $meetings;
}

add_filter('render_block_core/heading', function ($html, $block) {
  if (!is_page('about-us') && !is_page_template('page-templates/about-us.php')) {
    return $html;
  }
  $icons = [
    'A people shaped' => 'book-alt',
    'Discovering our identity' => 'shield',
    'Our vision' => 'lightbulb',
    'Local life' => 'admin-site-alt3',
    'The convictions' => 'compass',
    'A Word' => 'book',
    'Growing together' => 'groups',
  ];
  $title = wp_strip_all_tags($html);
  if (str_starts_with(trim($title), 'Local life')) {
    $logo = '<img class="congress-wbn-logo" src="' . esc_url(get_theme_file_uri('assets/images/congress-wbn-logo.png')) . '" alt="CongressWBN" width="400" height="115" loading="lazy">';
    $html = str_replace('</h2>', $logo . '</h2>', $html);
  }
  foreach ($icons as $prefix => $icon) {
    if (str_starts_with(trim($title), $prefix)) {
      return preg_replace('/(<h2\b[^>]*>)/', '$1<span class="about-icon dashicons dashicons-' . esc_attr($icon) . '" aria-hidden="true"></span>', $html, 1);
    }
  }
  return $html;
}, 10, 2);

add_filter('render_block_core/group', function ($html, $block) {
  if ((!is_page('about-us') && !is_page_template('page-templates/about-us.php')) || !str_contains($block['attrs']['className'] ?? '', 'about-chapter')) {
    return $html;
  }
  $children = array_values(array_filter($block['innerBlocks'] ?? [], static fn($child) => !empty($child['blockName'])));
  if (!$children || $children[0]['blockName'] !== 'core/heading') {
    return $html;
  }
  $title = trim(wp_strip_all_tags($children[0]['innerHTML']));
  $sections = [
    'A people shaped' => ['book-alt', 'scripture-light.jpg', 'An open Bible in morning light', []],
    'Discovering our identity' => ['shield', 'rooted-growth.jpg', 'An olive sapling with established roots', []],
    'Our vision' => ['lightbulb', 'rooted-growth.jpg', 'A young olive tree reaching toward the light', []],
    'Local life' => ['admin-site-alt3', 'congress-wbn-logo.png', 'CongressWBN', []],
    'The convictions' => ['yes-alt', 'shared-life.jpg', 'Illustration of hands sharing bread at a table', ['yes-alt' => 'Integrity and accountability', 'heart' => 'Humility and servanthood', 'hammer' => 'Diligence joined to divine initiative']],
    'A Word' => ['book', 'scripture-light.jpg', 'An open Bible on a wooden table', []],
    'Growing together' => ['groups', 'shared-life.jpg', 'Illustration of fellowship through sharing bread', []],
  ];
  $visual = null;
  foreach ($sections as $prefix => $settings) {
    if (str_starts_with($title, $prefix)) {
      $visual = $settings;
      break;
    }
  }
  if (!$visual) {
    return $html;
  }
  [$icon, $image, $alt, $points] = $visual;
  $classes = 'about-chapter about-story';
  if (str_starts_with($title, 'Our vision')) $classes .= ' story-vision';
  if (str_starts_with($title, 'Local life')) $classes .= ' story-congress';
  $identity = str_starts_with($title, 'Discovering our identity');
  $intro = isset($children[1]) ? render_block($children[1]) : '';
  $body = implode('', array_map('render_block', array_slice($children, 2)));
  ob_start();
  ?>
  <section class="<?php echo esc_attr($classes); ?>">
    <header class="story-header">
      <span class="story-icon dashicons dashicons-<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
      <h2><?php if ($identity) : ?><span class="story-kicker">Discovering our identity</span>Gadites Community<?php else : ?><?php echo esc_html($title); ?><?php endif; ?></h2>
      <div class="story-introduction"><?php echo $intro; ?></div>
    </header>
    <div class="story-content">
      <figure class="story-image<?php echo $image === 'congress-wbn-logo.png' ? ' story-logo' : ''; ?>"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/' . $image)); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy" width="<?php echo $image === 'congress-wbn-logo.png' ? '400' : '1536'; ?>" height="<?php echo $image === 'congress-wbn-logo.png' ? '115' : '1024'; ?>"></figure>
      <div class="story-details">
        <?php echo $body; ?>
        <?php if ($points) : ?><ul class="story-points"><?php foreach ($points as $point_icon => $point) : ?><li><span class="dashicons dashicons-<?php echo esc_attr($point_icon); ?>" aria-hidden="true"></span><span><?php echo esc_html($point); ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
}, 10, 2);

require_once get_theme_file_path('inc/downloads.php');

function nta_slide_has_content($slide) {
  foreach (['eyebrow', 'heading', 'body'] as $key) {
    if (trim((string) ($slide[$key] ?? '')) !== '') return true;
  }
  foreach (['primary', 'secondary'] as $button) {
    if (trim((string) ($slide[$button . '_label'] ?? '')) !== '' && trim((string) ($slide[$button . '_url'] ?? '')) !== '') return true;
  }
  return false;
}

require_once get_theme_file_path('inc/streams.php');
