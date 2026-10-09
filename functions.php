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
  wp_enqueue_script('nta-interactions', get_theme_file_uri('assets/interactions.js'), [], filemtime(get_theme_file_path('assets/interactions.js')), true);
});
function nta_register_media_type($type, $plural, $singular, $slug) { register_post_type($type, ['labels'=>['name'=>$plural,'singular_name'=>$singular,'add_new_item'=>"Add $singular",'edit_item'=>"Edit $singular"], 'public'=>true, 'show_in_rest'=>true, 'has_archive'=>$slug, 'rewrite'=>['slug'=>$slug,'with_front'=>false], 'menu_icon'=>'dashicons-media-document', 'supports'=>['title','editor','excerpt','thumbnail','revisions']]); }
add_action('init', function () { nta_register_media_type('nta_download','Downloads','Download','downloads'); nta_register_media_type('nta_message','Messages','Message','messages'); nta_register_media_type('nta_video','Videos','Video','videos'); nta_register_media_type('nta_audio','Audio','Audio item','audio'); });
add_action('carbon_fields_register_fields', function () {
  $menu_options=[]; foreach (wp_get_nav_menus() as $menu) { $menu_options[$menu->term_id]=$menu->name; }
  Container::make('theme_options','Header Settings')->set_page_menu_title('Header Settings')->add_fields([
    Field::make('image','nta_logo','Header logo')->set_value_type('id'),
    Field::make('select','nta_header_menu','Header menu')->set_options($menu_options),
    Field::make('text','nta_header_cta_label','Header button label')->set_default_value('Join Us'),
    Field::make('text','nta_header_cta_url','Header button URL')->set_attribute('type','url')->set_default_value('/meetings/#sunday-worship'),
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
    Field::make('complex','nta_home_slides','Hero slides')->set_help_text('Click Add Entry for each new image and message. Drag slides to change their order.')->add_fields([
      Field::make('image','image','Slide image')->set_value_type('id')->set_required(true),
      Field::make('text','eyebrow','Small heading'),
      Field::make('text','heading','Main heading')->set_required(true),
      Field::make('textarea','body','Description'),
      Field::make('text','primary_label','First button label'),
      Field::make('text','primary_url','First button URL'),
      Field::make('text','secondary_label','Second button label'),
      Field::make('text','secondary_url','Second button URL'),
    ]),
    Field::make('image','nta_home_leadership_image','Leadership image')->set_value_type('id')->set_help_text('Upload the landscape image shown beside the Senior Elder’s introduction.'),
  ]);
  $content = [Field::make('date','nta_date','Message date'),Field::make('text','nta_speaker','Speaker'),Field::make('text','nta_series','Series'),Field::make('complex','nta_scriptures','Scripture references')->add_fields([Field::make('text','reference','Reference')]),Field::make('rich_text','nta_summary','Summary')];
  Container::make('post_meta','Download details')->where('post_type','=','nta_download')->add_fields(array_merge($content,[Field::make('file','nta_pdf','PDF download')->set_type(['application/pdf']),Field::make('file','nta_audio_file','Audio file')->set_type(['audio']),Field::make('text','nta_spotify_url','Spotify episode URL')->set_attribute('type','url'),Field::make('text','nta_anchor_url','Anchor episode URL')->set_attribute('type','url'),Field::make('textarea','nta_podcast_embed','Podcast embed code'),Field::make('text','nta_video_replay_url','Full replay URL')->set_attribute('type','url'),Field::make('association','nta_related_videos','Related videos')->set_types([['type'=>'post','post_type'=>'nta_video']]),Field::make('checkbox','nta_featured','Featured download'),Field::make('checkbox','nta_homepage','Show on homepage')]));
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
function nta_page_intro($slug){$copy=['about-us'=>'A kingdom community not manufactured, but forged through process, endurance, and the discovery of divine naming within the present purpose of God.','our-foundations'=>'A community defined by the rigorous, enduring process of spiritual formation informed by the Present Truth Purpose of God.','meetings'=>'Gathered worship, the word, prayer, and life together.','people-groups'=>'People growing in shared responsibility, prayer, maturity, and meaningful participation in church life.','audio'=>'Listen to published audio messages and recordings.','videos'=>'Watch published video messages from New Tribes Assembly.'];return $copy[$slug]??'';}
function nta_meta($key,$id=0){ $id=$id ?: get_the_ID(); $value=function_exists('carbon_get_post_meta') ? carbon_get_post_meta($id,$key) : ''; return $value !== '' && $value !== null ? $value : get_post_meta($id,$key,true); }
