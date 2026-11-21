# Scheduled YouTube streams

## Setup

1. In Google Cloud, enable YouTube Data API v3 and create an API key. Restrict it to YouTube Data API v3 and the hosting server's outbound IP address.
2. Save the key under WordPress **Meeting Settings → YouTube Data API key**. Alternatively define `NTA_YOUTUBE_API_KEY` in wp-config.php. Never commit the key. Local and production need their own configuration; local IP restrictions must allow local server requests.
3. Under **Meeting Settings → YouTube channel**, save the channel URL, @handle, or UC channel ID once. The default is `https://www.youtube.com/@NewtribesAssembly`.
4. In WordPress, go to **Streams → Add Stream**. Add the **Stream title**, featured image, timezone, and start date/time. Leave the YouTube video override empty for automatic channel detection. Publish the entry immediately; the Stream schedule date controls the countdown, not WordPress's publish date.
5. In YouTube Studio, use a Public broadcast with embedding enabled. Connect OBS to YouTube using its account connection or saved stream key. Enable YouTube auto-start and auto-stop if you want OBS start/stop to publish/end the broadcast automatically. Verify those controls in Live Control Room. No advance YouTube scheduling is required for channel discovery.
6. For a specific or unlisted broadcast, optionally enter its exact video URL. That overrides channel discovery for the entry.

This integration discovers the active public broadcast on the configured channel; it does not create broadcasts on YouTube or start an encoder. It replaces the previous manually embedded Meetings player. Existing legacy embed settings remain in the database but are no longer used.

## Visitor flow

- Before the scheduled time: thumbnail and countdown.
- At zero without a detected live broadcast: thumbnail, `00:00:00`, and **Starting soon**. The countdown never becomes negative.
- Confirmed live: YouTube player with controls and fullscreen. Muted autoplay is requested; browser restrictions still apply. Visitors enable sound in the player.
- Confirmed ended: player removed, thumbnail restored, **This gathering has ended**, and **Browse messages** linking to `/downloads/`. The ended result is remembered for that video ID.
- No published valid stream: a general invitation and meeting details.
- No API key, invalid video, non-embeddable video, or unavailable API: countdown/waiting remains; there is no assumed live state based solely on the clock. An already-playing browser preserves the player through temporary API lookup failures.

The most recently scheduled past gathering remains selected for a 12-hour service window, including its ended message. After that, the next upcoming gathering is selected if available. With no upcoming gathering the latest past entry stays selected. Draft, password-protected, and invalid-date entries are excluded. The optional video override uses the existing YouTube URL parser. Set an entry to Draft to remove it from selection.

## Operation

Channel discovery starts 30 minutes before the scheduled start and continues up to four hours after it. It searches for public, embeddable live videos and caches discovery results for three minutes across all visitors. Once a live video is found, its ID is attached to that Stream entry; subsequent checks use the cheaper direct video lookup, and its ended state is retained. Create a new Stream entry for the next broadcast. A title entered in WordPress is preserved; YouTube's title does not replace it. Discovery uses YouTube's Search Queries quota, so do not poll continuously outside service windows. Check quota usage in Google Cloud if many services share the same API project.

The browser checks the site's `/wp-json/nta/v1/stream` endpoint every 30 seconds while visible and every 60 seconds in the background. YouTube responses are shared server-side for 45 seconds (ended results persist), so direct video status switches can take around a minute or more, while initial channel discovery can take around three to four minutes; it is not instantaneous. The API key is never included in the browser payload. Keep this REST route out of any CDN/page-cache rules; its response sends `Cache-Control: no-store`.

## Validation

Local PHP/JS syntax and whitespace checks passed. Mocked YouTube responses covered upcoming, delayed, live, ended, missing, and non-embeddable videos; URL/date validation and timezone conversion were checked. Client checks covered zero countdown, player insertion without restarting on each poll, API-failure continuity, and ended CTA/player removal. Desktop countdown and ended views and the 390px delayed-start view were inspected. Temporary test stream entries were removed. The saved local API key successfully resolved @NewtribesAssembly to UCVBIMpuFGmjBLAPjK-Srh9Q and performed a real live search; no active public embeddable broadcast was returned. Mocked channel tests verified no video URL required, retained Stream title, service-window gating, cached discovery, remembered video ID, and the live-to-ended transition. Real OBS live-to-ended playback remains to be verified during a public broadcast.

## Visitor refresh and cache protection

The Meetings poster includes **Check for live stream**. It POSTs to `/wp-json/nta/v1/stream/refresh`, clears only the selected stream's YouTube lookup/discovery caches, and returns the updated state. The result is announced inline. A shared one-minute cooldown prevents repeated visitors from forcing simultaneous searches. Channel discovery also has a shared 90-search daily ceiling, reset at midnight Pacific time, leaving headroom under YouTube's default search limit. The button does not purge the site's general cache or bypass the published entry's service window.

The Meetings page and stream API responses send no-store/no-cache. Breeze's JS exclusion filter protects streams.js. Install `deployment/mu-plugins/nta-live-cache-bypass.php` in `wp-content/mu-plugins/` as an additional early cache guard, and exclude Meetings plus `/wp-json/nta/v1/stream(.*)` in Breeze's Never Cache URLs. Keep streams.js out of minification and JS delay.

Production review on 21 November 2026: Breeze 2.6.5 was activated during the review; Object Cache Pro was already active. JS minification/grouping/delay were off. The Meetings response initially showed `X-Cache: HIT` and `Age: 10666`. Saved exclusions were added, the MU cache guard installed, Breeze configuration regenerated, and the Meetings page's cache purged through Breeze/Varnish. Two consecutive anonymous production requests then returned `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`, `Age: 0`, and `X-Cache: MISS`. Settings backup: `/home/master/nta-live-cache-20261121-061323/` outside the web root. The stream feature/refresh button itself remains theme source pending Git deployment; production's stream REST route was still 404 before that deployment.

Manual refresh validation: a cached negative discovery was replaced with a newly detected live video in a mocked integration check; the REST POST response carried no-store headers; repeated requests returned the cooldown message; browser interaction showed the result and disabled the button temporarily. No real live broadcast was started by these checks.
