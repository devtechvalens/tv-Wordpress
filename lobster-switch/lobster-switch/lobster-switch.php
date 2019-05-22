<?php
/*
 * Plugin Name: Lobster - Switch
 * Description: This plugin adds a new column to the post admin columns that allows you to easily mark a post as lobster.
 * Version:     1.0.0
 * Author:      
 * Author URI:  
 * Requires at least: 4.0
 * Tested up to: 4.9.7
 * Text Domain: lobster-switch
 * Domain Path: /languages/
 * License:     GPL v2 or later
 */

/**
 * Lobster - Switch
 *
 * LICENSE
 * This file is part of Sticky Posts Switch.
 *
 * Sticky Posts is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package    Lobster - Switch
 * @author     
 * @copyright  Copyright 2018 
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @link       
 * @since      1.0
 */

if(!defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('wp_lobster_switch') )
{
    /*
     * Main class of the plugin
     */
    class wp_lobster_switch
    {
        // <editor-fold desc="Datafields">

        /*
         * Datafields
         */
        private $settings;

        private $ignore_lobster = false;

        // </editor-fold>

        // <editor-fold desc="Properties">

        /*
         * Set all linked posts from multilingualpress
         */
        private function set_linked_multilingualpress_posts($post_id, $handle)
        {
            if(!function_exists('mlp_get_linked_elements')) {
                return false;
            }

            $linked_posts = mlp_get_linked_elements($post_id);

            foreach ($linked_posts as $linked_blog => $linked_post)
            {
                switch_to_blog( $linked_blog );

                $this->set_the_post_lobster($linked_post, $handle);

                restore_current_blog();
            }

            return true;
        }

        /*
         * Set all linked posts from WPML
         */
        private function set_linked_wpml_posts($post_id, $handle)
        {
            global $sitepress;

            $post_type = get_post_type($post_id);

            $trid = $sitepress->get_element_trid($post_id, 'post_'.$post_type);
            $translations = $sitepress->get_element_translations($trid ,'post_'.$post_type);

            remove_filter('pre_option_lobster', array($sitepress, 'option_lobster'));

            foreach($translations as $translation) {
                $this->set_the_post_lobster($translation->element_id, $handle);
            }
        }

        /*
         * Set the current post as lobster
         */
        private function set_the_post_lobster($post_id, $handle)
        {
            switch($handle)
            {
                case 1:
                    lobster_post($post_id);
                    break;
                case 0:
                    unlobster_post($post_id);
                    break;
            }
        }

        // </editor-fold>

        // <editor-fold desc="Constructor">

        /*
         *  Constructor
         */
        public function __construct()
        {
            // Include the settings class
            require_once dirname(__FILE__).'/settings/class-settings.php';

            add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));

            // Init settings page
            $this->settings = new wp_lobster_switch_settings(__FILE__);

            if(is_admin())
            {
                $post_types = $this->settings->get_post_types();

                // Post types ar available
                if(count($post_types) > 0)
                {
                    // Enqueue admin scripts
                    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

                    foreach($post_types as $post_type)
                    {
                        add_filter('manage_'.$post_type.'_posts_columns', array($this, 'manage_post_columns'), 1);
                        add_action('manage_'.$post_type.'_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
                        add_filter( 'manage_edit-'.$post_type.'_sortable_columns', array($this, 'grs_manage_post_sortable_columns') );
                    }

                    // Handle the ajax request
                    add_action('wp_ajax_process_lobster_post', array($this, 'process_ajax_lobster_post'));

                    // Add bulk and quick edit elements to CPT
                    //add_action('quick_edit_custom_box', array($this, 'quick_edit_lobster_post'), 10, 2 );
                    //add_action('bulk_edit_custom_box', array($this, 'bulk_edit_lobster_post'), 10, 2 );

                    add_action('admin_footer', array($this, 'add_lobster_post_checkbox'));
                }
            }

            // Change the retrieved posts from the custom post type, to put the lobster posts on the top
            if(!is_admin()) {
                //add_filter('pre_get_posts', array($this, 'pre_get_posts'), 1);
                //add_filter('the_posts', array($this, 'the_posts'), 1, 2);
            }

            add_filter('plugin_action_links_'.plugin_basename(__FILE__),  array($this, 'add_plugin_action_links'));
        }

        // </editor-fold>

        // <editor-fold desc="Hook Methods">

        /*
         * Initialize the textdomain
         */
        public function load_plugin_textdomain()
        {
            load_plugin_textdomain('lobster-switch', false, plugin_basename( dirname(__FILE__) ) . '/languages' );
        }

        /*
         * HOOK
         * Enqueue the post admin columns scripts
         */
        public function enqueue_scripts($hook_suffix)
        {
            // Enqueue only on edit.php
			if($hook_suffix == 'edit.php' && in_array(get_query_var('post_type'), $this->settings->get_post_types()))
            {
                $plugin_data = get_plugin_data( __FILE__ );

                wp_enqueue_style('lobster-posts-style', plugin_dir_url(__FILE__).'assets/css/admin-lobster-posts.css', array(), $plugin_data['Version']);
                wp_enqueue_script('jquery-ajax-queue',plugin_dir_url(__FILE__).'assets/jquery/jquery.ajaxQueue.min.js', array('jquery'), '0.1.2', true);
				wp_enqueue_script('lobster-posts-admin', plugin_dir_url(__FILE__).'assets/js/admin-lobster-posts.js', array('jquery'), $plugin_data['Version'], true);
                wp_enqueue_script('lobster-posts-admin-quick-edit', plugin_dir_url(__FILE__).'assets/js/admin-quick-edit.js', array('jquery'), $plugin_data['Version'], true);

                $l10n = array(
                    'ajaxUrl'   => admin_url('admin-ajax.php'),
                    'action'    => 'process_lobster_post'
                );

                wp_localize_script('lobster-posts-admin', 'lobsterPostObject', $l10n);
			}
		}

        /**
         * Manage custom columns for posts.
         * @param  array $columns
         * @return array
         */
        public function manage_post_columns($columns)
        {
            if(get_query_var('post_status') === 'trash') {
                return $columns;
            }

            // Add lobster post column
            $columns['lobster_post'] = '<span class="dashicons dashicons-lobster"></span>';

            $sort_order = $this->settings->get_sort_order();

            $i = 0;
            foreach($sort_order as $column)
            {
                if(!in_array($column, array_keys($columns))) {
                    unset($sort_order[$i]);
                }

                $i++;
            }

            // Reset sort array
            $sort_order = array_values($sort_order);

            // Reorder columns
            $columns = array_merge(array_flip($sort_order), $columns);

			return $columns;
        }

        /**
         * Make Custom Column Sortable
         * @param array columns
         * @return array
         */
        public function grs_manage_post_sortable_columns($columns)
        {
            $columns['lobster_post'] = 'lobster';
            $columns['price'] = 'price';
            //To make a column 'un-sortable' remove it from the array
            //unset($columns['date']);
            return $columns;
        }

        /**
         * Output custom columns for posts.
         * @param string $column
         */
        public function manage_posts_custom_column($column, $post_id)
        {
            switch ($column)
            {
                case 'lobster_post':
                    $hyperlink_class = 'lobster-posts';
                    $hyperlink_style = '';
                    $icon_class = 'dashicons-lobster-grey';

                    $icon_color = $this->settings->get_icon_color();

                    if(!empty($icon_color)) {
                        $hyperlink_style = 'style="color: '.$icon_color.';"';
                    }

                    if(is_lobster($post_id))
                    {
                        $hyperlink_class .= ' active';
                        $icon_class = 'dashicons-lobster';
                    }

                    printf('<a id="%s" title="%s" class="%s" %s href="javascript:void(0);" data-id="%d" data-nonce="%s"><span class="dashicons %s"></span></a>',
                        'lobster-post-'.$post_id,
                        __('Lobster Post'),
                        $hyperlink_class,
                        $hyperlink_style,
                        $post_id,
                        wp_create_nonce('lobster-post-nonce'),
                        $icon_class
                    );

                    break;
            }
        }

        /*
         * Add the lobster post checkbox to the custom post type quick edit
         */
        public function quick_edit_lobster_post($column_name, $post_type)
        {
            if($post_type !== 'post' && in_array($post_type, $this->settings->get_post_types()))
            {
                $post_type_object = get_post_type_object($post_type);

                if(current_user_can($post_type_object->cap->publish_posts) && current_user_can($post_type_object->cap->edit_others_posts))
                {
                    switch ( $column_name ) {
                        case 'lobster_post':
                            ?>
                            <fieldset class="inline-edit-col-right">
                                <div class="inline-edit-col">
                                    <div class="inline-edit-group wp-clearfix">
                                        <label class="alignleft">
                                            <input type="checkbox" name="lobster" value="1" />
                                            <span class="checkbox-title"><?php _e( 'Make this post lobster' ); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </fieldset>
                            <?php
                            break;
                    }
                }
            }
        }

        /*
         * Add the lobster post select to the custom post type bulk edit
         */
        public function bulk_edit_lobster_post($column_name, $post_type)
        {
            if($post_type !== 'post' && in_array($post_type, $this->settings->get_post_types()))
            {
                $post_type_object = get_post_type_object($post_type);

                if(current_user_can($post_type_object->cap->publish_posts) && current_user_can($post_type_object->cap->edit_others_posts))
                {
                    switch ( $column_name )
                    {
                        case 'lobster_post':
                            ?>
                            <fieldset class="inline-edit-col-right">
                                <div class="inline-edit-col">
                                    <div class="inline-edit-group wp-clearfix">
                                        <label class="alignleft">
                                            <span class="title"><?php _e( 'Lobster' ); ?></span>
                                            <select name="lobster">
                                                <option value="-1"><?php _e( '&mdash; No Change &mdash;' ); ?></option>
                                                <option value="1"><?php _e( 'Lobster' ); ?></option>
                                                <option value="0"><?php _e( 'Not a lobster' ); ?></option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                            </fieldset>
                            <?php
                            break;
                    }
                }
            }
        }

        /*
         * Implements the lobster checkbox on custom post type
         */
        public function add_lobster_post_checkbox()
        {
            global $pagenow;

            if('post.php' == $pagenow && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit')
            {
                $post_id = absint($_GET['post']);

                if(get_post_type($post_id) !== 'post' && in_array(get_post_type($post_id), $this->settings->get_post_types()) && current_user_can( 'edit_others_posts' ))
                {
                    $checked = is_lobster( $post_id ) ? ' checked="checked"' : '';

                    $checkbox = '<input id="lobster" name="lobster" type="checkbox" value="1"'.$checked.' />';
                    $label = '<label for="lobster" class="selectit">'.__( 'Lobster this post' ).'</label>';

                    $content = sprintf('<span id="lobster-span">%s %s <br /></span>', $checkbox, $label);

                    /*
                     * Add the lobster post checkbox with javascript
                     */
                    ?>
                    <script type="text/javascript">
                        (function($) {
                            if($('#post-visibility-select').length && $('label[for="visibility-radio-public"]').length) {
                                $('#post-visibility-select > label[for="visibility-radio-public"]+br').after('<?php echo $content; ?>');
                            }
                        })(jQuery);
                    </script>

                    <?php
                }
            }
        }

        /*
         * AJAX Call
         * Handle the ajax request and set/unset the post lobster
         */
        public function process_ajax_lobster_post()
        {
            // Nonce security check
            if(!check_ajax_referer('lobster-post-nonce')) {
                wp_send_json_error(__('An error has occurred. Please reload the page and try again.'));
            }

            $handle = sanitize_text_field($_POST['handle']);
            $post_id = absint($_POST['post_id']);
            $post_obj = get_post($post_id);
            $post_type_object = get_post_type_object($post_obj->post_type);

            // Check capabilities
            if (!current_user_can( $post_type_object->cap->edit_others_posts ) || !current_user_can( $post_type_object->cap->publish_posts ) ) {
                wp_send_json_error(__('Sorry, you are not allowed to edit this item.'));
            }

            // Mark the post as currently being edited by the current user
            wp_set_post_lock( $post_id );

            // Set all translations from the post
            if($this->settings->get_handle_multilingual_posts())
            {
                // Set all linked posts from multilingualpress
                if($this->settings->get_multilingualpress_is_active()) {
                    $this->set_linked_multilingualpress_posts($post_id, $handle);
                }

                // Set all linked posts from WPML
                if($this->settings->get_wpml_is_active()) {
                    $this->set_linked_wpml_posts($post_id, $handle);
                }
            }
            else
            {
                $this->set_the_post_lobster($post_id, $handle);
            }

            // Get all post states
            ob_start();
            _post_states(get_post($post_id));
            $post_states = ob_get_clean();

            // Ajax output response
            wp_send_json_success(array(
                'lobster'    => is_lobster($post_id),
                'states'    => $post_states
            ));
        }

        /*
         * Applied to the list of links to display on the plugins page
         */
        public function add_plugin_action_links($links)
        {
            $new_links[] = '<a href="'.admin_url('admin.php?page='.$this->settings->menu_slug) . '">'.__('Settings').'</a>';

            return array_merge($links, $new_links);
        }

        // </editor-fold>
    }

    new wp_lobster_switch;
}

/*Reuseable Plugin function to use site wide */
function lobster_post($pid)
{
    if(metadata_exists('post',$pid,'blue_lobster'))
    {
        update_post_meta($pid,'blue_lobster',1);
    }else
    {
        add_post_meta($pid,'blue_lobster',1);
    }    
    return true;
}

function unlobster_post($pid)
{
    if(metadata_exists('post',$pid,'blue_lobster'))
    {
        update_post_meta($pid,'blue_lobster',0);
    }else
    {
        add_post_meta($pid,'blue_lobster',0);
    } 
    return true;
}

/*
* Function to check if post is set as lobster or not. 
* @param $pid INT Post id to check. 
* @return BOOL 
*/
function is_lobster($pid)
{
    if(metadata_exists('post',$pid,'blue_lobster'))
    {
        $lbvalue = get_post_meta($pid,'blue_lobster');
        if($lbvalue[0] == 1)
        {
            return true;
        }else
        {
            return false;
        }
    } 
    return false;
}