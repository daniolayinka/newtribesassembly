<?php
get_header();
while (have_posts()) :
    the_post();
    $template_sections = [
        'page-templates/about-us.php' => 'about-us',
        'page-templates/meetings.php' => 'meetings',
        'page-templates/our-foundations.php' => 'our-foundations',
        'page-templates/people-groups.php' => 'people-groups',
        'page-templates/standard.php' => 'standard',
    ];
    $selected_template = get_page_template_slug(get_the_ID());
    $slug = $template_sections[$selected_template] ?? get_post_field('post_name', get_the_ID());
    $archives = [
        'downloads' => 'nta_download',
        'messages' => 'nta_message',
        'videos' => 'nta_video',
        'audio' => 'nta_audio',
    ];
    ?>
    <section class="page-hero">
        <div class="wrap">
            <p class="eyebrow"><?php echo esc_html($slug === 'meetings' ? 'Gathered Life' : ($slug === 'people-groups' ? 'Life Together' : 'New Tribes Assembly')); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="lead"><?php echo esc_html(nta_page_intro($slug)); ?></p>
        </div>
    </section>
    <?php if (isset($archives[$slug])) :
        $archive_query = new WP_Query(['post_type' => $archives[$slug], 'posts_per_page' => 20]); ?>
        <section class="section"><div class="wrap">
            <p class="eyebrow">Teaching Archive</p>
            <div class="archive-list">
                <?php if ($archive_query->have_posts()) :
                    while ($archive_query->have_posts()) :
                        $archive_query->the_post(); ?>
                        <a class="archive-row" href="<?php the_permalink(); ?>">
                            <div><span class="meta"><?php echo esc_html(get_the_date()); ?></span><h2><?php the_title(); ?></h2><p><?php echo esc_html(get_the_excerpt()); ?></p></div><span>→</span>
                        </a>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p>No published material yet.</p>
                <?php endif; ?>
            </div>
        </div></section>
    <?php elseif ($slug === 'meetings') : ?>
        <section id="sunday-worship" class="section band-sand"><div class="wrap split">
            <div><p class="eyebrow">Sunday Worship</p><h2>Gathered worship and the Word</h2><p>Join us for worship, the Word, and life together every Sunday at 9:00 AM.</p><a class="button" href="#">Join Worship Service</a></div>
            <div class="meeting-card"><p class="eyebrow">Friday Prayer Meeting</p><h2>Online · 8:00 PM</h2><p>Come ready for shared spiritual labour, not passive attendance.</p></div>
        </div></section>
    <?php elseif ($slug === 'people-groups') : ?>
        <section class="section band-sand"><div class="wrap split">
            <div><p class="eyebrow">Men's People Group</p><h2>Hebronite</h2><p>Hebronites are men being strengthened to stand like a wall: committed to prayer, maturity, responsibility, and significant participation in the life of the church.</p></div>
            <div><p class="eyebrow">Women's People Groups</p><h2>Excel &amp; Charis</h2><p>Women gathering in formation, mutual support, and the discovery of their identity within God's purpose.</p></div>
        </div></section>
    <?php elseif ($slug === 'our-foundations') : ?>
        <section class="section band-sand"><div class="wrap"><h2>Foundational Positions</h2><div class="cards">
            <?php foreach (['Pursuing the Purpose of the Lord', 'Progressive Christianity', 'Building Rather Than Blessing', 'Community Life', 'Spirit of Servanthood', 'Hard Work Joined to Divine Initiative'] as $foundation) : ?>
                <article class="card"><h3><?php echo esc_html($foundation); ?></h3><p>We pursue growth in truth, obedience, structure, and a shared life that carries God’s intention.</p></article>
            <?php endforeach; ?>
        </div></div></section>
    <?php endif; ?>
    <?php if (!isset($archives[$slug])) : ?>
        <section class="section"><article class="wrap prose"><?php the_content(); ?></article></section>
    <?php endif;
endwhile;
get_footer();
