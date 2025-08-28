<?php
/**
 * Plugin Name: CNW Charts
 * Description: Create beautiful charts using Chart.js with custom post types and shortcodes
 * Version: 1.0.0
 * Author: Cloud Nine Web
 * Author URI: https://cloudnineweb.co/
 * Text Domain: cnw-charts
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CNW_CHARTS_VERSION', '1.0.0');
define('CNW_CHARTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CNW_CHARTS_PLUGIN_PATH', plugin_dir_path(__FILE__));

class CNWCharts {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->register_post_type();
        add_shortcode('cnw_chart', array($this, 'chart_shortcode'));
        add_action('save_post', array($this, 'save_chart_meta'));
        add_action('add_meta_boxes', array($this, 'add_chart_meta_boxes'));
        add_action('admin_footer', array($this, 'add_shortcode_copy_script'));
    }
    
    public function register_post_type() {
        $args = array(
            'labels' => array(
                'name' => 'Charts',
                'singular_name' => 'Chart',
                'add_new' => 'Add New Chart',
                'add_new_item' => 'Add New Chart',
                'edit_item' => 'Edit Chart',
                'new_item' => 'New Chart',
                'view_item' => 'View Chart',
                'search_items' => 'Search Charts',
                'not_found' => 'No charts found',
                'not_found_in_trash' => 'No charts found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-chart-bar',
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false
        );
        register_post_type('cnw_chart', $args);
    }
    
    
    public function add_chart_meta_boxes() {
        add_meta_box(
            'cnw_chart_data',
            'Chart Configuration',
            array($this, 'chart_meta_box_callback'),
            'cnw_chart',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cnw_chart_shortcode',
            'Shortcode',
            array($this, 'shortcode_meta_box_callback'),
            'cnw_chart',
            'side',
            'high'
        );
    }
    
    public function chart_meta_box_callback($post) {
        wp_nonce_field('cnw_chart_meta_nonce', 'cnw_chart_meta_nonce_field');
        
        $chart_type = get_post_meta($post->ID, '_cnw_chart_type', true);
        $chart_data = get_post_meta($post->ID, '_cnw_chart_data', true);
        $chart_height = get_post_meta($post->ID, '_cnw_chart_height', true);
        if (empty($chart_height)) {
            $chart_height = 400; // Default height
        }
        
        ?>
        <div id="cnw-chart-config">
            <table class="form-table">
                <tr>
                    <th scope="row">Chart Type</th>
                    <td>
                        <select name="cnw_chart_type" id="cnw_chart_type" onchange="toggleChartFields()">
                            <option value="bar" <?php selected($chart_type, 'bar'); ?>>Bar Chart</option>
                            <option value="grouped-bar" <?php selected($chart_type, 'grouped-bar'); ?>>Grouped Bar Chart</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Height</th>
                    <td>
                        <input type="number" name="cnw_chart_height" id="cnw_chart_height" value="<?php echo esc_attr($chart_height); ?>" min="200" max="1000" style="width: 100px;" />
                        <span style="margin-left: 5px; color: #666;">px (default: 400px)</span>
                        <p class="description">Set the height of the chart in pixels. Recommended range: 200px - 1000px.</p>
                    </td>
                </tr>
                <tr id="bar-chart-data" style="display: none;">
                    <th scope="row">Chart Data</th>
                    <td>
                        <div id="data-items-container">
                            <?php
                            // Only load bar data if chart type is bar
                            $bar_data = array();
                            if ($chart_type === 'bar') {
                                $bar_data = json_decode($chart_data, true) ?: array();
                            }
                            
                            if (empty($bar_data)) {
                                echo '<p style="text-align: center; color: #666; margin: 20px 0;">No data items added yet. Click "Add Data Item" to get started.</p>';
                            } else {
                                foreach ($bar_data as $index => $item) {
                                    ?>
                                    <div class="data-item" data-index="<?php echo $index; ?>" style="display: flex; align-items: center; gap: 10px; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9;">
                                        <div style="flex: 1;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Label:</label>
                                            <input type="text" name="data_items[<?php echo $index; ?>][label]" value="<?php echo esc_attr($item['label']); ?>" placeholder="Item label" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 0 0 80px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Prefix:</label>
                                            <input type="text" name="data_items[<?php echo $index; ?>][prefix]" value="<?php echo esc_attr(isset($item['prefix']) ? $item['prefix'] : ''); ?>" placeholder="$" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 1;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Value:</label>
                                            <input type="number" step="0.01" name="data_items[<?php echo $index; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" placeholder="Item value" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 0 0 80px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Postfix:</label>
                                            <input type="text" name="data_items[<?php echo $index; ?>][postfix]" value="<?php echo esc_attr(isset($item['postfix']) ? $item['postfix'] : ''); ?>" placeholder="%" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 0 0 100px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Color:</label>
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="color" name="data_items[<?php echo $index; ?>][color]" value="<?php echo esc_attr($item['color']); ?>" style="width: 40px; height: 32px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; padding: 0;" />
                                                <input type="text" name="data_items[<?php echo $index; ?>][color_text]" value="<?php echo esc_attr($item['color']); ?>" placeholder="#007cba" style="width: 55px; padding: 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 11px;" />
                                            </div>
                                        </div>
                                        <div style="flex: 0 0 auto;">
                                            <button type="button" class="remove-data-item" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-top: 22px;">Remove</button>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" id="add-data-item" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 15px; font-size: 14px;">Add Data Item</button>
                    </td>
                </tr>
                <tr id="grouped-bar-chart-data" style="display: none;">
                    <th scope="row">Chart Groups</th>
                    <td>
                        <div id="chart-groups-container">
                            <?php
                            // Only load grouped data if chart type is grouped-bar
                            $grouped_data = array();
                            if ($chart_type === 'grouped-bar') {
                                $grouped_data = json_decode($chart_data, true) ?: array();
                            }
                            
                            if (empty($grouped_data)) {
                                echo '<p style="text-align: center; color: #666; margin: 20px 0;">No groups added yet. Click "Add Group" to get started.</p>';
                            } else {
                                foreach ($grouped_data as $group_index => $group) {
                                    ?>
                                    <div class="chart-group" data-group-index="<?php echo $group_index; ?>" style="border: 2px solid #007cba; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f0f8ff;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                                            <h4 style="margin: 0; color: #007cba;">Group <?php echo $group_index + 1; ?>: <?php echo esc_html($group['group_name']); ?></h4>
                                            <div style="display: flex; gap: 8px;">
                                                <button type="button" class="duplicate-chart-group" data-group-index="<?php echo $group_index; ?>" style="background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Duplicate</button>
                                                <button type="button" class="remove-chart-group" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Remove Group</button>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                            <div style="flex: 1;">
                                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Group Name:</label>
                                                <input type="text" name="chart_groups[<?php echo $group_index; ?>][group_name]" value="<?php echo esc_attr($group['group_name']); ?>" placeholder="Enter group name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                            </div>
                                            <div style="flex: 0 0 140px;">
                                                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Group Color:</label>
                                                <div style="display: flex; align-items: center; gap: 5px;">
                                                    <input type="color" name="chart_groups[<?php echo $group_index; ?>][color]" value="<?php echo esc_attr(isset($group['color']) ? $group['color'] : '#007cba'); ?>" style="width: 40px; height: 32px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; padding: 0;" />
                                                    <input type="text" name="chart_groups[<?php echo $group_index; ?>][color_text]" value="<?php echo esc_attr(isset($group['color']) ? $group['color'] : '#007cba'); ?>" placeholder="#007cba" style="width: 90px; padding: 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px;" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="group-data-items" data-group="<?php echo $group_index; ?>">
                                            <h5 style="margin-bottom: 10px; color: #555;">Data Items:</h5>
                                            <div class="group-items-container">
                                                <?php
                                                $group_items = isset($group['items']) ? $group['items'] : array();
                                                if (empty($group_items)) {
                                                    echo '<p style="text-align: center; color: #666; margin: 15px 0; font-style: italic;">No data items in this group yet. Click "Add Data Item" below.</p>';
                                                } else {
                                                    foreach ($group_items as $item_index => $item) {
                                                        ?>
                                                        <div class="group-data-item" data-item-index="<?php echo $item_index; ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 6px; background: white;">
                                                            <div style="flex: 1;">
                                                                <label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Label:</label>
                                                                <input type="text" name="chart_groups[<?php echo $group_index; ?>][items][<?php echo $item_index; ?>][label]" value="<?php echo esc_attr($item['label']); ?>" placeholder="Item label" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />
                                                            </div>
                                                            <div style="flex: 0 0 60px;">
                                                                <label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Prefix:</label>
                                                                <input type="text" name="chart_groups[<?php echo $group_index; ?>][items][<?php echo $item_index; ?>][prefix]" value="<?php echo esc_attr(isset($item['prefix']) ? $item['prefix'] : ''); ?>" placeholder="$" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />
                                                            </div>
                                                            <div style="flex: 1;">
                                                                <label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Value:</label>
                                                                <input type="number" step="0.01" name="chart_groups[<?php echo $group_index; ?>][items][<?php echo $item_index; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" placeholder="Item value" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />
                                                            </div>
                                                            <div style="flex: 0 0 60px;">
                                                                <label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Postfix:</label>
                                                                <input type="text" name="chart_groups[<?php echo $group_index; ?>][items][<?php echo $item_index; ?>][postfix]" value="<?php echo esc_attr(isset($item['postfix']) ? $item['postfix'] : ''); ?>" placeholder="%" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />
                                                            </div>
                                                            <div style="flex: 0 0 auto;">
                                                                <button type="button" class="remove-group-item" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 3px; cursor: pointer; font-size: 11px; margin-top: 15px;">Remove</button>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <button type="button" class="add-group-item" data-group="<?php echo $group_index; ?>" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 12px;">Add Data Item</button>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" id="add-chart-group" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 15px; font-size: 14px;">Add Group</button>
                    </td>
                </tr>
            </table>
        </div>
        
        <style>
        /* Light placeholder text styling */
        #cnw-chart-config input::placeholder {
            color: #c4c4c4 !important;
            opacity: 0.7;
        }
        #cnw-chart-config input::-webkit-input-placeholder {
            color: #c4c4c4 !important;
            opacity: 0.7;
        }
        #cnw-chart-config input::-moz-placeholder {
            color: #c4c4c4 !important;
            opacity: 0.7;
        }
        #cnw-chart-config input:-ms-input-placeholder {
            color: #c4c4c4 !important;
            opacity: 0.7;
        }
        #cnw-chart-config input:-moz-placeholder {
            color: #c4c4c4 !important;
            opacity: 0.7;
        }
        </style>
        
        <script>
        // Initialize chart data section visibility on page load
        jQuery(document).ready(function($) {
            function initChartDataSection() {
                var chartType = $('#cnw_chart_type').val();
                var barDataSection = $('#bar-chart-data');
                var groupedDataSection = $('#grouped-bar-chart-data');
                
                if (chartType === 'bar') {
                    barDataSection.show();
                    groupedDataSection.hide();
                } else if (chartType === 'grouped-bar') {
                    barDataSection.hide();
                    groupedDataSection.show();
                } else {
                    barDataSection.hide();
                    groupedDataSection.hide();
                }
            }
            
            // Initialize immediately
            initChartDataSection();
            
            // Also trigger when chart type changes
            $('#cnw_chart_type').on('change', initChartDataSection);
            
            // Sync color picker with text input
            $(document).on('input', 'input[type="color"]', function() {
                var colorValue = $(this).val();
                var textInput = $(this).siblings('input[type="text"]');
                textInput.val(colorValue);
            });
            
            // Sync text input with color picker
            $(document).on('input', 'input[name*="color_text"]', function() {
                var textValue = $(this).val();
                if (textValue.match(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/)) {
                    var colorInput = $(this).siblings('input[type="color"]');
                    colorInput.val(textValue);
                }
            });
        });
        </script>
        
        <?php
    }
    
    public function shortcode_meta_box_callback($post) {
        if ($post->ID) {
            $shortcode = '[cnw_chart id="' . $post->ID . '"]';
            echo '<p>Copy this shortcode to display the chart:</p>';
            echo '<input type="text" id="chart-shortcode" value="' . esc_attr($shortcode) . '" readonly style="width: 100%;" />';
            echo '<button type="button" id="copy-shortcode" class="button">Copy Shortcode</button>';
        } else {
            echo '<p>Save the chart first to generate a shortcode.</p>';
        }
    }
    
    public function save_chart_meta($post_id) {
        if (!isset($_POST['cnw_chart_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['cnw_chart_meta_nonce_field'], 'cnw_chart_meta_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['cnw_chart_type'])) {
            update_post_meta($post_id, '_cnw_chart_type', sanitize_text_field($_POST['cnw_chart_type']));
        }
        
        if (isset($_POST['cnw_chart_height'])) {
            $height = intval($_POST['cnw_chart_height']);
            if ($height < 200) $height = 200;
            if ($height > 1000) $height = 1000;
            update_post_meta($post_id, '_cnw_chart_height', $height);
        } else {
            update_post_meta($post_id, '_cnw_chart_height', 400); // Default height
        }
        
        // Process data items for bar chart
        if (isset($_POST['data_items']) && is_array($_POST['data_items'])) {
            $data_items = array();
            
            foreach ($_POST['data_items'] as $item) {
                if (!empty($item['label']) && !empty($item['value'])) {
                    $data_items[] = array(
                        'label' => sanitize_text_field($item['label']),
                        'prefix' => sanitize_text_field($item['prefix']),
                        'value' => floatval($item['value']),
                        'postfix' => sanitize_text_field($item['postfix']),
                        'color' => sanitize_text_field($item['color'])
                    );
                }
            }
            
            update_post_meta($post_id, '_cnw_chart_data', json_encode($data_items));
        }
        
        // Process chart groups for grouped bar chart
        if (isset($_POST['chart_groups']) && is_array($_POST['chart_groups'])) {
            $chart_groups = array();
            
            foreach ($_POST['chart_groups'] as $group) {
                if (!empty($group['group_name'])) {
                    $group_data = array(
                        'group_name' => sanitize_text_field($group['group_name']),
                        'color' => sanitize_text_field(isset($group['color']) ? $group['color'] : '#007cba'),
                        'items' => array()
                    );
                    
                    if (isset($group['items']) && is_array($group['items'])) {
                        foreach ($group['items'] as $item) {
                            if (!empty($item['label']) && !empty($item['value'])) {
                                $group_data['items'][] = array(
                                    'label' => sanitize_text_field($item['label']),
                                    'prefix' => sanitize_text_field($item['prefix']),
                                    'value' => floatval($item['value']),
                                    'postfix' => sanitize_text_field($item['postfix'])
                                );
                            }
                        }
                    }
                    
                    if (!empty($group_data['items'])) {
                        $chart_groups[] = $group_data;
                    }
                }
            }
            
            update_post_meta($post_id, '_cnw_chart_data', json_encode($chart_groups));
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('cnw-charts-frontend', CNW_CHARTS_PLUGIN_URL . 'assets/js/frontend.js', array('chartjs'), CNW_CHARTS_VERSION, true);
    }
    
    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post_type;
            if ('cnw_chart' === $post_type) {
                wp_enqueue_script('cnw-charts-admin', CNW_CHARTS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CNW_CHARTS_VERSION, true);
                wp_enqueue_style('cnw-charts-admin', CNW_CHARTS_PLUGIN_URL . 'assets/css/admin.css', array(), CNW_CHARTS_VERSION);
            }
        }
    }
    
    public function chart_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'cnw_chart');
        
        $chart_id = intval($atts['id']);
        
        if (!$chart_id) {
            return '<p>Invalid chart ID.</p>';
        }
        
        $post = get_post($chart_id);
        if (!$post || $post->post_type !== 'cnw_chart') {
            return '<p>Chart not found.</p>';
        }
        
        $chart_type = get_post_meta($chart_id, '_cnw_chart_type', true);
        $chart_data = json_decode(get_post_meta($chart_id, '_cnw_chart_data', true), true);
        $chart_height = get_post_meta($chart_id, '_cnw_chart_height', true);
        if (empty($chart_height)) {
            $chart_height = 400; // Default height
        }
        
        if (!$chart_data || empty($chart_data)) {
            return '<p>No chart data available.</p>';
        }
        
        $datasets = array();
        $chart_labels = array();
        
        if ($chart_type === 'bar') {
            // Simple Bar Chart - Single dataset with multiple colors
            $data_values = array();
            $colors = array();
            
            foreach ($chart_data as $item) {
                $chart_labels[] = $item['label'];
                $data_values[] = floatval($item['value']);
                $colors[] = $item['color'];
            }
            
            $datasets[] = array(
                'data' => $data_values,
                'backgroundColor' => $colors,
                'borderColor' => $colors,
                'borderWidth' => 1
            );
            
        } elseif ($chart_type === 'grouped-bar') {
            // Grouped Bar Chart - Multiple datasets, each with single color
            $all_labels = array();
            
            // First pass: collect all unique labels across all groups
            foreach ($chart_data as $group) {
                if (isset($group['items'])) {
                    foreach ($group['items'] as $item) {
                        if (!in_array($item['label'], $all_labels)) {
                            $all_labels[] = $item['label'];
                        }
                    }
                }
            }
            $chart_labels = $all_labels;
            
            // Second pass: create datasets for each group
            foreach ($chart_data as $group) {
                $group_data = array();
                
                // Initialize all values to 0
                foreach ($all_labels as $label) {
                    $group_data[$label] = 0;
                }
                
                // Fill in actual values
                if (isset($group['items'])) {
                    foreach ($group['items'] as $item) {
                        $group_data[$item['label']] = floatval($item['value']);
                    }
                }
                
                // Use group color
                $dataset_color = isset($group['color']) ? $group['color'] : '#007cba';
                
                $datasets[] = array(
                    'label' => $group['group_name'],
                    'data' => array_values($group_data),
                    'backgroundColor' => $dataset_color,
                    'borderColor' => $dataset_color,
                    'borderWidth' => 1
                );
            }
        }
        
        $chart_js_type = ($chart_type === 'grouped-bar') ? 'bar' : $chart_type;
        
        $chart_config = array(
            'type' => $chart_js_type,
            'data' => array(
                'labels' => $chart_labels,
                'datasets' => $datasets
            ),
            'options' => array(
                'responsive' => true,
                'maintainAspectRatio' => false,
                'resizeDelay' => 0,
                'plugins' => array(
                    'legend' => array(
                        'display' => ($chart_type === 'grouped-bar'),
                        'position' => 'top',
                        'align' => 'center',
                        'labels' => array(
                            'usePointStyle' => true,
                            'pointStyle' => 'rect',
                            'padding' => 20,
                            'font' => array(
                                'size' => 12
                            )
                        ),
                        'fullSize' => true
                    ),
                    'tooltip' => array(
                        'enabled' => true,
                        'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                        'titleColor' => '#ffffff',
                        'bodyColor' => '#ffffff',
                        'borderColor' => '#ddd',
                        'borderWidth' => 1,
                        'cornerRadius' => 4,
                        'displayColors' => true,
                        'callbacks' => array(
                            'label' => '%%TOOLTIP_CALLBACK%%'
                        )
                    )
                ),
                'layout' => array(
                    'padding' => array(
                        'top' => ($chart_type === 'grouped-bar') ? 30 : 0,
                        'bottom' => 10
                    )
                ),
                'scales' => array(
                    'y' => array(
                        'beginAtZero' => true,
                        'grid' => array(
                            'color' => '#e0e0e0'
                        ),
                        'ticks' => array(
                            'stepSize' => 10,
                            'font' => array(
                                'size' => 11
                            ),
                            'callback' => '%%Y_AXIS_CALLBACK%%'
                        )
                    ),
                    'x' => array(
                        'grid' => array(
                            'display' => false
                        ),
                        'ticks' => array(
                            'font' => array(
                                'size' => 11
                            )
                        )
                    )
                ),
                'interaction' => array(
                    'intersect' => false
                ),
                'hover' => array(
                    'mode' => 'index'
                )
            )
        );
        
        $unique_id = 'cnw-chart-' . $chart_id . '-' . uniqid();
        
        $output = '<div class="cnw-chart-container" style="width: 100%; max-width: 100%; height: ' . intval($chart_height) . 'px; margin: 20px 0; background: transparent; box-sizing: border-box;">';
        $output .= '<canvas id="' . esc_attr($unique_id) . '" style="width: 100%; height: 100%;"></canvas>';
        $output .= '</div>';
        // Prepare chart data with prefix/postfix for tooltips
        $chart_items_data = array();
        if ($chart_type === 'bar') {
            foreach ($chart_data as $item) {
                $chart_items_data[$item['label']] = array(
                    'prefix' => isset($item['prefix']) ? $item['prefix'] : '',
                    'postfix' => isset($item['postfix']) ? $item['postfix'] : ''
                );
            }
        } elseif ($chart_type === 'grouped-bar') {
            foreach ($chart_data as $group) {
                if (isset($group['items'])) {
                    foreach ($group['items'] as $item) {
                        if (!isset($chart_items_data[$item['label']])) {
                            $chart_items_data[$item['label']] = array();
                        }
                        $chart_items_data[$item['label']][$group['group_name']] = array(
                            'prefix' => isset($item['prefix']) ? $item['prefix'] : '',
                            'postfix' => isset($item['postfix']) ? $item['postfix'] : ''
                        );
                    }
                }
            }
        }
        
        // Replace the tooltip callback placeholder
        $config_json = json_encode($chart_config);
        $config_json = str_replace('"%%TOOLTIP_CALLBACK%%"', 'function(context) {
            var chartData = ' . json_encode($chart_items_data) . ';
            var label = context.label;
            var datasetLabel = context.dataset.label || "";
            var value = context.raw;
            
            var prefix = "";
            var postfix = "";
            
            if (chartData[label]) {
                if (datasetLabel && chartData[label][datasetLabel]) {
                    prefix = chartData[label][datasetLabel].prefix || "";
                    postfix = chartData[label][datasetLabel].postfix || "";
                } else if (chartData[label].prefix !== undefined) {
                    prefix = chartData[label].prefix || "";
                    postfix = chartData[label].postfix || "";
                }
            }
            
            var formattedValue = prefix + value + postfix;
            return (datasetLabel ? datasetLabel + ": " : "") + formattedValue;
        }', $config_json);
        
        // Replace the Y-axis callback placeholder
        $config_json = str_replace('"%%Y_AXIS_CALLBACK%%"', 'function(value, index, values) {
            var chartData = ' . json_encode($chart_items_data) . ';
            var prefix = "";
            var postfix = "";
            
            // For bar charts, get prefix/postfix from the first item
            // For grouped charts, use common format if all items have same prefix/postfix
            var firstKey = Object.keys(chartData)[0];
            if (firstKey) {
                if (typeof chartData[firstKey] === "object" && chartData[firstKey].prefix !== undefined) {
                    prefix = chartData[firstKey].prefix || "";
                    postfix = chartData[firstKey].postfix || "";
                } else {
                    // For grouped charts, check if all groups have the same format
                    var allPrefixes = [];
                    var allPostfixes = [];
                    Object.keys(chartData).forEach(function(label) {
                        Object.keys(chartData[label]).forEach(function(group) {
                            if (chartData[label][group].prefix !== undefined) {
                                allPrefixes.push(chartData[label][group].prefix);
                                allPostfixes.push(chartData[label][group].postfix);
                            }
                        });
                    });
                    
                    // Use common prefix/postfix if all are the same
                    if (allPrefixes.length > 0 && allPrefixes.every(p => p === allPrefixes[0])) {
                        prefix = allPrefixes[0] || "";
                    }
                    if (allPostfixes.length > 0 && allPostfixes.every(p => p === allPostfixes[0])) {
                        postfix = allPostfixes[0] || "";
                    }
                }
            }
            
            return prefix + value + postfix;
        }', $config_json);
        
        $output .= '<style>
            .cnw-chart-container {
                margin-bottom: 30px !important;
            }
        </style>';
        
        $output .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var ctx = document.getElementById("' . esc_js($unique_id) . '").getContext("2d");
                var chartConfig = ' . $config_json . ';
                var chart = new Chart(ctx, chartConfig);
                
                // Ensure chart resizes properly
                if (window.ResizeObserver) {
                    var resizeObserver = new ResizeObserver(function(entries) {
                        chart.resize();
                    });
                    resizeObserver.observe(ctx.canvas.parentElement);
                }
                
                // Handle window resize as well
                window.addEventListener("resize", function() {
                    chart.resize();
                });
            });
        </script>';
        
        return $output;
    }
    
    public function add_shortcode_copy_script() {
        global $post_type;
        if ('cnw_chart' === $post_type) {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var copyButton = document.getElementById('copy-shortcode');
                if (copyButton) {
                    copyButton.addEventListener('click', function() {
                        var shortcodeInput = document.getElementById('chart-shortcode');
                        shortcodeInput.select();
                        shortcodeInput.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        
                        copyButton.textContent = 'Copied!';
                        setTimeout(function() {
                            copyButton.textContent = 'Copy Shortcode';
                        }, 2000);
                    });
                }
            });
            </script>
            <?php
        }
    }
    
    public function activate() {
        $this->register_post_type();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
}

new CNWCharts();