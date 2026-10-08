<?php
get_header();
$home_page_id = (int) get_queried_object_id();
$slides = nta_meta('nta_home_slides', $home_page_id);
$slides = is_array($slides) ? array_values(array_filter($slides, static function ($slide) {
  return is_array($slide) && !empty($slide['image']) && wp_attachment_is_image((int) $slide['image']);
})) : [];
$leadership_image = (int) nta_meta('nta_home_leadership_image', $home_page_id);
$leadership_image = $leadership_image ?: (int) nta_theme_option('nta_pastor_image');
?>

<?php if ($slides) : ?>
<section class="hero hero-slider" x-data="ntaHeroSlider(<?php echo count($slides); ?>)" data-aos="fade" aria-label="Homepage highlights" aria-roledescription="carousel" @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()">
  <?php foreach ($slides as $index => $slide) : ?>
    <div class="hero-slide" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(($index + 1) . ' of ' . count($slides)); ?>" x-show="current === <?php echo (int) $index; ?>" x-transition.opacity.duration.500ms<?php echo $index ? ' x-cloak' : ''; ?>>
      <?php echo wp_get_attachment_image((int) $slide['image'], 'full', false, ['class' => 'hero-slide-image', 'alt' => '', 'loading' => $index ? 'lazy' : 'eager']); ?>
      <div class="wrap hero-slide-content">
        <?php if (!empty($slide['eyebrow'])) : ?><p class="eyebrow"><?php echo esc_html($slide['eyebrow']); ?></p><?php endif; ?>
        <?php if (!empty($slide['heading'])) : ?><h1><?php echo esc_html($slide['heading']); ?></h1><?php endif; ?>
        <?php if (!empty($slide['body'])) : ?><p class="lead"><?php echo nl2br(esc_html($slide['body'])); ?></p><?php endif; ?>
        <?php if ((!empty($slide['primary_label']) && !empty($slide['primary_url'])) || (!empty($slide['secondary_label']) && !empty($slide['secondary_url']))) : ?>
          <div class="actions">
            <?php if (!empty($slide['primary_label']) && !empty($slide['primary_url'])) : ?><a class="button outline" href="<?php echo esc_url($slide['primary_url']); ?>"><?php echo esc_html($slide['primary_label']); ?></a><?php endif; ?>
            <?php if (!empty($slide['secondary_label']) && !empty($slide['secondary_url'])) : ?><a class="button outline" href="<?php echo esc_url($slide['secondary_url']); ?>"><?php echo esc_html($slide['secondary_label']); ?></a><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (count($slides) > 1) : ?>
    <div class="hero-controls" x-cloak aria-label="Slide controls">
      <button type="button" class="hero-control" @click="previous()" aria-label="Previous slide">←</button>
      <div class="hero-dots">
        <?php foreach ($slides as $index => $slide) : ?>
          <button type="button" class="hero-dot" @click="go(<?php echo (int) $index; ?>)" :aria-current="current === <?php echo (int) $index; ?> ? 'true' : null" aria-label="Go to slide <?php echo (int) ($index + 1); ?>"></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="hero-control" @click="next()" aria-label="Next slide">→</button>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="section band-mist">
  <div class="wrap split" data-aos="fade-up">
    <div>
      <p class="eyebrow">Welcome to NTA</p>
      <h2>Raising believers who become light in their world.</h2>
      <p>We are a kingdom community committed to the present purpose of God. We believe believers are not merely gathered, but formed, adjusted, and raised into lives that can carry divine intention with clarity and maturity.</p>
      <a class="button" href="/about-us/">More About Us</a>
    </div>
    <aside class="meeting-card">
      <p class="eyebrow">Gather With Us</p>
      <h3>Weekly Meetings</h3>
      <div class="meeting"><strong>Sunday Worship <small>9:00 AM</small></strong>Gathered worship, the word, and life together.</div>
      <div class="meeting"><strong>Friday Prayer Meeting <small>Online · 8:00 PM</small></strong>Online prayer and shared spiritual labour.</div>
    </aside>
  </div>
</section>

<section class="section band-blue">
  <div class="wrap" data-aos="fade-up">
    <p class="eyebrow">Culture</p>
    <h2>What Shapes Our Culture</h2>
    <div class="grid3">
      <article><span class="meta">01 / Formation</span><h3>Building rather than blessing mentality.</h3><p>We take spiritual formation seriously, building lives that can carry God’s intention with substance.</p></article>
      <article><span class="meta">02 / Shared life</span><h3>Community Life</h3><p>We grow in shared life, shared burden, and shared obedience.</p></article>
      <article><span class="meta">03 / Responsibility</span><h3>Servanthood</h3><p>Humility, accountability, and significant hard work belong alongside divine initiative.</p></article>
    </div>
  </div>
</section>

<section class="section band-sand">
  <div class="wrap split" data-aos="fade-up">
    <div>
      <p class="eyebrow">Leadership</p>
      <h2>Senior Elder Brendy Mba Ndukwe</h2>
      <p>New Tribes Assembly is led by Senior Elder Brendy Mba Ndukwe, whose decades of ministry experience and spiritual leadership guide the community in pursuing God’s present purpose.</p>
    </div>
    <?php if ($leadership_image) : ?>
      <figure class="pastor-portrait">
        <?php echo wp_get_attachment_image($leadership_image, 'large', false, ['alt' => 'Senior Elder Brendy Mba Ndukwe', 'loading' => 'lazy']); ?>
      </figure>
    <?php endif; ?>
  </div>
</section>

<section class="section band-mist">
  <div class="wrap" data-aos="fade-up">
    <p class="eyebrow">Downloads</p>
    <h2>Building from What God is Saying</h2>
    <?php $q = new WP_Query(['post_type' => 'nta_download', 'posts_per_page' => 4]); ?>
    <?php if ($q->have_posts()) : ?>
      <div class="archive-list">
        <?php while ($q->have_posts()) : $q->the_post(); ?>
          <a class="archive-row" href="<?php the_permalink(); ?>">
            <div><span class="meta"><?php echo get_the_date(); ?></span><h2><?php the_title(); ?></h2></div>
            <span>→</span>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else : ?>
      <p>Weekly Sunday messages and foundational material will appear here.</p>
    <?php endif; ?>
    <p><a class="button" href="/downloads/">Explore Downloads</a></p>
  </div>
</section>

<?php get_footer();
