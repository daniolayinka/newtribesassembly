<?php
/** Downloads: optional message components and a portable PDF export. */
function nta_download_has_content($id) {
    $text = trim(wp_strip_all_tags(get_post_field('post_content', $id)));
    return $text !== '' && !preg_match('/^(Seeded from|Audio recording preserved from)/i', $text);
}
function nta_download_pdf_enabled($id) {
    // Existing posts retain PDF availability until explicitly disabled in the editor.
    return !metadata_exists('post', $id, '_nta_generate_pdf') || (bool) nta_meta('nta_generate_pdf', $id);
}
function nta_download_pdf_url($id) {
    $uploaded = wp_get_attachment_url((int) nta_meta('nta_pdf', $id));
    if ($uploaded) return $uploaded;
    return nta_download_has_content($id) && nta_download_pdf_enabled($id) ? add_query_arg('nta_message_pdf', $id, home_url('/')) : '';
}
function nta_download_pdf_html($id) {
    $content = do_blocks(get_post_field('post_content', $id));
    // A text-first export: no network requests, players, scripts, or layout wrappers.
    $content = preg_replace('~<(script|style|iframe|video|audio)\b[^>]*>.*?</\1>~is', '', $content);
    $content = wp_kses($content, array_fill_keys(['p','h2','h3','h4','ul','ol','li','blockquote','strong','em','b','i','br','hr','table','thead','tbody','tr','th','td','figcaption'], []));
    $title = esc_html(get_the_title($id));
    $speaker = esc_html(nta_meta('nta_speaker', $id));
    return '<!doctype html><html><head><meta charset="utf-8"><style>
    @page{margin:58px 56px 62px}body{font-family:DejaVu Sans,sans-serif;color:#233348;font-size:10pt;line-height:1.65}header{border-top:6px solid #193c62;border-bottom:1px solid #c8b68e;padding:16px 0 24px;margin-bottom:28px}.brand{font-size:10pt;letter-spacing:2px;color:#846428;text-transform:uppercase}h1{font-family:DejaVu Serif,serif;font-size:27pt;line-height:1.2;color:#193c62;margin:16px 0}h2{font-size:17pt;color:#193c62;margin-top:26px}h3{font-size:13pt;color:#193c62;margin-top:20px}h2,h3,h4{page-break-after:avoid}blockquote{border-left:3px solid #b99242;padding:8px 18px;color:#193c62;background:#f4f1e9;margin:20px 0}li{padding-bottom:5px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:6px}footer{position:fixed;bottom:-36px;font-size:8pt;color:#64748b}.summary{font-size:11pt;color:#526174}</style></head><body><footer>New Tribes Assembly · Teaching for study, prayer, and growth</footer><header><div class="brand">New Tribes Assembly / Message notes</div><h1>'.$title.'</h1><div>'.$speaker.'</div></header>'.$content.'</body></html>';
}
add_action('template_redirect', function () {
    if (!isset($_GET['nta_message_pdf'])) return;
    $id = absint($_GET['nta_message_pdf']);
    if (get_post_type($id) !== 'nta_download' || get_post_status($id) !== 'publish' || post_password_required($id) || !nta_download_has_content($id) || !nta_download_pdf_enabled($id)) {
        wp_die('This message PDF is not available.', 'PDF unavailable', ['response' => 404]);
    }
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', false);
    $pdf = new \Dompdf\Dompdf($options);
    $pdf->loadHtml(nta_download_pdf_html($id));
    $pdf->setPaper('A4');
    $pdf->render();
    $pdf->getCanvas()->page_text(475, 808, '{PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.4,0.45,0.5]);
    nocache_headers();
    $pdf->stream(sanitize_title(get_the_title($id)).'.pdf', ['Attachment' => true]);
    exit;
});
