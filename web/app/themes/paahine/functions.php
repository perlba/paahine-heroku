<?php

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}

load_theme_textdomain( 'dazzling', get_stylesheet_directory() . '/languages' );

function storefront_site_branding() {
?>
        <div class="site-branding">
            <h1 class="site-title">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
<?php
    $svg = substr(get_template_directory(), 0, -10) . 'paahine/assets/img/logopictext.svg';
    if (file_exists($svg)) {
        readfile($svg);
    } else {
        bloginfo( 'name' );
    }
?>
                </a></h1>
            <p class="site-description"><?php bloginfo( 'description' ); ?></p>
        </div>
<?php
}

if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {

    /**
     * Output the WooCommerce Breadcrumb
     */
    function woocommerce_breadcrumb( $args = array() ) {
        $args = wp_parse_args( $args, apply_filters( 'woocommerce_breadcrumb_defaults', array(
            'delimiter'   => ' ',
            'wrap_before' => '<nav class="woocommerce-breadcrumb" ' . ( is_single() ? 'itemprop="breadcrumb"' : '' ) . '>',
            'wrap_after'  => '</nav>',
            'before'      => '',
            'after'       => '',
            'home'        => _x( 'Home', 'breadcrumb', 'woocommerce' )
        ) ) );

        $breadcrumbs = new WC_Breadcrumb();

        if ( $args['home'] ) {
            $breadcrumbs->add_crumb( $args['home'], apply_filters( 'woocommerce_breadcrumb_home_url', home_url() ) );
        }

        $args['breadcrumb'] = $breadcrumbs->generate();

        wc_get_template( 'global/breadcrumb.php', $args );
    }
}


?>
