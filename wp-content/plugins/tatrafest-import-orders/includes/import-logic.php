<?php
/**
 * Import logic for orders
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main function to import order by order number
 */
function tatrafest_import_order_by_number($order_number)
{
    global $wpdb;
    $starting_list_table = $wpdb->prefix . 'starting_list';
    $starting_events_table = $wpdb->prefix . 'starting_events';
    $starting_distances_table = $wpdb->prefix . 'starting_distances';
    $starting_events_distances_table = $wpdb->prefix . 'starting_events_distances';

    try {
        // Get starting list data
        $starting_list_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$starting_list_table} WHERE orderNumber = %d",
                intval($order_number)
            )
        );

        if (!$starting_list_data) {
            return [
                'success' => false,
                'message' => 'Nie znaleziono zamówienia z numerem: ' . intval($order_number),
            ];
        }

        // Get event data
        $event_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$starting_events_table} WHERE eventID = %d",
                intval($starting_list_data->eventID)
            )
        );

        if (!$event_data) {
            return [
                'success' => false,
                'message' => 'Nie znaleziono danych eventu',
            ];
        }

        // Get distance data
        $distance_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$starting_distances_table} WHERE distanceID = %d",
                intval($starting_list_data->distanceID)
            )
        );

        if (!$distance_data) {
            return [
                'success' => false,
                'message' => 'Nie znaleziono danych dystansu',
            ];
        }

        // Get event-distance relation data
        $event_distance_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$starting_events_distances_table} WHERE eventID = %d AND distanceID = %d ORDER BY ordering ASC LIMIT 1",
                intval($starting_list_data->eventID),
                intval($starting_list_data->distanceID)
            )
        );

        if (!$event_distance_data) {
            return [
                'success' => false,
                'message' => 'Nie znaleziono danych mapowania event-distance',
            ];
        }

        // Create WooCommerce order
        $wc_order = create_wc_order_from_starting_list(
            $starting_list_data,
            $event_data,
            $distance_data,
            $event_distance_data
        );

        if (is_wp_error($wc_order)) {
            return [
                'success' => false,
                'message' => 'Błąd podczas tworzenia zamówienia: ' . $wc_order->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Zamówienie zostało pomyślnie importowane',
            'order_id' => $wc_order->get_id(),
            'order_number' => $wc_order->get_order_number(),
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Błąd: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create WooCommerce order from starting list data
 */
function create_wc_order_from_starting_list($starting_list, $event, $distance, $event_distance)
{
    try {
        // Create order
        $order = wc_create_order();

        if (is_wp_error($order)) {
            return $order;
        }

        // Get or create customer
        $customer_id = get_or_create_customer($starting_list);

        if ($customer_id > 0) {
            $order->set_customer_id($customer_id);
            $order->set_billing_first_name($starting_list->firstName);
            $order->set_billing_last_name($starting_list->surname ?? '');
            $order->set_billing_email($starting_list->email ?? '');
            $order->set_billing_phone($starting_list->phone ?? '');
            $order->set_billing_country($starting_list->country ?? 'PL');
            $order->set_billing_city($starting_list->city ?? '');
            $order->set_billing_state('');
            $order->set_billing_postcode($starting_list->postCode ?? '');
            $order->set_billing_address_1($starting_list->address ?? '');
        } else {
            // Set customer data directly on order if no customer ID
            $order->set_billing_first_name($starting_list->firstName);
            $order->set_billing_last_name($starting_list->surname ?? '');
            $order->set_billing_email($starting_list->email ?? '');
            $order->set_billing_phone($starting_list->phone ?? '');
            $order->set_billing_country($starting_list->country ?? 'PL');
            $order->set_billing_city($starting_list->city ?? '');
            $order->set_billing_state('');
            $order->set_billing_postcode($starting_list->postCode ?? '');
            $order->set_billing_address_1($starting_list->address ?? '');
        }

        // Add item to order
        $product = get_distance_product($distance, $event, $event_distance);

        if (!$product) {
            // Create a simple item if product doesn't exist
            $fallback_product = wc_get_product(1);

            if (!$fallback_product) {
                return new WP_Error(
                    'wc_product_missing',
                    'Nie znaleziono produktu do dodania do zamowienia (variantID/productID/fallback ID 1).'
                );
            }

            $order->add_product(
                $fallback_product, // Fallback to product ID 1
                1,
                [
                    'subtotal' => (float) $fallback_product->get_price(),
                    'total' => (float) $fallback_product->get_price(),
                ]
            );
        } else {
            $order->add_product(
                $product,
                1,
                [
                    'subtotal' => (float) $product->get_price(),
                    'total' => (float) $product->get_price(),
                ]
            );
        }

        // Set order meta data
        set_wc_order_meta($order, $starting_list, $event_distance);

        // Calculate totals
        $order->calculate_totals();

        // Set order status based on payment status from starting list
        $status = tatrafest_map_payment_status_to_wc_status($starting_list->paymentStatus ?? '');
        $order->set_status($status);

        // Keep original source date on order if present and valid.
        if (!empty($starting_list->date)) {
            $source_date = date_create($starting_list->date);
            if ($source_date instanceof DateTime) {
                $order->set_date_created($source_date->format('Y-m-d H:i:s'));
            }
        }

        // Save order
        $order->save();

        // Delete element from starting list with imported order ID
        global $wpdb;
        $table_starting_list = $wpdb->prefix . 'starting_list';
        $wpdb->delete($table_starting_list, ['orderNumber' => $starting_list->orderNumber]);

        return $order;

    } catch (Exception $e) {
        return new WP_Error('wc_order_creation_failed', $e->getMessage());
    }
}

/**
 * Get or create customer
 */
function get_or_create_customer($starting_list)
{
    $email = $starting_list->email ?? '';

    if (empty($email)) {
        return 0;
    }

    // Check if customer with this email exists
    $customer = get_user_by('email', $email);

    if ($customer) {
        return $customer->ID;
    }

    // Create new customer
    $user_id = wp_create_user(
        sanitize_user($email),
        wp_generate_password(),
        $email
    );

    if (is_wp_error($user_id)) {
        return 0;
    }

    // Update user meta with additional data
    update_user_meta($user_id, 'first_name', $starting_list->firstName ?? '');
    update_user_meta($user_id, 'last_name', $starting_list->surname ?? '');
    update_user_meta($user_id, 'billing_phone', $starting_list->phone ?? '');
    update_user_meta($user_id, 'billing_country', $starting_list->country ?? 'PL');
    update_user_meta($user_id, 'billing_city', $starting_list->city ?? '');
    update_user_meta($user_id, 'billing_state', '');
    update_user_meta($user_id, 'billing_postcode', $starting_list->postCode ?? '');
    update_user_meta($user_id, 'billing_address_1', $starting_list->address ?? '');

    return $user_id;
}

/**
 * Get product for distance and event
 */
function get_distance_product($distance, $event, $event_distance)
{
    global $wpdb;

    // Highest priority: variation mapping from {$wpdb->prefix}starting_events_distances.variantID
    if (!empty($event_distance->variantID)) {
        $variation = wc_get_product((int) $event_distance->variantID);
        if ($variation instanceof WC_Product) {
            return $variation;
        }
    }

    // Next: event-level product mapping from {$wpdb->prefix}starting_events.productID
    if (!empty($event->productID)) {
        $event_product = wc_get_product((int) $event->productID);
        if ($event_product instanceof WC_Product) {
            return $event_product;
        }
    }

    // Try to find product by event ID and distance name
    $product_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE (p.post_type = 'product' OR p.post_type = 'product_variation')
             AND pm.meta_key = '_product_event_id'
             AND pm.meta_value = %d
             LIMIT 1",
            intval($event->eventID)
        )
    );

    if ($product_id) {
        return wc_get_product($product_id);
    }

    // Fallback: search by product name containing distance name
    $products = wc_get_products([
        'search' => $distance->name ?? '',
        'limit' => 1,
    ]);

    return !empty($products) ? $products[0] : null;
}

/**
 * Set WooCommerce order meta data
 */
function set_wc_order_meta($order, $starting_list, $event_distance)
{
    // Set personal data
    $order->update_meta_data('_starting_list_id', $starting_list->id ?? '');
    $order->update_meta_data('billing_birth_date', $starting_list->birthDate ?? '');
    $order->update_meta_data('billing_sex', $starting_list->sex ?? 'kobieta');
    $order->update_meta_data('billing_alarm_phone', $starting_list->alarmPhone ?? '');
    $order->update_meta_data('billing_meal', $starting_list->meal ?? 'vege');
    $order->update_meta_data('billing_club', $starting_list->club ?? '-');

    // Set other fields
    $order->update_meta_data('_payment_status', $starting_list->paymentStatus ?? '');
    $order->update_meta_data('_source_date', $starting_list->date ?? '');
    $order->update_meta_data('_event_id', $starting_list->eventID ?? '');
    $order->update_meta_data('_distance_id', $starting_list->distanceID ?? '');
    $order->update_meta_data('_event_distance_ordering', $event_distance->ordering ?? '');
    $order->update_meta_data('_event_distance_variant_id', $event_distance->variantID ?? '');
    $order->update_meta_data('_imported_from_starting_list', '1');
    $order->update_meta_data('_import_date', current_time('mysql'));
}

/**
 * Maps paymentStatus from {$wpdb->prefix}starting_list to WooCommerce order status.
 */
function tatrafest_map_payment_status_to_wc_status($payment_status)
{
    $status = strtolower(trim((string) $payment_status));

    if (in_array($status, ['tak', 'yes', 'paid', 'completed', '1'], true)) {
        return 'completed';
    }

    if (in_array($status, ['failed', 'error', 'cancelled', 'canceled'], true)) {
        return 'failed';
    }

    return 'pending';
}

/**
 * Helper function to search for value in array
 */
function search_for_value($id, $key, $array)
{
    foreach ($array as $item) {
        if (isset($item->{$key}) && $item->{$key} == $id) {
            return $item;
        }
    }
    return null;
}

/**
 * Helper function to search for key-value pair in array
 */
function search_for_key_value($key, $array)
{
    foreach ($array as $item) {
        if (is_object($item)) {
            if (isset($item->key) && $item->key === $key) {
                return $item->value ?? '';
            }
        } elseif (is_array($item)) {
            if (isset($item['key']) && $item['key'] === $key) {
                return $item['value'] ?? '';
            }
        }
    }
    return '';
}
