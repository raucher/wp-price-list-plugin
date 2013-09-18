<?php

/**
 * Class PriceListPlugin
 *
 * @property WP_Post $_priceListObject Retrieved price list
 * @property array $_priceListItems Retrieved price list items
 * @property int $_itemsPerPage Count of item to display on frontend
 * @property array $_priceListParams Initial parameters for query, display style, etc.
 * @property string $_htmlContainerId Id to point JS on specific price list block
 * @property string $_themeClass CSS class to style price list according to theme
 */
class PriceListPlugin
{
    protected $_currencySign;
    protected $_priceListObject;
    protected $_priceListItems;
    protected $_itemsPerPage;
    protected $_priceListParams;
    protected $_htmlContainerId;
    protected $_themeClass;

    /**
     * Initializes the plugin
     *
     * Hooks necessary actions, registers new post type
     * handles post saving process and other useful stuff
     */
    public function init()
    {
        $this->registerPostType();
        $this->isFirstTimeInstall();

        // Initialize JS array in the document head.
        // Because several WP frameworks (such as bones) put enqueued scripts in to the footer
        add_action('wp_head', function(){
            echo '<script type="text/javascript">plpInitialDataContainer = new Array;</script>';
        });

        add_action('wp_ajax_plp-ajax-pagination', array($this, 'ajaxPaginationHandler'));
        add_action('wp_ajax_nopriv_plp-ajax-pagination', array($this, 'ajaxPaginationHandler'));

        add_action('admin_menu', array($this, 'registerHelpPage'));
        add_action('add_meta_boxes', array($this, 'registerPriceListMetaboxes'));
        add_action('admin_enqueue_scripts', array($this, 'adminScriptInit'));
        add_action('save_post', array($this, 'savePriceListMetaboxes'));
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

        // Generate currency sign and 20 dummy price list items
        $metaboxSamples = array('currency' => '$');
        for($i=0; $i<20; $i++){
            $metaboxSamples['data'][] = array(
                'desc' => 'Roasted nachos can be made smashed by covering with red wine.',
                'price' => '150.99',
            );
        }

        add_post_meta($samplePostId, '_plp_price_list_item', $metaboxSamples, true);
    }

    /**
     * Registers help sub-page under the main price list menu
     */
    public function registerHelpPage()
    {
        add_submenu_page('edit.php?post_type=price_list',
            __('Price list plugin help page', 'plp-domain'),
            __('What is this?', 'plp-domain'),
            'edit_dashboard', 'plp_help_page', array($this, 'renderHelpPage'));
    }

    /**
     * Renders help page layout
     */
    public function renderHelpPage()
    {
        $this->renderLayout('help-page');
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
     * Checks for first time installation
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
                  'menu_icon' => PLP_URL.'images/price_list.png', /* the icon for the custom post type menu */
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
     * Registers additional form fields for list items on the price list page
     */
    public function registerPriceListMetaboxes()
    {
        add_meta_box('price-list-items', 'Price List Items', array($this, 'renderPriceListMetaboxes'), 'price_list', 'normal', 'high');
    }

    protected function renderLayout($layout, array $data = array())
    {
        extract($data);
        require_once "layouts/{$layout}.php";
    }

    /**
     * Renders the layout for additional form fields
     *
     * @param $post
     * @param $box
     */
    public function renderPriceListMetaboxes($post, $box)
    {
        $metaData = get_post_meta($post->ID, '_plp_price_list_item', true);
        $this->renderLayout('metaboxes', array(
           'currency' => isset($metaData['currency']) ? $metaData['currency'] : '$',
           'metaBoxData' => isset($metaData['data']) ? $metaData['data'] : null,
        ));
    }

    /**
     * Hooks on save_post and checks whether the price list items is saving
     *
     * @param int $post_id
     */
    public function savePriceListMetaboxes($post_id)
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
            update_post_meta($post_id, '_plp_price_list_item', $data);
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
     * Makes a request for the given price list and fills $_priceListObject on success
     *
     * @return null|$this For the function chaining
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

        if(is_null($this->_priceListObject) || !is_object($this->_priceListObject))
            wp_die('Can not retrieve given price list');

        return $this;
    }

    /**
     * Retrieves the price list metaboxes data and fills $_priceListItems on success
     *
     * @return $this For the function chaining
     */
    protected function setPriceListItems()
    {
        $data = get_post_meta($this->_priceListObject->ID, '_plp_price_list_item', true);
        $this->_priceListItems = $data['data'];
        $this->_currencySign = $data['currency'];

        return $this;
    }

    /**
     * Fills properties with user-given initial params or sets default values
     *
     * @param array $params Array of the initial params
     * @return $this For the function chaining
     */
    protected function setProcessedParams($params)
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

    /**
     * Setups object environment
     *
     * @param array $envParams User given parameters
     */
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
            echo $this->renderPriceListItems();
        print '</dl></div>';
        $this->makeAjaxPagination();
    }

    /**
     * Composes JSON object for the JS script and enqueue the script
     */
    protected function makeAjaxPagination()
    {
        // If items count to show per page is greater or equal to total amount of items
        // then pagination not needed
        if(is_null($this->_itemsPerPage) || ($this->_itemsPerPage >= count($this->_priceListItems)))
            return;

        // Compose JSON
        $jsData = json_encode(array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'action' => 'plp-ajax-pagination',
            'nonce' => wp_create_nonce('plp-ajax-pagination-nonce'),
            'htmlContainerId' => $this->_htmlContainerId,
            'priceListObjectId' => $this->_priceListObject->ID,
            'totalItemCount' => count($this->_priceListItems),
            'itemsPerPage' => $this->_itemsPerPage,
        ));

        // Push it to global initial data array for further use in JS
        echo '<script type="text/javascript">'
            ."plpInitialDataContainer.push({$jsData})"
            .';</script>';

        // Enqueue main plugin JS
        wp_enqueue_script('plp_ajax_pagination', PLP_URL.'js/plp-ajax-pagination.js', array('jquery'), false, false);
    }

    /**
     * Handler for the AJAX queries
     */
    public function ajaxPaginationHandler()
    {
        if(!check_ajax_referer('plp-ajax-pagination-nonce', 'plp-ajax-nonce', false))
            wp_die('Busted!');

        $this->setupEnvironment(array(
            'list_id' => (int)$_POST['plp-price-list-id'],
            'per_page' => (int)$_POST['plp-items-per-page'],
        ));

        echo json_encode(array(
            'priceListHtml' => $this->renderPriceListItems((int)$_POST['plp-pagination-offset']),
        ));
        die();
    }

    /**
     * Returns HTML representation for the price list items
     *
     * @param int $offset Offset from which to start
     * @return string HTML
     */
    protected function renderPriceListItems($offset = 0)
    {
        $itemsToShow = array_slice($this->_priceListItems, $offset, $this->_itemsPerPage);
        $htmlOutput = '';

        foreach($itemsToShow as $item)
        {
            $htmlOutput .= "<dt>{$item['desc']}</dt><dd>{$this->_currencySign} {$item['price']}</dd>";
        }

        return $htmlOutput;
    }

    /**
     * Makes a shortcode to render price list on the front-end
     *
     * @param array $atts User-given shortcode attributes
     * @return string HTML to display on front-end
     */
    public function makeShortcode($atts)
    {
        ob_start();
        $this->renderPriceList($atts);
        return ob_get_clean();
    }
}