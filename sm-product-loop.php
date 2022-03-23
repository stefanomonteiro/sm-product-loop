<?php

/**
 * 
 * Plugin Name: sm Product Loop
 * Plugin URI: https://stefanomonteiro.com/wp-plugins
 * Author: Stefano Monteiro
 * Author URI: https://stefanomonteiro.com
 * Version: 1.0.0
 * Description: Loop through products and create a grid  
 * Text Domain: sm_
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Basic security, prevents file from being loaded directly.
defined('ABSPATH') or die('Cheatin&#8217; uh?');


if (!function_exists('add_sm_product_loop_shortcode')) {
    function add_sm_product_loop_shortcode($atts)
    {

        $a = shortcode_atts(array(
            'extra_class'           => '',
            'post__in'              => '',
            'orderby'               => 'menu_order',
            'order'                 =>  'ASC',
            'posts_per_page'        => -1,
            'product_cat'           => '',
            'only_sale'             => false,
            'hide_filter'           => false
        ), $atts);


        /* 
        * ! Pass shortcode parameters to variables to be used in WP_Query
        */
        $post__in = [];
        if ($a['post__in']) {
            foreach (explode(',', $a['post__in']) as $post_id ) {
                array_push($post__in, $post_id);
            }
        }

        // If category id is passed on, assigne them to $terms_id. 
        // Else 
        // Find if current loop (Object) is a term 
        // and get its children or get all children
        $terms_id = [];
        $category_filter = [];
        if ($a['product_cat']) {
            foreach (explode(',', $a['product_cat']) as $term_id) {
                array_push($terms_id, $term_id);
                array_push($category_filter, $term_id);
            }
        } else{
            // Get The queried object if is Archive Page (a WP_Term or a WP_Post Object)
            $term = get_queried_object();
            
            // To be sure that is a WP_Term Object (Category page) - get the children of the category
            if (is_a($term, 'WP_Term')) {
                $terms_id = $term->term_id;
                $category_filter = get_term_children($term->term_id, 'product_cat');
            } else {
                // If $term does not return the WP_Term Object (Main Product Archive Page) get all categories to pass on WP_Query below
                $categories = get_categories(array(
                    'taxonomy'  => 'product_cat',
                    'parent' => 0
                ));

                foreach ($categories as $category) {
                    array_push($terms_id, $category->term_id);
                }
                $category_filter = $terms_id;
            }
        }

        // ! Create Filter
        $product_filter = '';
        $product_filter_cats = '';
        if ($category_filter && !$post__in && !$a['hide_filter']) {
            $a['extra_class'] = 'sm_has-filter';

            foreach ($category_filter as $cat_id) {

                $category_name = get_the_category_by_ID($cat_id);

                $product_filter_cats = $product_filter_cats . '
                    <li data-filter=".' . str_replace(' ', '', $category_name) . '"> ' . $category_name . ' </li>
                ';
            }

            $product_filter = '<ul>
                                    <li data-filter="*">Todos</li>
                                    ' . $product_filter_cats . '
                                </ul>';
        }


        // ! Create Product Grid
        // Setup custom query
        $args = array(
            'post__in'              => $post__in,
            'post_type'             => 'product',
            'status'                => 'publish',
            'orderby'               => $a['menu_order'],
            'order'                 =>  $a['ASC'],
            'posts_per_page'        => $a['posts_per_page'],
            'tax_query'             => array(array(
                'taxonomy' => 'product_cat', // The taxonomy name
                'field'    => 'term_id', // Type of field ('term_id', 'slug', 'name' or 'term_taxonomy_id')
                'terms'    => $terms_id, // can be an integer, a string or an array
            )),
        );
        $loop = new WP_Query($args);

        // var_dump($loop->posts);
        $products_items = '';
        foreach ($loop->posts as $post) {
            $product = wc_get_product($post->ID);
            // More here: https://woocommerce.github.io/code-reference/classes/WC-Product.html
            // var_dump($post->ID);
            // var_dump($product->get_title());
            // var_dump($product->get_slug());
            // var_dump($product->get_image());
            // var_dump($product->get_image_id());
            // var_dump($product->get_gallery_image_ids());
            // var_dump($product->get_stock_status());
            // var_dump($product->get_stock_quantity());
            // var_dump($product->get_type());
            // var_dump($product->get_children());
            // var_dump($product->get_price());
            // var_dump($product->get_regular_price());
            // var_dump($product->get_sale_price());
            // var_dump($product->add_to_cart_description());

            // var_dump($product->get_price_html());
            // var_dump(wp_get_post_terms($post->ID, 'product_cat')[0]->name);


            // Check if product is On Sale
            $is_on_sale = $product->get_sale_price() ? 'on_sale' : '';
            $sale_price = $product->get_sale_price();
            $regular_price = $product->get_regular_price();

            // Get Variable Products Price
            if ($product->get_type() == 'variable') {

                foreach ($product->get_children() as $variable_id) {
                    $variable = wc_get_product($variable_id);

                    if ($variable->get_sale_price()) {
                        $is_on_sale = $variable->get_sale_price() ? 'on_sale' : '';

                        $sale_price = $variable->get_sale_price();
                        $regular_price = $variable->get_regular_price();
                    }
                }
            }

            $sale_percentage = 0;
            if ($is_on_sale) {
                $sale_percentage = round((intval($sale_price) / intval($regular_price) - 1) * -100);
            }

            $product_cats = wp_get_post_terms($post->ID, 'product_cat');
            $product_cats_string = ''; // Get all categories of a product and add as class to the grid_item (so it can be filtered)
            foreach ($product_cats as $cat) {
                $product_cats_string = $product_cats_string . str_replace(' ', '', $cat->name) . ' ';
            }

            // Get Second Image
            $second_image =  $product->get_gallery_image_ids()[0] ? wp_get_attachment_image($product->get_gallery_image_ids()[0], 'medium-large', false, ['class' => 'attachment-medium-large size-medium-large sm_image-two']) : wp_get_attachment_image($product->get_image_id(), 'medium-large', false, ['class' => 'attachment-medium-large size-medium-large sm_image-two']);

            if ($a['only_sale'] && !$is_on_sale) {
                // var_dump($product->get_title());
            } else{
                $products_items = $products_items . '
                                 <div class="sm_product-loop--grid_item ' . $product_cats_string . ' ">
                                    <article class="sm_product-loop--article ' . $product->get_type() . ' ' . $product->get_stock_status() . ' ' . $is_on_sale . '">
                                        <div class="sm_product-loop--images">
                                            <span class="sm_product-loop--badge-stock">Esgotado</span>
                                            <span class="sm_product-loop--badge-sale">' . $sale_percentage . '% off</span>
                                            <a href="/product/' . $product->get_slug() . '" class="sm_product-loop--link">
                                                ' . $second_image . '
                                                ' . wp_get_attachment_image($product->get_image_id(), 'medium-large', false, ['class' => 'attachment-medium-large size-medium-large sm_image-one']) . '
                                            </a>
                                            <div class="sm_product-loop--buttons">
                                                <div class="sm_product-loop--add">
                                                    <a href="' . $product->add_to_cart_url() . '" class="add_to_cart_button ajax_add_to_cart" data-product_id="' . $post->ID . '">' . $product->add_to_cart_text() . '</a>
                                                    <a href="' . $product->add_to_cart_url() . '" class="select_options_button" data-product_id="' . $post->ID . '">' . $product->add_to_cart_text() . '</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sm_product-loop--bottom">
                                            <h4 class="sm_product-loop--cat">' . $product_cats[1]->name . '</h4>
                                            <h2 class="sm_product-loop--title"><a href="/product/' . $product->get_slug() . '">' . $product->get_title() . '</a></h2>
                                            <div class="sm_product-loop--price">
                                                ' . $product->get_price_html() . '
                                            </div>
                                        </div>
                                    </article>
                                </div>';
            }

        }


        $html = '<div class="sm_product-loop '. $a['extra_class'] .'">
                        <div class="sm_product-loop--filter">
                            ' . $product_filter . '
                        </div>
                        <div class="sm_product-loop--grid">
                            ' . $products_items . '
                        </div>
                    </div>';

        // Enqueue
        if (!wp_style_is('sm_product_loop-css', 'enqueued')) {
            wp_enqueue_style('sm_product_loop-css');
        }
        if (!wp_script_is('sm_product_loop-js', 'enqueued')) {
            wp_enqueue_script('sm_product_loop-js');
        }
        wp_reset_query(); // Remember to reset 
        return $html;
    }
}
add_shortcode('sm_product_loop', 'add_sm_product_loop_shortcode');


// Register Scripts

wp_register_style('sm_product_loop-css', plugin_dir_url(__FILE__) . 'css/sm_product_loop.css', [], time());
wp_register_script('isotope-js', plugin_dir_url(__FILE__) . 'js/isotope.pkgd.min.js', [], time(), true);
wp_register_script('sm_product_loop-js', plugin_dir_url(__FILE__) . 'js/sm_product_loop.js', ['isotope-js'], time(), true);

// Enqueue Scripts Elementor Editor
if (!function_exists('sm_product_loop_enqueue_styles_elementor_editor')) {
    function sm_product_loop_enqueue_styles_elementor_editor()
    {

        if (!wp_style_is('sm_product_loop-css', 'enqueued')) {
            wp_enqueue_style('sm_product_loop-css');
        }
    }
}

if (!function_exists('sm_product_loop_enqueue_scripts_elementor_editor')) {
    function sm_product_loop_enqueue_scripts_elementor_editor()
    {

        if (!wp_script_is('sm_product_loop-js', 'enqueued')) {
            wp_enqueue_script('sm_product_loop-js');
        }
    }
}

// Add Action elementor/preview/enqueue_styles 
add_action('elementor/preview/enqueue_styles', 'sm_product_loop_enqueue_styles_elementor_editor');
add_action('elementor/preview/enqueue_scripts', 'sm_product_loop_enqueue_scripts_elementor_editor');




/* 
* Ajax Add To Cart
*/
// 
// add_action('wp_ajax_woocommerce_ajax_add_to_cart', 'woocommerce_ajax_add_to_cart');
// add_action('wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'woocommerce_ajax_add_to_cart');
// function woocommerce_ajax_add_to_cart()
// {

//     // More info here: https://quadmenu.com/add-to-cart-with-woocommerce-and-ajax-step-by-step/

//     $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
//     $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
//     // $variation_id = absint($_POST['variation_id']);
//     $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
//     $product_status = get_post_status($product_id);

//     if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity) && 'publish' === $product_status) {

//         do_action('woocommerce_ajax_added_to_cart', $product_id);

//         if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
//             wc_add_to_cart_message(array($product_id => $quantity), true);
//         }

//         WC_AJAX::get_refreshed_fragments();
//     } else {

//         $data = array(
//             'error' => true,
//             'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
//         );

//         echo wp_send_json($data);
//     }

//     wp_die();
// }
