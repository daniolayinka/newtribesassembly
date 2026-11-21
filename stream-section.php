<?php $stream = nta_stream_payload(); $stream_state = $stream['state']; ?>
<section class="meeting-broadcast scheduled-broadcast" data-nta-stream data-initial="<?php echo esc_attr(wp_json_encode($stream)); ?>" data-endpoint="<?php echo esc_url(rest_url('nta/v1/stream')); ?>" data-refresh-endpoint="<?php echo esc_url(rest_url('nta/v1/stream/refresh')); ?>" data-stream-state="<?php echo esc_attr($stream_state); ?>" aria-label="Online gathering">
    <div class="stream-stage" data-stream-poster>
        <img class="stream-thumbnail" width="1920" height="1080" data-stream-image <?php if (!empty($stream['thumbnail'])) : ?>src="<?php echo esc_url($stream['thumbnail']); ?>"<?php else : ?>hidden<?php endif; ?> alt="">
        <div class="wrap stream-intro">
            <div class="stream-topic"><p class="stream-status" data-stream-status aria-live="polite"><?php echo esc_html(['countdown' => 'Scheduled Stream', 'waiting' => 'Starting soon', 'live' => 'Connecting to the live broadcast…', 'ended' => 'This gathering has ended.', 'empty' => 'Join our next gathering.'][$stream_state]); ?></p>
            <div class="stream-check-row"><button type="button" class="stream-check" data-stream-refresh>Check for live stream ↻</button><span class="stream-check-feedback" data-stream-feedback role="status" aria-live="polite"></span></div>
            </div><div class="stream-countdown" data-stream-countdown aria-label="Time until the gathering" <?php if (!in_array($stream_state, ['countdown', 'waiting'], true)) echo 'hidden'; ?>><?php echo $stream_state === 'waiting' ? '00:00:00' : esc_html(isset($stream['start']) ? gmdate('H:i:s', max(0, $stream['start'] - time())) : ''); ?></div>
            <p data-stream-message <?php if (in_array($stream_state, ['countdown', 'waiting'], true)) echo 'hidden'; ?>><?php echo $stream_state === 'ended' ? 'Continue studying and reflecting through our messages and teaching notes.' : 'Gather with us for worship, the Word, and prayer.'; ?></p>
            <noscript><p>Enable JavaScript for automatic live updates. <a href="<?php echo esc_url(!empty($stream['video']) ? 'https://www.youtube.com/watch?v=' . $stream['video'] : home_url('/downloads/')); ?>">Open the broadcast or message library →</a></p></noscript>
        </div>
    </div>
    <div class="meeting-player" data-stream-player hidden></div>
    <div class="wrap stream-tools">
            <div class="actions stream-actions">
                <a class="button stream-details" href="#sunday-worship">View meeting details</a>
                <a class="button outline stream-downloads" href="<?php echo esc_url(home_url('/downloads/')); ?>">Browse messages →</a>
            </div>

    </div>
</section>
