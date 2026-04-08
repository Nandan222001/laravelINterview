# CMS Platforms Interview Questions

## WordPress

### Hooks & Filters

**Q1:** What is the difference between WordPress actions and filters?
- Actions allow you to add or change WordPress functionality
- Filters allow you to modify data before it's displayed or saved
- Actions don't return values, filters must return values

**Q2:** How do you create a custom action hook in WordPress?
```php
// Creating a custom hook
do_action('my_custom_hook', $arg1, $arg2);

// Hooking into it
add_action('my_custom_hook', 'my_callback_function', 10, 2);
function my_callback_function($arg1, $arg2) {
    // Your code here
}
```

**Q3:** Explain the difference between `add_action()` and `add_filter()`.
- `add_action()`: Executes a function at a specific point in WordPress execution
- `add_filter()`: Modifies data before saving or displaying
- Both accept priority (default 10) and argument count parameters

**Q4:** What are the most commonly used WordPress hooks?
- `init` - Fires after WordPress has finished loading
- `wp_enqueue_scripts` - Enqueue scripts and styles
- `the_content` - Filter post content
- `save_post` - Fires when a post is saved
- `wp_head` - Outputs content in the head section
- `wp_footer` - Outputs content before closing body tag

**Q5:** How do you remove a hook that was added by a plugin or theme?
```php
remove_action('hook_name', 'function_name', priority);
remove_filter('hook_name', 'function_name', priority);
```

**Q6:** What is the purpose of priority in WordPress hooks?
- Lower numbers = higher priority (executed first)
- Default priority is 10
- Allows control over execution order when multiple functions hook to the same action/filter

**Q7:** How do you create a custom filter hook?
```php
// Creating the filter
$value = apply_filters('my_custom_filter', $value, $arg1, $arg2);

// Using the filter
add_filter('my_custom_filter', 'my_filter_callback', 10, 3);
function my_filter_callback($value, $arg1, $arg2) {
    // Modify and return value
    return $modified_value;
}
```

### Plugins

**Q8:** What is the basic structure of a WordPress plugin?
```php
/*
Plugin Name: My Plugin Name
Plugin URI: https://example.com/plugin
Description: Brief description
Version: 1.0.0
Author: Author Name
Author URI: https://example.com
License: GPL2
Text Domain: my-plugin
*/
```

**Q9:** How do you create a WordPress plugin activation hook?
```php
register_activation_hook(__FILE__, 'my_plugin_activate');
function my_plugin_activate() {
    // Create database tables, set default options, etc.
    flush_rewrite_rules();
}
```

**Q10:** What is the WordPress Plugin API?
- Set of hooks (actions and filters) that plugins can use
- Allows plugins to interact with WordPress core
- Provides standardized way to extend WordPress functionality

**Q11:** How do you add custom settings to a plugin?
```php
add_action('admin_menu', 'my_plugin_menu');
function my_plugin_menu() {
    add_options_page(
        'My Plugin Settings',
        'My Plugin',
        'manage_options',
        'my-plugin',
        'my_plugin_settings_page'
    );
}

add_action('admin_init', 'my_plugin_settings');
function my_plugin_settings() {
    register_setting('my-plugin-settings', 'my_plugin_option');
}
```

**Q12:** What is the WordPress Transients API?
- Temporary cached data storage
- Automatically expires after set time
- Stores data in the database with expiration
```php
set_transient('my_transient', $value, 12 * HOUR_IN_SECONDS);
$value = get_transient('my_transient');
delete_transient('my_transient');
```

**Q13:** How do you internationalize a WordPress plugin?
```php
// Load text domain
add_action('plugins_loaded', 'my_plugin_load_textdomain');
function my_plugin_load_textdomain() {
    load_plugin_textdomain('my-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Use translation functions
__('Text to translate', 'my-plugin');
_e('Text to echo', 'my-plugin');
_n('Singular', 'Plural', $count, 'my-plugin');
```

**Q14:** What are WordPress shortcodes and how do you create them?
```php
add_shortcode('my_shortcode', 'my_shortcode_handler');
function my_shortcode_handler($atts, $content = null) {
    $atts = shortcode_atts(array(
        'attribute' => 'default',
    ), $atts);
    
    return '<div>' . esc_html($atts['attribute']) . '</div>';
}
```

**Q15:** How do you create a custom widget in WordPress?
```php
class My_Custom_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('my_widget', 'My Widget', array(
            'description' => 'Widget description'
        ));
    }
    
    public function widget($args, $instance) {
        // Output widget content
    }
    
    public function form($instance) {
        // Widget form in admin
    }
    
    public function update($new_instance, $old_instance) {
        // Save widget settings
    }
}

add_action('widgets_init', function() {
    register_widget('My_Custom_Widget');
});
```

### Themes

**Q16:** What are the required files for a WordPress theme?
- `style.css` - Main stylesheet with theme header
- `index.php` - Main template file
- `functions.php` - Theme functions and features
- `screenshot.png` - Theme thumbnail (optional but recommended)

**Q17:** What is the WordPress template hierarchy?
- WordPress uses a specific order to determine which template file to use
- More specific templates override general ones
- Example: `single-{post-type}.php` > `single.php` > `singular.php` > `index.php`

**Q18:** How do you enqueue styles and scripts in a WordPress theme?
```php
add_action('wp_enqueue_scripts', 'my_theme_enqueue_scripts');
function my_theme_enqueue_scripts() {
    wp_enqueue_style('main-style', get_stylesheet_uri());
    wp_enqueue_script('main-js', get_template_directory_uri() . '/js/main.js', array('jquery'), '1.0', true);
}
```

**Q19:** What is the purpose of `functions.php` in a WordPress theme?
- Add theme support features
- Register menus, sidebars, widgets
- Enqueue scripts and styles
- Create custom functions
- Add hooks and filters

**Q20:** How do you create a child theme in WordPress?
```php
// style.css
/*
Theme Name: My Child Theme
Template: parent-theme-folder
*/

// functions.php
add_action('wp_enqueue_scripts', 'my_child_theme_enqueue_styles');
function my_child_theme_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}
```

**Q21:** What are WordPress theme support features?
```php
add_theme_support('title-tag');
add_theme_support('post-thumbnails');
add_theme_support('custom-logo');
add_theme_support('html5', array('search-form', 'comment-form', 'gallery'));
add_theme_support('customize-selective-refresh-widgets');
add_theme_support('custom-background');
add_theme_support('custom-header');
```

**Q22:** How do you register navigation menus in WordPress?
```php
register_nav_menus(array(
    'primary' => __('Primary Menu', 'theme-textdomain'),
    'footer' => __('Footer Menu', 'theme-textdomain'),
));

// Display menu
wp_nav_menu(array(
    'theme_location' => 'primary',
    'container' => 'nav',
    'menu_class' => 'primary-menu',
));
```

**Q23:** What is the WordPress Customizer API?
- Allows users to customize theme settings in real-time
- Live preview of changes before saving
- Sections, panels, settings, and controls
```php
add_action('customize_register', 'my_customize_register');
function my_customize_register($wp_customize) {
    $wp_customize->add_setting('header_color', array(
        'default' => '#000000',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'header_color', array(
        'label' => 'Header Color',
        'section' => 'colors',
    )));
}
```

### Custom Post Types

**Q24:** How do you register a custom post type in WordPress?
```php
add_action('init', 'create_custom_post_type');
function create_custom_post_type() {
    register_post_type('product',
        array(
            'labels' => array(
                'name' => __('Products'),
                'singular_name' => __('Product')
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'products'),
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
        )
    );
}
```

**Q25:** How do you create custom taxonomies in WordPress?
```php
add_action('init', 'create_custom_taxonomy');
function create_custom_taxonomy() {
    register_taxonomy(
        'product_category',
        'product',
        array(
            'label' => __('Product Categories'),
            'hierarchical' => true,
            'rewrite' => array('slug' => 'product-category'),
        )
    );
}
```

**Q26:** What is the difference between hierarchical and non-hierarchical taxonomies?
- Hierarchical: Like categories, can have parent-child relationships
- Non-hierarchical: Like tags, flat structure
- Hierarchical uses checkboxes in admin, non-hierarchical uses tag-like input

**Q27:** How do you query custom post types?
```php
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 10,
    'tax_query' => array(
        array(
            'taxonomy' => 'product_category',
            'field' => 'slug',
            'terms' => 'electronics',
        )
    ),
    'meta_query' => array(
        array(
            'key' => 'price',
            'value' => 100,
            'compare' => '<=',
            'type' => 'NUMERIC',
        )
    ),
);

$query = new WP_Query($args);
```

**Q28:** How do you add custom meta boxes to post types?
```php
add_action('add_meta_boxes', 'add_custom_meta_box');
function add_custom_meta_box() {
    add_meta_box(
        'product_details',
        'Product Details',
        'product_meta_box_callback',
        'product',
        'normal',
        'high'
    );
}

function product_meta_box_callback($post) {
    wp_nonce_field('save_product_meta', 'product_meta_nonce');
    $value = get_post_meta($post->ID, '_product_price', true);
    echo '<input type="text" name="product_price" value="' . esc_attr($value) . '">';
}

add_action('save_post', 'save_product_meta');
function save_product_meta($post_id) {
    if (!isset($_POST['product_meta_nonce']) || !wp_verify_nonce($_POST['product_meta_nonce'], 'save_product_meta')) {
        return;
    }
    
    if (isset($_POST['product_price'])) {
        update_post_meta($post_id, '_product_price', sanitize_text_field($_POST['product_price']));
    }
}
```

**Q29:** What are custom fields and how do you use them?
- Store additional metadata for posts
- Use `add_post_meta()`, `get_post_meta()`, `update_post_meta()`, `delete_post_meta()`
- Keys starting with underscore are hidden from custom fields UI

**Q30:** How do you add custom columns to admin post lists?
```php
add_filter('manage_product_posts_columns', 'add_product_columns');
function add_product_columns($columns) {
    $columns['price'] = 'Price';
    return $columns;
}

add_action('manage_product_posts_custom_column', 'fill_product_columns', 10, 2);
function fill_product_columns($column, $post_id) {
    if ($column == 'price') {
        echo get_post_meta($post_id, '_product_price', true);
    }
}

add_filter('manage_edit-product_sortable_columns', 'make_product_columns_sortable');
function make_product_columns_sortable($columns) {
    $columns['price'] = 'price';
    return $columns;
}
```

### Deployment & Configuration

**Q31:** What are the essential WordPress configuration settings in `wp-config.php`?
- Database credentials (DB_NAME, DB_USER, DB_PASSWORD, DB_HOST)
- Authentication keys and salts
- Database table prefix
- Debug mode (WP_DEBUG)
- Content directory path (WP_CONTENT_DIR)
- Memory limits

**Q32:** How do you enable debug mode in WordPress?
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

**Q33:** What is the WordPress REST API?
- Provides JSON endpoints for WordPress data
- Allows interaction with sites from external applications
- CRUD operations on posts, users, taxonomies, etc.
- Extensible for custom endpoints

**Q34:** How do you create a custom REST API endpoint?
```php
add_action('rest_api_init', function() {
    register_rest_route('myplugin/v1', '/products/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_product_data',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
    ));
});

function get_product_data($request) {
    $product_id = $request['id'];
    $product = get_post($product_id);
    
    if (!$product) {
        return new WP_Error('not_found', 'Product not found', array('status' => 404));
    }
    
    return rest_ensure_response(array(
        'id' => $product->ID,
        'title' => $product->post_title,
        'content' => $product->post_content,
    ));
}
```

**Q35:** What are WordPress cron jobs and how do you create them?
```php
// Schedule event
if (!wp_next_scheduled('my_custom_cron')) {
    wp_schedule_event(time(), 'hourly', 'my_custom_cron');
}

// Hook into scheduled event
add_action('my_custom_cron', 'my_custom_cron_function');
function my_custom_cron_function() {
    // Execute scheduled task
}

// Unschedule on deactivation
register_deactivation_hook(__FILE__, 'my_plugin_deactivate');
function my_plugin_deactivate() {
    wp_clear_scheduled_hook('my_custom_cron');
}
```

**Q36:** How do you handle WordPress migrations?
- Export/import database with SQL dump or plugins
- Update site URLs in database (wp_options, post_content, post_meta)
- Copy wp-content folder (themes, plugins, uploads)
- Update wp-config.php with new credentials
- Regenerate permalinks

**Q37:** What are best practices for WordPress security?
- Keep WordPress, themes, and plugins updated
- Use strong passwords and two-factor authentication
- Limit login attempts
- Use security plugins
- Disable file editing in admin
- Use HTTPS/SSL
- Regular backups
- Sanitize and validate all inputs
- Escape all outputs

### Optimization

**Q38:** How do you optimize WordPress performance?
- Use caching plugins (WP Rocket, W3 Total Cache)
- Optimize images and lazy load
- Minify CSS, JavaScript, and HTML
- Use a CDN
- Optimize database queries
- Enable GZIP compression
- Limit post revisions
- Use object caching (Redis, Memcached)

**Q39:** What is WordPress object caching?
- Stores database query results in memory
- Reduces database load
- Uses `wp_cache_set()`, `wp_cache_get()`, `wp_cache_delete()`
- Requires persistent caching backend (Redis, Memcached)

**Q40:** How do you optimize WordPress database queries?
```php
// Use WP_Query efficiently
$query = new WP_Query(array(
    'post_type' => 'product',
    'posts_per_page' => 10,
    'fields' => 'ids', // Only get IDs
    'no_found_rows' => true, // Skip pagination count
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
));

// Use transients for expensive queries
$products = get_transient('featured_products');
if (false === $products) {
    $products = // expensive query
    set_transient('featured_products', $products, HOUR_IN_SECONDS);
}
```

**Q41:** How do you optimize WordPress images?
- Use image optimization plugins (Smush, ShortPixel)
- Implement lazy loading
- Use responsive images (srcset)
- Choose appropriate image formats (WebP)
- Resize images to actual display size
- Use CDN for image delivery

**Q42:** What is WordPress page caching?
- Stores static HTML versions of pages
- Reduces server processing for repeat visitors
- Types: browser caching, server-side caching, CDN caching
- Implemented via plugins or server configuration

**Q43:** How do you implement lazy loading in WordPress?
```php
// WordPress 5.5+ has native lazy loading
// For images:
<img src="image.jpg" loading="lazy" alt="Description">

// For custom implementation:
add_filter('the_content', 'add_lazy_loading');
function add_lazy_loading($content) {
    $content = preg_replace('/<img(.*?)src=/', '<img$1loading="lazy" src=', $content);
    return $content;
}
```

**Q44:** What are WordPress database optimization techniques?
- Delete post revisions and trash
- Remove spam comments
- Optimize database tables (OPTIMIZE TABLE)
- Delete transient options
- Remove orphaned post meta
- Limit post revisions in wp-config.php
```php
define('WP_POST_REVISIONS', 3);
define('AUTOSAVE_INTERVAL', 300);
```

### Multisite

**Q45:** What is WordPress Multisite?
- Allows multiple WordPress sites in single installation
- Shares same codebase (core, themes, plugins)
- Separate databases (tables with different prefixes) or shared database
- Central network admin for management

**Q46:** How do you enable WordPress Multisite?
```php
// wp-config.php
define('WP_ALLOW_MULTISITE', true);

// After network setup:
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', 'example.com');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);
```

**Q47:** What is the difference between subdomain and subdirectory multisite?
- Subdomain: site1.example.com, site2.example.com
- Subdirectory: example.com/site1, example.com/site2
- Subdomain requires wildcard DNS configuration
- Subdirectory easier to set up but conflicts with page slugs

**Q48:** How do you switch between sites in WordPress Multisite code?
```php
switch_to_blog($blog_id);
// Do something with the switched site
$posts = get_posts();
restore_current_blog();
```

**Q49:** What are network-activated plugins vs site-activated plugins?
- Network-activated: Active across all sites, managed by super admin
- Site-activated: Individual sites can activate/deactivate
- Some plugins are network-only (must be network activated)

**Q50:** How do you query across multiple sites in WordPress Multisite?
```php
$sites = get_sites();
$all_posts = array();

foreach ($sites as $site) {
    switch_to_blog($site->blog_id);
    $posts = get_posts(array('posts_per_page' => 5));
    $all_posts = array_merge($all_posts, $posts);
    restore_current_blog();
}
```

### Security

**Q51:** How do you sanitize and validate user input in WordPress?
```php
// Sanitization
$text = sanitize_text_field($_POST['field']);
$email = sanitize_email($_POST['email']);
$url = esc_url_raw($_POST['url']);
$html = wp_kses_post($_POST['content']);

// Validation
if (!is_email($_POST['email'])) {
    // Invalid email
}

if (!absint($_POST['id'])) {
    // Invalid integer
}
```

**Q52:** What are WordPress nonces and how do you use them?
```php
// Create nonce
wp_nonce_field('my_action', 'my_nonce_field');

// Verify nonce
if (!isset($_POST['my_nonce_field']) || !wp_verify_nonce($_POST['my_nonce_field'], 'my_action')) {
    die('Security check failed');
}

// URL nonces
$url = wp_nonce_url('http://example.com/action', 'my_action');

// Check URL nonce
if (!wp_verify_nonce($_GET['_wpnonce'], 'my_action')) {
    die('Security check failed');
}
```

**Q53:** How do you escape output in WordPress?
```php
echo esc_html($text); // Escape HTML
echo esc_attr($attribute); // Escape HTML attributes
echo esc_url($url); // Escape URLs
echo esc_js($javascript); // Escape JavaScript
echo wp_kses_post($content); // Allow safe HTML tags
```

**Q54:** What are WordPress capabilities and roles?
```php
// Check user capabilities
if (current_user_can('edit_posts')) {
    // User can edit posts
}

// Add custom capability
$role = get_role('editor');
$role->add_cap('manage_products');

// Create custom role
add_role('product_manager', 'Product Manager', array(
    'read' => true,
    'edit_posts' => true,
    'delete_posts' => false,
));
```

**Q55:** How do you secure the WordPress wp-config.php file?
```php
// Disable file editing
define('DISALLOW_FILE_EDIT', true);

// Move wp-config.php above web root
// Set file permissions to 400 or 440

// Use environment variables for sensitive data
define('DB_PASSWORD', getenv('DB_PASSWORD'));
```

**Q56:** What is prepared statement in WordPress and why use it?
```php
global $wpdb;

// Unsafe (SQL injection vulnerable)
$wpdb->query("SELECT * FROM $wpdb->posts WHERE ID = " . $_GET['id']);

// Safe (prepared statement)
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $wpdb->posts WHERE ID = %d",
    $_GET['id']
));

// Multiple placeholders
$wpdb->prepare(
    "SELECT * FROM $wpdb->posts WHERE post_status = %s AND post_author = %d",
    $_GET['status'],
    $_GET['author']
);
```

**Q57:** How do you implement two-factor authentication in WordPress?
- Use plugins like Two Factor Authentication, Wordfence, or Google Authenticator
- Hooks into login process
- Requires secondary verification code
- Can use SMS, email, or authenticator apps

**Q58:** What are WordPress file permissions best practices?
- Directories: 755 or 750
- Files: 644 or 640
- wp-config.php: 400 or 440
- Never use 777 permissions
- Web server user should own files

## Magento

### EAV (Entity-Attribute-Value)

**Q59:** What is the EAV model in Magento?
- Entity-Attribute-Value database design pattern
- Allows flexible product attributes without schema changes
- Entities: Products, categories, customers
- Attributes: Dynamic properties
- Values: Attribute data stored separately

**Q60:** What are the advantages and disadvantages of EAV?
Advantages:
- Flexible attribute management
- Add attributes without altering database schema
- Different entities can have different attributes

Disadvantages:
- Complex queries with multiple joins
- Performance overhead
- Difficult to index and optimize

**Q61:** What are the main EAV tables in Magento?
- `eav_attribute` - Attribute definitions
- `eav_entity_type` - Entity types
- `catalog_product_entity` - Product entities
- `catalog_product_entity_varchar` - Text attribute values
- `catalog_product_entity_int` - Integer attribute values
- `catalog_product_entity_decimal` - Decimal attribute values
- `catalog_product_entity_datetime` - Date/time attribute values
- `catalog_product_entity_text` - Long text attribute values

**Q62:** How do you create a custom EAV attribute in Magento 2?
```php
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'custom_attribute',
            [
                'type' => 'varchar',
                'label' => 'Custom Attribute',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );
    }
}
```

**Q63:** What are attribute sets and attribute groups in Magento?
- Attribute Sets: Collections of attributes grouped together (e.g., Default, Clothing)
- Products are assigned to one attribute set
- Attribute Groups: Organize attributes within sets (e.g., General, Prices, Meta Information)
- Helps manage attributes efficiently

**Q64:** How do you get product attributes programmatically in Magento 2?
```php
$product->getData('attribute_code');
$product->getAttributeCode();
$product->getCustomAttribute('attribute_code')->getValue();
$product->getResource()->getAttribute('attribute_code')->getFrontend()->getValue($product);
```

### Plugins (Interceptors)

**Q65:** What are plugins in Magento 2?
- Design pattern to modify behavior of public methods
- Also called interceptors
- Can run code before, after, or around target methods
- Non-invasive way to extend functionality

**Q66:** What are the types of plugins in Magento 2?
- `before`: Runs before target method
- `after`: Runs after target method
- `around`: Wraps target method, can prevent execution

**Q67:** How do you create a plugin in Magento 2?
```xml
<!-- etc/di.xml -->
<config>
    <type name="Magento\Catalog\Model\Product">
        <plugin name="vendor_module_product_plugin" 
                type="Vendor\Module\Plugin\ProductPlugin" 
                sortOrder="10" />
    </type>
</config>
```

```php
namespace Vendor\Module\Plugin;

class ProductPlugin
{
    // Before plugin
    public function beforeSetName(\Magento\Catalog\Model\Product $subject, $name)
    {
        // Modify arguments
        return ['Modified: ' . $name];
    }
    
    // After plugin
    public function afterGetName(\Magento\Catalog\Model\Product $subject, $result)
    {
        // Modify return value
        return $result . ' (modified)';
    }
    
    // Around plugin
    public function aroundSave(\Magento\Catalog\Model\Product $subject, \Closure $proceed)
    {
        // Before original method
        $result = $proceed(); // Call original method
        // After original method
        return $result;
    }
}
```

**Q68:** What is the difference between plugins and preferences in Magento 2?
- Plugins: Extend specific methods, non-invasive, multiple plugins can modify same method
- Preferences: Override entire class, can cause conflicts with other modules
- Plugins are recommended over preferences

**Q69:** What are plugin priority and sort order?
- sortOrder attribute determines execution order
- Lower number = higher priority
- For same priority, alphabetical by plugin name
- Affects execution sequence when multiple plugins target same method

**Q70:** Can plugins be used on final classes or methods?
- No, plugins cannot be used on final classes/methods
- Cannot plugin private or protected methods
- Cannot plugin static methods
- Only public methods are pluginable

### Observers

**Q71:** What are observers in Magento 2?
- Event-driven mechanism for extending functionality
- Listen to dispatched events
- Execute custom code when events fire
- Similar to WordPress hooks/actions

**Q72:** How do you create an observer in Magento 2?
```xml
<!-- etc/events.xml -->
<config>
    <event name="catalog_product_save_after">
        <observer name="vendor_module_product_save" 
                  instance="Vendor\Module\Observer\ProductSaveObserver" />
    </event>
</config>
```

```php
namespace Vendor\Module\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ProductSaveObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        // Custom logic
    }
}
```

**Q73:** What is the difference between observers and plugins?
- Observers: Event-based, loosely coupled, multiple can listen to same event
- Plugins: Method-based, tightly coupled to specific class/method
- Observers for events, plugins for modifying method behavior
- Observers don't modify return values or parameters directly

**Q74:** How do you dispatch a custom event in Magento 2?
```php
namespace Vendor\Module\Model;

use Magento\Framework\Event\ManagerInterface;

class MyClass
{
    protected $eventManager;
    
    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }
    
    public function doSomething()
    {
        $data = ['key' => 'value'];
        $this->eventManager->dispatch(
            'custom_event_name',
            ['data' => $data, 'object' => $this]
        );
    }
}
```

**Q75:** What are common Magento 2 events?
- `catalog_product_save_before/after` - Product save
- `sales_order_place_before/after` - Order placement
- `customer_login` - Customer login
- `checkout_cart_product_add_after` - Add to cart
- `controller_action_predispatch/postdispatch` - Controller actions
- `catalog_product_collection_load_after` - Product collection load

### Payment Gateways

**Q76:** How do you create a custom payment method in Magento 2?
```xml
<!-- etc/config.xml -->
<config>
    <default>
        <payment>
            <custom_payment>
                <active>1</active>
                <model>Vendor\Module\Model\Payment\CustomPayment</model>
                <order_status>pending</order_status>
                <title>Custom Payment Method</title>
                <allowspecific>0</allowspecific>
                <sort_order>1</sort_order>
            </custom_payment>
        </payment>
    </default>
</config>
```

```php
namespace Vendor\Module\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;

class CustomPayment extends AbstractMethod
{
    protected $_code = 'custom_payment';
    protected $_isOffline = true;
    
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }
}
```

**Q77:** What are the different payment method types in Magento?
- Online: Process through payment gateway (Authorize.net, PayPal)
- Offline: Manual processing (Check/Money Order, Bank Transfer)
- Gateway: Redirect to external site
- Direct: Process on site with card details

**Q78:** How do you configure payment method backend settings?
```xml
<!-- etc/adminhtml/system.xml -->
<config>
    <system>
        <section id="payment">
            <group id="custom_payment" translate="label" type="text" sortOrder="100" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>Custom Payment</label>
                <field id="active" translate="label" type="select" sortOrder="1" showInDefault="1" showInWebsite="1" showInStore="0">
                    <label>Enabled</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="title" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Title</label>
                </field>
            </group>
        </section>
    </system>
</config>
```

**Q79:** What is the payment flow in Magento 2?
1. Customer selects payment method
2. Payment information validation
3. Order placement
4. Payment authorization
5. Payment capture (immediate or later)
6. Order confirmation
7. Invoice generation

**Q80:** How do you implement payment capture in Magento 2?
```php
namespace Vendor\Module\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;

class CustomPayment extends AbstractMethod
{
    protected $_canCapture = true;
    
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);
        
        // API call to payment gateway
        $transactionId = $this->processPayment($payment, $amount);
        
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(false);
        
        return $this;
    }
    
    private function processPayment($payment, $amount)
    {
        // Payment gateway integration
        return 'transaction_id_123';
    }
}
```

### Module Development

**Q81:** What is the structure of a Magento 2 module?
```
Vendor/Module/
├── Api/
├── Block/
├── Controller/
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── config.xml
├── Model/
├── Observer/
├── Plugin/
├── Setup/
├── view/
│   └── frontend/
│       ├── layout/
│       ├── templates/
│       └── web/
├── composer.json
└── registration.php
```

**Q82:** How do you register a Magento 2 module?
```php
// registration.php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Vendor_Module',
    __DIR__
);
```

```xml
<!-- etc/module.xml -->
<config>
    <module name="Vendor_Module" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Catalog"/>
        </sequence>
    </module>
</config>
```

**Q83:** What is dependency injection in Magento 2?
- Design pattern for managing class dependencies
- Constructor injection via di.xml
- Automatic dependency resolution
- Promotes loose coupling and testability
```php
class MyClass
{
    protected $dependency;
    
    public function __construct(\Vendor\Module\Model\Dependency $dependency)
    {
        $this->dependency = $dependency;
    }
}
```

**Q84:** What are Magento 2 repositories?
- Service contracts for data access
- CRUD operations abstraction
- Provides consistent API interface
- Supports search criteria and filters
```php
interface ProductRepositoryInterface
{
    public function save(ProductInterface $product);
    public function get($sku);
    public function delete(ProductInterface $product);
    public function getList(SearchCriteriaInterface $searchCriteria);
}
```

**Q85:** What is the difference between collections and repositories?
- Collections: Direct database access, chaining methods, Magento 1 style
- Repositories: Service contract layer, uses search criteria, Magento 2 best practice
- Repositories provide better abstraction and flexibility

**Q86:** How do you create a custom controller in Magento 2?
```php
namespace Vendor\Module\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;
    
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
```

```xml
<!-- etc/frontend/routes.xml -->
<config>
    <router id="standard">
        <route id="module" frontName="module">
            <module name="Vendor_Module" />
        </route>
    </router>
</config>
```

**Q87:** What are Magento 2 UI components?
- Reusable UI elements for admin and frontend
- XML-based configuration
- Examples: forms, grids, listings
- Decouples UI from business logic

**Q88:** How do you create a custom admin grid in Magento 2?
```xml
<!-- view/adminhtml/ui_component/custom_grid.xml -->
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dataSource name="custom_grid_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">Vendor\Module\Ui\DataProvider\CustomDataProvider</argument>
            <argument name="name" xsi:type="string">custom_grid_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">entity_id</argument>
            <argument name="requestFieldName" xsi:type="string">id</argument>
        </argument>
    </dataSource>
    <columns name="custom_columns">
        <column name="entity_id">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="label" xsi:type="string" translate="true">ID</item>
                </item>
            </argument>
        </column>
    </columns>
</listing>
```

### Performance & Optimization

**Q89:** What are Magento 2 caching types?
- Configuration: System configuration
- Layout: Page layouts
- Block HTML: Block output
- Collections: Collections data
- Reflection: API class reflection
- DDL: Database schema
- EAV: EAV attributes
- Full Page: Full page cache
- Translation: Translation files

**Q90:** How do you implement full page cache in Magento 2?
- Built-in Varnish support
- Configuration in admin panel
- VCL file generation
- Cache invalidation on content changes
- Hole punching for dynamic content

**Q91:** What is the difference between cacheable and non-cacheable blocks?
```xml
<!-- Cacheable block (default) -->
<block class="Vendor\Module\Block\MyBlock" name="my.block" cacheable="true"/>

<!-- Non-cacheable block -->
<block class="Vendor\Module\Block\MyBlock" name="my.block" cacheable="false"/>
```
- Cacheable blocks stored in cache
- Non-cacheable regenerated on each request
- Use non-cacheable for customer-specific content

**Q92:** How do you optimize Magento 2 performance?
- Enable all cache types
- Use Varnish for full page cache
- Enable flat catalog (products and categories)
- Merge and minify CSS/JS
- Use Redis for session and cache storage
- Enable production mode
- Optimize images
- Use CDN
- Database query optimization

**Q93:** What are Magento 2 indexers?
- Transform data for faster queries
- Reindex on schedule or manually
- Types: Category Products, Product Categories, Product Prices, Product EAV, Stock, etc.
```bash
bin/magento indexer:reindex
bin/magento indexer:status
bin/magento indexer:set-mode {realtime|schedule}
```

**Q94:** How do you implement custom indexer in Magento 2?
```xml
<!-- etc/indexer.xml -->
<config>
    <indexer id="custom_indexer" view_id="custom_indexer" class="Vendor\Module\Model\Indexer\Custom">
        <title translate="true">Custom Indexer</title>
        <description translate="true">Custom indexer description</description>
    </indexer>
</config>
```

```php
namespace Vendor\Module\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

class Custom implements ActionInterface, MviewActionInterface
{
    public function executeFull()
    {
        // Full reindex
    }
    
    public function executeList(array $ids)
    {
        // Partial reindex
    }
    
    public function executeRow($id)
    {
        // Single row reindex
    }
    
    public function execute($ids)
    {
        // Execute indexing
    }
}
```

### CLI Commands

**Q95:** How do you create a custom CLI command in Magento 2?
```php
namespace Vendor\Module\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomCommand extends Command
{
    protected function configure()
    {
        $this->setName('custom:command')
            ->setDescription('Custom command description');
        
        parent::configure();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Executing custom command...');
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
```

```xml
<!-- etc/di.xml -->
<type name="Magento\Framework\Console\CommandList">
    <arguments>
        <argument name="commands" xsi:type="array">
            <item name="customCommand" xsi:type="object">Vendor\Module\Console\Command\CustomCommand</item>
        </argument>
    </arguments>
</type>
```

**Q96:** What are common Magento CLI commands?
```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
bin/magento cache:flush
bin/magento indexer:reindex
bin/magento module:enable Vendor_Module
bin/magento module:disable Vendor_Module
bin/magento deploy:mode:set production
```

## Drupal

### Modules

**Q97:** What is the basic structure of a Drupal module?
```
module_name/
├── module_name.info.yml
├── module_name.module
├── module_name.routing.yml
├── module_name.services.yml
├── src/
│   ├── Controller/
│   ├── Form/
│   ├── Plugin/
│   └── Entity/
└── config/
    ├── install/
    └── schema/
```

**Q98:** What is the module_name.info.yml file?
```yaml
name: Module Name
type: module
description: 'Module description'
core_version_requirement: ^9 || ^10
package: Custom
dependencies:
  - drupal:node
  - drupal:views
```

**Q99:** How do you create a custom module in Drupal?
1. Create module directory in modules/custom/
2. Create .info.yml file with module metadata
3. Create .module file for hooks
4. Clear cache
5. Enable module via UI or Drush

**Q100:** What is the difference between core and contrib modules?
- Core: Included with Drupal installation
- Contrib: Community-developed, downloaded separately
- Custom: Developed specifically for the site

**Q101:** How do you programmatically enable/disable modules in Drupal?
```php
// Enable module
\Drupal::service('module_installer')->install(['module_name']);

// Disable module
\Drupal::service('module_installer')->uninstall(['module_name']);
```

### Hooks

**Q102:** What are hooks in Drupal?
- Functions that allow modules to interact with Drupal core
- Named pattern: `hook_name()` becomes `modulename_name()`
- Executed at specific points in code execution
- Allow altering behavior without modifying core

**Q103:** What are commonly used Drupal hooks?
- `hook_form_alter()` - Modify forms
- `hook_entity_presave()` - Before entity save
- `hook_entity_insert()` - After entity insert
- `hook_entity_update()` - After entity update
- `hook_node_view()` - Modify node display
- `hook_preprocess_HOOK()` - Modify template variables
- `hook_theme()` - Define custom templates

**Q104:** How do you implement hook_form_alter()?
```php
function mymodule_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if ($form_id == 'node_article_form') {
    $form['title']['widget'][0]['value']['#description'] = t('Custom description');
    $form['actions']['submit']['#value'] = t('Save Article');
  }
}
```

**Q105:** How do you implement hook_entity_presave()?
```php
function mymodule_entity_presave(\Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'article') {
    $entity->setTitle(strtoupper($entity->getTitle()));
  }
}
```

**Q106:** What is hook_theme() used for?
```php
function mymodule_theme($existing, $type, $theme, $path) {
  return [
    'custom_template' => [
      'variables' => [
        'title' => NULL,
        'content' => NULL,
      ],
      'template' => 'custom-template',
    ],
  ];
}
```

**Q107:** How do you implement hook_preprocess_page()?
```php
function mymodule_preprocess_page(&$variables) {
  $variables['custom_variable'] = 'Custom value';
  $variables['site_name'] = \Drupal::config('system.site')->get('name');
}
```

**Q108:** What is the difference between hook_form_alter() and hook_form_FORM_ID_alter()?
- `hook_form_alter()`: Affects all forms
- `hook_form_FORM_ID_alter()`: Affects specific form
- Specific form alter runs after general form_alter
- Specific version is more performant

### Views

**Q109:** What is Views in Drupal?
- Query builder for creating content listings
- No coding required for basic functionality
- Creates pages, blocks, feeds
- Filters, sorts, paginates content
- Extensible with custom plugins

**Q110:** How do you programmatically execute a View?
```php
$view = \Drupal\views\Views::getView('view_id');
$view->setDisplay('display_id');
$view->setArguments(['argument_value']);
$view->execute();
$results = $view->result;

// Render view
$render = $view->render();
$output = \Drupal::service('renderer')->renderRoot($render);
```

**Q111:** How do you create a custom Views field?
```php
namespace Drupal\mymodule\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * @ViewsField("custom_field")
 */
class CustomField extends FieldPluginBase {
  
  public function query() {
    // Add query modifications if needed
  }
  
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    return [
      '#markup' => 'Custom output: ' . $entity->label(),
    ];
  }
}
```

**Q112:** How do you create a custom Views filter?
```php
namespace Drupal\mymodule\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * @ViewsFilter("custom_filter")
 */
class CustomFilter extends FilterPluginBase {
  
  public function query() {
    $this->ensureMyTable();
    $this->query->addWhere(
      $this->options['group'],
      "$this->tableAlias.$this->realField",
      $this->value,
      $this->operator
    );
  }
}
```

**Q113:** How do you alter an existing View?
```php
function mymodule_views_pre_view(\Drupal\views\ViewExecutable $view, $display_id, array &$args) {
  if ($view->id() == 'content' && $display_id == 'page_1') {
    // Modify view before execution
    $view->setItemsPerPage(20);
  }
}

function mymodule_views_post_execute(\Drupal\views\ViewExecutable $view) {
  if ($view->id() == 'content') {
    // Modify results after execution
    foreach ($view->result as $key => $row) {
      // Modify row data
    }
  }
}
```

**Q114:** What are Views relationships?
- Connect different entities in queries
- Example: Node to User (author relationship)
- Allows access to related entity fields
- Configured in Views UI under "Relationships"

**Q115:** How do you create a custom Views display plugin?
```php
namespace Drupal\mymodule\Plugin\views\display;

use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * @ViewsDisplay(
 *   id = "custom_display",
 *   title = @Translation("Custom Display"),
 *   admin = @Translation("Custom Display"),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   returns_response = TRUE
 * )
 */
class CustomDisplay extends DisplayPluginBase {
  // Implementation
}
```

### Twig

**Q116:** What is Twig in Drupal?
- Template engine for Drupal 8+
- Separates logic from presentation
- Secure by default (auto-escaping)
- Cleaner syntax than PHP templates
- Part of Symfony framework

**Q117:** What are common Twig syntax elements?
```twig
{# Comments #}

{# Output variable #}
{{ variable }}

{# Execute statement #}
{% if condition %}
  Content
{% endif %}

{# Set variable #}
{% set name = 'value' %}

{# Loop #}
{% for item in items %}
  {{ item }}
{% endfor %}
```

**Q118:** How do you access Drupal variables in Twig?
```twig
{# Node fields #}
{{ node.title.value }}
{{ node.body.value }}
{{ node.field_custom.value }}

{# Entity URL #}
{{ url('entity.node.canonical', {'node': node.id}) }}

{# Render array #}
{{ content.field_image }}

{# Translation #}
{{ 'Text to translate'|t }}
```

**Q119:** What are Twig filters in Drupal?
```twig
{# Common filters #}
{{ text|upper }}
{{ text|lower }}
{{ text|capitalize }}
{{ text|length }}
{{ html|striptags }}
{{ text|trim }}
{{ date|date('Y-m-d') }}
{{ text|trans }}
{{ markup|raw }}
```

**Q120:** How do you create a custom Twig template?
```php
// In .module file
function mymodule_theme($existing, $type, $theme, $path) {
  return [
    'custom_template' => [
      'variables' => ['items' => []],
      'template' => 'custom-template',
    ],
  ];
}
```

```twig
{# templates/custom-template.html.twig #}
<div class="custom-wrapper">
  {% for item in items %}
    <div class="item">{{ item }}</div>
  {% endfor %}
</div>
```

**Q121:** How do you create a custom Twig function?
```php
namespace Drupal\mymodule;

use Drupal\Core\Template\TwigExtension;

class MyTwigExtension extends TwigExtension {
  
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('custom_function', [$this, 'customFunction']),
    ];
  }
  
  public function customFunction($arg) {
    return 'Processed: ' . $arg;
  }
}
```

```yaml
# mymodule.services.yml
services:
  mymodule.twig_extension:
    class: Drupal\mymodule\MyTwigExtension
    tags:
      - { name: twig.extension }
```

**Q122:** How do you debug Twig templates?
```twig
{# Enable Twig debugging in services.yml #}
{# Then use dump() function #}
{{ dump(variable) }}
{{ dump() }} {# Dumps all variables #}

{# Show available variables #}
{{ kint(variable) }} {# Requires Devel module #}
```

**Q123:** What are Twig template suggestions?
- Allow template overrides based on conditions
- More specific suggestions take precedence
- Example: `node--article.html.twig` overrides `node.html.twig`
- Can add custom suggestions via hook_theme_suggestions_HOOK()

**Q124:** How do you add custom Twig template suggestions?
```php
function mymodule_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    $suggestions[] = 'page__node__' . $node->bundle();
    $suggestions[] = 'page__node__' . $node->id();
  }
}
```

### Configuration & Services

**Q125:** What is the Configuration API in Drupal?
- Stores configuration in YAML files
- Separates configuration from content
- Exportable and versionable
- Synchronizable between environments
- Located in config/sync directory

**Q126:** How do you get configuration values?
```php
// Get simple config
$config = \Drupal::config('system.site');
$site_name = $config->get('name');

// Get editable config
$config = \Drupal::service('config.factory')->getEditable('mymodule.settings');
$value = $config->get('setting_name');
```

**Q127:** How do you set configuration values?
```php
$config = \Drupal::service('config.factory')->getEditable('mymodule.settings');
$config->set('setting_name', 'value');
$config->save();
```

**Q128:** What is dependency injection in Drupal?
```php
namespace Drupal\mymodule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class MyController extends ControllerBase {
  
  protected $configFactory;
  
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }
}
```

**Q129:** How do you define custom services?
```yaml
# mymodule.services.yml
services:
  mymodule.custom_service:
    class: Drupal\mymodule\CustomService
    arguments: ['@entity_type.manager', '@current_user']
```

```php
namespace Drupal\mymodule;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

class CustomService {
  
  protected $entityTypeManager;
  protected $currentUser;
  
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }
  
  public function doSomething() {
    // Service logic
  }
}
```

**Q130:** What are common Drupal services?
- `entity_type.manager` - Entity operations
- `current_user` - Current user information
- `config.factory` - Configuration access
- `database` - Database operations
- `logger.factory` - Logging
- `messenger` - User messages
- `module_handler` - Module operations
- `cache.default` - Caching

### Entities & Content Types

**Q131:** What are entities in Drupal?
- Structured data objects
- Types: Node, User, Taxonomy Term, Comment, etc.
- Can have fields attached
- Fieldable and revisionable
- Translatable

**Q132:** How do you create a node programmatically?
```php
$node = \Drupal\node\Entity\Node::create([
  'type' => 'article',
  'title' => 'Node title',
  'body' => [
    'value' => 'Node body content',
    'format' => 'full_html',
  ],
  'field_custom' => 'Custom value',
  'uid' => 1,
  'status' => 1,
]);
$node->save();
```

**Q133:** How do you load and update entities?
```php
// Load node
$node = \Drupal\node\Entity\Node::load($nid);

// Load multiple
$nodes = \Drupal\node\Entity\Node::loadMultiple([1, 2, 3]);

// Update
$node->setTitle('New title');
$node->set('field_custom', 'New value');
$node->save();

// Delete
$node->delete();
```

**Q134:** How do you query entities?
```php
// Entity query
$query = \Drupal::entityQuery('node')
  ->condition('type', 'article')
  ->condition('status', 1)
  ->condition('field_custom', 'value', '=')
  ->range(0, 10)
  ->sort('created', 'DESC')
  ->accessCheck(TRUE);

$nids = $query->execute();
$nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
```

**Q135:** How do you create a custom entity type?
```php
namespace Drupal\mymodule\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "custom_entity",
 *   label = @Translation("Custom Entity"),
 *   base_table = "custom_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class CustomEntity extends ContentEntityBase {
  
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE);
    
    return $fields;
  }
}
```

**Q136:** What is the difference between content entities and config entities?
- Content entities: User-generated data (nodes, users, comments)
- Config entities: Site configuration (views, content types, vocabularies)
- Content entities are translatable and revisionable
- Config entities are exportable and synchronizable

### Forms & Validation

**Q137:** How do you create a custom form in Drupal?
```php
namespace Drupal\mymodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CustomForm extends FormBase {
  
  public function getFormId() {
    return 'mymodule_custom_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
    ];
    
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    
    return $form;
  }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('name')) < 3) {
      $form_state->setErrorByName('name', $this->t('Name too short.'));
    }
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    \Drupal::messenger()->addMessage($this->t('Thank you @name', ['@name' => $name]));
  }
}
```

**Q138:** How do you create a configuration form?
```php
namespace Drupal\mymodule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {
  
  protected function getEditableConfigNames() {
    return ['mymodule.settings'];
  }
  
  public function getFormId() {
    return 'mymodule_settings_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mymodule.settings');
    
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
    ];
    
    return parent::buildForm($form, $form_state);
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mymodule.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
    
    parent::submitForm($form, $form_state);
  }
}
```

**Q139:** What are common form element types in Drupal?
- `textfield` - Single line text
- `textarea` - Multi-line text
- `email` - Email input
- `select` - Dropdown
- `checkboxes` - Multiple checkboxes
- `radios` - Radio buttons
- `date` - Date picker
- `entity_autocomplete` - Entity reference autocomplete
- `file` - File upload
- `password` - Password field

**Q140:** How do you implement AJAX in Drupal forms?
```php
public function buildForm(array $form, FormStateInterface $form_state) {
  $form['category'] = [
    '#type' => 'select',
    '#title' => $this->t('Category'),
    '#options' => ['cat1' => 'Category 1', 'cat2' => 'Category 2'],
    '#ajax' => [
      'callback' => '::updateSubcategory',
      'wrapper' => 'subcategory-wrapper',
    ],
  ];
  
  $form['subcategory'] = [
    '#type' => 'select',
    '#title' => $this->t('Subcategory'),
    '#options' => $this->getSubcategories($form_state->getValue('category')),
    '#prefix' => '<div id="subcategory-wrapper">',
    '#suffix' => '</div>',
  ];
  
  return $form;
}

public function updateSubcategory(array &$form, FormStateInterface $form_state) {
  return $form['subcategory'];
}
```

### Routing & Controllers

**Q141:** How do you define routes in Drupal?
```yaml
# mymodule.routing.yml
mymodule.custom_page:
  path: '/custom-page/{parameter}'
  defaults:
    _controller: '\Drupal\mymodule\Controller\CustomController::customPage'
    _title: 'Custom Page'
  requirements:
    _permission: 'access content'
    parameter: \d+
  options:
    parameters:
      parameter:
        type: integer
```

**Q142:** How do you create a controller?
```php
namespace Drupal\mymodule\Controller;

use Drupal\Core\Controller\ControllerBase;

class CustomController extends ControllerBase {
  
  public function customPage($parameter) {
    return [
      '#markup' => $this->t('Parameter value: @param', ['@param' => $parameter]),
    ];
  }
  
  public function renderContent() {
    return [
      '#theme' => 'custom_template',
      '#items' => ['item1', 'item2', 'item3'],
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['user'],
        'tags' => ['node:1'],
      ],
    ];
  }
}
```

**Q143:** How do you generate URLs in Drupal?
```php
// Generate URL from route
use Drupal\Core\Url;

$url = Url::fromRoute('mymodule.custom_page', ['parameter' => 123]);
$url_string = $url->toString();

// Entity URL
$url = Url::fromRoute('entity.node.canonical', ['node' => 1]);

// External URL
$url = Url::fromUri('https://example.com');

// URL with query parameters
$url = Url::fromRoute('mymodule.page')->setOption('query', ['param' => 'value']);
```

**Q144:** How do you implement access control?
```php
// In routing
requirements:
  _permission: 'administer site configuration'
  # or
  _custom_access: '\Drupal\mymodule\Controller\CustomController::checkAccess'

// Custom access callback
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

public function checkAccess(AccountInterface $account) {
  return AccessResult::allowedIf($account->id() == 1);
}
```

### Plugins

**Q145:** What are plugins in Drupal?
- Swappable functionality components
- Types: Block, Field, Filter, etc.
- Discovered via annotations
- Can be provided by any module

**Q146:** How do you create a custom block plugin?
```php
namespace Drupal\mymodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "custom_block",
 *   admin_label = @Translation("Custom Block"),
 *   category = @Translation("Custom")
 * )
 */
class CustomBlock extends BlockBase {
  
  public function build() {
    return [
      '#markup' => $this->t('Custom block content'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
  
  public function blockForm($form, FormStateInterface $form_state) {
    $form['custom_setting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Setting'),
      '#default_value' => $this->configuration['custom_setting'] ?? '',
    ];
    return $form;
  }
  
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['custom_setting'] = $form_state->getValue('custom_setting');
  }
}
```

**Q147:** How do you create a custom field formatter?
```php
namespace Drupal\mymodule\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldFormatter(
 *   id = "custom_formatter",
 *   label = @Translation("Custom Formatter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class CustomFormatter extends FormatterBase {
  
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => strtoupper($item->value),
      ];
    }
    
    return $elements;
  }
}
```

**Q148:** How do you create a custom field widget?
```php
namespace Drupal\mymodule\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "custom_widget",
 *   label = @Translation("Custom Widget"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class CustomWidget extends WidgetBase {
  
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? '',
      '#placeholder' => $this->t('Enter value'),
    ];
    
    return $element;
  }
}
```

### Database & Queries

**Q149:** How do you perform database queries in Drupal?
```php
// Select query
$database = \Drupal::database();
$query = $database->select('node_field_data', 'n');
$query->fields('n', ['nid', 'title']);
$query->condition('n.type', 'article');
$query->condition('n.status', 1);
$query->range(0, 10);
$results = $query->execute()->fetchAll();

// Insert
$database->insert('custom_table')
  ->fields([
    'name' => 'Value',
    'created' => time(),
  ])
  ->execute();

// Update
$database->update('custom_table')
  ->fields(['name' => 'New value'])
  ->condition('id', 1)
  ->execute();

// Delete
$database->delete('custom_table')
  ->condition('id', 1)
  ->execute();
```

**Q150:** How do you create custom database tables in Drupal?
```php
// mymodule.install

function mymodule_schema() {
  $schema['custom_table'] = [
    'description' => 'Custom table description',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'not null' => TRUE,
      ],
      'name' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
      ],
      'created' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'name' => ['name'],
    ],
  ];
  
  return $schema;
}
```
