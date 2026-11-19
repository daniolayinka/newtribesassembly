<?php get_header(); while (have_posts()) : the_post();
if (post_password_required()) { echo '<section class="section"><div class="wrap">' . get_the_password_form() . '</div></section>'; continue; }
$video = trim(wp_kses(nta_meta('nta_embed_code'), nta_embed_allowed_html()));
$audio = trim(wp_kses(nta_meta('nta_podcast_embed'), nta_embed_allowed_html()));
$audio_file = wp_get_attachment_url((int) nta_meta('nta_audio_file'));
$pdf = nta_download_pdf_url(get_the_ID());
?>
<article class="download-message">
<header class="download-heading wrap">
 <div class="download-toolbar"><a class="back-link" href="<?php echo esc_url(get_post_type_archive_link('nta_download')); ?>">← All downloads</a><?php if ($pdf) : ?><a class="download-save" href="<?php echo esc_url($pdf); ?>" download>Save message PDF <span aria-hidden="true">↓</span></a><?php endif; ?></div>
 <p class="eyebrow">Study · Reflect · Grow</p><h1><?php the_title(); ?></h1>
 <p class="meta"><?php echo esc_html(nta_resource_date()); ?><?php if (nta_meta('nta_speaker')) : ?> · <?php echo esc_html(nta_meta('nta_speaker')); ?><?php endif; ?><?php if (nta_meta('nta_series')) : ?> · <?php echo esc_html(nta_meta('nta_series')); ?><?php endif; ?></p>
</header>
<?php if ($video) : ?><section class="download-visual wrap" aria-label="Video message"><div class="download-video"><?php echo $video; ?></div></section>
<?php elseif (has_post_thumbnail()) : ?><figure class="download-visual wrap download-cover"><?php the_post_thumbnail('full'); ?></figure><?php endif; ?>
<?php if ($audio || $audio_file || nta_meta('nta_spotify_url') || nta_meta('nta_anchor_url')) : ?>
<section class="download-audio wrap"><div><p class="eyebrow">Listen</p><h2>Take the message with you</h2></div><div class="download-audio-player">
<?php if ($audio) : ?><?php echo $audio; ?><?php elseif ($audio_file) : ?><audio controls preload="metadata" src="<?php echo esc_url($audio_file); ?>"></audio><?php endif; ?>
<?php foreach (['nta_spotify_url'=>'Listen on Spotify','nta_anchor_url'=>'Open audio message'] as $key=>$label) : if (nta_meta($key)) : ?><a class="text-link" href="<?php echo esc_url(nta_meta($key)); ?>"><?php echo esc_html($label); ?> ↗</a><?php endif; endforeach; ?>
</div></section><?php endif; ?>
<?php if (nta_download_has_content(get_the_ID())) : ?><section class="download-reading"><div class="download-prose"><?php the_content(); ?></div></section><?php endif; ?>
<?php if ($pdf) : ?><section class="download-pdf wrap"><div><p class="eyebrow">Your study copy</p><h2>Read. Reflect. Return to the Word.</h2><p>Keep a PDF of this message for personal study, prayer, and shared discussion.</p></div><a class="button" href="<?php echo esc_url($pdf); ?>" download>Download message PDF ↓</a></section><?php endif; ?>
<div class="wrap download-return"><a class="text-link" href="<?php echo esc_url(home_url('/resources/')); ?>">Explore the teaching library →</a></div>
</article>
<?php endwhile; get_footer(); ?>
