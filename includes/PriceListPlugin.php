<?php

class PriceListPlugin 
{
    public function init()
    {
        $this->registerPostType();
        $this->isFirstTimeInstall();
        add_action('admin_menu', array($this, 'registerHelpPage'));
        add_action('add_meta_boxes', array($this, 'registerPostMetaboxes'));
        add_action('admin_enqueue_scripts', array($this, 'adminScriptInit'));
        add_action('save_post', array($this, 'savePriceListItems'));
        add_shortcode('plp-price-list', array($this, 'makeShortcode'));
    }

    protected function makeSamplePriceList()
    {
        $samplePostId = wp_insert_post(array(
            'post_type' => 'price_list',
            'post_name' => 'plp-sample-price-list',
            'post_title' => 'Sample price list',
            'post_status' => 'publish',
        ), true);

        if(is_a($samplePostId, 'WP_Error'))
            wp_die('PLP can not create the sample price list');

        add_post_meta($samplePostId, '_price_list_item', array(
            array(
                'desc' => 'Roasted nachos can be made smashed by covering with red wine.',
                'price' => '150.99',
            ),
            array(
                'desc' => 'Per guest prepare one quarter cup of tabasco.',
                'price' => '05.79',
            ),
            array(
                'desc' => 'Flavor one jar of tofu in one cup of whiskey.',
                'price' => '25.00',
            ),
        ), true);
    }

    public function registerHelpPage()
    {
        add_submenu_page('edit.php?post_type=price_list',
            __('Price list plugin help page', 'plp-domain'),
            __('What is this?', 'plp-domain'),
            'edit_dashboard', 'plp_help_page', array($this, 'helpPageLayout'));
    }

    public function helpPageLayout()
    {
        echo '<h1>'.__('What is this?', 'plp-domain').'</h1>';
    }

    protected function sanitizeArrayData(array $input)
    {
        array_walk_recursive($input, function(&$data){
            $data = sanitize_text_field($data);
        });
        return $input;
    }

    protected function isFirstTimeInstall()
    {
        if(!get_option('plp_is_installed'))
        {
            flush_rewrite_rules();
            $this->makeSamplePriceList();
            add_option('plp_is_installed', true, '', 'no');
        }
    }

    protected function registerPostType()
    {
        register_post_type( 'price_list',
            // let's now add all the options for this post type
            array('labels' => array(
                'name' => __('Price Lists', 'plp-domain'), /* This is the Title of the Group */
                'singular_name' => __('Price Lists', 'plp-domain'), /* This is the individual type */
                //'all_items' => __('All Custom Posts', 'plp-domain'), /* the all items menu item */
                'add_new' => __('Add New', 'plp-domain'), /* The add new menu item */
                'add_new_item' => __('Add New Price List', 'plp-domain'), /* Add New Display Title */
                'edit' => __( 'Edit', 'plp-domain' ), /* Edit Dialog */
                'edit_item' => __('Edit Price Lists', 'plp-domain'), /* Edit Display Title */
                'new_item' => __('New Price List', 'plp-domain'), /* New Display Title */
                'view_item' => __('View Price List', 'plp-domain'), /* View Display Title */
                'search_items' => __('Search Price List', 'plp-domain'), /* Search Custom Type Title */
                'not_found' =>  __('Nothing found in the Database.', 'plp-domain'), /* This displays if there are no entries yet */
                'not_found_in_trash' => __('Nothing found in Trash', 'plp-domain'), /* This displays if there is nothing in the trash */
                'parent_item_colon' => ''
            ), /* end of arrays */
                  'description' => __( 'Price list to display on frontend', 'plp-domain' ), /* Custom Type Description */
                  'public' => true,
                  'publicly_queryable' => true,
                  'exclude_from_search' => false,
                  'show_ui' => true,
                  'query_var' => true,
                  'menu_position' => 7, /* this is what order you want it to appear in on the left hand side menu */
                  'menu_icon' => PLP_URL.'images/money_euro.png', //get_stylesheet_directory_uri() . '/library/images/custom-post-icon.png', /* the icon for the custom post type menu */
                  'rewrite'	=> array( 'slug' => 'price_list', 'with_front' => false ), /* you can specify its url slug */
                  'has_archive' => 'price_list', /* you can rename the slug here */
                  'capability_type' => 'post',
                  'hierarchical' => false,
                /* the next one is important, it tells what's enabled in the post editor */
                  'supports' => array( 'title', 'author', 'slug'),
            ) /* end of options */
        ); /* end of register post type */
    }

    public function registerPostMetaboxes()
    {
        add_meta_box('price-list-items', 'Price List Items', array($this, 'plpPriceListItems'), 'price_list', 'normal', 'high');
    }

    public function plpPriceListItems($post, $box)
    {
        $priceListItems = get_post_meta($post->ID, '_price_list_item', true);
        wp_nonce_field('plp_save_list_items', 'plp_nonce_field');
        print '<div id="postcustomstuff"><table id="newmeta" width="100%"><thead><tr><th class="left">Item Description</th><th>Item Price</th></tr></thead>';
        if(is_array($priceListItems)){
            foreach ($priceListItems as $i => $item) {
                print '<tr class="price-list-item-wrapper">';
                printf('<td class="left" width="80%%"><textarea name="price-list-item[%d][desc]">%s</textarea></td> ', $i, $item['desc']);
                printf('<td width="20%%"><input type="text" name="price-list-item[%d][price]" value="%s"></td> ', $i, $item['price']);
                print '</tr>';
            }
        }else{
            print '<tr class="price-list-item-wrapper">';
            printf('<td class="left" width="80%%"><textarea name="price-list-item[%d][desc]"></textarea></td> ', 0);
            printf('<td width="20%%"><input type="text" name="price-list-item[%d][price]" value=""></td> ', 0);
            print '</tr>';
        }
        print '</table></div>'; // Close main table
        print '<div style="margin-top:10px">
            <button id="add-price-list-item">Add Item</button>
            <button id="remove-price-list-item">Remove Item</button>
           </div>';
    }

    public function savePriceListItems($post_id)
    {
        if(isset($_POST['price-list-item']))
        {
            check_admin_referer('plp_save_list_items', 'plp_nonce_field');
            // If autosave is running - do nothing
            if((defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE))
                return;
            // Sanitize input data
            $data = $this->sanitizeArrayData($_POST['price-list-item']);
            // And save it
            update_post_meta($post_id, '_price_list_item', $data);
        }
    }

    public function adminScriptInit()
    {
        if(get_current_screen()->post_type === 'price_list')
            wp_enqueue_script('plp_meta_boxes', PLP_URL.'js/plp-meta-boxes.js', array('jquery'));
    }

    protected function getPriceList($postInfo)
    {
        if(empty($postInfo))
            return;

        if(!is_array($postInfo))
            return get_page_by_title($postInfo, 'OBJECT', 'price_list');

        $args = array(
            'p' => $postInfo['post_id'],
            'name' => $postInfo['post_slug'],
            'post_type' => 'price_list',
            'posts_per_page' => 1,
        );
        $query = new WP_Query($args);
        return $query->post;
    }

    protected function renderPriceList($postInfo)
    {   // TODO: Replace wp_die with something less harmful
        if(is_null($priceList = $this->getPriceList($postInfo)) || !is_a($priceList, 'WP_Post'))
            wp_die('Can not retrieve given price list');

        $priceListItems = get_post_meta($priceList->ID, '_price_list_item', true);
        print '<div class="plp-price-list-block">';
            print "<h3>{$priceList->post_title}</h3>";
            print '<dl class="dl-horizontal special green">';
            foreach($priceListItems as $item)
            {
                print "<dt>{$item['desc']}</dt><dd>{$item['price']}</dd>";
            }
            print '</dl>';
        print '</div>';
    }

    public function makeShortcode($atts)
    {
        $postInfo = isset($atts['list_title']) ? $atts['list_title'] : $atts;
        ob_start();
        $this->renderPriceList($postInfo);

        return ob_get_clean();
    }
}