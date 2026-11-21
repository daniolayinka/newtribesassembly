<?php
get_header();
while (have_posts()) :
    the_post();
    $template_sections = [
        'page-templates/about-us.php' => 'about-us',
        'page-templates/meetings.php' => 'meetings',
        'page-templates/our-foundations.php' => 'our-foundations',
        'page-templates/people-groups.php' => 'people-groups',
        'page-templates/audio-messages.php' => 'audio',
        'page-templates/video-messages.php' => 'videos',
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
    <?php if ($slug === 'meetings') : get_template_part('stream-section'); endif; ?>
    <section class="page-hero<?php echo $slug === 'about-us' ? ' about-hero' : ''; ?>">
        <div class="wrap">
            <?php if ($slug === 'about-us') : ?><div class="about-hero-copy"><?php endif; ?>
            <p class="eyebrow"><?php echo esc_html($slug === 'meetings' ? 'Gathered Life' : ($slug === 'people-groups' ? 'Life Together' : 'New Tribes Assembly')); ?></p>
            <?php if ($slug === 'meetings') : ?><h2><?php the_title(); ?></h2><?php else : ?><h1><?php the_title(); ?></h1><?php endif; ?>
            <p class="lead"><?php echo esc_html(nta_page_intro($slug)); ?></p>
            <?php if ($slug === 'about-us') : ?></div>
                <figure class="about-hero-image"><?php if (has_post_thumbnail()) : ?><?php the_post_thumbnail('large', ['loading' => 'eager']); ?><?php else : ?><img src="<?php echo esc_url(get_theme_file_uri('assets/images/scripture-light.jpg')); ?>" alt="An open Bible in warm morning light" width="1536" height="1024" fetchpriority="high"><?php endif; ?></figure>
            <?php endif; ?>
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
                            <div><span class="meta"><?php echo esc_html(nta_resource_date()); ?></span><h2><?php the_title(); ?></h2><p><?php echo esc_html(get_the_excerpt()); ?></p></div><span>→</span>
                        </a>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p>No published material yet.</p>
                <?php endif; ?>
            </div>
        </div></section>
    <?php elseif ($slug === 'meetings') : ?>
        <section id="sunday-worship" class="meeting-listings" aria-label="Meeting schedule">
            <div class="wrap">
                <?php foreach (nta_meetings() as $meeting) : ?>
                    <article class="meeting-listing">
                        <div class="meeting-introduction">
                            <?php if (!empty($meeting['day'])) : ?><p class="eyebrow"><?php echo esc_html($meeting['day']); ?></p><?php endif; ?>
                            <h2><?php echo esc_html($meeting['title']); ?></h2>
                            <?php if (!empty($meeting['description'])) : ?><p><?php echo nl2br(esc_html($meeting['description'])); ?></p><?php endif; ?>
                            <?php if (!empty($meeting['location'])) : ?><p class="meeting-location"><?php echo nl2br(esc_html($meeting['location'])); ?></p><?php endif; ?>
                            <?php if (!empty($meeting['url'])) : ?><a class="button" href="<?php echo esc_url($meeting['url']); ?>"><?php echo esc_html($meeting['button_label'] ?: 'Join this meeting'); ?></a><?php endif; ?>
                        </div>
                        <?php if (!empty($meeting['sessions'])) : ?>
                            <dl class="meeting-sessions">
                                <?php foreach ($meeting['sessions'] as $session) : ?>
                                    <div><dt><?php echo esc_html($session['name']); ?></dt><dd><?php echo esc_html($session['time']); ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($slug === 'people-groups') : ?>
        <section class="section band-sand"><div class="wrap split">
            <div><p class="eyebrow">Men's People Group</p><h2>Hebronite</h2><p>Hebronites are men being strengthened to stand like a wall: committed to prayer, maturity, responsibility, and significant participation in the life of the church.</p></div>
            <div><p class="eyebrow">Women's People Groups</p><h2>Excel &amp; Charis</h2><p>Excel and Charis bring women together to grow in faith, support one another, and participate in the shared life of the church.</p></div>
        </div><div class="wrap"><p>Speak with us at a Sunday gathering to learn more about a people group.<?php if (is_email(nta_theme_option('nta_contact_email'))) : ?> <a class="text-link" href="<?php echo esc_url('mailto:' . nta_theme_option('nta_contact_email')); ?>">Ask about joining a group</a><?php endif; ?></p></div></section>
    <?php elseif ($slug === 'our-foundations') : ?>
        <section class="section band-sand"><div class="wrap"><h2>Foundational Positions</h2><div class="cards">
            <?php foreach ([
                'Pursuing the Purpose of the Lord' => 'We seek to understand what God is saying and respond with faith, obedience, and a life shaped by His Word.',
                'Progressive Christianity' => 'We keep growing in our understanding of truth, allowing the Word to mature us and shape how we live.',
                'Building Rather Than Blessing' => 'We value lasting spiritual formation, developing lives that can carry responsibility and serve God’s purpose.',
                'Community Life' => 'We share life, prayer, and responsibility, learning to grow together through practical care and mutual support.',
                'Spirit of Servanthood' => 'We serve with humility and accountability, placing our gifts and strength at the service of others.',
                'Hard Work Joined to Divine Initiative' => 'We respond to God’s leading with diligence, perseverance, and faithful participation in the work before us.',
            ] as $foundation => $description) : ?>
                <article class="card"><h3><?php echo esc_html($foundation); ?></h3><p><?php echo esc_html($description); ?></p></article>
            <?php endforeach; ?>
        </div></div></section>
    <?php endif; ?>
    <?php if (!isset($archives[$slug]) && $slug !== 'meetings') : ?>
        <section class="section<?php echo $slug === 'about-us' ? ' about-page-content' : ''; ?>"><article class="wrap prose"><?php the_content(); ?></article></section>
    <?php endif;
endwhile;
get_footer();
