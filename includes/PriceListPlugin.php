<?php

/**
 * Class PriceListPlugin
 */
class PriceListPlugin
{
    protected $_priceListItems;
    protected $_itemsPerPage;
    protected $_priceListParams;
    protected $_priceListObject;
    protected $_htmlContainerId;
    protected $_themeClass;

    /**
     * Initializes the plugin
     *
     * Hooks necessary actions, registers new post type
     * handles post saving proccess and other useful stuff
     */
    public function init()
    {
        $this->registerPostType();
        $this->isFirstTimeInstall();
        add_action('wp_head', function(){
            echo '<script type="text/javascript">plpInitialDataContainer = new Array;</script>';
        });

        add_action('wp_ajax_plp-ajax-pagination', array($this, 'ajaxPaginationHandler'));
        add_action('wp_ajax_nopriv_plp-ajax-pagination', array($this, 'ajaxPaginationHandler'));

        add_action('admin_menu', array($this, 'registerHelpPage'));
        add_action('add_meta_boxes', array($this, 'registerPostMetaboxes'));
        add_action('admin_enqueue_scripts', array($this, 'adminScriptInit'));
        add_action('save_post', array($this, 'savePriceListItems'));
        add_shortcode('plp-price-list', array($this, 'makeShortcode'));
    }

    /**
     * Generates sample price list for informative needs
     */
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

    /**
     * Registers help sub-page under the main price list menu
     */
    public function registerHelpPage()
    {
        add_submenu_page('edit.php?post_type=price_list',
            __('Price list plugin help page', 'plp-domain'),
            __('What is this?', 'plp-domain'),
            'edit_dashboard', 'plp_help_page', array($this, 'helpPageLayout'));
    }

    /**
     * Layout of the help page
     */
    public function helpPageLayout()
    {
        echo '<h1>'.__('What is this?', 'plp-domain').'</h1>';
    }

    /**
     * Sanitizes the given array. Price list items in our case
     *
     * @param array $input
     * @return array
     */
    protected function sanitizeArrayData(array $input)
    {
        array_walk_recursive($input, function(&$data){
            $data = sanitize_text_field($data);
        });
        return $input;
    }

    /**
     * Checks for first time instalation
     */
    protected function isFirstTimeInstall()
    {
        if(!get_option('plp_is_installed'))
        {
            flush_rewrite_rules(); // Fix a 404 error bug on newly created post type pages
            $this->makeSamplePriceList();
            add_option('plp_is_installed', true, '', 'no');
        }
    }

    /**
     * Registers the price_list post type
     */
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

    /**
     * Registers additional form fields on price list page for list items
     */
    public function registerPostMetaboxes()
    {
        add_meta_box('price-list-items', 'Price List Items', array($this, 'renderPriceListMetaboxes'), 'price_list', 'normal', 'high');
    }

    /**
     * Renders the layout for additional form fields
     *
     * @param $post
     * @param $box
     */
    public function renderPriceListMetaboxes($post, $box)
    {   // Get price list items
        $priceListItems = get_post_meta($post->ID, '_price_list_item', true);
        // enerate a nonce
        wp_nonce_field('plp_save_list_items', 'plp_nonce_field');
        // Output table with list item content
        print '<div id="postcustomstuff">
                <table id="newmeta" width="100%">
                  <thead><tr>
                    <th class="left">'.__('Item Description', 'plp-domain').'</th>
                    <th>'.__('Item Price', 'plp-domain').'</th>
                  </tr></thead>';
        if(is_array($priceListItems)){
            foreach ($priceListItems as $i => $item) {
                print '<tr class="price-list-item-wrapper">';
                printf('<td class="left" width="80%%"><textarea name="price-list-item[%d][desc]">%s</textarea></td> ', $i, $item['desc']);
                printf('<td width="20%%"><input type="text" name="price-list-item[%d][price]" value="%s"></td> ', $i, $item['price']);
                print '</tr>';
            }
        }
        // If price list doesn't have any item yet, generate an empty fields for them
        else{
            print '<tr class="price-list-item-wrapper">';
            printf('<td class="left" width="80%%"><textarea name="price-list-item[%d][desc]"></textarea></td> ', 0);
            printf('<td width="20%%"><input type="text" name="price-list-item[%d][price]" value=""></td> ', 0);
            print '</tr>';
        }
        print '</table></div>'; // Close main table
        // Echo out Add/Delete buttons
        print '<div style="margin-top:10px">
                <button id="add-price-list-item">'. __('Add Item', 'plp-domain').'</button>
                <button id="remove-price-list-item">'.__('Remove Item', 'plp-domain').'</button>
              </div>';
    }

    /**
     * Hooks on save_post and checks whether the price list items is saving
     *
     * @param $post_id
     */
    public function savePriceListItems($post_id)
    {
        if(isset($_POST['price-list-item']))
        {   // Check the nonce
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

    /**
     * Enqueue JS only for the price list dashboard page
     */
    public function adminScriptInit()
    {
        if(get_current_screen()->post_type === 'price_list')
            wp_enqueue_script('plp_meta_boxes', PLP_URL.'js/plp-meta-boxes.js', array('jquery'));
    }

    /**
     * Makes a request for the given price list
     *
     * @param $postInfo
     * @return null|object|WP_Post
     */
    protected function setPriceListObject()
    {
        if(empty($this->_priceListParams))
            return;

        // Return price list by name
        if(!is_array($this->_priceListParams)){
            $this->_priceListObject = get_page_by_title((string)$this->_priceListParams, 'OBJECT', 'price_list');
        }
        else{
            // Return price list by additional arguments
            $query = new WP_Query($this->_priceListParams);
            $this->_priceListObject = $query->post;
        }

        if(is_null($this->_priceListObject) || !is_a($this->_priceListObject, 'WP_Post'))
            wp_die('Can not retrieve given price list');

        return $this;
    }

    protected function setPriceListItems()
    {
        $this->_priceListItems = get_post_meta($this->_priceListObject->ID, '_price_list_item', true);

        return $this;
    }

    protected function setProcessedParams($params, $rewrite = false)
    {
        $this->_htmlContainerId = 'plp-price-list-'.mt_rand(0, 256);
        $this->_itemsPerPage = isset($params['per_page']) ? (int)$params['per_page'] : null;
        $this->_themeClass = isset($params['theme']) ?(string)$params['theme'] : 'green-circle';

        if(isset($params['list_title'])){
            $this->_priceListParams = $params['list_title'];
        }
        else{
            $defaults = array(
                'p' => isset($params['list_id']) ? $params['list_id'] : null,
                'name' => isset($params['list_slug']) ? $params['list_slug'] : null,
                'post_type' => 'price_list',
                'posts_per_page' => 1, // get only 1 object per request
            );

            $this->_priceListParams = wp_parse_args($params, $defaults);
        }

        return $this;
    }

    protected function setupEnvironment($envParams)
    {
        $this->setProcessedParams($envParams)
             ->setPriceListObject()
             ->setPriceListItems();
    }

    /**
     * Renders the price list or dies trying
     *
     * @param $postInfo
     */
    protected function renderPriceList($postInfo)
    {
        wp_enqueue_style('plp-style', PLP_URL.'css/plp-style.css');
        $this->setupEnvironment($postInfo);

        print '<div id="'.$this->_htmlContainerId.'" class="plp-price-list-block">';
            print "<h3>{$this->_priceListObject->post_title}</h3>";
            printf('<dl class="horizontal %s">', $this->_themeClass);
            echo $this->renderPriceListItemsFrontend();
        print '</dl></div>';
        $this->makeAjaxPagination();
    }

    protected function makeAjaxPagination()
    {
        if(is_null($this->_itemsPerPage) || ($this->_itemsPerPage >= count($this->_priceListItems)))
            return;

        $jsData = json_encode(array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'action' => 'plp-ajax-pagination',
            'nonce' => wp_create_nonce('plp-ajax-pagination-nonce'),
            'htmlContainerId' => $this->_htmlContainerId,
            'priceListObjectId' => $this->_priceListObject->ID,
            'totalItemCount' => count($this->_priceListItems),
            'itemsPerPage' => $this->_itemsPerPage,
        ));

        echo '<script type="text/javascript">'
            ."plpInitialDataContainer.push({$jsData})"
            .';</script>';

        wp_enqueue_script('plp_ajax_pagination', PLP_URL.'js/plp-ajax-pagination.js', array('jquery'), false, false);
    }

    public function ajaxPaginationHandler()
    {
        if(!check_ajax_referer('plp-ajax-pagination-nonce', 'plp-ajax-nonce', false))
            wp_die('Busted!');

        $this->setupEnvironment(array(
            'list_id' => (int)$_POST['plp-price-list-id'],
            'per_page' => (int)$_POST['plp-items-per-page'],
        ));

        echo json_encode(array(
            'priceListHtml' => $this->renderPriceListItemsFrontend((int)$_POST['plp-pagination-offset']),
        ));
        die();
    }

    protected function renderPriceListItemsFrontend($offset = 0)
    {
        $itemsToShow = array_slice($this->_priceListItems, $offset, $this->_itemsPerPage);
        $htmlOutput = '';

        foreach($itemsToShow as $item)
        {
            $htmlOutput .= "<dt>{$item['desc']}</dt><dd>{$item['price']}</dd>";
        }

        return $htmlOutput;
    }

    /**
     * Makes a shortcode to render price list on front-end
     *
     * @param $atts
     * @return string
     */
    public function makeShortcode($atts)
    {
        ob_start();
        $this->renderPriceList($atts);
        return ob_get_clean();
    }
}