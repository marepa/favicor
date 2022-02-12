<?php
    /*
     * Plugin Name: Favicor
     * Plugin URI: https://www.sikmo.cz/
     * Description: Load favicons easily.
     * Version: 1.0.0
     * Author: Šikmo.cz
     * Author URI: https://www.sikmo.cz/
    */

    namespace Favicor;

    defined( 'ABSPATH' ) || exit;


    if ( ! class_exists( 'Favicor' ) ) :

        class Favicor {

            /** @var string The plugin version number. */
            var $version = '1.0.0';

            /** @var array The plugin settings array. */
            var $settings = array();

            /** @var array Storage for class instances. */
            var $instances = array();

            /**
             * __construct
             *
             * A dummy constructor to ensure favicor is only setup once.
             *
             * @date    23/06/12
             * @since   1.0.0
             *
             * @param   void
             * @return  void
             */
            function __construct() {
                // when activating plugin go for base files
                register_activation_hook( __FILE__, array( $this, 'activation' ) );
            }

            /**
             * initialize
             *
             * Sets up the favicor plugin.
             *
             * @date    28/09/13
             * @since   1.0.0
             *
             * @param   void
             * @return  void
             */
            function initialize() {

                // Define constants.
                $this->define( 'FAVICOR', true );
                $this->define( 'FAVICOR_PATH', plugin_dir_path( __FILE__ ) );
                $this->define( 'FAVICOR_URL',  plugin_dir_url( __FILE__ ) );
                $this->define( 'FAVICOR_INCLUDES', FAVICOR_PATH . 'includes/' );
                $this->define( 'FAVICOR_ASSETS_URI', FAVICOR_URL . 'assets/' );
                $this->define( 'FAVICOR_BASENAME', plugin_basename( __FILE__ ) );
                $this->define( 'FAVICOR_FAVICONS_PATH', ABSPATH . "favicons/" );
                $this->define( 'FAVICOR_FAVICONS_URI', site_url() . "/favicons/" );
                $this->define( 'FAVICOR_VERSION', $this->version );

                // Define settings.
                $this->settings = array(
                    'name'                   => __( 'Favicor', 'favicor' ),
                    'slug'                   => dirname( FAVICOR_BASENAME ),
                    'version'                => FAVICOR_VERSION,
                    'basename'               => FAVICOR_BASENAME,
                    'path'                   => FAVICOR_PATH,
                    'url'                    => plugin_dir_url( __FILE__ ),
                    'capability'             => 'manage_options',
                );

                $include_once = array(
                    'base' 		        => 'Base', 		// load needed functions
                    'php-ico' 			=> 'PHP_ICO', 		// register post type
                );

                foreach( (array) $include_once as $class_slug => $class_name ) {
                    // $class_name 	= ucfirst( $class_slug );
                    $class_space 	= "favicor\\{$class_name}";

                    include_once FAVICOR_INCLUDES . "class-{$class_slug}.php";

                    $class_slug = str_replace( '-', '_', $class_slug );
                    $GLOBALS[ "favicor_$class_slug" ] = new $class_space;
                }

                /**
                 * Fires after favicor is completely "initialized".
                 *
                 * @date    15/01/22
                 * @since   1.0.0
                 */
                do_action( 'favicor/init' );
            }

            function activation() {
                mkdir( FAVICOR_FAVICONS_PATH );
            }

            /**
             * define
             *
             * Defines a constant if doesnt already exist.
             *
             * @date    3/5/17
             * @since   5.5.13
             *
             * @param   string $name The constant name.
             * @param   mixed  $value The constant value.
             * @return  void
             */
            function define( $name, $value = true ) {
                if ( ! defined( $name ) ) {
                    define( $name, $value );
                }
            }
        }

        /*
        * favicor
        *
        * The main function responsible for returning the one true favicor Instance to functions everywhere.
        * Use this function like you would a global variable, except without needing to declare the global.
        *
        * Example: <?php $favicor = favicor(); ?>
        *
        * @date    4/09/13
        * @since   4.3.0
        *
        * @param   void
        * @return  favicor
        */
        function favicor() {
            global $favicor;

            // Instantiate only once.
            if ( ! isset( $favicor ) ) {
                $favicor = new Favicor();
                $favicor->initialize();
            }
            
            return $favicor;
        }

        // Instantiate.
        favicor();

    endif;