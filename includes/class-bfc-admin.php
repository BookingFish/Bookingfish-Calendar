<?php
/**
 * BFC_Admin — WordPress admin interface for the BookingFish Calendar plugin.
 *
 * Two tabs:
 *   1. Connection     — login / logout / register link
 *   2. Configuration  — create / delete calendar & certificate pages
 *
 * All user-facing strings are bilingual (FR / EN) controlled by option bfc_language.
 * AJAX actions are wp_ajax_bfc_* and protected with nonces.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BFC_Admin {

    private $api;
    private $pages_manager;

    public function __construct() {
        $this->api           = new BFC_API();
        $this->pages_manager = new BFC_Pages();
    }

    // =========================================================================
    // Init — register hooks
    // =========================================================================

    public function init() {
        add_action( 'admin_menu',            array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX handlers (logged-in admin users only)
        add_action( 'wp_ajax_bfc_login',                array( $this, 'ajax_login' ) );
        add_action( 'wp_ajax_bfc_logout',               array( $this, 'ajax_logout' ) );
        add_action( 'wp_ajax_bfc_sync',                 array( $this, 'ajax_sync' ) );
        add_action( 'wp_ajax_bfc_create_page',          array( $this, 'ajax_create_page' ) );
        add_action( 'wp_ajax_bfc_delete_page',          array( $this, 'ajax_delete_page' ) );
        add_action( 'wp_ajax_bfc_rename_page',          array( $this, 'ajax_rename_page' ) );
        add_action( 'wp_ajax_bfc_set_lang',             array( $this, 'ajax_set_language' ) );
        add_action( 'wp_ajax_bfc_get_boat_calendar_url', array( $this, 'ajax_get_boat_calendar_url' ) );
        add_action( 'wp_ajax_bfc_get_zonemembre_url',   array( $this, 'ajax_get_zonemembre_url' ) );

        add_action( 'admin_footer', array( $this, 'render_deactivation_modal' ) );
    }

    // =========================================================================
    // Menu
    // =========================================================================

    public function register_menu() {
        $lang = get_option( 'bfc_language', 'fr' );
        $t    = function( $fr, $en ) use ( $lang ) { return $lang === 'fr' ? $fr : $en; };

        add_menu_page(
            'BookingFish Calendar',
            'BookingFish',
            'manage_options',
            'bookingfish-calendar',
            array( $this, 'render_page' ),
            'dashicons-calendar-alt',
            80
        );
        add_submenu_page(
            'bookingfish-calendar',
            'BookingFish — ' . $t( 'Configuration', 'Setup' ),
            $t( 'Configuration', 'Setup' ),
            'manage_options',
            'bookingfish-calendar',
            array( $this, 'render_page' )
        );
    }

    // =========================================================================
    // Assets
    // =========================================================================

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_bookingfish-calendar' ) {
            return;
        }
        wp_enqueue_style(
            'bfc-admin',
            BFC_PLUGIN_URL . 'admin/css/bfc-admin.css',
            array(),
            BFC_VERSION
        );
        wp_enqueue_script(
            'bfc-admin',
            BFC_PLUGIN_URL . 'admin/js/bfc-admin.js',
            array( 'jquery' ),
            BFC_VERSION,
            true
        );
        wp_localize_script( 'bfc-admin', 'bfcData', array(
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'bfc_nonce' ),
            'lang'            => get_option( 'bfc_language', 'fr' ),
            'isConnected'     => $this->api->is_connected() ? '1' : '0',
            'siteUrl'         => BFC_SITE_URL,
            'boatCalendarUrl' => BFC_SITE_URL . '/zonemembre/?tab=calendar',
        ) );
    }

    // =========================================================================
    // Main page render
    // =========================================================================

    public function render_page() {
        $lang          = get_option( 'bfc_language', 'fr' );
        $connected     = $this->api->is_connected();
        $token         = $this->api->get_stored_token();
        $vendor_name      = get_option( 'bfc_vendor_name',       '' );
        $vendor_email     = get_option( 'bfc_vendor_email',      '' );
        $last_login_email = $connected ? '' : get_option( 'bfc_last_login_email', '' );
        $last_sync     = (int) get_option( 'bfc_last_sync', 0 );
        $embed_codes   = get_option( 'bfc_embed_codes', array() );
        $created_pages = $this->pages_manager->get_created_pages();

        // Refresh embed codes if connected and cache is older than 1 hour
        if ( $connected && ( $last_sync === 0 || ( time() - $last_sync ) > 3600 ) ) {
            $fresh = $this->api->get_embed_codes( $token );
            if ( ! is_wp_error( $fresh ) && ! empty( $fresh['success'] ) ) {
                update_option( 'bfc_embed_codes', $fresh );
                update_option( 'bfc_last_sync',   time() );
                $embed_codes = $fresh;
            }
        }

        $boats        = $connected ? ( $embed_codes['calendar']['boats'] ?? array() ) : array();
        $certificates = $connected ? ( $embed_codes['certificates'] ?? array() ) : array();

        $t = function( $fr, $en ) use ( $lang ) {
            return $lang === 'fr' ? $fr : $en;
        };
        ?>

        <div class="wrap bfcc-wrap">

            <!-- ===== HEADER ===== -->
            <div class="bfcc-header">
                <div class="bfcc-header-inner">
                    <span class="bfcc-logo">🐟</span>
                    <div>
                        <h1 class="bfcc-title">BookingFish Calendar</h1>
                        <p class="bfcc-subtitle"><?php echo esc_html( $t( 'Connectez votre site à votre compte BookingFish', 'Connect your site to your BookingFish account' ) ); ?> &nbsp;·&nbsp; Version&nbsp;<?php echo esc_html( BFC_VERSION ); ?></p>
                    </div>
                    <div class="bfcc-lang-toggle">
                        <button class="bfcc-lang-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" data-lang="fr">FR</button>
                        <button class="bfcc-lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">EN</button>
                    </div>
                </div>
            </div>

            <!-- ===== TAB NAV ===== -->
            <nav class="bfcc-tab-nav">
                <button class="bfcc-tab-btn active" data-tab="connection">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php echo esc_html( $t( 'Connexion', 'Connection' ) ); ?>
                </button>
                <button class="bfcc-tab-btn" data-tab="setup" <?php echo ! $connected ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php echo esc_html( $t( 'Configuration', 'Setup' ) ); ?>
                </button>
                <button type="button" class="bfcc-tab-btn bfcc-btn-zonemembre">
                    <span class="dashicons dashicons-networking"></span>
                    Dash Board Bookingfish.ca
                </button>
            </nav>

            <!-- ===== TAB: CONNECTION ===== -->
            <div class="bfcc-tab-panel active" id="bfcc-tab-connection">

                <?php if ( $connected ) : ?>
                    <!-- Connected state -->
                    <div class="bfcc-card bfcc-connected-card">
                        <div class="bfcc-connected-status">
                            <span class="bfcc-status-dot connected"></span>
                            <strong><?php echo esc_html( $t( 'Connecté à BookingFish', 'Connected to BookingFish' ) ); ?></strong>
                        </div>
                        <p class="bfcc-connected-info">
                            👤 <strong><?php echo esc_html( $vendor_name ); ?></strong>
                            &nbsp;·&nbsp;
                            <?php echo esc_html( $vendor_email ); ?>
                        </p>
                        <?php if ( $last_sync > 0 ) : ?>
                            <p class="bfcc-sync-info">
                                🔄 <?php echo esc_html( $t( 'Dernière synchronisation :', 'Last sync:' ) ); ?>
                                <span class="bfcc-sync-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_sync ) ); ?></span>
                            </p>
                        <?php endif; ?>
                        <div class="bfcc-connected-actions">
                            <button class="bfcc-btn bfcc-btn-secondary" id="bfcc-btn-sync">
                                <span class="dashicons dashicons-update"></span>
                                <?php echo esc_html( $t( 'Synchroniser', 'Sync Now' ) ); ?>
                            </button>
                            <button class="bfcc-btn bfcc-btn-danger" id="bfcc-btn-logout">
                                <span class="dashicons dashicons-exit"></span>
                                <?php echo esc_html( $t( 'Se déconnecter', 'Disconnect' ) ); ?>
                            </button>
                        </div>
                    </div>

                <?php else : ?>
                    <!-- Login form -->
                    <div class="bfcc-card">
                        <h2 class="bfcc-card-title">
                            🔗 <?php echo esc_html( $t( 'Connecter votre compte BookingFish', 'Connect your BookingFish account' ) ); ?>
                        </h2>
                        <p class="bfcc-card-description">
                            <?php echo esc_html( $t(
                                'Entrez vos identifiants du site bookingfish.ca pour récupérer automatiquement vos codes d\'intégration.',
                                'Enter your bookingfish.ca credentials to automatically retrieve your embed codes.'
                            ) ); ?>
                        </p>

                        <div id="bfcc-login-error" class="bfcc-notice bfcc-notice-error" style="display:none;"></div>

                        <div class="bfcc-form-group">
                            <label for="bfcc-email">
                                <span class="dashicons dashicons-email"></span>
                                <?php echo esc_html( $t( 'Adresse courriel', 'Email address' ) ); ?>
                            </label>
                            <input type="email" id="bfcc-email" class="bfcc-input"
                                   placeholder="vous@exemple.ca"
                                   autocomplete="off"
                                   value="<?php echo esc_attr( $last_login_email ); ?>" />
                        </div>

                        <div class="bfcc-form-group">
                            <label for="bfcc-password">
                                <span class="dashicons dashicons-lock"></span>
                                <?php echo esc_html( $t( 'Mot de passe', 'Password' ) ); ?>
                            </label>
                            <input type="password" id="bfcc-password" class="bfcc-input" placeholder="••••••••" autocomplete="off" />
                        </div>

                        <button class="bfcc-btn bfcc-btn-primary bfcc-btn-full" id="bfcc-btn-login">
                            <span class="dashicons dashicons-admin-network"></span>
                            <?php echo esc_html( $t( 'Se connecter à BookingFish', 'Connect to BookingFish' ) ); ?>
                        </button>

                        <div class="bfcc-separator">
                            <span><?php echo esc_html( $t( 'Pas encore de compte ?', 'No account yet?' ) ); ?></span>
                        </div>

                        <a href="<?php echo esc_url( BFC_SITE_URL . '/bookingfish-inscription/' ); ?>"
                           target="_blank" rel="noopener"
                           class="bfcc-btn bfcc-btn-outline bfcc-btn-full">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php echo esc_html( $t( 'Créer un compte sur bookingfish.ca', 'Create an account on bookingfish.ca' ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>

            </div><!-- /tab-connection -->

            <!-- ===== TAB: SETUP ===== -->
            <div class="bfcc-tab-panel" id="bfcc-tab-setup">

                <?php if ( ! $connected ) : ?>
                    <div class="bfcc-card bfcc-notice-card">
                        <p><?php echo esc_html( $t( 'Veuillez vous connecter dans l\'onglet Connexion pour continuer.', 'Please connect in the Connection tab to continue.' ) ); ?></p>
                    </div>
                <?php else : ?>

                    <!-- SECTION: CALENDAR -->
                    <div class="bfcc-card">
                        <h2 class="bfcc-card-title">
                            📅 <?php echo esc_html( $t( 'Calendrier de réservation', 'Reservation Calendar' ) ); ?>
                        </h2>
                        <p class="bfcc-card-description">
                            <?php echo esc_html( $t(
                                'Créez une page WordPress qui affiche votre formulaire de réservation. Vous pourrez partager son URL sur vos réseaux sociaux.',
                                'Create a WordPress page that displays your booking form. You can share its URL on social media.'
                            ) ); ?>
                        </p>

                        <!-- All boats -->
                        <?php $existing_all = $this->find_created_page( $created_pages, 'all_boats' ); ?>
                        <div class="bfcc-page-creator <?php echo $existing_all ? 'bfcc-already-created' : ''; ?>">
                            <div class="bfcc-creator-label">
                                🚢 <?php echo esc_html( $t( 'Tous les bateaux (menu déroulant)', 'All boats (dropdown menu)' ) ); ?>
                            </div>
                            <?php if ( $existing_all ) : ?>
                                <div class="bfcc-existing-page">
                                    <span class="dashicons dashicons-yes-alt bfcc-icon-success"></span>
                                    <?php echo esc_html( $t( 'Page créée :', 'Page created:' ) ); ?>
                                    <a href="<?php echo esc_url( $existing_all['url'] ); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html( $existing_all['url'] ); ?>
                                    </a>
                                    <button class="bfcc-btn-copy-url bfcc-icon-btn"
                                            data-url="<?php echo esc_attr( $existing_all['url'] ); ?>"
                                            title="<?php echo esc_attr( $t( 'Copier le lien', 'Copy link' ) ); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                    <button class="bfcc-btn bfcc-btn-danger bfcc-btn-sm bfcc-btn-delete"
                                            data-page-id="<?php echo esc_attr( $existing_all['page_id'] ); ?>"
                                            data-confirm="<?php echo esc_attr( $t( 'Supprimer cette page ?', 'Delete this page?' ) ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php echo esc_html( $t( 'Supprimer', 'Delete' ) ); ?>
                                    </button>
                                </div>
                            <?php else : ?>
                                <div class="bfcc-creator-inputs">
                                    <input type="text" class="bfcc-input bfcc-page-title"
                                           placeholder="<?php echo esc_attr( $t( 'Nom de la page (ex: Mon calendrier)', 'Page name (e.g. My calendar)' ) ); ?>"
                                           data-type="calendar"
                                           data-sub-type="all_boats" />
                                    <button class="bfcc-btn bfcc-btn-create" data-sub-type="all_boats">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php echo esc_html( $t( 'Créer la page', 'Create page' ) ); ?>
                                    </button>
                                </div>
                                <div class="bfcc-page-result" data-sub-type="all_boats" style="display:none;"></div>
                            <?php endif; ?>
                        </div>

                        <!-- Per boat -->
                        <?php if ( ! empty( $boats ) ) : ?>
                            <div class="bfcc-boats-section">
                                <div class="bfcc-section-subtitle">
                                    🚤 <?php echo esc_html( $t( 'Pages individuelles par bateau', 'Individual pages per boat' ) ); ?>
                                </div>
                                <?php foreach ( $boats as $boat ) :
                                    $sub_type      = 'boat:' . $boat['name'];
                                    $existing_boat = $this->find_created_page( $created_pages, $sub_type );
                                    $has_published = ! empty( $boat['has_published_months'] );
                                ?>
                                    <div class="bfcc-page-creator <?php echo $existing_boat ? 'bfcc-already-created' : ''; ?>">
                                        <div class="bfcc-creator-label">
                                            🚤 <?php echo esc_html( $boat['name'] ); ?>
                                        </div>
                                        <?php if ( $existing_boat ) : ?>
                                            <div class="bfcc-existing-page">
                                                <span class="dashicons dashicons-yes-alt bfcc-icon-success"></span>
                                                <a href="<?php echo esc_url( $existing_boat['url'] ); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html( $existing_boat['url'] ); ?>
                                                </a>
                                                <button class="bfcc-btn-copy-url bfcc-icon-btn"
                                                        data-url="<?php echo esc_attr( $existing_boat['url'] ); ?>"
                                                        title="<?php echo esc_attr( $t( 'Copier le lien', 'Copy link' ) ); ?>">
                                                    <span class="dashicons dashicons-admin-page"></span>
                                                </button>
                                                <button class="bfcc-btn bfcc-btn-danger bfcc-btn-sm bfcc-btn-delete"
                                                        data-page-id="<?php echo esc_attr( $existing_boat['page_id'] ); ?>"
                                                        data-confirm="<?php echo esc_attr( $t( 'Supprimer cette page ?', 'Delete this page?' ) ); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    <?php echo esc_html( $t( 'Supprimer', 'Delete' ) ); ?>
                                                </button>
                                            </div>
                                        <?php elseif ( ! $has_published ) : ?>
                                            <div class="bfcc-notice bfcc-notice-warning" style="margin-top:8px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                                                <span>⚠️ <?php echo esc_html( $t(
                                                    'Aucun mois publié pour ce bateau. Publiez au moins un mois dans Boat Calendar → onglet Publication.',
                                                    'No month published for this boat. Publish at least one month in Boat Calendar → Publication tab.'
                                                ) ); ?></span>
                                                <button type="button" class="bfcc-btn bfcc-btn-sm bfcc-btn-secondary bfcc-btn-boat-cal">
                                                    <span class="dashicons dashicons-calendar-alt"></span>
                                                    Boat Calendar
                                                </button>
                                            </div>
                                        <?php else : ?>
                                            <div class="bfcc-creator-inputs">
                                                <input type="text" class="bfcc-input bfcc-page-title"
                                                       placeholder="<?php echo esc_attr( $t( 'Nom de la page', 'Page name' ) ); ?>"
                                                       data-type="calendar"
                                                       data-sub-type="<?php echo esc_attr( $sub_type ); ?>" />
                                                <button class="bfcc-btn bfcc-btn-create" data-sub-type="<?php echo esc_attr( $sub_type ); ?>">
                                                    <span class="dashicons dashicons-plus"></span>
                                                    <?php echo esc_html( $t( 'Créer', 'Create' ) ); ?>
                                                </button>
                                            </div>
                                            <div class="bfcc-page-result" data-sub-type="<?php echo esc_attr( $sub_type ); ?>" style="display:none;"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SECTION: CERTIFICATES -->
                    <?php if ( ! empty( $certificates ) ) : ?>
                        <div class="bfcc-card">
                            <h2 class="bfcc-card-title">
                                🎁 <?php echo esc_html( $t( 'Certificats cadeaux', 'Gift Certificates' ) ); ?>
                            </h2>
                            <p class="bfcc-card-description">
                                <?php echo esc_html( $t(
                                    'Créez une page WordPress qui permet à vos clients d\'acheter un certificat cadeau en ligne.',
                                    'Create a WordPress page that allows your clients to purchase a gift certificate online.'
                                ) ); ?>
                            </p>

                            <?php foreach ( $certificates as $cert ) :
                                $sub_type      = 'cert:' . $cert['id'];
                                $existing_cert = $this->find_created_page( $created_pages, $sub_type );
                            ?>
                                <div class="bfcc-page-creator <?php echo $existing_cert ? 'bfcc-already-created' : ''; ?>">
                                    <div class="bfcc-creator-label">
                                        🎁 <?php echo esc_html( $cert['name'] ); ?>
                                    </div>
                                    <?php if ( $existing_cert ) : ?>
                                        <div class="bfcc-existing-page">
                                            <span class="dashicons dashicons-yes-alt bfcc-icon-success"></span>
                                            <a href="<?php echo esc_url( $existing_cert['url'] ); ?>" target="_blank" rel="noopener">
                                                <?php echo esc_html( $existing_cert['url'] ); ?>
                                            </a>
                                            <button class="bfcc-btn-copy-url bfcc-icon-btn"
                                                    data-url="<?php echo esc_attr( $existing_cert['url'] ); ?>"
                                                    title="<?php echo esc_attr( $t( 'Copier le lien', 'Copy link' ) ); ?>">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </button>
                                            <button class="bfcc-btn bfcc-btn-danger bfcc-btn-sm bfcc-btn-delete"
                                                    data-page-id="<?php echo esc_attr( $existing_cert['page_id'] ); ?>"
                                                    data-confirm="<?php echo esc_attr( $t( 'Supprimer cette page ?', 'Delete this page?' ) ); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                                <?php echo esc_html( $t( 'Supprimer', 'Delete' ) ); ?>
                                            </button>
                                        </div>
                                    <?php else : ?>
                                        <div class="bfcc-creator-inputs">
                                            <input type="text" class="bfcc-input bfcc-page-title"
                                                   placeholder="<?php echo esc_attr( $t( 'Nom de la page', 'Page name' ) ); ?>"
                                                   data-type="certificate"
                                                   data-sub-type="<?php echo esc_attr( $sub_type ); ?>" />
                                            <button class="bfcc-btn bfcc-btn-create bfcc-btn-cert" data-sub-type="<?php echo esc_attr( $sub_type ); ?>">
                                                <span class="dashicons dashicons-plus"></span>
                                                <?php echo esc_html( $t( 'Créer la page', 'Create page' ) ); ?>
                                            </button>
                                        </div>
                                        <div class="bfcc-page-result" data-sub-type="<?php echo esc_attr( $sub_type ); ?>" style="display:none;"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ( $connected ) : ?>
                        <div class="bfcc-card bfcc-notice-card">
                            <p>
                                🎁 <?php echo esc_html( $t(
                                    'Aucun certificat cadeau configuré sur votre compte BookingFish.',
                                    'No gift certificates configured on your BookingFish account.'
                                ) ); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Pass embed codes data to JS -->
                    <script>
                    window.bfcEmbedCodes = <?php echo wp_json_encode( array(
                        'calendar' => array(
                            'all_boats' => $embed_codes['calendar']['all_boats'] ?? '',
                            'boats'     => $boats,
                        ),
                        'certificates' => $certificates,
                    ) ); ?>;
                    </script>

                <?php endif; // connected ?>
            </div><!-- /tab-setup -->

            <!-- Global spinner -->
            <div id="bfcc-spinner" class="bfcc-spinner" style="display:none;">
                <div class="bfcc-spinner-inner">
                    <div class="bfcc-spin"></div>
                    <span id="bfcc-spinner-msg"><?php echo esc_html( $t( 'Traitement…', 'Processing…' ) ); ?></span>
                </div>
            </div>

        </div><!-- /bfcc-wrap -->
        <?php
    }

    // =========================================================================
    // AJAX handlers
    // =========================================================================

    public function ajax_login() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $password = sanitize_text_field( wp_unslash( $_POST['password'] ?? '' ) );

        bfc_log( "BFC_Admin::ajax_login — attempt for email={$email}" );

        if ( ! $email || ! $password ) {
            bfc_log( 'BFC_Admin::ajax_login — missing email or password.', 'WARNING' );
            wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'bookingfish-calendar' ) ) );
        }

        $result = $this->api->login( $email, $password );

        if ( is_wp_error( $result ) ) {
            bfc_log( 'BFC_Admin::ajax_login — login WP_Error: ' . $result->get_error_message(), 'ERROR' );
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        if ( empty( $result['success'] ) ) {
            $msg = $result['message'] ?? __( 'Login failed.', 'bookingfish-calendar' );
            bfc_log( 'BFC_Admin::ajax_login — login rejected: ' . $msg, 'WARNING' );
            wp_send_json_error( array( 'message' => $msg ) );
        }

        $this->api->store_credentials( $result );
        update_option( 'bfc_last_login_email', $result['vendor_email'] );

        // Fetch embed codes immediately after login
        $embed_codes = $this->api->get_embed_codes( $result['token'] );
        if ( is_wp_error( $embed_codes ) ) {
            bfc_log( 'BFC_Admin::ajax_login — embed codes fetch failed: ' . $embed_codes->get_error_message(), 'ERROR' );
        } elseif ( ! empty( $embed_codes['success'] ) ) {
            update_option( 'bfc_embed_codes', $embed_codes );
            update_option( 'bfc_last_sync',   time() );
            $nb_certs = count( $embed_codes['certificates'] ?? array() );
            $nb_boats = count( $embed_codes['calendar']['boats'] ?? array() );
            bfc_log( "BFC_Admin::ajax_login — embed codes stored: {$nb_boats} boat(s), {$nb_certs} certificate(s)." );
        }

        bfc_log( 'BFC_Admin::ajax_login — login successful for ' . $result['vendor_email'] );
        wp_send_json_success( array(
            'message'      => __( 'Connected successfully! Redirecting…', 'bookingfish-calendar' ),
            'vendor_name'  => $result['vendor_name'],
            'vendor_email' => $result['vendor_email'],
        ) );
    }

    public function ajax_logout() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        bfc_log( 'BFC_Admin::ajax_logout — logout requested.' );
        $token = $this->api->get_stored_token();
        if ( $token ) {
            $this->api->logout( $token );
        }
        $this->api->clear_stored_credentials();

        wp_send_json_success( array( 'message' => __( 'Disconnected.', 'bookingfish-calendar' ) ) );
    }

    public function ajax_sync() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        bfc_log( 'BFC_Admin::ajax_sync — manual sync requested.' );

        if ( ! $this->api->is_connected() ) {
            bfc_log( 'BFC_Admin::ajax_sync — not connected.', 'WARNING' );
            wp_send_json_error( array( 'message' => __( 'Not connected.', 'bookingfish-calendar' ) ) );
        }

        $token       = $this->api->get_stored_token();
        $embed_codes = $this->api->get_embed_codes( $token );

        if ( is_wp_error( $embed_codes ) ) {
            bfc_log( 'BFC_Admin::ajax_sync — error: ' . $embed_codes->get_error_message(), 'ERROR' );
            wp_send_json_error( array( 'message' => $embed_codes->get_error_message() ) );
        }

        update_option( 'bfc_embed_codes', $embed_codes );
        update_option( 'bfc_last_sync',   time() );
        $this->pages_manager->sync_pages( $embed_codes );

        bfc_log( 'BFC_Admin::ajax_sync — sync complete.' );
        wp_send_json_success( array( 'message' => __( 'Sync complete. Reloading…', 'bookingfish-calendar' ) ) );
    }

    public function ajax_create_page() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $title    = sanitize_text_field( wp_unslash( $_POST['title']    ?? '' ) );
        $type     = sanitize_key(        wp_unslash( $_POST['type']     ?? '' ) );
        $sub_type = sanitize_text_field( wp_unslash( $_POST['sub_type'] ?? '' ) );

        bfc_log( "BFC_Admin::ajax_create_page — title='{$title}' type='{$type}' sub_type='{$sub_type}'" );

        if ( ! $this->api->is_connected() ) {
            bfc_log( 'BFC_Admin::ajax_create_page — not connected.', 'WARNING' );
            wp_send_json_error( array( 'message' => __( 'Not connected.', 'bookingfish-calendar' ) ) );
        }

        if ( ! $title ) {
            bfc_log( 'BFC_Admin::ajax_create_page — missing title.', 'WARNING' );
            wp_send_json_error( array( 'message' => __( 'Please enter a page name.', 'bookingfish-calendar' ) ) );
        }

        $embed_codes = get_option( 'bfc_embed_codes', array() );
        $embed_code  = $this->extract_embed_code( $embed_codes, $type, $sub_type );

        if ( ! $embed_code ) {
            bfc_log( "BFC_Admin::ajax_create_page — embed code not found for type='{$type}' sub_type='{$sub_type}'.", 'ERROR' );
            wp_send_json_error( array( 'message' => __( 'Embed code not found. Try syncing first.', 'bookingfish-calendar' ) ) );
        }

        $result = $this->pages_manager->create_page( $title, $embed_code, $type, $sub_type );

        if ( is_wp_error( $result ) ) {
            bfc_log( 'BFC_Admin::ajax_create_page — create_page failed: ' . $result->get_error_message(), 'ERROR' );
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        bfc_log( "BFC_Admin::ajax_create_page — page created ID={$result['page_id']} URL={$result['url']}" );
        wp_send_json_success( array(
            'message' => __( 'Page created successfully!', 'bookingfish-calendar' ),
            'url'     => $result['url'],
            'page_id' => $result['page_id'],
        ) );
    }

    public function ajax_delete_page() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $page_id = intval( wp_unslash( $_POST['page_id'] ?? 0 ) );
        bfc_log( "BFC_Admin::ajax_delete_page — page_id={$page_id}" );

        if ( ! $page_id ) {
            bfc_log( 'BFC_Admin::ajax_delete_page — invalid page ID.', 'WARNING' );
            wp_send_json_error( array( 'message' => __( 'Invalid page ID.', 'bookingfish-calendar' ) ) );
        }

        $this->pages_manager->delete_page( $page_id );

        wp_send_json_success( array( 'message' => __( 'Page deleted.', 'bookingfish-calendar' ) ) );
    }

    public function ajax_rename_page() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $page_id   = intval( wp_unslash( $_POST['page_id']   ?? 0 ) );
        $new_title = sanitize_text_field( wp_unslash( $_POST['new_title'] ?? '' ) );

        bfc_log( "BFC_Admin::ajax_rename_page — page_id={$page_id} new_title='{$new_title}'" );

        if ( ! $page_id || ! $new_title ) {
            bfc_log( 'BFC_Admin::ajax_rename_page — invalid data.', 'WARNING' );
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'bookingfish-calendar' ) ) );
        }

        $result = $this->pages_manager->rename_page( $page_id, $new_title );

        if ( is_wp_error( $result ) ) {
            bfc_log( 'BFC_Admin::ajax_rename_page — failed: ' . $result->get_error_message(), 'ERROR' );
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message'   => __( 'Page renamed.', 'bookingfish-calendar' ),
            'new_title' => $new_title,
        ) );
    }

    public function ajax_set_language() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $raw  = sanitize_key( wp_unslash( $_POST['lang'] ?? '' ) );
        $lang = in_array( $raw, array( 'fr', 'en' ), true ) ? $raw : 'fr';
        update_option( 'bfc_language', $lang );
        bfc_log( "BFC_Admin::ajax_set_language — language set to '{$lang}'." );

        wp_send_json_success( array( 'lang' => $lang ) );
    }

    public function ajax_get_boat_calendar_url() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $token  = $this->api->get_stored_token();
        $result = $this->api->get_magic_link( $token, 'calendar' );

        if ( is_wp_error( $result ) || empty( $result['success'] ) || empty( $result['url'] ) ) {
            bfc_log( 'BFC_Admin::ajax_get_boat_calendar_url — magic link failed, using fallback URL.', 'WARNING' );
            wp_send_json_success( array( 'url' => BFC_SITE_URL . '/zonemembre/?tab=calendar' ) );
            return;
        }

        bfc_log( 'BFC_Admin::ajax_get_boat_calendar_url — magic link obtained.' );
        wp_send_json_success( array( 'url' => $result['url'] ) );
    }

    public function ajax_get_zonemembre_url() {
        check_ajax_referer( 'bfc_nonce', 'nonce' );

        $token  = $this->api->get_stored_token();
        $result = $this->api->get_magic_link( $token, 'welcome' );

        if ( is_wp_error( $result ) || empty( $result['success'] ) || empty( $result['url'] ) ) {
            bfc_log( 'BFC_Admin::ajax_get_zonemembre_url — magic link failed, using fallback URL.', 'WARNING' );
            wp_send_json_success( array( 'url' => BFC_SITE_URL . '/zonemembre/' ) );
            return;
        }

        bfc_log( 'BFC_Admin::ajax_get_zonemembre_url — magic link obtained.' );
        wp_send_json_success( array( 'url' => $result['url'] ) );
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Extract the embed code for a given type/sub_type from the stored codes.
     */
    private function extract_embed_code( array $embed_codes, $type, $sub_type ) {
        if ( $type === 'calendar' ) {
            if ( $sub_type === 'all_boats' ) {
                return $embed_codes['calendar']['all_boats'] ?? null;
            }
            if ( substr( $sub_type, 0, 5 ) === 'boat:' ) {
                $boat_name = substr( $sub_type, 5 );
                foreach ( $embed_codes['calendar']['boats'] ?? array() as $boat ) {
                    if ( $boat['name'] === $boat_name ) {
                        return $boat['code'];
                    }
                }
            }
        }

        if ( $type === 'certificate' ) {
            if ( substr( $sub_type, 0, 5 ) === 'cert:' ) {
                $cert_id = (int) substr( $sub_type, 5 );
                foreach ( $embed_codes['certificates'] ?? array() as $cert ) {
                    if ( (int) $cert['id'] === $cert_id ) {
                        return $cert['code'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find a created page by its sub_type.
     */
    private function find_created_page( array $pages, $sub_type ) {
        foreach ( $pages as $page ) {
            if ( isset( $page['sub_type'] ) && $page['sub_type'] === $sub_type ) {
                return $page;
            }
        }
        return null;
    }

    // =========================================================================
    // Deactivation feedback modal (shown on plugins.php)
    // =========================================================================

    public function render_deactivation_modal() {
        global $pagenow;
        if ( $pagenow !== 'plugins.php' ) {
            return;
        }
        $plugin_basename = 'bookingfish-calendar/bookingfish-calendar.php';
        $feedback_url    = BFC_SITE_URL . '/wp-json/bfcm/v1/deactivation-feedback';
        $lang            = get_option( 'bfc_language', 'fr' );
        $is_fr           = $lang === 'fr';
        $reasons         = $is_fr ? [
            'not-working'      => 'Le plugin ne fonctionne pas correctement',
            'found-better'     => "J'ai trouvé un meilleur plugin",
            'no-longer-needed' => "Je n'en ai plus besoin",
            'temporary'        => 'C\'est temporaire, je réactive plus tard',
            'site-closed'      => 'Mon site est fermé / je change de direction',
            'other'            => 'Autre raison',
        ] : [
            'not-working'      => 'The plugin is not working properly',
            'found-better'     => 'I found a better plugin',
            'no-longer-needed' => 'I no longer need it',
            'temporary'        => "Temporary \u{2014} I'll reactivate later",
            'site-closed'      => 'My site is closing / changing direction',
            'other'            => 'Other reason',
        ];
        $lbl_title   = $is_fr ? '🐟 Vous désactivez BookingFish Calendar' : '🐟 You are deactivating BookingFish Calendar';
        $lbl_sub     = $is_fr ? 'Pouvez-vous nous indiquer la raison ? Cela nous aide à améliorer le plugin.' : 'Could you tell us why? It helps us improve the plugin.';
        $lbl_comment = $is_fr ? 'Commentaire optionnel…' : 'Optional comment…';
        $lbl_skip    = $is_fr ? 'Ignorer & désactiver' : 'Skip & deactivate';
        $lbl_submit  = $is_fr ? 'Envoyer & désactiver' : 'Submit & deactivate';
        ?>
<!-- BookingFish Calendar Deactivation Feedback Modal -->
<div id="bfc-deactivate-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:8px;padding:28px 32px;max-width:480px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.3)">
    <h2 style="margin:0 0 6px;font-size:1.1rem;color:#1a1a2e"><?php echo esc_html( $lbl_title ); ?></h2>
    <p style="margin:0 0 18px;color:#555;font-size:.88rem"><?php echo esc_html( $lbl_sub ); ?></p>
    <div id="bfc-deactivate-reasons" style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px">
      <?php foreach ( $reasons as $key => $label ) : ?>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.9rem;padding:8px 12px;border:1px solid #ddd;border-radius:6px"
             class="bfc-reason-label">
        <input type="radio" name="bfc_deactivate_reason"
               value="<?php echo esc_attr( $key ); ?>"
               data-label="<?php echo esc_attr( $label ); ?>">
        <?php echo esc_html( $label ); ?>
      </label>
      <?php endforeach; ?>
    </div>
    <div id="bfc-deactivate-comment-wrap" style="display:none;margin-bottom:16px">
      <textarea id="bfc-deactivate-comment" rows="3"
        placeholder="<?php echo esc_attr( $lbl_comment ); ?>"
        style="width:100%;box-sizing:border-box;border:1px solid #ddd;border-radius:6px;padding:8px;font-size:.88rem;resize:vertical"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" id="bfc-deactivate-skip"
              style="background:none;border:1px solid #ddd;border-radius:6px;padding:8px 16px;cursor:pointer;font-size:.88rem;color:#555">
        <?php echo esc_html( $lbl_skip ); ?>
      </button>
      <button type="button" id="bfc-deactivate-submit" disabled
              style="background:#0073aa;color:#fff;border:none;border-radius:6px;padding:8px 20px;cursor:pointer;font-size:.88rem;font-weight:600;opacity:.5">
        <?php echo esc_html( $lbl_submit ); ?>
      </button>
    </div>
  </div>
</div>
<script>
(function(){
    var pluginFile   = <?php echo wp_json_encode( $plugin_basename ); ?>;
    var feedbackUrl  = <?php echo wp_json_encode( $feedback_url ); ?>;
    var siteData = {
        site_name:      <?php echo wp_json_encode( get_bloginfo( 'name' ) ); ?>,
        site_url:       <?php echo wp_json_encode( get_site_url() ); ?>,
        admin_email:    <?php echo wp_json_encode( get_option( 'admin_email' ) ); ?>,
        wp_version:     <?php echo wp_json_encode( get_bloginfo( 'version' ) ); ?>,
        plugin_version: <?php echo wp_json_encode( BFC_VERSION ); ?>
    };

    var deactivateUrl = null;
    var modal       = document.getElementById('bfc-deactivate-modal');
    var skipBtn     = document.getElementById('bfc-deactivate-skip');
    var submitBtn   = document.getElementById('bfc-deactivate-submit');
    var commentWrap = document.getElementById('bfc-deactivate-comment-wrap');
    var commentTa   = document.getElementById('bfc-deactivate-comment');

    function interceptLinks() {
        var row = document.querySelector('tr[data-slug="bookingfish-calendar"]');
        if (!row) {
            var rows = document.querySelectorAll('#the-list tr');
            rows.forEach(function(r) {
                var dp = r.getAttribute('data-plugin');
                if (dp && dp.indexOf('bookingfish-calendar/') === 0) { row = r; }
            });
        }
        if (!row) return;
        var link = row.querySelector('.deactivate a');
        if (!link) return;
        link.addEventListener('click', function(e) {
            e.preventDefault();
            deactivateUrl = link.href;
            modal.style.display = 'flex';
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', interceptLinks);
    } else {
        interceptLinks();
    }

    document.querySelectorAll('input[name="bfc_deactivate_reason"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var showComment = (radio.value === 'other' || radio.value === 'not-working');
            commentWrap.style.display = showComment ? 'block' : 'none';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            document.querySelectorAll('.bfc-reason-label').forEach(function(lbl) {
                lbl.style.borderColor = lbl.querySelector('input').checked ? '#0073aa' : '#ddd';
            });
        });
    });

    skipBtn.addEventListener('click', function() {
        if (deactivateUrl) window.location.href = deactivateUrl;
    });

    submitBtn.addEventListener('click', function() {
        var selected = document.querySelector('input[name="bfc_deactivate_reason"]:checked');
        if (!selected || !deactivateUrl) {
            if (deactivateUrl) window.location.href = deactivateUrl;
            return;
        }
        submitBtn.disabled = true;
        submitBtn.style.opacity = '.5';
        submitBtn.textContent = <?php echo wp_json_encode( $is_fr ? 'Envoi…' : 'Sending…' ); ?>;

        fetch(feedbackUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.assign({
                reason_key:   selected.value,
                reason_label: selected.dataset.label,
                comment:      commentTa.value
            }, siteData))
        }).finally(function() {
            window.location.href = deactivateUrl;
        });
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal && deactivateUrl) window.location.href = deactivateUrl;
    });
})();
</script>
        <?php
    }

    /**
     * Human-readable label for the page type column.
     */
    private function type_label( $type, $sub_type, $lang ) {
        $fr = $en = '';

        if ( $type === 'calendar' ) {
            if ( $sub_type === 'all_boats' ) {
                $fr = '📅 Calendrier – tous les bateaux';
                $en = '📅 Calendar – all boats';
            } elseif ( substr( $sub_type, 0, 5 ) === 'boat:' ) {
                $boat = substr( $sub_type, 5 );
                $fr   = '🚤 Calendrier – ' . $boat;
                $en   = '🚤 Calendar – ' . $boat;
            }
        } elseif ( $type === 'certificate' ) {
            $fr = '🎁 Certificat cadeau';
            $en = '🎁 Gift Certificate';
        }

        return $lang === 'fr' ? $fr : $en;
    }
}
