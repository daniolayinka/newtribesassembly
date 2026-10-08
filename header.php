<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
$logo = (int) nta_theme_option('nta_logo');
$cta_label = nta_theme_option('nta_header_cta_label', 'Join Worship Service');
$cta_url = nta_theme_option('nta_header_cta_url', '/meetings/#sunday-worship');
?>
<header class="site-header">
    <div class="wrap">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if ($logo): ?>
                <?php echo wp_get_attachment_image($logo, 'full', false, ['class' => 'brand-image', 'style' => 'max-height:58px;width:auto;max-width:180px;object-fit:contain', 'alt' => get_bloginfo('name')]); ?>
            <?php else: ?>
                <span class="brand-mark" aria-label="New Tribes Assembly">†</span>
            <?php endif; ?>
        </a>
        <button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false">
            <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
            <span class="screen-reader-text">Open menu</span>
        </button>
        <?php nta_menu(); ?>
        <a class="button header-cta" data-aos="fade-left" data-aos-duration="600" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_label); ?></a>
    </div>
</header>
<main>
