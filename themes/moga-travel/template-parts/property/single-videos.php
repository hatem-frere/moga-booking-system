<?php
/**
 * Property Videos — Sidebar Widget
 *
 * Path: themes/moga-travel/template-parts/property/single-videos.php
 *
 * Displays in the sidebar below the map/location widget.
 * Shows 1 video thumbnail. If multiple videos exist, the
 * thumbnail gets a "+N more" overlay where N = total - 1.
 * Clicking opens GLightbox for all videos in sequence.
 *
 * Supports all YouTube URL formats:
 *   - youtube.com/watch?v=VIDEO_ID   (standard)
 *   - youtu.be/VIDEO_ID              (short link)
 *   - youtube.com/shorts/VIDEO_ID    (YouTube Shorts)
 *   - youtube.com/embed/VIDEO_ID     (embed link)
 *   All are normalized to watch?v= format for GLightbox compatibility.
 *
 * Supports Vimeo URLs:
 *   - vimeo.com/VIDEO_ID
 *   - vimeo.com/video/VIDEO_ID
 *
 * Supports local video uploads (.mp4, .webm, .ogv).
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property_id = get_the_ID();

// ============================================================
// READ VIDEO META
// ============================================================

$videos_json = get_post_meta( $property_id, '_moga_videos', true );
$video_items = $videos_json ? json_decode( $videos_json, true ) : array();
$video_items = is_array( $video_items ) ? $video_items : array();

// Remove entries without a usable URL or ID.
$video_items = array_values( array_filter( $video_items, function( $v ) {
    if ( ! isset( $v['type'] ) ) return false;
    if ( 'upload' === $v['type'] ) return ! empty( $v['id'] ) || ! empty( $v['url'] );
    if ( 'url'    === $v['type'] ) return ! empty( $v['url'] );
    return false;
} ) );

if ( empty( $video_items ) ) {
    return; // No videos — render nothing.
}

$total_videos = count( $video_items );
$extra_videos = $total_videos - 1; // Shown: 1. Extra: rest.
$gallery_key  = 'property-' . $property_id; // shared page key, videos append '-videos'


// ============================================================
// URL NORMALIZATION HELPERS (inline — no named functions
// to avoid redeclaration if template is loaded more than once)
// ============================================================

/*
 * Extracts the YouTube video ID from ANY YouTube URL format:
 *   - youtube.com/watch?v=VIDEO_ID
 *   - youtu.be/VIDEO_ID
 *   - youtube.com/shorts/VIDEO_ID   ← Shorts fix
 *   - youtube.com/embed/VIDEO_ID
 * Returns the 11-character video ID string, or empty string if not matched.
 */
$moga_extract_yt_id = function( $url ) {
    if ( preg_match(
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
        $url,
        $m
    ) ) {
        return $m[1];
    }
    return '';
};

/*
 * Extracts the Vimeo video ID from a Vimeo URL:
 *   - vimeo.com/VIDEO_ID
 *   - vimeo.com/video/VIDEO_ID
 * Returns the numeric ID string, or empty string if not matched.
 */
$moga_extract_vimeo_id = function( $url ) {
    if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m ) ) {
        return $m[1];
    }
    return '';
};

/*
 * Resolves a video item into the data needed for GLightbox and thumbnail display.
 * Returns an array: [url, thumb, type_attr]
 *   - url:       normalized href for the GLightbox <a> tag
 *   - thumb:     thumbnail image URL (empty string if unavailable)
 *   - type_attr: value for data-type attribute ('' = GLightbox auto-detect)
 */
$moga_resolve_video = function( $video, $property_id ) use ( $moga_extract_yt_id, $moga_extract_vimeo_id ) {

    $url       = '';
    $thumb     = '';
    $type_attr = '';

    if ( 'upload' === $video['type'] ) {

        // Local video upload.
        $aid   = absint( $video['id'] ?? 0 );
        $url   = $aid ? wp_get_attachment_url( $aid ) : ( $video['url'] ?? '' );
        $type_attr = 'video'; // GLightbox needs explicit type for local files.

        // Poster frame: try attachment image, then property thumbnail.
        $thumb = $aid ? wp_get_attachment_image_url( $aid, 'medium' ) : '';
        if ( ! $thumb ) {
            $thumb = get_the_post_thumbnail_url( $property_id, 'medium' ) ?: '';
        }

    } elseif ( 'url' === $video['type'] ) {

        $raw_url = $video['url'] ?? '';

        // Check YouTube first — all formats normalized to watch?v= for GLightbox.
        $yt_id = $moga_extract_yt_id( $raw_url );

        if ( $yt_id ) {
            // Normalize to standard format — GLightbox auto-detects this reliably.
            $url   = 'https://www.youtube.com/watch?v=' . $yt_id;
            $thumb = 'https://img.youtube.com/vi/' . $yt_id . '/hqdefault.jpg';
            $type_attr = ''; // GLightbox auto-detects standard YouTube URLs.
            return compact( 'url', 'thumb', 'type_attr' );
        }

        // Check Vimeo.
        $vimeo_id = $moga_extract_vimeo_id( $raw_url );

        if ( $vimeo_id ) {
            $url   = 'https://vimeo.com/' . $vimeo_id;
            $thumb = get_the_post_thumbnail_url( $property_id, 'medium' ) ?: '';
            $type_attr = ''; // GLightbox auto-detects Vimeo URLs.
            return compact( 'url', 'thumb', 'type_attr' );
        }

        // Other URL (unknown format) — pass as-is and let GLightbox try.
        $url       = $raw_url;
        $thumb     = get_the_post_thumbnail_url( $property_id, 'medium' ) ?: '';
        $type_attr = '';
    }

    return compact( 'url', 'thumb', 'type_attr' );
};


// ============================================================
// RESOLVE FIRST VIDEO (visible thumbnail)
// ============================================================

$first_video = $moga_resolve_video( $video_items[0], $property_id );
$first_url   = $first_video['url'];
$first_thumb = $first_video['thumb'];
$first_type  = $first_video['type_attr'];

if ( ! $first_url ) {
    return; // Could not resolve URL — render nothing.
}


// ============================================================
// RESOLVE EXTRA VIDEOS (hidden GLightbox links)
// ============================================================

$extra_video_links = array();

for ( $i = 1; $i < $total_videos; $i++ ) {
    $resolved = $moga_resolve_video( $video_items[ $i ], $property_id );
    if ( $resolved['url'] ) {
        $extra_video_links[] = $resolved;
    }
}

// Recalculate extra count based on resolved (valid) links only.
$extra_videos = count( $extra_video_links );
?>

<div class="moga-sidebar-videos" id="moga-sidebar-videos">

    <h3 class="moga-sidebar-videos__title">
        <?php esc_html_e( 'Videos', 'moga-travel' ); ?>
        <?php if ( $total_videos > 1 ) : ?>
            <span class="moga-sidebar-videos__count">(<?php echo esc_html( $total_videos ); ?>)</span>
        <?php endif; ?>
    </h3>

    <div class="moga-sidebar-videos__wrap">

        <?php // ---- Main video thumbnail (the only visible one) ---- ?>
        <a
            href="<?php echo esc_url( $first_url ); ?>"
            class="moga-sidebar-videos__thumb<?php echo $extra_videos > 0 ? ' moga-sidebar-videos__thumb--has-more' : ''; ?>"
            data-gallery="<?php echo esc_attr( $gallery_key . '-videos' ); ?>"
            data-glightbox="gallery: <?php echo esc_attr( $gallery_key . '-videos' ); ?>"
            <?php if ( $first_type ) : ?>
                data-type="<?php echo esc_attr( $first_type ); ?>"
            <?php endif; ?>
            aria-label="<?php
                if ( $total_videos > 1 ) {
                    printf( esc_attr__( 'Play video 1 of %d', 'moga-travel' ), $total_videos );
                } else {
                    esc_attr_e( 'Play video', 'moga-travel' );
                }
            ?>"
        >
            <?php if ( $first_thumb ) : ?>
                <img
                    src="<?php echo esc_url( $first_thumb ); ?>"
                    alt="<?php esc_attr_e( 'Video thumbnail', 'moga-travel' ); ?>"
                    class="moga-sidebar-videos__img"
                    loading="lazy"
                >
            <?php else : ?>
                <div class="moga-sidebar-videos__placeholder"></div>
            <?php endif; ?>

            <?php // Play button — always centered over the thumbnail. ?>
            <div class="moga-sidebar-videos__play" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="24" fill="rgba(0,0,0,0.55)"/>
                    <polygon points="19,14 38,24 19,34" fill="#ffffff"/>
                </svg>
            </div>

            <?php // "+N more" overlay — only when extra videos exist. ?>
            <?php if ( $extra_videos > 0 ) : ?>
                <div class="moga-sidebar-videos__more" aria-hidden="true">
                    <span class="moga-sidebar-videos__more-count">+<?php echo esc_html( $extra_videos ); ?></span>
                    <span class="moga-sidebar-videos__more-label">
                        <?php echo 1 === $extra_videos
                            ? esc_html__( 'more video', 'moga-travel' )
                            : esc_html__( 'more videos', 'moga-travel' );
                        ?>
                    </span>
                </div>
            <?php endif; ?>

        </a>

        <?php // ---- Hidden GLightbox links for remaining videos ---- ?>
        <?php foreach ( $extra_video_links as $vi => $ev ) : ?>
            <a
                href="<?php echo esc_url( $ev['url'] ); ?>"
                class="moga-gallery-hidden-link"
                data-gallery="<?php echo esc_attr( $gallery_key . '-videos' ); ?>"
                data-glightbox="gallery: <?php echo esc_attr( $gallery_key . '-videos' ); ?>"
                <?php if ( $ev['type_attr'] ) : ?>
                    data-type="<?php echo esc_attr( $ev['type_attr'] ); ?>"
                <?php endif; ?>
                aria-hidden="true"
                tabindex="-1"
            >
                <span class="sr-only">
                    <?php printf( esc_html__( 'Video %d', 'moga-travel' ), $vi + 2 ); ?>
            </a>
        <?php endforeach; ?>

    </div>

</div>