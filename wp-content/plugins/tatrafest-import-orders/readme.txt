=== Tatrafest Import Orders ===
Contributors: tatrafest
Requires at least: 5.0
Requires PHP: 7.2
Tested up to: 6.4
Requires Plugins: woocommerce
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import zamowien do WooCommerce z dynamicznym prefiksem WordPressa.

== Description ==

Plugin importuje dane do zamowien WooCommerce z tabel:
- {$wpdb->prefix}starting_list
- {$wpdb->prefix}starting_events
- {$wpdb->prefix}starting_distances

Prefix jest pobierany dynamicznie z konfiguracji WordPressa.

== Installation ==

1. Wgraj plugin do /wp-content/plugins/
2. Aktywuj plugin
3. Wejdz do "Tatrafest Import" i uruchom import

== Changelog ==

= 1.0.0 =
* Pierwsza wersja
* Dynamiczny prefix WordPressa
