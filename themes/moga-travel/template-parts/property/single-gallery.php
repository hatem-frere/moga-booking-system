<?php
/**
 * Property Gallery — Single Page Mosaic + Thumbnail Strip
 *
 * Path: themes/moga-travel/template-parts/property/single-gallery.php
 *
 * Layout:
 *   - Mosaic: 1 large image left + up to 4 smaller images right (2×2)
 *   - Thumbnail strip below mosaic (remaining images)
 *   - Last thumbnail shows "See all X photos" overlay
 *   - Clicking any image opens GLightbox for ALL gallery images
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property_id = get_the_ID();

$gallery_json = get_post_meta( $property_id, '_moga_gallery', true );
$gallery_ids  = $gallery_json ? json_decode( $gallery_json, true ) : array();

if ( empty( $gallery_ids ) && has_post_thumbnail() ) {
    $gallery_ids[] = get_post_thumbnail_id( $property_id );
}

if ( empty( $gallery_ids ) ) {
    return;
}

$gallery_count = count( $gallery_ids );
$mosaic_ids    = array_slice( $gallery_ids, 0, 5 );
$mosaic_count  = count( $mosaic_ids );
$thumb_ids     = $gallery_count > 5 ? array_slice( $gallery_ids, 5 ) : array();
$gallery_key   = 'property-' . $property_id;
?>

<div class="moga-property-gallery" id="moga-property-gallery">

    <?php // ---- Mosaic Grid ---- ?>
    <div class="moga-gallery-mosaic moga-gallery-mosaic--<?php echo esc_attr( $mosaic_count ); ?>" data-total="<?php echo esc_attr( $gallery_count ); ?>">

        <?php foreach ( $mosaic_ids as $index => $attachment_id ) :
            $is_large = ( 0 === $index );
            $is_last  = ( $index === $mosaic_count - 1 );
            $img_size = $is_large ? 'large' : 'medium_large';
            $img_url  = wp_get_attachment_image_url( $attachment_id, $img_size );
            $img_full = wp_get_attachment_image_url( $attachment_id, 'full' );
            $img_alt  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true )
                ?: ( get_the_title( $property_id ) . ' — ' . sprintf( __( 'photo %d', 'moga-travel' ), $index + 1 ) );
            if ( ! $img_url ) continue;
        ?>
            <a
                href="<?php echo esc_url( $img_full ); ?>"
                class="moga-gallery-mosaic__item<?php echo $is_large ? ' moga-gallery-mosaic__item--large' : ''; ?>"
                data-gallery="<?php echo esc_attr( $gallery_key ); ?>"
                data-glightbox="gallery: <?php echo esc_attr( $gallery_key ); ?>; description: <?php echo esc_attr( get_the_title( $property_id ) ); ?>"
                aria-label="<?php printf( esc_attr__( 'Photo %1$d of %2$d — %3$s', 'moga-travel' ), $index + 1, $gallery_count, get_the_title( $property_id ) ); ?>"
            >
                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>" class="moga-gallery-mosaic__img" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>">

                <?php if ( $is_last && empty( $thumb_ids ) && $gallery_count > 1 ) : ?>
                    <div class="moga-gallery-mosaic__overlay" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <span><?php printf( esc_html__( 'See all %d photos', 'moga-travel' ), $gallery_count ); ?></span>
                    </div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <?php // Hidden links for all images beyond the mosaic (picked up by GLightbox). ?>
        <?php foreach ( $thumb_ids as $attachment_id ) :
            $img_full = wp_get_attachment_image_url( $attachment_id, 'full' );
            $img_alt  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $property_id );
            if ( ! $img_full ) continue;
        ?>
            <a href="<?php echo esc_url( $img_full ); ?>" class="moga-gallery-hidden-link" data-gallery="<?php echo esc_attr( $gallery_key ); ?>" data-glightbox="gallery: <?php echo esc_attr( $gallery_key ); ?>" aria-hidden="true" tabindex="-1">
                <span class="sr-only"><?php echo esc_html( $img_alt ); ?></span>
            </a>
        <?php endforeach; ?>

    </div>
    <?php // ---- End Mosaic Grid ---- ?>


    <?php // ---- Thumbnail Strip (images 6+ shown as thumbnails below mosaic) ---- ?>
    <?php if ( ! empty( $thumb_ids ) ) :
        $max_thumbs   = 6; // max thumbnails shown in strip
        $strip_ids    = array_slice( $thumb_ids, 0, $max_thumbs );
        $remaining    = $gallery_count - 5 - count( $strip_ids );
        $last_strip   = count( $strip_ids ) - 1;
    ?>
        <div class="moga-gallery-thumbs" role="list" aria-label="<?php esc_attr_e( 'More photos', 'moga-travel' ); ?>">
            <?php foreach ( $strip_ids as $ti => $attachment_id ) :
                $thumb_url = wp_get_attachment_image_url( $attachment_id, 'moga-card' );
                $full_url  = wp_get_attachment_image_url( $attachment_id, 'full' );
                $alt       = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $property_id );
                $is_last_t = ( $ti === $last_strip );
                if ( ! $thumb_url ) continue;
            ?>
                <a
                    href="<?php echo esc_url( $full_url ); ?>"
                    class="moga-gallery-thumbs__item<?php echo ( $is_last_t && $remaining > 0 ) ? ' moga-gallery-thumbs__item--more' : ''; ?>"
                    data-gallery="<?php echo esc_attr( $gallery_key ); ?>"
                    data-glightbox="gallery: <?php echo esc_attr( $gallery_key ); ?>"
                    role="listitem"
                    aria-label="<?php printf( esc_attr__( 'Photo %d — %s', 'moga-travel' ), 5 + $ti + 1, get_the_title( $property_id ) ); ?>"
                >
                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" class="moga-gallery-thumbs__img" loading="lazy">
                    <?php if ( $is_last_t && $remaining > 0 ) : ?>
                        <div class="moga-gallery-thumbs__overlay" aria-hidden="true">
                            <span>+<?php echo esc_html( $remaining ); ?></span>
                            <small><?php esc_html_e( 'more', 'moga-travel' ); ?></small>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php // ---- End Thumbnail Strip ---- ?>


    <?php // "View all" button — mobile only. ?>
    <button type="button" class="moga-gallery-view-all-btn" id="moga-gallery-view-all" aria-label="<?php printf( esc_attr__( 'View all %d photos', 'moga-travel' ), $gallery_count ); ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
        </svg>
        <?php printf( esc_html__( 'View all %d photos', 'moga-travel' ), $gallery_count ); ?>
    </button>

</div>