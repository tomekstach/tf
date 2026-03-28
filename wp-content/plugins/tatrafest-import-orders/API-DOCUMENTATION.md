# API Documentation

## Main function

`tatrafest_import_order_by_number($order_number)`

Importuje zamowienie po numerze `orderNumber`.

## Data sources

Plugin czyta:

- `{$wpdb->prefix}starting_events_distances`

## Returned structure

Sukces:

```php
[
  'success' => true,
  'message' => '... ',
  'order_id' => 123,
  'order_number' => '123'
]
```

Blad:

```php
[
  'success' => false,
  'message' => '...'
]
```
