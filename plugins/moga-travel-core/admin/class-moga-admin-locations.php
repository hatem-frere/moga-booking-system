<?php
/**
 * Location Settings — Admin Page
 *
 * Provides two features accessible via tabs:
 *
 *   Tab 1 — Import Wizard
 *     Reads the three bundled JSON files and imports countries,
 *     provinces and cities into the four location DB tables via
 *     chunked AJAX so PHP never times out. A live progress bar
 *     shows import status. The wizard is idempotent — safe to
 *     run multiple times (uses ON DUPLICATE KEY UPDATE).
 *
 *   Tab 2 — Location Editor
 *     A four-level interactive cascade (Country → Province →
 *     City → District) with full CRUD: add, rename and delete
 *     entries at every level. The only way to add districts is
 *     here — no JSON file ships with district data.
 *
 * JSON source files (bundled with the plugin):
 *   data/locations/countries.json  — 247 world countries
 *   data/locations/provinces.json  — 4,120 provinces / states / governorates
 *   data/locations/cities.json     — 48,313 cities worldwide
 *
 * DB tables written to:
 *   {prefix}moga_loc_countries
 *   {prefix}moga_loc_provinces
 *   {prefix}moga_loc_cities
 *   {prefix}moga_loc_districts
 *
 * All AJAX actions in this file are admin-only (wp_ajax_).
 * Public-facing cascade dropdowns (property/tour meta boxes,
 * search form) are handled in class-moga-ajax.php.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/admin
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Admin_Locations
 */
class Moga_Admin_Locations {

    // ============================================================
    // CONSTANTS
    // ============================================================

    /** Provinces per AJAX batch during import. */
    const BATCH_PROVINCES = 500;

    /** Cities per AJAX batch during import. */
    const BATCH_CITIES = 500;

    /** Total provinces in the bundled JSON (used for JS progress). */
    const TOTAL_PROVINCES = 4120;

    /** Total cities in the bundled JSON (used for JS progress). */
    const TOTAL_CITIES = 48313;

    /** @var string Hook suffix returned by add_submenu_page(). */
    private static $page_hook = '';


    // ============================================================
    // INIT
    // ============================================================

    /**
     * Register all hooks.
     * Called from Moga_Admin::init_components().
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {

        // Store the page hook so we can conditionally load assets.
        add_action( 'admin_menu', array( __CLASS__, 'store_page_hook' ), 20 );

        // Enqueue assets only on the location page.
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        // ---- Import AJAX actions ----
        add_action( 'wp_ajax_moga_loc_import_countries',       array( __CLASS__, 'ajax_import_countries' ) );
        add_action( 'wp_ajax_moga_loc_import_provinces',       array( __CLASS__, 'ajax_import_provinces' ) );
        add_action( 'wp_ajax_moga_loc_import_cities',          array( __CLASS__, 'ajax_import_cities' ) );
        add_action( 'wp_ajax_moga_loc_reset',                  array( __CLASS__, 'ajax_reset' ) );
        add_action( 'wp_ajax_moga_loc_get_stats',              array( __CLASS__, 'ajax_get_stats' ) );

        // ---- Location Editor cascade AJAX ----
        add_action( 'wp_ajax_moga_loc_get_provinces',          array( __CLASS__, 'ajax_get_provinces' ) );
        add_action( 'wp_ajax_moga_loc_get_cities',             array( __CLASS__, 'ajax_get_cities' ) );
        add_action( 'wp_ajax_moga_loc_get_districts',          array( __CLASS__, 'ajax_get_districts' ) );

        // ---- Location Editor CRUD AJAX ----
        add_action( 'wp_ajax_moga_loc_add',                    array( __CLASS__, 'ajax_add' ) );
        add_action( 'wp_ajax_moga_loc_update',                 array( __CLASS__, 'ajax_update' ) );
        add_action( 'wp_ajax_moga_loc_delete',                 array( __CLASS__, 'ajax_delete' ) );
    }

    /**
     * Store the page hook returned by add_submenu_page().
     * Called on 'admin_menu' priority 20 (after Moga_Admin_Menus
     * registers the page at default priority 10).
     *
     * @since  1.0.0
     * @return void
     */
    public static function store_page_hook() {
        global $_parent_pages;
        // Derive the hook from the registered submenu.
        self::$page_hook = get_plugin_page_hookname( 'moga-locations', 'moga-dashboard' );
    }

    /**
     * Enqueue CSS and JS only on the Location Settings page.
     *
     * @since  1.0.0
     * @param  string $hook Current admin page hook.
     * @return void
     */
    public static function enqueue_assets( $hook ) {

        if ( self::$page_hook && $hook !== self::$page_hook ) {
            return;
        }

        // Fallback: also match by page query var if hook detection fails.
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( self::$page_hook && 'moga-locations' !== $page ) {
            return;
        }

        $admin_url = MOGA_CORE_URL . 'admin/assets/';

        wp_enqueue_style(
            'moga-admin-locations',
            $admin_url . 'css/admin-locations.css',
            array(),
            MOGA_CORE_VERSION
        );

        wp_enqueue_script(
            'moga-admin-locations',
            $admin_url . 'js/admin-locations.js',
            array( 'jquery' ),
            MOGA_CORE_VERSION,
            true
        );

        wp_localize_script(
            'moga-admin-locations',
            'mogaLocData',
            array(
                'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
                'nonce'           => wp_create_nonce( 'moga_loc_nonce' ),
                'batchProvinces'  => self::BATCH_PROVINCES,
                'batchCities'     => self::BATCH_CITIES,
                'totalProvinces'  => self::TOTAL_PROVINCES,
                'totalCities'     => self::TOTAL_CITIES,
                'i18n'            => array(
                    'importing'         => __( 'Importing…', 'moga-travel-core' ),
                    'importCountries'   => __( 'Importing countries…', 'moga-travel-core' ),
                    'importProvinces'   => __( 'Importing provinces…', 'moga-travel-core' ),
                    'importCities'      => __( 'Importing cities…', 'moga-travel-core' ),
                    'importDone'        => __( '✅ Import complete! All location data is ready.', 'moga-travel-core' ),
                    'importFailed'      => __( '❌ Import failed. Check browser console for details.', 'moga-travel-core' ),
                    'resetting'         => __( 'Resetting…', 'moga-travel-core' ),
                    'resetDone'         => __( 'All location data has been reset.', 'moga-travel-core' ),
                    'resetConfirm'      => __( 'This will delete ALL imported location data (countries, provinces, cities and districts). Are you sure?', 'moga-travel-core' ),
                    'deleteConfirm'     => __( 'Delete this entry? This will also delete all its children (provinces, cities, districts).', 'moga-travel-core' ),
                    'saving'            => __( 'Saving…', 'moga-travel-core' ),
                    'saved'             => __( 'Saved.', 'moga-travel-core' ),
                    'errorSave'         => __( 'Save failed. Please try again.', 'moga-travel-core' ),
                    'selectCountry'     => __( '— Select Country —', 'moga-travel-core' ),
                    'selectProvince'    => __( '— Select Province —', 'moga-travel-core' ),
                    'selectCity'        => __( '— Select City —', 'moga-travel-core' ),
                    'noProvinces'       => __( 'No provinces found for this country.', 'moga-travel-core' ),
                    'noCities'          => __( 'No cities found for this province.', 'moga-travel-core' ),
                    'noDistricts'       => __( 'No districts yet. Use the form below to add the first one.', 'moga-travel-core' ),
                    'enterName'         => __( 'Enter name', 'moga-travel-core' ),
                    'enterIsoCode'      => __( 'ISO code (e.g. EG)', 'moga-travel-core' ),
                    'latitude'          => __( 'Latitude (optional)', 'moga-travel-core' ),
                    'longitude'         => __( 'Longitude (optional)', 'moga-travel-core' ),
                    'add'               => __( 'Add', 'moga-travel-core' ),
                    'cancel'            => __( 'Cancel', 'moga-travel-core' ),
                    'edit'              => __( 'Edit', 'moga-travel-core' ),
                    'delete'            => __( 'Delete', 'moga-travel-core' ),
                    'save'              => __( 'Save', 'moga-travel-core' ),
                ),
            )
        );
    }


    // ============================================================
    // PAGE RENDER
    // ============================================================

    /**
     * Render the full Location Settings page (both tabs).
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'moga-travel-core' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $tab = isset( $_GET['tab'] )
            ? sanitize_text_field( wp_unslash( $_GET['tab'] ) )
            : 'import';

        // Live DB counts for the status cards.
        $count_countries  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_countries" );
        $count_provinces  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_provinces" );
        $count_cities     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_cities" );
        $count_districts  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_districts" );

        // Load all countries for the Editor tab dropdown.
        $all_countries = $wpdb->get_results(
            "SELECT id, name, iso_code FROM {$prefix}loc_countries ORDER BY name ASC"
        );
        ?>
        <div class="wrap moga-loc-wrap">

            <h1 class="moga-loc-page-title">
                📍 <?php esc_html_e( 'Location Settings', 'moga-travel-core' ); ?>
            </h1>

            <?php // Tab navigation ?>
            <div class="moga-loc-tabs">
                <a
                    href="<?php echo esc_url( admin_url( 'admin.php?page=moga-locations&tab=import' ) ); ?>"
                    class="moga-loc-tab<?php echo 'import' === $tab ? ' moga-loc-tab--active' : ''; ?>"
                >
                    <?php esc_html_e( 'Import Data', 'moga-travel-core' ); ?>
                </a>
                <a
                    href="<?php echo esc_url( admin_url( 'admin.php?page=moga-locations&tab=editor' ) ); ?>"
                    class="moga-loc-tab<?php echo 'editor' === $tab ? ' moga-loc-tab--active' : ''; ?>"
                >
                    <?php esc_html_e( 'Location Editor', 'moga-travel-core' ); ?>
                </a>
            </div>

            <div class="moga-loc-tab-body">

                <?php // ============================================================ ?>
                <?php // TAB 1: IMPORT WIZARD ?>
                <?php // ============================================================ ?>
                <?php if ( 'import' === $tab ) : ?>

                    <div class="moga-loc-import">

                        <p class="moga-loc-import__intro">
                            <?php esc_html_e( 'Click "Start Import" to load all countries, provinces/states/governorates, and cities from the bundled data files into the database. The import runs in small batches so it never times out. It is safe to run multiple times.', 'moga-travel-core' ); ?>
                        </p>

                        <?php // Status cards ?>
                        <div class="moga-loc-stats">

                            <?php
                            $stat_cards = array(
                                array(
                                    'label'     => __( 'Countries', 'moga-travel-core' ),
                                    'available' => 247,
                                    'imported'  => $count_countries,
                                ),
                                array(
                                    'label'     => __( 'Provinces / States', 'moga-travel-core' ),
                                    'available' => self::TOTAL_PROVINCES,
                                    'imported'  => $count_provinces,
                                ),
                                array(
                                    'label'     => __( 'Cities', 'moga-travel-core' ),
                                    'available' => self::TOTAL_CITIES,
                                    'imported'  => $count_cities,
                                ),
                                array(
                                    'label'     => __( 'Districts', 'moga-travel-core' ),
                                    'available' => null, // Districts are added manually.
                                    'imported'  => $count_districts,
                                ),
                            );
                            foreach ( $stat_cards as $card ) :
                                $done = ( null === $card['available'] )
                                    || ( $card['imported'] >= $card['available'] );
                            ?>
                                <div class="moga-loc-stat-card<?php echo $done && $card['imported'] > 0 ? ' moga-loc-stat-card--done' : ''; ?>">
                                    <div class="moga-loc-stat-card__count">
                                        <?php echo esc_html( number_format( $card['imported'] ) ); ?>
                                    </div>
                                    <div class="moga-loc-stat-card__label">
                                        <?php echo esc_html( $card['label'] ); ?>
                                    </div>
                                    <?php if ( null !== $card['available'] ) : ?>
                                        <div class="moga-loc-stat-card__available">
                                            <?php
                                            printf(
                                                /* translators: %s: total available */
                                                esc_html__( 'of %s available', 'moga-travel-core' ),
                                                esc_html( number_format( $card['available'] ) )
                                            );
                                            ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="moga-loc-stat-card__available">
                                            <?php esc_html_e( 'Added via Location Editor', 'moga-travel-core' ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $done && $card['imported'] > 0 ) : ?>
                                        <div class="moga-loc-stat-card__badge">✓</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                        </div>
                        <?php // End status cards ?>

                        <?php // Action buttons ?>
                        <div class="moga-loc-import-actions">
                            <button
                                type="button"
                                id="moga-loc-import-btn"
                                class="button button-primary button-large"
                            >
                                <?php esc_html_e( '▶ Start Import', 'moga-travel-core' ); ?>
                            </button>
                            <button
                                type="button"
                                id="moga-loc-reset-btn"
                                class="button button-large moga-loc-btn-reset"
                            >
                                <?php esc_html_e( '↺ Reset All Data', 'moga-travel-core' ); ?>
                            </button>
                        </div>

                        <?php // Progress bar (hidden until import starts) ?>
                        <div id="moga-loc-progress" class="moga-loc-progress" style="display:none;" aria-live="polite">
                            <div class="moga-loc-progress__bar-wrap">
                                <div
                                    id="moga-loc-progress-fill"
                                    class="moga-loc-progress__fill"
                                    role="progressbar"
                                    aria-valuenow="0"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    style="width:0%"
                                ></div>
                            </div>
                            <div id="moga-loc-progress-text" class="moga-loc-progress__text">0%</div>
                            <div id="moga-loc-progress-step" class="moga-loc-progress__step"></div>
                        </div>

                        <?php // Import log ?>
                        <div id="moga-loc-log" class="moga-loc-log" aria-live="polite"></div>

                        <?php // Data quality note ?>
                        <div class="moga-loc-note">
                            <strong><?php esc_html_e( 'Data Quality Note:', 'moga-travel-core' ); ?></strong>
                            <?php esc_html_e( 'The bundled data covers 247 countries and 48,313 cities worldwide. Province and city names for Arab countries use English transliteration — use the Location Editor tab to rename entries to your preferred spellings after import. Districts are not included in the import and must be added manually via the Location Editor.', 'moga-travel-core' ); ?>
                        </div>

                    </div>
                    <?php // End import tab ?>

                <?php // ============================================================ ?>
                <?php // TAB 2: LOCATION EDITOR ?>
                <?php // ============================================================ ?>
                <?php elseif ( 'editor' === $tab ) : ?>

                    <div class="moga-loc-editor">

                        <p class="moga-loc-editor__intro">
                            <?php esc_html_e( 'Select a country to load its provinces, then select a province to load its cities, then select a city to manage its districts. Use the Add, Edit and Delete buttons at each level to manage your location data.', 'moga-travel-core' ); ?>
                        </p>

                        <?php if ( empty( $all_countries ) ) : ?>
                            <div class="moga-loc-editor__empty">
                                <p>
                                    <?php
                                    printf(
                                        /* translators: %s: link to import tab */
                                        esc_html__( 'No location data found. Please %s first.', 'moga-travel-core' ),
                                        '<a href="' . esc_url( admin_url( 'admin.php?page=moga-locations&tab=import' ) ) . '">'
                                            . esc_html__( 'run the import', 'moga-travel-core' )
                                        . '</a>'
                                    );
                                    ?>
                                </p>
                            </div>
                        <?php else : ?>

                        <div class="moga-loc-panels">

                            <?php // ---- Panel 1: Country ---- ?>
                            <div class="moga-loc-panel" id="moga-loc-panel-country">
                                <div class="moga-loc-panel__header">
                                    <h3 class="moga-loc-panel__title">
                                        <span class="moga-loc-panel__num">1</span>
                                        <?php esc_html_e( 'Country', 'moga-travel-core' ); ?>
                                    </h3>
                                </div>

                                <div class="moga-loc-panel__body">
                                    <select
                                        id="moga-loc-country-select"
                                        class="moga-loc-select"
                                        data-level="country"
                                    >
                                        <option value="">— <?php esc_html_e( 'Select Country', 'moga-travel-core' ); ?> —</option>
                                        <?php foreach ( $all_countries as $country ) : ?>
                                            <option
                                                value="<?php echo esc_attr( $country->id ); ?>"
                                                data-iso="<?php echo esc_attr( $country->iso_code ); ?>"
                                            ><?php echo esc_html( $country->name ); ?> (<?php echo esc_html( $country->iso_code ); ?>)</option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div class="moga-loc-panel__actions">
                                        <button
                                            type="button"
                                            class="button moga-loc-add-trigger"
                                            data-level="country"
                                        >+ <?php esc_html_e( 'Add Country', 'moga-travel-core' ); ?></button>

                                        <button
                                            type="button"
                                            class="button moga-loc-edit-trigger"
                                            data-level="country"
                                            disabled
                                        ><?php esc_html_e( 'Edit', 'moga-travel-core' ); ?></button>

                                        <button
                                            type="button"
                                            class="button moga-loc-delete-trigger"
                                            data-level="country"
                                            disabled
                                        ><?php esc_html_e( 'Delete', 'moga-travel-core' ); ?></button>
                                    </div>

                                    <?php // Inline add/edit form for country ?>
                                    <div id="moga-loc-form-country" class="moga-loc-inline-form" style="display:none;">
                                        <div class="moga-loc-inline-form__row">
                                            <input
                                                type="text"
                                                id="moga-loc-country-name"
                                                class="regular-text"
                                                placeholder="<?php esc_attr_e( 'Country name', 'moga-travel-core' ); ?>"
                                            >
                                            <input
                                                type="text"
                                                id="moga-loc-country-iso"
                                                class="small-text"
                                                maxlength="3"
                                                placeholder="<?php esc_attr_e( 'ISO (e.g. EG)', 'moga-travel-core' ); ?>"
                                                style="width:90px;"
                                            >
                                        </div>
                                        <div class="moga-loc-inline-form__row">
                                            <button type="button" class="button button-primary moga-loc-save-btn" data-level="country">
                                                <?php esc_html_e( 'Save', 'moga-travel-core' ); ?>
                                            </button>
                                            <button type="button" class="button moga-loc-cancel-btn" data-level="country">
                                                <?php esc_html_e( 'Cancel', 'moga-travel-core' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php // End panel country ?>

                            <?php // ---- Panel 2: Province ---- ?>
                            <div class="moga-loc-panel" id="moga-loc-panel-province" style="display:none;">
                                <div class="moga-loc-panel__header">
                                    <h3 class="moga-loc-panel__title">
                                        <span class="moga-loc-panel__num">2</span>
                                        <?php esc_html_e( 'State / Province / Governorate', 'moga-travel-core' ); ?>
                                    </h3>
                                    <div class="moga-loc-panel__spinner" id="moga-loc-province-spinner" style="display:none;"></div>
                                </div>

                                <div class="moga-loc-panel__body">
                                    <div
                                        id="moga-loc-province-list"
                                        class="moga-loc-list"
                                        data-level="province"
                                    ></div>

                                    <div class="moga-loc-panel__actions">
                                        <button
                                            type="button"
                                            class="button moga-loc-add-trigger"
                                            data-level="province"
                                        >+ <?php esc_html_e( 'Add Province', 'moga-travel-core' ); ?></button>
                                    </div>

                                    <div id="moga-loc-form-province" class="moga-loc-inline-form" style="display:none;">
                                        <div class="moga-loc-inline-form__row">
                                            <input
                                                type="text"
                                                id="moga-loc-province-name"
                                                class="regular-text"
                                                placeholder="<?php esc_attr_e( 'Province / State / Governorate name', 'moga-travel-core' ); ?>"
                                            >
                                        </div>
                                        <div class="moga-loc-inline-form__row">
                                            <button type="button" class="button button-primary moga-loc-save-btn" data-level="province">
                                                <?php esc_html_e( 'Save', 'moga-travel-core' ); ?>
                                            </button>
                                            <button type="button" class="button moga-loc-cancel-btn" data-level="province">
                                                <?php esc_html_e( 'Cancel', 'moga-travel-core' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php // End panel province ?>

                            <?php // ---- Panel 3: City ---- ?>
                            <div class="moga-loc-panel" id="moga-loc-panel-city" style="display:none;">
                                <div class="moga-loc-panel__header">
                                    <h3 class="moga-loc-panel__title">
                                        <span class="moga-loc-panel__num">3</span>
                                        <?php esc_html_e( 'City', 'moga-travel-core' ); ?>
                                    </h3>
                                    <div class="moga-loc-panel__spinner" id="moga-loc-city-spinner" style="display:none;"></div>
                                </div>

                                <div class="moga-loc-panel__body">
                                    <div
                                        id="moga-loc-city-list"
                                        class="moga-loc-list"
                                        data-level="city"
                                    ></div>

                                    <div class="moga-loc-panel__actions">
                                        <button
                                            type="button"
                                            class="button moga-loc-add-trigger"
                                            data-level="city"
                                        >+ <?php esc_html_e( 'Add City', 'moga-travel-core' ); ?></button>
                                    </div>

                                    <div id="moga-loc-form-city" class="moga-loc-inline-form" style="display:none;">
                                        <div class="moga-loc-inline-form__row">
                                            <input
                                                type="text"
                                                id="moga-loc-city-name"
                                                class="regular-text"
                                                placeholder="<?php esc_attr_e( 'City name', 'moga-travel-core' ); ?>"
                                            >
                                        </div>
                                        <div class="moga-loc-inline-form__row">
                                            <input
                                                type="text"
                                                id="moga-loc-city-lat"
                                                class="small-text"
                                                placeholder="<?php esc_attr_e( 'Latitude', 'moga-travel-core' ); ?>"
                                            >
                                            <input
                                                type="text"
                                                id="moga-loc-city-lng"
                                                class="small-text"
                                                placeholder="<?php esc_attr_e( 'Longitude', 'moga-travel-core' ); ?>"
                                            >
                                            <small style="color:#888;"><?php esc_html_e( 'GPS (optional)', 'moga-travel-core' ); ?></small>
                                        </div>
                                        <div class="moga-loc-inline-form__row">
                                            <button type="button" class="button button-primary moga-loc-save-btn" data-level="city">
                                                <?php esc_html_e( 'Save', 'moga-travel-core' ); ?>
                                            </button>
                                            <button type="button" class="button moga-loc-cancel-btn" data-level="city">
                                                <?php esc_html_e( 'Cancel', 'moga-travel-core' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php // End panel city ?>

                            <?php // ---- Panel 4: District ---- ?>
                            <div class="moga-loc-panel" id="moga-loc-panel-district" style="display:none;">
                                <div class="moga-loc-panel__header">
                                    <h3 class="moga-loc-panel__title">
                                        <span class="moga-loc-panel__num">4</span>
                                        <?php esc_html_e( 'District / Neighborhood', 'moga-travel-core' ); ?>
                                    </h3>
                                    <div class="moga-loc-panel__spinner" id="moga-loc-district-spinner" style="display:none;"></div>
                                </div>

                                <div class="moga-loc-panel__body">
                                    <div
                                        id="moga-loc-district-list"
                                        class="moga-loc-list"
                                        data-level="district"
                                    ></div>

                                    <div class="moga-loc-panel__actions">
                                        <button
                                            type="button"
                                            class="button moga-loc-add-trigger"
                                            data-level="district"
                                        >+ <?php esc_html_e( 'Add District', 'moga-travel-core' ); ?></button>
                                    </div>

                                    <div id="moga-loc-form-district" class="moga-loc-inline-form" style="display:none;">
                                        <div class="moga-loc-inline-form__row">
                                            <input
                                                type="text"
                                                id="moga-loc-district-name"
                                                class="regular-text"
                                                placeholder="<?php esc_attr_e( 'District / Neighborhood name', 'moga-travel-core' ); ?>"
                                            >
                                        </div>
                                        <div class="moga-loc-inline-form__row">
                                            <button type="button" class="button button-primary moga-loc-save-btn" data-level="district">
                                                <?php esc_html_e( 'Save', 'moga-travel-core' ); ?>
                                            </button>
                                            <button type="button" class="button moga-loc-cancel-btn" data-level="district">
                                                <?php esc_html_e( 'Cancel', 'moga-travel-core' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php // End panel district ?>

                        </div>
                        <?php // End panels ?>

                        <?php endif; // End countries check ?>

                    </div>
                    <?php // End editor tab ?>

                <?php endif; // End tab switch ?>

            </div>
            <?php // End tab body ?>

        </div>
        <?php // End wrap ?>
        <?php
    }


    // ============================================================
    // AJAX — IMPORT
    // ============================================================

    /**
     * Verify the shared location nonce and capability.
     * Calls wp_send_json_error() and exits on failure.
     *
     * @since  1.0.0
     * @return void
     */
    private static function verify_request() {
        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_loc_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
        }
    }

    /**
     * Get the absolute path to a location JSON data file.
     *
     * @since  1.0.0
     * @param  string $filename  'countries.json', 'provinces.json', or 'cities.json'
     * @return string
     */
    private static function data_path( $filename ) {
        return MOGA_CORE_PATH . 'data/locations/' . $filename;
    }

    /**
     * AJAX: Import countries from countries.json into the DB.
     *
     * Single call — 247 records, runs in under 1 second.
     * Uses explicit IDs matching Careerfy source so that
     * province.country_id foreign keys remain valid.
     *
     * Returns: { count: 247 }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_import_countries() {
        self::verify_request();

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        $table  = "{$prefix}loc_countries";

        $file = self::data_path( 'countries.json' );
        if ( ! file_exists( $file ) ) {
            wp_send_json_error( array( 'message' => 'countries.json not found.' ) );
        }

        $data      = json_decode( file_get_contents( $file ), true );
        $countries = $data['countries'] ?? array();

        if ( empty( $countries ) ) {
            wp_send_json_error( array( 'message' => 'countries.json is empty or malformed.' ) );
        }

        // Build bulk INSERT — uses explicit IDs to preserve
        // foreign key references in provinces table.
        $placeholders = array();
        $values       = array();

        foreach ( $countries as $c ) {
            $placeholders[] = '(%d, %s, %s)';
            $values[]       = absint( $c['id'] );
            $values[]       = sanitize_text_field( $c['name'] );
            $values[]       = strtoupper( sanitize_text_field( $c['sortname'] ) );
        }

        $sql = "INSERT INTO {$table} (id, name, iso_code) VALUES "
            . implode( ', ', $placeholders )
            . ' ON DUPLICATE KEY UPDATE name = VALUES(name), iso_code = VALUES(iso_code)';

        $result = $wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
        }

        wp_send_json_success( array( 'count' => count( $countries ) ) );
    }

    /**
     * AJAX: Import a batch of provinces from provinces.json.
     *
     * POST params:
     *   offset (int) — starting index in the provinces array.
     *
     * Returns: { imported: N, next_offset: N|null, done: bool, total: 4120 }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_import_provinces() {
        self::verify_request();

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        $table  = "{$prefix}loc_provinces";

        $offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

        $file = self::data_path( 'provinces.json' );
        if ( ! file_exists( $file ) ) {
            wp_send_json_error( array( 'message' => 'provinces.json not found.' ) );
        }

        $data      = json_decode( file_get_contents( $file ), true );
        // Handle both 'provinces' and 'states' root keys.
        $provinces = $data['provinces'] ?? $data['states'] ?? array();

        if ( empty( $provinces ) ) {
            wp_send_json_error( array( 'message' => 'provinces.json is empty or malformed.' ) );
        }

        $batch = array_slice( $provinces, $offset, self::BATCH_PROVINCES );
        $total = count( $provinces );

        if ( ! empty( $batch ) ) {
            $placeholders = array();
            $values       = array();

            foreach ( $batch as $p ) {
                $placeholders[] = '(%d, %s, %d)';
                $values[]       = absint( $p['id'] );
                $values[]       = sanitize_text_field( $p['name'] );
                $values[]       = absint( $p['country_id'] );
            }

            $sql = "INSERT INTO {$table} (id, name, country_id) VALUES "
                . implode( ', ', $placeholders )
                . ' ON DUPLICATE KEY UPDATE name = VALUES(name), country_id = VALUES(country_id)';

            $result = $wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore

            if ( false === $result ) {
                wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
            }
        }

        $next_offset = $offset + self::BATCH_PROVINCES;
        $done        = ( $next_offset >= $total );

        wp_send_json_success( array(
            'imported'    => count( $batch ),
            'offset'      => $offset,
            'next_offset' => $done ? null : $next_offset,
            'done'        => $done,
            'total'       => $total,
        ) );
    }

    /**
     * AJAX: Import a batch of cities from cities.json.
     *
     * Requires provinces to be imported first (queries province→country map).
     * Sanitizes IDs with intval() to handle any data corruption in the JSON.
     *
     * POST params:
     *   offset (int) — starting index in the cities array.
     *
     * Returns: { imported: N, next_offset: N|null, done: bool, total: 48313 }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_import_cities() {
        self::verify_request();

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        $table  = "{$prefix}loc_cities";

        $offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

        // Load province→country mapping from DB (fast, ~4120 rows).
        // This is queried fresh each batch call — trivially fast (~1ms).
        $province_map = array();
        $rows = $wpdb->get_results(
            "SELECT id, country_id FROM {$prefix}loc_provinces",
            ARRAY_A
        );
        foreach ( $rows as $row ) {
            $province_map[ (int) $row['id'] ] = (int) $row['country_id'];
        }

        $file = self::data_path( 'cities.json' );
        if ( ! file_exists( $file ) ) {
            wp_send_json_error( array( 'message' => 'cities.json not found.' ) );
        }

        $data   = json_decode( file_get_contents( $file ), true );
        $cities = $data['cities'] ?? array();

        if ( empty( $cities ) ) {
            wp_send_json_error( array( 'message' => 'cities.json is empty or malformed.' ) );
        }

        $batch = array_slice( $cities, $offset, self::BATCH_CITIES );
        $total = count( $cities );

        if ( ! empty( $batch ) ) {
            $placeholders = array();
            $values       = array();

            foreach ( $batch as $city ) {
                // intval() sanitizes corrupt values like '3976);'
                $city_id     = absint( $city['id'] );
                $province_id = intval( $city['state_id'] );
                $country_id  = $province_map[ $province_id ] ?? 0;

                // Skip cities whose province wasn't imported.
                if ( ! $province_id || ! $country_id ) {
                    continue;
                }

                $placeholders[] = '(%d, %s, %d, %d)';
                $values[]       = $city_id;
                $values[]       = sanitize_text_field( $city['name'] );
                $values[]       = $province_id;
                $values[]       = $country_id;
            }

            if ( ! empty( $placeholders ) ) {
                $sql = "INSERT INTO {$table} (id, name, province_id, country_id) VALUES "
                    . implode( ', ', $placeholders )
                    . ' ON DUPLICATE KEY UPDATE name = VALUES(name), province_id = VALUES(province_id), country_id = VALUES(country_id)';

                $result = $wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore

                if ( false === $result ) {
                    wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
                }
            }
        }

        $next_offset = $offset + self::BATCH_CITIES;
        $done        = ( $next_offset >= $total );

        if ( $done ) {
            // Mark import as complete.
            update_option( 'moga_locations_imported', '1' );
        }

        wp_send_json_success( array(
            'imported'    => count( $batch ),
            'offset'      => $offset,
            'next_offset' => $done ? null : $next_offset,
            'done'        => $done,
            'total'       => $total,
        ) );
    }

    /**
     * AJAX: Reset all location data.
     * Truncates all four location tables and resets the imported flag.
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_reset() {
        self::verify_request();

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        // Truncate in reverse dependency order.
        $wpdb->query( "TRUNCATE TABLE {$prefix}loc_districts" );
        $wpdb->query( "TRUNCATE TABLE {$prefix}loc_cities" );
        $wpdb->query( "TRUNCATE TABLE {$prefix}loc_provinces" );
        $wpdb->query( "TRUNCATE TABLE {$prefix}loc_countries" );

        update_option( 'moga_locations_imported', '0' );

        wp_send_json_success( array( 'message' => 'All location data reset.' ) );
    }

    /**
     * AJAX: Return current row counts for all four location tables.
     * Used by JS to refresh status cards without a page reload.
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_get_stats() {
        self::verify_request();

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        wp_send_json_success( array(
            'countries' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_countries" ),
            'provinces' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_provinces" ),
            'cities'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_cities" ),
            'districts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}loc_districts" ),
        ) );
    }


    // ============================================================
    // AJAX — LOCATION EDITOR CASCADE
    // ============================================================

    /**
     * AJAX: Get provinces for a given country_id.
     *
     * POST params: country_id (int)
     * Returns: { provinces: [{id, name}, ...] }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_get_provinces() {
        self::verify_request();

        $country_id = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;
        if ( ! $country_id ) {
            wp_send_json_error( array( 'message' => 'country_id required.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_provinces WHERE country_id = %d ORDER BY name ASC",
            $country_id
        ), ARRAY_A );

        wp_send_json_success( array(
            'provinces'  => $rows ?: array(),
            'country_id' => $country_id,
        ) );
    }

    /**
     * AJAX: Get cities for a given province_id.
     *
     * POST params: province_id (int)
     * Returns: { cities: [{id, name, lat, lng}, ...] }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_get_cities() {
        self::verify_request();

        $province_id = isset( $_POST['province_id'] ) ? absint( $_POST['province_id'] ) : 0;
        if ( ! $province_id ) {
            wp_send_json_error( array( 'message' => 'province_id required.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name, lat, lng FROM {$prefix}loc_cities WHERE province_id = %d ORDER BY name ASC",
            $province_id
        ), ARRAY_A );

        wp_send_json_success( array(
            'cities'      => $rows ?: array(),
            'province_id' => $province_id,
        ) );
    }

    /**
     * AJAX: Get districts for a given city_id.
     *
     * POST params: city_id (int)
     * Returns: { districts: [{id, name}, ...] }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_get_districts() {
        self::verify_request();

        $city_id = isset( $_POST['city_id'] ) ? absint( $_POST['city_id'] ) : 0;
        if ( ! $city_id ) {
            wp_send_json_error( array( 'message' => 'city_id required.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_districts WHERE city_id = %d ORDER BY name ASC",
            $city_id
        ), ARRAY_A );

        wp_send_json_success( array(
            'districts' => $rows ?: array(),
            'city_id'   => $city_id,
        ) );
    }


    // ============================================================
    // AJAX — LOCATION EDITOR CRUD
    // ============================================================

    /**
     * AJAX: Add a new location entry.
     *
     * POST params:
     *   level     (string) country|province|city|district
     *   name      (string) display name
     *   parent_id (int)    parent record ID
     *   iso_code  (string) for country only
     *   lat       (float)  for city only
     *   lng       (float)  for city only
     *
     * Returns: { id: N, name: '...' }
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_add() {
        self::verify_request();

        $level     = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
        $name      = isset( $_POST['name'] )  ? sanitize_text_field( wp_unslash( $_POST['name'] ) )  : '';
        $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;

        if ( ! in_array( $level, array( 'country', 'province', 'city', 'district' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid level.' ) );
        }
        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Name is required.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        switch ( $level ) {
            case 'country':
                $iso = strtoupper( sanitize_text_field( wp_unslash( $_POST['iso_code'] ?? '' ) ) );
                $result = $wpdb->insert(
                    "{$prefix}loc_countries",
                    array( 'name' => $name, 'iso_code' => $iso ),
                    array( '%s', '%s' )
                );
                break;

            case 'province':
                if ( ! $parent_id ) {
                    wp_send_json_error( array( 'message' => 'country_id required for province.' ) );
                }
                $result = $wpdb->insert(
                    "{$prefix}loc_provinces",
                    array( 'name' => $name, 'country_id' => $parent_id ),
                    array( '%s', '%d' )
                );
                break;

            case 'city':
                if ( ! $parent_id ) {
                    wp_send_json_error( array( 'message' => 'province_id required for city.' ) );
                }
                // Get country_id from province.
                $country_id = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT country_id FROM {$prefix}loc_provinces WHERE id = %d",
                    $parent_id
                ) );
                $lat = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : null;
                $lng = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : null;
                $result = $wpdb->insert(
                    "{$prefix}loc_cities",
                    array(
                        'name'        => $name,
                        'province_id' => $parent_id,
                        'country_id'  => $country_id,
                        'lat'         => $lat,
                        'lng'         => $lng,
                    ),
                    array( '%s', '%d', '%d', $lat ? '%f' : 'NULL', $lng ? '%f' : 'NULL' )
                );
                break;

            case 'district':
                if ( ! $parent_id ) {
                    wp_send_json_error( array( 'message' => 'city_id required for district.' ) );
                }
                $result = $wpdb->insert(
                    "{$prefix}loc_districts",
                    array( 'name' => $name, 'city_id' => $parent_id ),
                    array( '%s', '%d' )
                );
                break;

            default:
                wp_send_json_error( array( 'message' => 'Unknown level.' ) );
        }

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
        }

        wp_send_json_success( array(
            'id'   => $wpdb->insert_id,
            'name' => $name,
        ) );
    }

    /**
     * AJAX: Update an existing location entry's name (and optional fields).
     *
     * POST params:
     *   level    (string) country|province|city|district
     *   id       (int)    record ID
     *   name     (string) new display name
     *   iso_code (string) for country only
     *   lat/lng  (float)  for city only
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_update() {
        self::verify_request();

        $level = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
        $id    = isset( $_POST['id'] )    ? absint( $_POST['id'] )                                : 0;
        $name  = isset( $_POST['name'] )  ? sanitize_text_field( wp_unslash( $_POST['name'] ) )  : '';

        if ( ! in_array( $level, array( 'country', 'province', 'city', 'district' ), true ) || ! $id ) {
            wp_send_json_error( array( 'message' => 'level and id required.' ) );
        }
        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Name cannot be empty.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $table_map = array(
            'country'  => 'loc_countries',
            'province' => 'loc_provinces',
            'city'     => 'loc_cities',
            'district' => 'loc_districts',
        );
        $table = "{$prefix}{$table_map[ $level ]}";

        $data   = array( 'name' => $name );
        $format = array( '%s' );

        if ( 'country' === $level ) {
            $iso = strtoupper( sanitize_text_field( wp_unslash( $_POST['iso_code'] ?? '' ) ) );
            if ( $iso ) {
                $data['iso_code'] = $iso;
                $format[]         = '%s';
            }
        }

        if ( 'city' === $level ) {
            if ( isset( $_POST['lat'] ) && '' !== $_POST['lat'] ) {
                $data['lat'] = floatval( $_POST['lat'] );
                $format[]    = '%f';
            }
            if ( isset( $_POST['lng'] ) && '' !== $_POST['lng'] ) {
                $data['lng'] = floatval( $_POST['lng'] );
                $format[]    = '%f';
            }
        }

        $result = $wpdb->update( $table, $data, array( 'id' => $id ), $format, array( '%d' ) );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
        }

        wp_send_json_success( array( 'id' => $id, 'name' => $name ) );
    }

    /**
     * AJAX: Delete a location entry and all its children (cascade).
     *
     * Cascade rules:
     *   country  → deletes all provinces → cities → districts
     *   province → deletes all cities → districts
     *   city     → deletes all districts
     *   district → deletes only itself
     *
     * POST params:
     *   level (string) country|province|city|district
     *   id    (int)    record ID to delete
     *
     * @since  1.0.0
     * @return void
     */
    public static function ajax_delete() {
        self::verify_request();

        $level = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
        $id    = isset( $_POST['id'] )    ? absint( $_POST['id'] )                                : 0;

        if ( ! in_array( $level, array( 'country', 'province', 'city', 'district' ), true ) || ! $id ) {
            wp_send_json_error( array( 'message' => 'level and id required.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        switch ( $level ) {
            case 'country':
                // Get all provinces.
                $province_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT id FROM {$prefix}loc_provinces WHERE country_id = %d",
                    $id
                ) );
                if ( ! empty( $province_ids ) ) {
                    $placeholders = implode( ',', array_fill( 0, count( $province_ids ), '%d' ) );
                    // Get all cities.
                    $city_ids = $wpdb->get_col( $wpdb->prepare(
                        "SELECT id FROM {$prefix}loc_cities WHERE province_id IN ($placeholders)", // phpcs:ignore
                        $province_ids
                    ) );
                    if ( ! empty( $city_ids ) ) {
                        $city_ph = implode( ',', array_fill( 0, count( $city_ids ), '%d' ) );
                        $wpdb->query( $wpdb->prepare(
                            "DELETE FROM {$prefix}loc_districts WHERE city_id IN ($city_ph)", // phpcs:ignore
                            $city_ids
                        ) );
                        $wpdb->query( $wpdb->prepare(
                            "DELETE FROM {$prefix}loc_cities WHERE id IN ($city_ph)", // phpcs:ignore
                            $city_ids
                        ) );
                    }
                    $wpdb->query( $wpdb->prepare(
                        "DELETE FROM {$prefix}loc_provinces WHERE id IN ($placeholders)", // phpcs:ignore
                        $province_ids
                    ) );
                }
                $wpdb->delete( "{$prefix}loc_countries", array( 'id' => $id ), array( '%d' ) );
                break;

            case 'province':
                $city_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT id FROM {$prefix}loc_cities WHERE province_id = %d",
                    $id
                ) );
                if ( ! empty( $city_ids ) ) {
                    $ph = implode( ',', array_fill( 0, count( $city_ids ), '%d' ) );
                    $wpdb->query( $wpdb->prepare(
                        "DELETE FROM {$prefix}loc_districts WHERE city_id IN ($ph)", // phpcs:ignore
                        $city_ids
                    ) );
                    $wpdb->query( $wpdb->prepare(
                        "DELETE FROM {$prefix}loc_cities WHERE id IN ($ph)", // phpcs:ignore
                        $city_ids
                    ) );
                }
                $wpdb->delete( "{$prefix}loc_provinces", array( 'id' => $id ), array( '%d' ) );
                break;

            case 'city':
                $wpdb->delete( "{$prefix}loc_districts", array( 'city_id' => $id ), array( '%d' ) );
                $wpdb->delete( "{$prefix}loc_cities",    array( 'id'      => $id ), array( '%d' ) );
                break;

            case 'district':
                $wpdb->delete( "{$prefix}loc_districts", array( 'id' => $id ), array( '%d' ) );
                break;
        }

        wp_send_json_success( array( 'deleted' => $id, 'level' => $level ) );
    }
}