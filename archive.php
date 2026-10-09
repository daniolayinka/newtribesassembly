<?php
get_header();
$resources_page = get_page_by_path('resources');
?>
<section class="page-hero"><div class="wrap"><p class="eyebrow">Teaching Archive</p><h1><?php post_type_archive_title(); ?></h1><p class="lead">Material for prayer, obedience, and building in the present purpose of God.</p><?php if ($resources_page && get_post_status($resources_page) === 'publish') : ?><p><a href="<?php echo esc_url(get_permalink($resources_page)); ?>">← Browse Audio Messages, Video Messages &amp; Downloads</a></p><?php endif; ?></div></section>
<section class="section"><div class="wrap archive-list"><?php if (have_posts()) : while (have_posts()) : the_post(); ?><a class="archive-row" href="<?php the_permalink(); ?>"><div><span class="meta"><?php echo esc_html(get_the_date()); ?></span><h2><?php the_title(); ?></h2><p><?php echo esc_html(get_the_excerpt()); ?></p></div><span aria-hidden="true">→</span></a><?php endwhile; else : ?><p>No published material yet.</p><?php endif; ?></div></section>
<?php get_footer();
