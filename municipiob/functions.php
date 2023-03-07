<?php
/**
 * municipioB Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package municipioB
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_MUNICIPIOB_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'municipiob-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_MUNICIPIOB_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

add_action( 'wp_head', function() {
  if ( is_page( 2043 ) || is_page( 135 ) ) {
    $leafLinks = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css"
     integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ=="
     crossorigin=""/>';
    $leafLinks .= '<script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js"
     integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ=="
     crossorigin=""></script>';
                 $leafLinks .= '<link rel="stylesheet" href="'.get_template_directory_uri().'/../municipiob/mapa-de-cuidados.css" />';
    echo $leafLinks;
  }
} );

function render_map($atts){
        ob_start();
  get_template_part('_mapa-de-cuidados');
  return ob_get_clean();
}

add_shortcode ('municipio-b-mapa-de-cuidados', 'render_map');

