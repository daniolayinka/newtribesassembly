# Downloads editor and frontend

Create or edit a Download in WordPress. Write the message in the main Gutenberg editor using Paragraph, Heading, List, Quote, and other blocks.

The Download sections panel contains:
- Message details: date, speaker, series, and references.
- Video: iframe or HTML embed markup. Script tags are stripped; providers requiring JavaScript SDKs need an iframe embed instead. When empty, the featured image is shown. If both are empty, no media panel is rendered.
- Audio: optional podcast/Spotify embed, audio upload, or external listening links.
- PDF download: automatic branded PDF from the message text, or an uploaded PDF override. The automatic version excludes images, players, and interactive embeds. Disable the checkbox to hide automatic generation.

Downloads is the single admin creation workflow. Legacy Messages, Videos, and Audio admin menu entries are hidden; their existing records and public URLs are preserved. Only entered components appear. Previously uploaded PDFs and audio files retain their field keys. Placeholder import notes are not presented as message content. Existing records need Gutenberg content entered before an automatic PDF is available; original uploaded PDFs continue to download.

The archive uses featured-image cards, with a typographic fallback when no image is supplied.

## Deployment

Composer's locked Dompdf dependency and vendor files are included for the existing Cloudways Git-pull workflow. PHP must satisfy composer.lock (PHP 8.1+, DOM, mbstring, and iconv). Run `composer check-platform-reqs --no-dev` on the target to verify its environment. No migration is needed. PDF generation disables remote asset fetching and executable PHP. Only published, non-password-protected Downloads can use the public PDF endpoint.

## Local verification

PHP lint and dependency platform checks passed. Verified the archive and existing download pages at desktop and 390px width, plus optional embed/featured-image selection and hidden empty audio sections using a temporary test message. Generated and visually checked all three pages of an A4 PDF containing H2, H3, lists, and quotes. The test message and image were removed afterward. External provider playback depends on a valid, publicly embeddable video/episode; the test verified container rendering, not provider playback availability.
