/**
 * BookingFish Calendar — Admin JavaScript
 *
 * Handles:
 *  - Tab navigation
 *  - Language toggle
 *  - Login / Logout / Sync (AJAX)
 *  - Create / Delete / Rename page (AJAX)
 *  - URL copy to clipboard
 */

/* global bfishData, bfcEmbedCodes, jQuery */

(function ($) {
    'use strict';

    var ajaxUrl     = bfishData.ajaxUrl;
    var nonce       = bfishData.nonce;
    var isConnected = bfishData.isConnected === '1';

    // =========================================================================
    // Helpers
    // =========================================================================

    function showSpinner(msg) {
        $('#bfcc-spinner-msg').text(msg || '…');
        $('#bfcc-spinner').show();
    }

    function hideSpinner() {
        $('#bfcc-spinner').hide();
    }

    function showNotice($container, type, msg) {
        $container
            .attr('class', 'bfcc-notice bfcc-notice-' + type)
            .text(msg)
            .show();
    }

    function activateTab(tabName) {
        $('.bfcc-tab-btn').removeClass('active');
        $('.bfcc-tab-panel').removeClass('active');
        $('[data-tab="' + tabName + '"]').addClass('active');
        $('#bfcc-tab-' + tabName).addClass('active');
    }

    // =========================================================================
    // Tab navigation — sync + reload when switching to setup tab
    // =========================================================================

    // Restore active tab after a sync-triggered reload
    (function () {
        var savedTab = localStorage.getItem('bfish_active_tab');
        if (savedTab) {
            localStorage.removeItem('bfish_active_tab');
            activateTab(savedTab);
        }
    })();

    $(document).on('click', '.bfcc-tab-btn:not([disabled])', function () {
        // Dashboard tab — handled by its own handler below
        if ( $(this).hasClass('bfcc-btn-zonemembre') ) return;

        var tab = $(this).data('tab');

        // Already on this tab — nothing to do
        if ($(this).hasClass('active')) return;

        // Switching to setup while connected: sync then reload
        if ( isConnected && tab === 'setup' ) {
            localStorage.setItem('bfish_active_tab', tab);
            showSpinner(bfishData.lang === 'fr' ? 'Synchronisation…' : 'Syncing…');
            $.post(ajaxUrl, { action: 'bfish_sync', nonce: nonce })
            .always(function () {
                location.reload();
            });
            return;
        }

        activateTab(tab);
    });

    // =========================================================================
    // Language toggle
    // =========================================================================

    $(document).on('click', '.bfcc-lang-btn', function () {
        var lang = $(this).data('lang');
        if ($(this).hasClass('active')) return;

        $.post(ajaxUrl, { action: 'bfish_set_lang', nonce: nonce, lang: lang }, function () {
            location.reload();
        });
    });

    // =========================================================================
    // Login
    // =========================================================================

    $('#bfcc-btn-login').on('click', function () {
        var email    = $('#bfcc-email').val().trim();
        var password = $('#bfcc-password').val();

        if (!email || !password) {
            showNotice($('#bfcc-login-error'), 'error', bfishData.lang === 'fr'
                ? 'Veuillez remplir tous les champs.'
                : 'Please fill in all fields.');
            return;
        }

        showSpinner(bfishData.lang === 'fr' ? 'Connexion en cours…' : 'Connecting…');

        $.post(ajaxUrl, {
            action:   'bfish_login',
            nonce:    nonce,
            email:    email,
            password: password
        })
        .done(function (resp) {
            hideSpinner();
            if (resp.success) {
                location.reload();
            } else {
                showNotice($('#bfcc-login-error'), 'error', resp.data.message);
            }
        })
        .fail(function () {
            hideSpinner();
            showNotice($('#bfcc-login-error'), 'error', bfishData.lang === 'fr'
                ? 'Erreur de connexion. Veuillez réessayer.'
                : 'Connection error. Please try again.');
        });
    });

    // Allow pressing Enter in password field
    $('#bfcc-password').on('keydown', function (e) {
        if (e.key === 'Enter') $('#bfcc-btn-login').trigger('click');
    });

    // =========================================================================
    // Logout
    // =========================================================================

    $('#bfcc-btn-logout').on('click', function () {
        var confirmMsg = bfishData.lang === 'fr'
            ? 'Se déconnecter de BookingFish ?'
            : 'Disconnect from BookingFish?';

        if (!confirm(confirmMsg)) return;

        showSpinner(bfishData.lang === 'fr' ? 'Déconnexion…' : 'Disconnecting…');

        $.post(ajaxUrl, { action: 'bfish_logout', nonce: nonce })
        .done(function () {
            hideSpinner();
            location.reload();
        });
    });

    // =========================================================================
    // Sync
    // =========================================================================

    function doSync() {
        showSpinner(bfishData.lang === 'fr' ? 'Synchronisation…' : 'Syncing…');

        $.post(ajaxUrl, { action: 'bfish_sync', nonce: nonce })
        .done(function (resp) {
            hideSpinner();
            if (resp.success) {
                location.reload();
            } else {
                alert(resp.data.message);
            }
        })
        .fail(function () {
            hideSpinner();
            alert(bfishData.lang === 'fr' ? 'Erreur de synchronisation.' : 'Sync error.');
        });
    }

    $('#bfcc-btn-sync').on('click', doSync);

    // =========================================================================
    // Create page
    // =========================================================================

    $(document).on('click', '.bfcc-btn-create', function () {
        var $btn     = $(this);
        var subType  = $btn.data('sub-type');
        var $creator = $btn.closest('.bfcc-page-creator');
        var $input   = $creator.find('.bfcc-page-title[data-sub-type="' + subType + '"]');
        var type     = $input.data('type');
        var title    = $input.val().trim();

        if (!title) {
            $input.addClass('bfcc-input-error');
            $input.focus();
            return;
        }
        $input.removeClass('bfcc-input-error');

        showSpinner(bfishData.lang === 'fr' ? 'Création de la page…' : 'Creating page…');

        $.post(ajaxUrl, {
            action:   'bfish_create_page',
            nonce:    nonce,
            title:    title,
            type:     type,
            sub_type: subType
        })
        .done(function (resp) {
            hideSpinner();

            if (resp.success) {
                var url        = resp.data.url;
                var pageId     = resp.data.page_id;
                var isFr       = bfishData.lang === 'fr';
                var confirmMsg = isFr ? 'Supprimer cette page ?' : 'Delete this page?';
                var createdLbl = isFr ? 'Page créée :' : 'Page created:';
                var deleteLbl  = isFr ? 'Supprimer' : 'Delete';

                $creator.find('.bfcc-creator-inputs').replaceWith(
                    '<div class="bfcc-existing-page">' +
                        '<span class="dashicons dashicons-yes-alt bfcc-icon-success"></span> ' +
                        createdLbl + ' ' +
                        '<a href="' + url + '" target="_blank" rel="noopener">' + url + '</a>' +
                        ' <button class="bfcc-btn-copy-url bfcc-icon-btn" data-url="' + url + '" title="Copier">' +
                            '<span class="dashicons dashicons-admin-page"></span>' +
                        '</button>' +
                        ' <button class="bfcc-btn bfcc-btn-danger bfcc-btn-sm bfcc-btn-delete"' +
                            ' data-page-id="' + pageId + '"' +
                            ' data-confirm="' + confirmMsg + '">' +
                            '<span class="dashicons dashicons-trash"></span> ' + deleteLbl +
                        '</button>' +
                    '</div>'
                );
                $creator.find('.bfcc-page-result').hide();
                $creator.addClass('bfcc-already-created');
            } else {
                var $result = $creator.find('.bfcc-page-result[data-sub-type="' + subType + '"]');
                $result.html(resp.data.message)
                       .attr('class', 'bfcc-page-result bfcc-notice bfcc-notice-error')
                       .show();
            }
        })
        .fail(function () {
            hideSpinner();
            alert(bfishData.lang === 'fr' ? 'Erreur lors de la création.' : 'Error creating page.');
        });
    });

    // =========================================================================
    // Delete page
    // =========================================================================

    $(document).on('click', '.bfcc-btn-delete', function () {
        var $btn        = $(this);
        var pageId      = $btn.data('page-id');
        var confirm_msg = $btn.data('confirm') ||
            (bfishData.lang === 'fr' ? 'Supprimer cette page ?' : 'Delete this page?');

        if (!confirm(confirm_msg)) return;

        showSpinner(bfishData.lang === 'fr' ? 'Suppression…' : 'Deleting…');

        $.post(ajaxUrl, { action: 'bfish_delete_page', nonce: nonce, page_id: pageId })
        .done(function (resp) {
            hideSpinner();
            if (resp.success) {
                localStorage.setItem('bfish_active_tab', 'setup');
                location.reload();
            } else {
                alert(resp.data.message);
            }
        })
        .fail(function () {
            hideSpinner();
        });
    });

    // =========================================================================
    // Copy URL to clipboard
    // =========================================================================

    $(document).on('click', '.bfcc-btn-copy-url', function () {
        var url = $(this).data('url');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                var $btn = $(this);
                $btn.find('.dashicons').removeClass('dashicons-admin-page').addClass('dashicons-yes');
                setTimeout(function () {
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-admin-page');
                }, 1500);
            }.bind(this));
        } else {
            var $tmp = $('<textarea>').val(url).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
        }
    });

    // =========================================================================
    // Boat Calendar button — magic link (auto-login sur bookingfish.ca)
    // =========================================================================

    $(document).on('click', '.bfcc-btn-boat-cal', function () {
        showSpinner(bfishData.lang === 'fr' ? 'Ouverture de Boat Calendar…' : 'Opening Boat Calendar…');

        $.post(ajaxUrl, { action: 'bfish_get_boat_calendar_url', nonce: nonce })
        .done(function (resp) {
            hideSpinner();
            var url = (resp.success && resp.data && resp.data.url)
                ? resp.data.url
                : bfishData.boatCalendarUrl;
            window.open(url, '_blank');
        })
        .fail(function () {
            hideSpinner();
            window.open(bfishData.boatCalendarUrl, '_blank');
        });
    });

    // =========================================================================
    // Dash Board Bookingfish.ca tab — magic link (auto-login)
    // =========================================================================

    $(document).on('click', '.bfcc-btn-zonemembre', function () {
        showSpinner(bfishData.lang === 'fr' ? 'Ouverture du tableau de bord…' : 'Opening dashboard…');

        $.post(ajaxUrl, { action: 'bfish_get_zonemembre_url', nonce: nonce })
        .done(function (resp) {
            hideSpinner();
            var url = (resp.success && resp.data && resp.data.url)
                ? resp.data.url
                : bfishData.siteUrl + '/zonemembre/';
            window.open(url, '_blank');
        })
        .fail(function () {
            hideSpinner();
            window.open(bfishData.siteUrl + '/zonemembre/', '_blank');
        });
    });

})(jQuery);
