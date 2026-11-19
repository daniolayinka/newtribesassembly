<?php
get_header();
while (have_posts()) :
    the_post();
    $type = get_post_type();
    $pdf_url = wp_get_attachment_url((int) nta_meta('nta_pdf'));
    $audio_url = wp_get_attachment_url((int) nta_meta('nta_audio_file'));
    $video_url = wp_get_attachment_url((int) nta_meta('nta_video_file'));
    $external_url = nta_meta('nta_external_url');
    $embed = nta_meta($type === 'nta_video' ? 'nta_embed_code' : 'nta_podcast_embed');
    $external_audio = $type === 'nta_audio' && preg_match('/\.(mp3|m4a|ogg|wav|aac)(\?|$)/i', $external_url);
    $has_media = $pdf_url || $audio_url || $video_url || $external_url || $embed;
    ?>
    <section class="page-hero resource-detail-hero"><div class="wrap">
        <a class="back-link" href="<?php echo esc_url(get_post_type_archive_link($type)); ?>">Back to <?php echo esc_html(get_post_type_object($type)->labels->name); ?></a>
        <p class="eyebrow"><?php echo esc_html(get_post_type_object($type)->labels->singular_name); ?></p>
        <h1><?php the_title(); ?></h1>
        <p class="meta"><?php echo esc_html(nta_resource_date()); ?><?php if (nta_meta('nta_speaker')) : ?> · <?php echo esc_html(nta_meta('nta_speaker')); ?><?php endif; ?><?php if (nta_meta('nta_duration')) : ?> · <?php echo esc_html(nta_meta('nta_duration')); ?><?php endif; ?></p>
        <?php if ($pdf_url) : ?><a class="button" href="<?php echo esc_url($pdf_url); ?>" download>Download PDF</a><?php endif; ?>
    </div></section>
    <section class="section resource-detail-body"><article class="wrap prose">
        <?php if ($audio_url || $external_audio) : ?>
            <audio controls preload="metadata" aria-label="<?php echo esc_attr(get_the_title()); ?>"><source src="<?php echo esc_url($audio_url ?: $external_url); ?>"></audio>
        <?php endif; ?>
        <?php if ($type === 'nta_audio' && $external_url && !$external_audio) : ?><p><a class="button" href="<?php echo esc_url($external_url); ?>">Listen to this message</a></p><?php endif; ?>
        <?php if ($video_url) : ?>
            <video controls preload="metadata" playsinline aria-label="<?php echo esc_attr(get_the_title()); ?>" src="<?php echo esc_url($video_url); ?>"></video>
        <?php elseif ($type === 'nta_video' && $external_url) : ?>
            <?php $oembed = wp_oembed_get($external_url); ?>
            <?php if ($oembed) : ?><div class="media-embed"><?php echo $oembed; // WordPress provider output. ?></div><?php endif; ?>
            <p><a class="button" href="<?php echo esc_url($external_url); ?>">Watch video</a></p>
        <?php endif; ?>
        <?php if ($embed) : ?><div class="media-embed"><?php echo wp_kses($embed, nta_embed_allowed_html()); ?></div><?php endif; ?>
        <?php if (nta_meta('nta_summary')) : ?><div><?php echo wp_kses_post(wpautop(nta_meta('nta_summary'))); ?></div><?php endif; ?>
        <?php if ($pdf_url) : ?>
            <details class="pdf-preview"><summary>Read the document here</summary><iframe title="<?php echo esc_attr(get_the_title()); ?> PDF" src="<?php echo esc_url($pdf_url); ?>" loading="lazy"></iframe><p><a href="<?php echo esc_url($pdf_url); ?>">Open the PDF in your browser</a></p></details>
        <?php endif; ?>
        <?php if (nta_meta('nta_transcript')) : ?><section><h2>Message notes</h2><?php echo wp_kses_post(wpautop(nta_meta('nta_transcript'))); ?></section><?php endif; ?>
        <?php
        $content = get_the_content();
        $is_archive_note = preg_match('/^(Seeded from|Audio recording preserved from)/i', trim(wp_strip_all_tags($content)));
        if (!$is_archive_note) {
            the_content();
        }
        if (!$has_media && !nta_meta('nta_transcript') && ($is_archive_note || trim($content) === '')) : ?>
            <p class="resource-notice">The recording or document for this message is not available yet. Explore the library for more teaching.</p>
        <?php endif; ?>
        <?php foreach (['nta_spotify_url' => 'Listen on Spotify', 'nta_anchor_url' => 'Listen to this episode', 'nta_video_replay_url' => 'Watch the full replay'] as $key => $label) : ?>
            <?php if (nta_meta($key)) : ?><p><a class="text-link" href="<?php echo esc_url(nta_meta($key)); ?>"><?php echo esc_html($label); ?></a></p><?php endif; ?>
        <?php endforeach; ?>
        <?php
        $related = [];
        foreach (['nta_download', 'nta_audio', 'nta_videos', 'nta_related_videos', 'nta_parent_download', 'nta_parent_message'] as $key) {
            foreach ((array) nta_meta($key) as $relation) {
                $related_id = is_array($relation) ? (int) ($relation['id'] ?? 0) : (int) $relation;
                if ($related_id && get_post_status($related_id) === 'publish' && $related_id !== get_the_ID()) {
                    $related[$related_id] = get_the_title($related_id);
                }
            }
        }
        if ($related) : ?><section class="related-resources"><h2>Related teaching</h2><ul><?php foreach ($related as $id => $title) : ?><li><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html($title); ?></a></li><?php endforeach; ?></ul></section><?php endif; ?>
        <p class="library-return"><a class="text-link" href="<?php echo esc_url(home_url('/resources/')); ?>">Explore the teaching library</a></p>
    </article></section>
<?php endwhile;
get_footer();
