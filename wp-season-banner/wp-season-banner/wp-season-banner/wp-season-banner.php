<?php
/*
 * Plugin Name: WP Season Banner
 * Plugin URI: http://autumnlane.co/
 * Description: This plugin provide different banner for each season automatically. Shortcode: [Season_Banner].
 * Version: 1.0.0
 * Author: Autumn lane
 * Author URI: http://autumnlane.co/
 * License: GNU GENERAL PUBLIC LICENSE Version 3
 */

/**
 * Register Season Banners Post Type
 */
add_action( 'init', 'grs_season_banner_post_type', 0 );
function grs_season_banner_post_type() {
    $labels = array(
        'name'                => _x( 'Season Banners', 'Post Type General Name', 'autumnlane' ),
        'singular_name'       => _x( 'Season Banners', 'Post Type Singular Name', 'autumnlane' ),
        'menu_name'           => __( 'Season Banners', 'autumnlane' ),
        'parent_item_colon'   => __( 'Parent Season Banner', 'autumnlane' ),
        'all_items'           => __( 'All Season Banner', 'autumnlane' ),
        'view_item'           => __( 'View SeasonBanner', 'autumnlane' ),
        'add_new_item'        => __( 'Add New Season Banner', 'autumnlane' ),
        'add_new'             => __( 'Add New', 'autumnlane' ),
        'edit_item'           => __( 'Edit Season Banner', 'autumnlane' ),
        'update_item'         => __( 'Update Season Banner', 'autumnlane' ),
        'search_items'        => __( 'Search Season Banners', 'autumnlane' ),
        'not_found'           => __( 'No Banners Found', 'autumnlane' ),
        'not_found_in_trash'  => __( 'No Banners found in Trash', 'autumnlane' ),
    );
    
    $args = array(
        'label'               => __( 'Season Banners', 'autumnlane' ),
        'description'         => __( 'Banner images with selected season.', 'autumnlane' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'thumbnail', 'custom-fields', ),
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-id',
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 6,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'rewrite'            => array( 'slug' => 'season_banners' ),
    );
    register_post_type( 'grs_season_banners', $args );
}

/**
 * Add Meta Box for seasons to select with each banner.
 */

add_action( 'add_meta_boxes', 'grs_season_banner_add_meta_box' );
function grs_season_banner_add_meta_box() {
    $screens = array( 'grs_season_banners' );
    foreach ( $screens as $screen ) {
        add_meta_box(
            'grs_seasons',
            __( 'Select Season', 'autumnlane' ),
            'grs_season_banner_meta_box_cb',
            $screen
        );
    }
}

/**
 * Outputs meta box for the seasons to select.
 */

function grs_season_banner_meta_box_cb( $post ) {
    wp_nonce_field( 'season_banner_meta_box', 'season_banner_meta_box_nonce' );

    $season_banner = get_post_meta( $post->ID, 'grs_season', true );
    
    ?>
            <select id="grs_season" name="grs_season" style="width: 100%;">
              <option>select</option>
              <option value="spring"<?php if ($season_banner =='spring') echo 'selected="selected"' ?>>Spring</option>
              <option value="summer"<?php if ($season_banner =='summer') echo 'selected="selected"' ?>>Summer</option>
              <option value="autumn"<?php if ($season_banner =='autumn') echo 'selected="selected"' ?>>Autumn</option>
              <option value="winter"<?php if ($season_banner =='winter') echo 'selected="selected"' ?>>Winter</option>
            </select>
<?php
}

/**
 * Saving the Season value when banner post is publish/updates
 */

add_action( 'save_post', 'season_banner_save_meta_box_data' );
function season_banner_save_meta_box_data( $post_id ) {
    //Validating Nonce
    if ( ! isset( $_POST['season_banner_meta_box_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['season_banner_meta_box_nonce'], 'season_banner_meta_box' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // If this isn't a 'grs_season_banners' post, don't update it.    
    $post_type = get_post_type($post_id);
    if ( "grs_season_banners" != $post_type ) return;

    // Sanitize user input.
    $season_val = sanitize_text_field( $_POST['grs_season'] );

    // Update the meta field in the database.
    update_post_meta( $post_id, 'grs_season', $season_val );
}

/**
 * Season Banner Shortcode to display banners on front-end.
 * @param $atts Array Shorcode Attributes.
 * @return $banner_imgs String Url of the image when only need url from attributes.
 */
add_shortcode( 'Season_Banner', 'season_banner_shortcode' );
function season_banner_shortcode($atts){
    $atts = shortcode_atts(
            array(
                'banner_img' => 'url'
            ), $atts, 'season_Banner' 
        );
    $bannerimage = grs_get_season_by_today();
    $season_posts = get_posts(array(
        'post_type' => 'cpt_season_banner',
        'meta_query' => array(
            array(
                'key' => '_season_banner',
                'value' => $bannerimage, 
                'compare' => 'LIKE'
            )
        )
    ));
   $banner_url = $atts['banner_img'];
   if($banner_url == "imgurl"){
      foreach($season_posts as $season_post)
        {
           $banner_imgs = wp_get_attachment_url(get_post_thumbnail_id($season_post->ID));
           return $banner_imgs;
        }
   }else{
    ?>
        <div class="images">
            <?php
             foreach($season_posts as $season_post)
             {
                $banner_title = $season_post->post_title;
                $banner_imgs = wp_get_attachment_url(get_post_thumbnail_id($season_post->ID));        
            ?>
                <img src="<?php echo $banner_imgs; ?>" alt="<?php echo $banner_title; ?>" style="max-width: 100%;">
            <?php
             }
            ?>
        </div>
    <?php
   }
}

/**
* Heart of plugin. This function returns the season according to current day.
* @return String Season Name.
*/
function grs_get_season_by_today() { 
    $dateTime =  new DateTime();
    $dayOfTheYear = $dateTime->format('z'); 
    if($dayOfTheYear < 80 || $dayOfTheYear > 356){ return 'winter'; } 
    if($dayOfTheYear < 173){ return 'spring'; } 
    if($dayOfTheYear < 266){ return 'summer'; } 
    return 'autumn'; 
}