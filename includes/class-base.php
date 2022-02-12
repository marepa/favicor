<?php
/**
 * Base
 */

namespace Favicor;

defined( 'ABSPATH' ) || exit;

/**
 * Base Class.
 */
class Base {
	/**
	 * Favicor colors.
	 *
	 * @var string
	 */
	public $mask_color = '#000000';
	public $theme_color = '#ffffff';

	/**
	 * The Constructor.
	 */
	public function __construct() {		
		// add admin page
        add_action( 'admin_menu', array( $this, 'admin_page' ) );

        // view favicons
        add_action( 'wp_head', array( $this, 'display_favicons' ), 5 );
		add_action( 'admin_head', array( $this, 'display_favicons' ), 5 );

        // load JS
        add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );

        // save it
        add_action( 'admin_init', array( $this, 'update_favicon' ) );
	}

	public function admin_page(){
        add_submenu_page( 
            'themes.php',
            __( 'Favicor', 'favicor' ), 
            __( 'Favicor', 'favicor' ), 
            'manage_options', 
            'favicor',
            array( $this, 'admin_page_content' ),
            "data:image/svg+xml,%3C%3Fxml version='1.0'%3F%3E%3Csvg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.com/svgjs' version='1.1' width='16' height='16' x='0' y='0' viewBox='0 0 16 16' style='enable-background:new 0 0 16 16' xml:space='preserve'%3E%3Ccircle cx='8' cy='8' r='8' fill='%23ffffff'/%3E%3C/svg%3E",
            60
        );
    }

    public function load_assets () {
        if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
     
        wp_enqueue_script( 'favicor-js', FAVICOR_ASSETS_URI . '/js/favicor.js', array( 'jquery' ) );
    }

    public function update_favicon() {
        if( isset( $_POST['favicor_main_image'] ) && is_numeric( $_POST['favicor_main_image'] ) ) {
            $attachment_id  = $_POST['favicor_main_image'];

            // nonce for evil hackers
            if( isset( $_POST['favicor_main_image_control'] ) && wp_verify_nonce( $_POST['favicor_main_image_control'], 'favicor_main_image_update' ) ) {
                $can_proceed = true;

                // create folder for favs, if can't be created just fuck off
                if( ! is_dir( FAVICOR_FAVICONS_PATH ) ) {
                    if( ! mkdir( FAVICOR_FAVICONS_PATH ) ) {
                        $can_proceed = false;
                    }
                }

                // if post_id is not attachment
                if( get_post_type( $attachment_id ) != 'attachment' ) {
                    $can_proceed = false;
                }

                // do not update if the same
                if( $attachment_id == get_option( 'favicor_image_id' ) ) {
                    $can_proceed = false;
                }

                if( $can_proceed ) {
                    
					if( $this->image_is_valid( $attachment_id ) ) {

                        $does_auto = $this->generate_favs( $attachment_id );
                        if( $does_auto ) {
							update_option( 'favicor_image_id', $attachment_id, true );
						}
                    }
                }
            }
        }
    }

    // generate all files necceary
    public function generate_favs( $attachment_id ) {
        $path_to_image  = wp_get_original_image_path( $attachment_id );
        $original_image = wp_get_image_editor( $path_to_image );

        if ( is_wp_error( $original_image ) ) return false;

        $image_set = array(
            array( 'android-chrome-512x512.png', 512, 512 ),
            array( 'android-chrome-192x192.png', 192, 192 ),
            array( 'apple-touch-icon.png', 180, 180 ),
            array( 'mstile-150x150.png', 150, 150 ),
            array( 'favicon-32x32.png', 32, 32 ),
            array( 'favicon-16x16.png', 16, 16 ),
        );

        foreach( (array) $image_set as $favicon ) {
            $original_image->resize( $favicon[1], $favicon[2], true );
            $original_image->save( FAVICOR_FAVICONS_PATH . $favicon[0] );
        }

		// reate ICO
		$this->create_ico( FAVICOR_FAVICONS_PATH . "favicon-32x32.png" );

        $this->save_xml();
        $this->save_manifest();
        
        return true;
    }

	public function create_ico( $source ) {
		global $favicor_php_ico;

		$destination = FAVICOR_FAVICONS_PATH . 'favicon.ico';

		$favicor_php_ico->create_ico( $source );
    	$favicor_php_ico->save_ico( $destination );
	}

    public function image_is_valid( $attachment_id ) {
        $attachment = wp_get_attachment_metadata( $attachment_id );

        if( $attachment['width'] != 512 ) return false;
        if( $attachment['height'] != 512 ) return false;
        
        if( get_post_mime_type( $attachment_id ) != 'image/png' ) return false;

        return true;
    }

    public function save_xml( $color = "#000000" ) {
        $fav_path   = FAVICOR_FAVICONS_PATH;
        $file_name  = "browserconfig.xml";
        $file       = file_get_contents( FAVICOR_PATH . "configs/{$file_name}" ); 

        $output     = str_replace(
            array( '{{ COLOR }}', '{{ PATH }}' ),
            array( $color, $fav_path ),
            $file
        );
        
        file_put_contents( FAVICOR_FAVICONS_PATH . $file_name, $output );
    }

    public function save_manifest( $color = "#000000" ) {
        $fav_path   = FAVICOR_FAVICONS_PATH;
        $file_name  = "site.webmanifest";
        $site_name  = get_bloginfo( 'name' );
        $file       = file_get_contents( FAVICOR_PATH . "configs/{$file_name}" ); 

        $output     = str_replace(
            array( '{{ SITE_NAME }}', '{{ COLOR }}', '{{ PATH }}' ),
            array( $site_name, $color, $fav_path ),
            $file
        );

        file_put_contents( FAVICOR_FAVICONS_PATH . $file_name, $output );
    }

    public function admin_page_content(){
        $image_id   = get_option( 'favicor_image_id' );
        $image      = wp_get_attachment_image_src( $image_id, 'full' );
        // print_r( $image );
?>
        <style>.favicor-upload img{height:64px;}.welcome-panel{padding:20px 25px;}h2{padding:0 !important;margin-bottom:10px !important;}</style>
        
        <div class="wrap">
            <h1><?php _e( 'Favicor', 'favicor' ); ?></h1>

            <div>
                <h2><?php _e( 'Just upload and be done.', 'favicor' ); ?></h2>
                <p class="about-description"><?php _e( 'Upload 512x512.png and plugin does the rest.', 'favicor' ); ?></p>
            </div>

            <form method="post" action="<?php echo site_url( '/wp-admin/themes.php?page=favicor' ); ?>">
                <?php wp_nonce_field( 'favicor_main_image_update', 'favicor_main_image_control' ); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="blogname"><?php _e( 'Pick icon', 'favicor' ); ?></label>
                            </th>
                            <td>
                                <?php if( $image ): ?>
                                <a href="#" class="favicor-upload"><img src="<?php echo $image[0]; ?>" /></a>
                                <?php endif; ?>

                                <input type="hidden" name="favicor_main_image" type="text" value="<?php echo $image_id; ?>">
                                <p>
                                    <a href="#" class="favicor-upload"><?php _e( 'Pick icon', 'favicor' ); ?></a> <!-- | <a href="#" class="misha-rmv">Odebrat obrázek</a> -->
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save changes', 'favicor' ); ?>">
                </p>
            </form>
        </div>
<?php
    }

    // https://stackoverflow.com/questions/46688663/what-is-the-difference-between-favicon-and-image
    public function display_favicons() {
        $favicons = FAVICOR_FAVICONS_URI;

		$visible_favs = array(
			'180' 		=> array( 'rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => "{$favicons}apple-touch-icon.png" ),
			'32' 		=> array( 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => "{$favicons}favicon-32x32.png" ),
			'16' 		=> array( 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => "{$favicons}favicon-32x32.png" ),
			'ico' 		=> array( 'rel' => 'icon', 'type' => 'image/x-icon', 'href' => "{$favicons}favicon.ico" ),
			'manifest'	=> array( 'rel'	=> 'manifest', 'href' => "{$favicons}site.webmanifest" )
		);

		// TODO: SVG
		/* <link rel="mask-icon" href="<?php echo "{$favicons}safari-pinned-tab.svg"; ?>" color="<?php echo $this->mask_color; ?>"> */ 

		$filter_favs = apply_filters( 'favicor/filter-favs', $visible_favs );
		foreach( (array) $filter_favs as $filter_favs_item ) {
			echo $this->build_tag( 'link', $filter_favs_item );
		}

		$metas = array(
			'msapplication-TileColor' 	=> array( 'name' => 'msapplication-TileColor', 'content' => $this->mask_color ),
			'theme-color' 				=> array( 'name' => 'theme-color', 'content' => $this->theme_color ),
		);
		
		foreach( (array) $metas as $meta ) {
			echo $this->build_tag( 'meta', $meta );
		}
    }

	function build_tag( $tag, $params ) {
		$params_string = '';

		foreach( (array) $params as $key => $param ) {
			$params_string .= "{$key}=\"{$param}\"";
		}

		return "<{$tag} {$params_string}>";
	}
}