<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('woocommerce_update_order', 'custom_update_order');
function custom_update_order($order_id)
{
    global $wpdb;
    // Get events from the database
    $events = $wpdb->get_results("SELECT * FROM tf_starting_events WHERE year = YEAR(CURDATE())+1");
    if (empty($events)) {
        $events = $wpdb->get_results("SELECT * FROM tf_starting_events WHERE year = YEAR(CURDATE())");
    }

    $eventsIDs = [];
    foreach ($events as $key => $event) {
        $eventsIDs[] = $event->eventID;
    }

    // Get distances from the database
    $distances = $wpdb->get_results("SELECT distance.*, event_distance.ordering FROM tf_starting_distances AS distance LEFT JOIN tf_starting_events_distances AS event_distance ON event_distance.distanceID = distance.distanceID WHERE event_distance.eventID IN (" . implode(',', $eventsIDs) . ") ORDER BY event_distance.ordering ASC");

    $order  = wc_get_order($order_id);
    $status = $order->get_status();

    // Remove starting list for given orderNumber
    $wpdb->delete('tf_starting_list', ['orderNumber' => $order_id]);

    if ($status === 'cancelled' or $status === 'failed' or $status === 'refunded' or $status === 'checkout-draft') {
        return;
    }

    if ($status === 'completed') {
        $status = 'tak';
    } else {
        $status = 'nie';
    }

    $startingList = [];
    foreach ($order->get_items() as $item_id => $item) {
        $product    = $item->get_product();
        $productID  = $product->get_parent_id();
        $attributes = $product->get_attributes();

//         if ($attributes['typ'] === 'Bez kategorii') {

        $distance = searchForValue($attributes['dystans'], 'name', $distances);
        if ($distance == null or $distance == '') {
            $distance = searchForValue($attributes['rodzaj'], 'name', $distances);
        }
        $event = searchForValue($productID, 'productID', $events);

        $club       = '-';
        $sex        = 'kobieta';
        $meal       = 'vege';
        $birthDate  = '';
        $alarmPhone = '';

        if ($distance->distanceID == 6 or $distance->distanceID == 7) {
            $itemMetaData = $item->get_data()['meta_data'];
            $sex          = searchForKeyValue('Płeć', $itemMetaData);
            if ($sex === 'Mężczyzna' or $sex === 'Mezczyzna' or $sex === 'mężczyzna') {
                $sex = 'mezczyzna';
            } else {
                $sex = 'kobieta';
            }
            $birthDate = searchForKeyValue('Data urodzenia', $itemMetaData);
            // Change date format from dd-mm-yyyy to yyyy-mm-dd
            if ($birthDate) {
                $birthDateParts = explode('-', $birthDate);
                if (count($birthDateParts) === 3) {
                    $birthDate = $birthDateParts[2] . '-' . $birthDateParts[1] . '-' . $birthDateParts[0];
                }
            }

            $meal = searchForKeyValue('Posiłek', $itemMetaData);
            if ($meal === 'Mięsny' or $meal === 'miesny') {
                $meal = 'miesny';
            } else {
                $meal = 'vege';
            }

            $club = searchForKeyValue('Klub', $itemMetaData);
            if (strlen(trim($club)) > 0) {
                $club = $club;
            } else {
                $club = '-';
            }

            $country   = searchForKeyValue('Kraj', $itemMetaData);
            $city      = searchForKeyValue('Miejscowość', $itemMetaData);
            $firstName = searchForKeyValue('Imię dziecka', $itemMetaData);
            $lastName  = searchForKeyValue('Nazwisko dziecka', $itemMetaData);

            foreach ($order->meta_data as $metaItem) {
                $data = $metaItem->get_data();

                switch ($data['key']) {
                    case 'billing_alarm_phone':
                        $alarmPhone = $metaItem->value;
                        break;
                }
            }
        } else {
            foreach ($order->meta_data as $metaItem) {
                $data = $metaItem->get_data();

                switch ($data['key']) {
                    case 'billing_birth_date':
                        $birthDate = $metaItem->value;
                        break;
                    case 'billing_sex':
                        $sex = $metaItem->value;
                        if ($sex === 'mezczyzna') {
                            $sex = 'mezczyzna';
                        } else {
                            $sex = 'kobieta';
                        }
                        break;
                    case 'billing_alarm_phone':
                        $alarmPhone = $metaItem->value;
                        break;
                    case 'billing_meal':
                        $meal = $metaItem->value;
                        if ($meal === 'miesny') {
                            $meal = 'miesny';
                        } else {
                            $meal = 'vege';
                        }
                        break;
                    case 'billing_club':
                        $club = $metaItem->value;
                        if (strlen(trim($club)) > 0) {
                            $club = $club;
                        } else {
                            $club = '-';
                        }
                        break;
                }
            }

            $country   = $order->data['billing']['country'];
            $city      = $order->data['billing']['city'];
            $firstName = $order->data['billing']['first_name'];
            $lastName  = $order->data['billing']['last_name'];
        }

        if ($distance->distanceID > 0 and $event->eventID > 0) {
            $startingList = [
                'orderNumber'   => $order_id,
                'firstName'     => $firstName,
                'surname'       => $lastName,
                'address'       => $order->data['billing']['address_1'],
                'city'          => $city,
                'postcode'      => $order->data['billing']['postcode'],
                'country'       => $country,
                'email'         => $order->data['billing']['email'],
                'phone'         => $order->data['billing']['phone'],
                'birthDate'     => $birthDate,
                'sex'           => $sex,
                'club'          => $club,
                'alarmPhone'    => $alarmPhone,
                'meal'          => $meal,
                'paymentStatus' => $status,
                'distanceID'    => $distance->distanceID,
                'eventID'       => $event->eventID,
            ];

            // Store data in the database
            $return = $wpdb->insert('tf_starting_list', $startingList);

            if ($return === false) {
                print_r($wpdb->last_error);
                echo 'Error while inserting data to the database';
                exit();
            }
        }
    }
//     }
}

// Function to search for a specific object variable value in an array of objects
function searchForValue($value, $key, $array)
{
    foreach ($array as $k => $val) {
        if ($val->$key == $value) {
            return $val;
        }
    }
    return null;
}

function searchForKeyValue($key, $array)
{
    foreach ($array as $k => $val) {
        $objectData = $val->get_data();
        if ($objectData['key'] == $key) {
            return $objectData['value'];
        }
    }
    return null;
}

/**
 * Register REST API endpoint for getting starting list for the given run
 */
add_action('rest_api_init', function () {
    register_rest_route('tf/v1', '/event/lists', [
        'methods'  => 'GET',
        'callback' => 'get_tf_event',
    ]);
});

function get_tf_event($data)
{
    global $wpdb;
    $events = $wpdb->get_results("SELECT * FROM tf_starting_events WHERE year = YEAR(CURDATE())+1");
    if (empty($events)) {
        $events = $wpdb->get_results("SELECT * FROM tf_starting_events WHERE year = YEAR(CURDATE())");
    }

    $eventsIDs = [];
    foreach ($events as $key => $event) {
        $eventsIDs[] = $event->eventID;
    }

    // Get starting list for the given run from the database
    $startingList = $wpdb->get_results($wpdb->prepare("SELECT list.*, events.name AS event, dist.name AS distance FROM tf_starting_list AS list LEFT JOIN tf_starting_events AS events ON events.eventID = list.eventID LEFT JOIN tf_starting_distances AS dist ON dist.distanceID = list.distanceID WHERE list.eventID IN (" . implode(',', $eventsIDs) . ") ORDER BY list.orderNumber ASC"));

    if (empty($startingList)) {
        $startingList = [];
    }

    return $startingList;
}

// Disable email notifications for password reset
remove_action('after_password_reset', 'wp_password_change_notification');
