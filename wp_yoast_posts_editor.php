<?php
/*
Plugin Name: WP Yoast Posts and Terms Editor
Description: Plugin que permite editar los titles, keywords, slugs y meta descriptions de los posts y terms rápidamente.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: wp_yoast_posts_editor
Version: 1.1
Plugin URI: http://www.ovimedia.es/
*/

if ( ! class_exists( 'wp_yoast_posts_editor' ) ) 
{
    class wp_yoast_posts_editor 
    {
        function __construct() 
        {   
            add_action( 'admin_menu', array( $this, 'wype_admin_menu' ));
            add_action( 'admin_print_scripts', array( $this, 'wype_admin_js_css') );
            add_action( 'init', array( $this, 'wype_save_options') );
            add_action( 'wp_ajax_wype_load_posts', array( $this, 'wype_ajax_load_posts') );
            add_action( 'wp_ajax_wype_load_terms', array( $this, 'wype_ajax_load_terms') );
        }
        
        public function wype_admin_menu() 
        {	
            add_submenu_page('wpseo_dashboard', 'Posts and Terms Editor', 'Posts and Terms Editor', 'manage_options',  
                'wp_yoast_posts_editor', array( $this,'wype_form'));
        }  

        public function wype_admin_js_css() 
        {
            wp_register_style( 'custom_wype_admin_css', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/css/style.css', false, '1.0.0' );

            wp_enqueue_style( 'custom_wype_admin_css' );

            wp_enqueue_script( 'wype_script', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/js/scripts.js', array('jquery') );
        }

        public function wype_save_options() 
        {
            $page_viewed = basename($_SERVER['REQUEST_URI']);
            
            if( $page_viewed == "wype_save_options" ) 
            {
                if($_REQUEST["wype_type"] == "post")
                {
                    foreach($_REQUEST as $key => $value)
                    {
                        if($key != "wype_type" && $key != "wype_post_type_id" && $key != "wype_post_terms_id" && $key != "wype_total_results" )
                        {
                            $id = substr($key, strrpos($key, "_") + 1 );
                            $metakey = substr($key, 0, strrpos($key, "_"));

                            if(trim($value) != "")
                            {
                                if($metakey != "post_name")
                                    update_post_meta( $id, $metakey, $value );
                                else
                                    wp_update_post(array("ID" => $id, "post_name" => $value ));
                            }
                        }
                    }
                }
                else if($_REQUEST["wype_type"] == "taxonomy")
                {
                    $meta = get_option( "wpseo_taxonomy_meta");

                    foreach($_REQUEST as $key => $value)
                    {
                        if($key != "wype_type" && $key != "wype_post_type_id" && $key != "wype_post_terms_id" && $key != "wype_total_results"  )
                        {
                            $id = substr($key, strrpos($key, "_") + 1 );
                            $metakey = substr($key, 0, strrpos($key, "_"));

                            if(trim($value) != "")
                            {
                                if($metakey != "term_name")
                                    $meta[$_REQUEST["wype_post_terms_id"]][$id][$metakey] = $value;
                                else
                                    wp_update_term($id, $_REQUEST["wype_post_terms_id"], array("slug" => $value));
                            }
                        }
                    }

                    update_option( "wpseo_taxonomy_meta", $meta);
                }
 
                wp_redirect(get_admin_url()."admin.php?page=wp_yoast_posts_editor
                &wype_type=".$_REQUEST["wype_type"]."&wype_total_results=".$_REQUEST["wype_total_results"]."
                &wype_post_type_id=".$_REQUEST["wype_post_type_id"]."&wype_post_terms_id=".$_REQUEST["wype_post_terms_id"]);
                exit();
            }
        }
        public function wype_ajax_load_posts()
        {
            $this->wype_load_posts_list(true, $_REQUEST["post_type"], $_REQUEST["total"]);
        }

        public function wype_load_posts_list($ajax, $type, $results)
        {
            $args = array(
                'orderby' => 'title',
                'order' => 'asc',
                'numberposts' => $results,
                'post_type' => $type, 
                'post_status' => 'publish'
            ); 
                
            $posts = get_posts($args); 

            foreach ($posts as $post)
            {
                $yoast_wpseo_title = get_post_meta( $post->ID, "_yoast_wpseo_title", true );
                $yoast_metadescription = get_post_meta( $post->ID, "_yoast_wpseo_metadesc", true );
                $yoast_keywords = get_post_meta( $post->ID, "_yoast_wpseo_focuskw", true );

                echo "<div class='row'>";
                echo "<div class='col'><p>".$post->post_title."<br><a target='_blank' href='".get_edit_post_link($post->ID)."'>Editar</a> 
                | <a target='_blank' href='".get_permalink($post->ID)."'>Ver</a> ";
                echo "</p></div>";
                echo "<div class='col slug'><input type='text' id='post_name_".$post->ID."' name='post_name_".$post->ID."' 
                value='".$post->post_name."'/></div>";
                echo "<div class='col ytitles'><input type='text' id='_yoast_wpseo_title_".$post->ID."' name='_yoast_wpseo_title_".$post->ID."' 
                value='".$yoast_wpseo_title."' placeholder='Title'/></div>";
                echo "<div class='col ykeywords'><input type='text' id='_yoast_wpseo_focuskw_".$post->ID."' name='_yoast_wpseo_focuskw_".$post->ID."' 
                value='".$yoast_keywords."' placeholder='Keywords'/></div>";
                echo "<div class='col ydescriptions'><textarea id='_yoast_wpseo_metadesc_".$post->ID."' name='_yoast_wpseo_metadesc_".$post->ID."'
                placeholder='Meta Description'>".$yoast_metadescription."</textarea></div></div>";
            }

            if($ajax) 
            exit(); 
        }

        public function wype_ajax_load_terms()
        {
            $this->wype_load_terms_list(true, $_REQUEST["taxonomy_id"], $_REQUEST["total"]);
        }

        public function wype_load_terms_list($ajax, $type, $results)
        {
            if($results == -1)
                $results = "";

            $terms = get_terms( $type, array(
                'hide_empty' => false,
                'number' => $results,
            ) );           

            $meta = get_option( "wpseo_taxonomy_meta");

            foreach ($terms as $term)
            {
                echo "<div class='row'>";
                echo "<div class='col'><p>".$term->name."<br><a target='_blank' href='".get_edit_term_link($term->term_id, $type)."'>Editar</a> 
                | <a target='_blank' href='".get_term_link($term->term_id)."'>Ver</a> ";
                echo "</p></div>";
                echo "<div class='col slug'><input type='text' id='term_name_".$term->term_id."' name='term_name_".$term->term_id."' 
                value='".$term->slug."'/></div>";
                echo "<div class='col ytitles'><input type='text' id='wpseo_title_".$term->term_id."' name='wpseo_title_".$term->term_id."' 
                value='".$meta[$type][$term->term_id]["wpseo_title"]."' placeholder='Title'/></div>";
                echo "<div class='col ykeywords'><input type='text' id='wpseo_focuskw_".$term->term_id."' name='wpseo_focuskw_".$term->term_id."' 
                value='".$meta[$type][$term->term_id]["wpseo_focuskw"]."' placeholder='Keywords'/></div>";
                echo "<div class='col ydescriptions'><textarea id='wpseo_desc_".$term->term_id."' name='wpseo_desc_".$term->term_id."'
                placeholder='Meta Description'>".$meta[$type][$term->term_id]["wpseo_desc"]."</textarea></div></div>";
            }

            if($ajax) 
            exit(); 
        }

        public function wype_form()
        {
            global $wpdb;
            ?>

            <div class="wrap_wype_content">

                    <form method="post" action="<?php echo get_admin_url(); ?>wype_save_options">

                        <input type="hidden" id="wype_type" name="wype_type" value="<?php echo $_REQUEST["wype_type"]; ?>" />

                        <p class="submit">

                            <input type="submit" value="Guardar" class="button button-primary" />

                            <select  id="wype_post_type_id" name="wype_post_type_id">

                            <option> --- Post Types --- </opton>

                                <?php

                                    $results = $wpdb->get_results( 'SELECT DISTINCT post_type FROM '.$wpdb->prefix.'posts 
                                    WHERE post_status like "publish" and post_type <> "code" 
                                    and post_type <> "nav_menu_item" and post_type <> "wpcf7_contact_form" order by 1 asc'  );

                                    foreach ( $results as $row )
                                    {                                    
                                        echo '<option value="'.$row->post_type.'"';
                                        
                                        if($_REQUEST["wype_post_type_id"] == $row->post_type && $_REQUEST["wype_type"] == "post") echo ' selected="selected" ';

                                        echo '>'.ucfirst ($row->post_type).'</option>';
                                    } 

                                ?>

                            </select>

                            <input type="button" id="wype_ajax_load_posts" value="Cargar posts" class="button button-secondary" />

                            <select  id="wype_post_terms_id" name="wype_post_terms_id">

                            <option> --- Taxonomies --- </opton>

                            <?php
                    
                                $taxonomies = get_taxonomies();

                                foreach ( $taxonomies as $tax)
                                {    
                                    echo '<option value="'.$tax.'"';                         
                                    if($_REQUEST["wype_post_terms_id"] == $tax && $_REQUEST["wype_type"] == "taxonomy") echo ' selected="selected" ';       
                                    echo '>'.ucfirst ($tax).'</option>';
                                } 

                            ?>

                            </select>

                            <input type="button" id="wype_ajax_load_taxonomies" value="Cargar terms" class="button button-secondary" />     

                            <select  id="wype_total_results" name="wype_total_results">

                            <?php $results = array("20 Resultados" => 20, "50 Resultados" => 50, "100 Resultados" => 100, "Todos los resultados" => -1);

                            foreach($results as $result => $value)
                            {
                                echo '<option value="'.$value.'"';                         
                                if($_REQUEST["wype_total_results"] == $value) echo ' selected="selected" ';       
                                echo '>'.ucfirst ($result).'</option>'; 
                            }

                            ?>
                            </select> 

                            <input type="text" id="search" placeholder="Buscar" />

                            <input type="checkbox" checked id="show_slugs" /> Slugs        
                            <input type="checkbox" checked id="show_ytitles" /> Titles 
                            <input type="checkbox" checked id="show_ykeywords" /> Keywords 
                            <input type="checkbox" checked id="show_ydescriptions" /> Meta Descriptions
                        </p>

                        <div id="wype_posts_content" class="wype_post_list">
                        
                        <?php

                            $results = 20;

                            if($_REQUEST["wype_total_results"] != "") $results = $_REQUEST["wype_total_results"];

                            if($_REQUEST['wype_post_type_id'] != "" && $_REQUEST["wype_type"] == "post")
                                $this->wype_load_posts_list(false,$_REQUEST['wype_post_type_id'], $results);

                            if($_REQUEST["wype_post_terms_id"] != "" && $_REQUEST["wype_type"] == "taxonomy")
                                $this->wype_load_terms_list(false,$_REQUEST["wype_post_terms_id"], $results);
                        ?>
                        
                        </div>

                    </form>
                    
            </div>

            <?php 
        }
    }
}

$GLOBALS['wp_yoast_posts_editor'] = new wp_yoast_posts_editor();   
    
?>