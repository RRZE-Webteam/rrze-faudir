[![Aktuelle Version](https://img.shields.io/github/package-json/v/rrze-webteam/rrze-faudir/main?label=Version)](https://github.com/RRZE-Webteam/rrze-faudir)
[![Release Version](https://img.shields.io/github/v/release/rrze-webteam/rrze-faudir?label=Release+Version)](https://github.com/rrze-webteam/rrze-faudir/releases/)
[![GitHub License](https://img.shields.io/github/license/rrze-webteam/rrze-faudir)](https://github.com/RRZE-Webteam/rrze-faudir)
[![GitHub issues](https://img.shields.io/github/issues/RRZE-Webteam/rrze-faudir)](https://github.com/RRZE-Webteam/rrze-faudir/issues)

# RRZE FAUdir

Plugin zur Darstellung des Personen- und Einrichtungsverzeichnis der FAU in Websites


## Contributors

* RRZE-Webteam, http://www.rrze.fau.de (Version 2.2 and later)
* Mondula, https://mondula.com/ (Version 1 - Version 2.1)

## Copyright

GNU General Public License (GPL) Version 3


## Documentation

See documenation at https://www.wp.rrze.fau.de

## Feedback

* https://github.com/RRZE-Webteam/rrze-faudir/issues
* webmaster@rrze.fau.de


## Filter for external plugins and themes

To get data from the plugin to use in other plugins or themes, the following filters are avaible.

* rrze_faudir_get_target_url , input: FAUdir identifier, output: URL
  
   Example usage:
    
   ```php
   $url = apply_filters('rrze_faudir_get_target_url', [], $identifier);
   ```

    To add own overwrites:

    ```php
    add_filter('rrze_faudir_get_target_url', function($url, $identifier){
	// z.B. eigene Routing-Logik, Sonderfälle, Tracking-Parameter …
	return $url;
    }, 20, 2);
    ```

    Its also possible to make a direct function call in the following ways:
    ```php
    $url = \RRZE\FAUdir\Filters::get_target_url($identifier);

    // or, if wrapper is avaible:
    $url = function_exists('faudir_get_target_url') ? faudir_get_target_url($identifier) : '';
    ```

* rrze_faudir_get_person_array , input: FAUdir identifier, output: Array with person data

    Example usage:

   ```php
   $person = apply_filters('rrze_faudir_get_person_array', [], $identifier);
   ```

   To add own overwrites:

    ```php
    add_filter('rrze_faudir_get_person_array', function(array $data, string $identifier) {
	return $data;
    }, 20, 2);
    ```

   Its also possible to make a direct function call in the following ways:

   ```php
    $person = \RRZE\FAUdir\Filters::get_person_array($identifier);

    // or, if wrapper is avaible:
    $person = function_exists('faudir_get_person_array') ? faudir_get_person_array($identifier) : [];
    ```

* Abhängig vom Theme werden die Filter 
        
    * `fau_elemental_copyright_info` (beim Theme FAU Elemental )
    * `fau_copyright_info` (bei allen anderen FAU-Themes)
  
  gesetzt. Diese enthalten bei der Ausgabe von Personenbildern mit Copyright-Text den jeweiligen text und die Bild-ID
