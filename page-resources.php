<?php
/**
 * Resource hub for the audio, video, and download archives.
 * Also used by the selectable Messages & Resources page template.
 */
get_header();
while (have_posts()) :
    the_post();
    $resource_sections = [
        ['type' => 'nta_audio', 'title' => 'Audio Messages', 'intro' => 'Listen to published messages and recordings.', 'action' => 'View all audio messages'],
        ['type' => 'nta_video', 'title' => 'Video Messages', 'intro' => 'Watch published message videos.', 'action' => 'View all video messages'],
        ['type' => 'nta_download', 'title' => 'Downloads', 'intro' => 'Browse teaching documents and other downloadable materials.', 'action' => 'View all downloads'],
    ];
    ?>
    <section class="page-hero resources-hero">
        <div class="wrap">
            <p class="eyebrow">Teaching Library</p>
            <h1><?php the_title(); ?></h1>
            <p class="lead">Explore New Tribes Assembly teaching in the format that works for you.</p>
        </div>
    </section>
    <nav class="resources-index wrap" aria-label="Browse resource formats">
        <?php foreach ($resource_sections as $section) : ?>
            <a class="resource-index-link" href="#<?php echo esc_attr($section['type']); ?>">
                <span><?php echo esc_html($section['title']); ?></span><span aria-hidden="true">↓</span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php foreach ($resource_sections as $index => $section) :
        $archive_url = get_post_type_archive_link($section['type']);
        $resource_query = new WP_Query([
            'post_type' => $section['type'],
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'no_found_rows' => true,
        ]);
        ?>
        <section id="<?php echo esc_attr($section['type']); ?>" class="section resource-section <?php echo $index % 2 ? 'band-mist' : ''; ?>">
            <div class="wrap">
                <div class="resource-section-heading">
                    <div>
                        <p class="eyebrow"><?php echo esc_html(sprintf('%02d / Teaching Library', $index + 1)); ?></p>
                        <h2><?php echo esc_html($section['title']); ?></h2>
                        <p><?php echo esc_html($section['intro']); ?></p>
                    </div>
                    <?php if ($archive_url) : ?>
                        <a class="button" href="<?php echo esc_url($archive_url); ?>"><?php echo esc_html($section['action']); ?></a>
                    <?php endif; ?>
                </div>
                <div class="archive-list">
                    <?php if ($resource_query->have_posts()) :
                        while ($resource_query->have_posts()) :
                            $resource_query->the_post(); ?>
                            <a class="archive-row" href="<?php the_permalink(); ?>">
                                <div>
                                    <span class="meta"><?php echo esc_html(get_the_date()); ?></span>
                                    <h3><?php the_title(); ?></h3>
                                </div>
                                <span aria-hidden="true">→</span>
                            </a>
                        <?php endwhile;
                        wp_reset_postdata();
                    else : ?>
                        <p class="resource-empty">No published <?php echo esc_html(strtolower($section['title'])); ?> yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
    <?php if (trim(get_the_content())) : ?>
        <section class="section"><div class="wrap prose"><?php the_content(); ?></div></section>
    <?php endif;
endwhile;
get_footer();
