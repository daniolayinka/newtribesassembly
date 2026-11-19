<?php get_header(); ?>
<header class="downloads-library-heading wrap"><div><p class="eyebrow">The teaching library</p><h1>Words to build with.</h1><p class="lead">Explore messages for study, prayer, and a life shaped by the Word.</p></div><a class="text-link" href="<?php echo esc_url(home_url('/resources/')); ?>">All resources →</a></header>
<section class="wrap downloads-grid" aria-label="Downloads">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article class="download-card"><a href="<?php the_permalink(); ?>" class="download-card-image" tabindex="-1" aria-hidden="true"><?php if (has_post_thumbnail()) : the_post_thumbnail('large'); else : ?><span>New Tribes<br>Assembly</span><?php endif; ?></a><div class="download-card-copy"><p class="meta"><?php echo esc_html(nta_resource_date()); ?></p><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php if (nta_meta('nta_speaker')) : ?><p><?php echo esc_html(nta_meta('nta_speaker')); ?></p><?php endif; ?><p><?php $description = wp_strip_all_tags(get_the_excerpt()); if (preg_match('/^(Source (teaching )?document|Seeded from)/i', trim($description))) $description = ''; echo esc_html(wp_trim_words($description, 22)); ?></p><a class="text-link" href="<?php the_permalink(); ?>">Open message →</a></div></article>
<?php endwhile; else : ?><p>Messages will appear here when published.</p><?php endif; ?>
</section><div class="wrap download-return"><?php the_posts_pagination(); ?></div>
<?php get_footer(); ?>
