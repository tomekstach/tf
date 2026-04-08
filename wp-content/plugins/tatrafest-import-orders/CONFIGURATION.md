# Konfiguracja Tatrafest Import Orders Plugin

## Prefiks tabel

Plugin korzysta z prefiksu WordPressa przez `$wpdb->prefix`.
Nazwy tabel sa budowane dynamicznie:

- `{$wpdb->prefix}starting_list`
- `{$wpdb->prefix}starting_events`
- `{$wpdb->prefix}starting_distances`
- `{$wpdb->prefix}starting_events_distances`

Przyklad dla prefiksu `wp_`:

- `wp_starting_list`
- `wp_starting_events`
- `wp_starting_distances`
- `wp_starting_events_distances`

## Oczekiwane kolumny

### starting_list

- id
- orderNumber
- firstName
- surname
- address
- city
- postCode
- sex
- country
- birthDate
- club
- alarmPhone
- phone
- email
- meal
- paymentStatus
- date
- eventID
- distanceID

### starting_events

- eventID
- name
- year
- productID

### starting_distances

- distanceID
- name

### starting_events_distances

- eventID
- distanceID
- ordering
- variantID

## Sprawdzenie SQL (przyklad dla `wp_`)

```sql
SHOW TABLES LIKE 'wp\_%';
DESCRIBE wp_starting_list;
DESCRIBE wp_starting_events;
DESCRIBE wp_starting_distances;
DESCRIBE wp_starting_events_distances;
SELECT COUNT(*) FROM wp_starting_list;
```

## Uwagi

- Zawsze uzywany jest prefiks z `wp-config.php`.
