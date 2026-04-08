# Tatrafest Import Orders Plugin

Plugin importuje zamowienia z tabel pomocniczych do WooCommerce.

## Jak dziala prefiks

Plugin uzywa dynamicznego prefiksu WordPressa (`$wpdb->prefix`) dla tabel:

- `{$wpdb->prefix}starting_list`
- `{$wpdb->prefix}starting_events`
- `{$wpdb->prefix}starting_distances`
- `{$wpdb->prefix}starting_events_distances`

## Import

1. Wejdz do panelu: Tatrafest Import.
2. Podaj `orderNumber`.
3. Plugin pobierze rekordy z tabel starting_list, starting_events, starting_distances i starting_events_distances.
4. Plugin utworzy zamowienie WooCommerce.

## Mapowanie kolumn

### starting_list

`id, orderNumber, firstName, surname, address, city, postCode, sex, country, birthDate, club, alarmPhone, phone, email, meal, paymentStatus, date, eventID, distanceID`

### starting_events

`eventID, name, year, productID`

### starting_distances

`distanceID, name`

### starting_events_distances

`eventID, distanceID, ordering, variantID`
