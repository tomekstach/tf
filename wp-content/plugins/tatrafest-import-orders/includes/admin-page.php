<?php
/**
 * Admin page for importing orders
 */

if (!defined('ABSPATH')) {
    exit;
}

function tatrafest_import_orders_render_admin_page()
{
    global $wpdb;

    $table_starting_list = $wpdb->prefix . 'starting_list';
    $table_starting_events = $wpdb->prefix . 'starting_events';
    $table_starting_distances = $wpdb->prefix . 'starting_distances';
    $table_starting_events_distances = $wpdb->prefix . 'starting_events_distances';
    $table_wc_orders = $wpdb->prefix . 'wc_orders';

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Nie masz uprawnien do dostepu do tej strony.');
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="tatrafest-import-container">
            <div class="tatrafest-import-form-wrapper">
                <h2>Importuj zamowienie</h2>
                <p>Podaj numer zamowienia z tabeli <code><?php echo esc_html($table_starting_list); ?></code> (jest to rowne ID z tabeli <code><?php echo esc_html($table_wc_orders); ?></code>)</p>

                <form id="tatrafest-import-form" class="tatrafest-import-form">
                    <div class="form-group">
                        <label for="orderNumber">Numer zamowienia:</label>
                        <input
                            type="text"
                            id="orderNumber"
                            name="orderNumber"
                            placeholder="np. 12345"
                            class="regular-text"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <button type="submit" class="button button-primary button-large">
                            Importuj zamowienie
                        </button>
                        <span class="spinner" style="float: none; margin-left: 10px;"></span>
                    </div>
                </form>

                <div id="tatrafest-import-message" class="notice" style="display: none;"></div>
            </div>

            <div class="tatrafest-import-info">
                <h3>Informacje</h3>
                <p>
                    Ten plugin importuje dane z nastepujacych tabel:
                </p>
                <ul>
                    <li><strong><?php echo esc_html($table_starting_list); ?></strong> - glowne dane zamowienia</li>
                    <li><strong><?php echo esc_html($table_starting_events); ?></strong> - informacje o eventach</li>
                    <li><strong><?php echo esc_html($table_starting_distances); ?></strong> - slownik dystansow</li>
                    <li><strong><?php echo esc_html($table_starting_events_distances); ?></strong> - mapowanie event-distance (ordering, variantID)</li>
                </ul>
                <p>
                    Dane zostana skonwertowane i dodane do systemu WooCommerce.
                </p>
            </div>
        </div>
    </div>
    <?php
}
