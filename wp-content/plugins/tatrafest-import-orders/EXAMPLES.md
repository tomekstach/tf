# Examples

## Pojedynczy import

```php
$result = tatrafest_import_order_by_number(123);
if ($result['success']) {
    echo 'OK: ' . $result['order_id'];
} else {
    echo 'BLAD: ' . $result['message'];
}
```

## Tabele i prefix

W kodzie pluginu tabele sa liczone dynamicznie:

```php
$starting_list_table = $wpdb->prefix . 'starting_list';
$starting_events_table = $wpdb->prefix . 'starting_events';
$starting_distances_table = $wpdb->prefix . 'starting_distances';
```
