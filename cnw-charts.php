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
        add_action('admin_init', array($this, 'handle_export_import'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_menu', array($this, 'add_import_export_menu'));
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
                            <option value="pie" <?php selected($chart_type, 'pie'); ?>>Pie Chart</option>
                            <option value="doughnut" <?php selected($chart_type, 'doughnut'); ?>>Donut Chart</option>
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
                <tr id="pie-chart-data" style="display: none;">
                    <th scope="row">Chart Data</th>
                    <td>
                        <div id="pie-data-items-container">
                            <?php
                            // Only load pie data if chart type is pie or doughnut
                            $pie_data = array();
                            if ($chart_type === 'pie' || $chart_type === 'doughnut') {
                                $pie_data = json_decode($chart_data, true) ?: array();
                            }
                            
                            if (empty($pie_data)) {
                                echo '<p style="text-align: center; color: #666; margin: 20px 0;">No data items added yet. Click "Add Data Item" to get started.</p>';
                            } else {
                                foreach ($pie_data as $index => $item) {
                                    ?>
                                    <div class="pie-data-item" data-index="<?php echo $index; ?>" style="display: flex; align-items: center; gap: 10px; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9;">
                                        <div style="flex: 1;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Label:</label>
                                            <input type="text" name="pie_data_items[<?php echo $index; ?>][label]" value="<?php echo esc_attr($item['label']); ?>" placeholder="Item label" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 0 0 80px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Prefix:</label>
                                            <input type="text" name="pie_data_items[<?php echo $index; ?>][prefix]" value="<?php echo esc_attr(isset($item['prefix']) ? $item['prefix'] : ''); ?>" placeholder="$" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 1;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Value:</label>
                                            <input type="number" step="0.01" name="pie_data_items[<?php echo $index; ?>][value]" value="<?php echo esc_attr($item['value']); ?>" placeholder="Item value" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 0 0 80px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Postfix:</label>
                                            <input type="text" name="pie_data_items[<?php echo $index; ?>][postfix]" value="<?php echo esc_attr(isset($item['postfix']) ? $item['postfix'] : ''); ?>" placeholder="%" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                                        </div>
                                        <div style="flex: 0 0 100px;">
                                            <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Color:</label>
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="color" name="pie_data_items[<?php echo $index; ?>][color]" value="<?php echo esc_attr($item['color']); ?>" style="width: 40px; height: 32px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; padding: 0;" />
                                                <input type="text" name="pie_data_items[<?php echo $index; ?>][color_text]" value="<?php echo esc_attr($item['color']); ?>" placeholder="#007cba" style="width: 55px; padding: 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 11px;" />
                                            </div>
                                        </div>
                                        <div style="flex: 0 0 auto;">
                                            <button type="button" class="remove-pie-data-item" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-top: 22px;">Remove</button>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" id="add-pie-data-item" onclick="addPieDataItem()" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 15px; font-size: 14px;">Add Data Item</button>
                        <script>
                        function addPieDataItem() {
                            console.log('Add pie data item clicked - inline function');
                            var container = document.getElementById('pie-data-items-container');
                            if (!container) {
                                console.error('Container not found');
                                return;
                            }
                            
                            // Remove empty state message if it exists
                            var emptyMessage = container.querySelector('p');
                            if (emptyMessage) {
                                emptyMessage.remove();
                            }
                            
                            var index = container.querySelectorAll('.pie-data-item').length;
                            
                            // Color palette for pie chart slices
                            var colorPalette = [
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
                                '#36A2EB', '#FFCE56'
                            ];
                            var itemColor = colorPalette[index % colorPalette.length];
                            
                            var newItemHTML = '<div class="pie-data-item" data-index="' + index + '" style="display: flex; align-items: center; gap: 10px; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9;">' +
                                '<div style="flex: 1;">' +
                                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Label:</label>' +
                                    '<input type="text" name="pie_data_items[' + index + '][label]" placeholder="Item label" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
                                '</div>' +
                                '<div style="flex: 0 0 80px;">' +
                                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Prefix:</label>' +
                                    '<input type="text" name="pie_data_items[' + index + '][prefix]" placeholder="$" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
                                '</div>' +
                                '<div style="flex: 1;">' +
                                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Value:</label>' +
                                    '<input type="number" step="0.01" name="pie_data_items[' + index + '][value]" placeholder="Item value" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
                                '</div>' +
                                '<div style="flex: 0 0 80px;">' +
                                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Postfix:</label>' +
                                    '<input type="text" name="pie_data_items[' + index + '][postfix]" placeholder="%" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
                                '</div>' +
                                '<div style="flex: 0 0 100px;">' +
                                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Color:</label>' +
                                    '<div style="display: flex; align-items: center; gap: 5px;">' +
                                        '<input type="color" name="pie_data_items[' + index + '][color]" value="' + itemColor + '" style="width: 40px; height: 32px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; padding: 0;" />' +
                                        '<input type="text" name="pie_data_items[' + index + '][color_text]" value="' + itemColor + '" placeholder="' + itemColor + '" style="width: 55px; padding: 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 11px;" />' +
                                    '</div>' +
                                '</div>' +
                                '<div style="flex: 0 0 auto;">' +
                                    '<button type="button" class="remove-pie-data-item" onclick="removePieDataItem(this)" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-top: 22px;">Remove</button>' +
                                '</div>' +
                            '</div>';
                            
                            container.insertAdjacentHTML('beforeend', newItemHTML);
                        }
                        
                        function removePieDataItem(button) {
                            var item = button.closest('.pie-data-item');
                            var container = document.getElementById('pie-data-items-container');
                            item.remove();
                            
                            // If no more data items, show empty state message
                            if (container.querySelectorAll('.pie-data-item').length === 0) {
                                container.innerHTML = '<p style="text-align: center; color: #666; margin: 20px 0;">No data items added yet. Click "Add Data Item" to get started.</p>';
                            }
                        }
                        </script>
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
                var pieDataSection = $('#pie-chart-data');
                
                if (chartType === 'bar') {
                    barDataSection.show();
                    groupedDataSection.hide();
                    pieDataSection.hide();
                } else if (chartType === 'grouped-bar') {
                    barDataSection.hide();
                    groupedDataSection.show();
                    pieDataSection.hide();
                } else if (chartType === 'pie' || chartType === 'doughnut') {
                    barDataSection.hide();
                    groupedDataSection.hide();
                    pieDataSection.show();
                } else {
                    barDataSection.hide();
                    groupedDataSection.hide();
                    pieDataSection.hide();
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
        
        // Process data items for pie/donut chart
        if (isset($_POST['pie_data_items']) && is_array($_POST['pie_data_items'])) {
            $pie_data_items = array();
            
            // Default color palette for pie charts
            $default_colors = array(
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
                '#36A2EB', '#FFCE56'
            );
            
            $index = 0;
            foreach ($_POST['pie_data_items'] as $item) {
                if (!empty($item['label']) && !empty($item['value'])) {
                    // Use provided color or default from palette
                    $item_color = !empty($item['color']) ? $item['color'] : $default_colors[$index % count($default_colors)];
                    
                    $pie_data_items[] = array(
                        'label' => sanitize_text_field($item['label']),
                        'prefix' => sanitize_text_field($item['prefix']),
                        'value' => floatval($item['value']),
                        'postfix' => sanitize_text_field($item['postfix']),
                        'color' => sanitize_text_field($item_color)
                    );
                    $index++;
                }
            }
            
            update_post_meta($post_id, '_cnw_chart_data', json_encode($pie_data_items));
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
            
        } elseif ($chart_type === 'pie' || $chart_type === 'doughnut') {
            // Pie/Donut Chart - Single dataset with multiple colors
            $data_values = array();
            $colors = array();
            
            // Default color palette for pie charts
            $default_colors = array(
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
                '#36A2EB', '#FFCE56'
            );
            
            foreach ($chart_data as $index => $item) {
                $chart_labels[] = $item['label'];
                $data_values[] = floatval($item['value']);
                // Use item color if available, otherwise fall back to palette
                $item_color = !empty($item['color']) ? $item['color'] : $default_colors[$index % count($default_colors)];
                $colors[] = $item_color;
            }
            
            $datasets[] = array(
                'data' => $data_values,
                'backgroundColor' => $colors,
                'borderColor' => '#ffffff',
                'borderWidth' => 2
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
                        'display' => ($chart_type === 'grouped-bar' || $chart_type === 'pie' || $chart_type === 'doughnut'),
                        'position' => 'top',
                        'align' => 'center',
                        'labels' => array(
                            'usePointStyle' => true,
                            'pointStyle' => ($chart_type === 'pie' || $chart_type === 'doughnut') ? 'circle' : 'rect',
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
                        'top' => ($chart_type === 'grouped-bar' || $chart_type === 'pie' || $chart_type === 'doughnut') ? 30 : 0,
                        'bottom' => 10
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
        
        // Add scales only for bar charts (pie/donut don't use scales)
        if ($chart_type === 'bar' || $chart_type === 'grouped-bar') {
            $chart_config['options']['scales'] = array(
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
            );
        }
        
        $unique_id = 'cnw-chart-' . $chart_id . '-' . uniqid();
        
        $output = '<div class="cnw-chart-container" style="width: 100%; max-width: 100%; height: ' . intval($chart_height) . 'px; margin: 20px 0; background: transparent; box-sizing: border-box;">';
        $output .= '<canvas id="' . esc_attr($unique_id) . '" style="width: 100%; height: 100%;"></canvas>';
        $output .= '</div>';
        // Prepare chart data with prefix/postfix for tooltips
        $chart_items_data = array();
        if ($chart_type === 'bar' || $chart_type === 'pie' || $chart_type === 'doughnut') {
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
    
    public function add_import_export_menu() {
        add_submenu_page(
            'edit.php?post_type=cnw_chart',
            'Import/Export Charts',
            'Import/Export',
            'manage_options',
            'cnw-charts-import-export',
            array($this, 'import_export_page')
        );
    }
    
    public function import_export_page() {
        $charts = get_posts(array(
            'post_type' => 'cnw_chart',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        $chart_count = count($charts);
        ?>
        <div class="wrap">
            <h1>📊 CNW Charts - Import/Export</h1>
            <p>Transfer your charts between WordPress sites using CSV files.</p>
            
            <div style="display: flex; gap: 30px; margin-top: 30px;">
                <!-- Export Section -->
                <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0; color: #2271b1;">📥 Export Charts</h2>
                    <p>Export all your charts to a CSV file that can be imported on another WordPress site.</p>
                    
                    <div style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; margin: 15px 0;">
                        <strong>📈 Charts Available:</strong> <?php echo $chart_count; ?> chart<?php echo $chart_count !== 1 ? 's' : ''; ?>
                    </div>
                    
                    <?php if ($chart_count > 0): ?>
                        <p><strong>What will be exported:</strong></p>
                        <ul style="margin-left: 20px;">
                            <li>Chart titles and configurations</li>
                            <li>Chart types (bar/grouped bar)</li>
                            <li>Custom heights</li>
                            <li>All data items with values, colors, prefix/suffix</li>
                        </ul>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?action=export_charts_csv')); ?>" 
                           class="button button-primary button-large" 
                           style="margin-top: 15px; text-decoration: none;">
                            📥 Export All Charts to CSV
                        </a>
                    <?php else: ?>
                        <p style="color: #646970; font-style: italic;">No charts available to export. Create some charts first!</p>
                    <?php endif; ?>
                </div>
                
                <!-- Import Section -->
                <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0; color: #2271b1;">📤 Import Charts</h2>
                    <p>Import charts from a CSV file exported from another CNW Charts installation.</p>
                    
                    <form method="post" enctype="multipart/form-data" id="import-form">
                        <?php wp_nonce_field('import_charts', 'import_charts_nonce'); ?>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffecb5; border-radius: 4px; padding: 15px; margin: 15px 0;">
                            <strong>⚠️ Important:</strong> Only upload CSV files exported from CNW Charts plugin.
                        </div>
                        
                        <p><strong>Requirements:</strong></p>
                        <ul style="margin-left: 20px; margin-bottom: 20px;">
                            <li>CSV file exported from CNW Charts</li>
                            <li>Valid chart data format</li>
                            <li>Supported chart types: bar, grouped-bar</li>
                        </ul>
                        
                        <div style="border: 2px dashed #c3c4c7; border-radius: 8px; padding: 30px; text-align: center; background: #fafafa; margin: 20px 0;">
                            <input type="file" name="csv_file" accept=".csv" required id="csv-file-input" style="margin-bottom: 15px;">
                            <br>
                            <label for="csv-file-input" style="color: #646970;">Select your CSV file to import</label>
                        </div>
                        
                        <input type="submit" name="import_charts_csv" value="📤 Import Charts from CSV" 
                               class="button button-primary button-large" style="margin-top: 15px;">
                    </form>
                </div>
            </div>
            
            <div style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin: 30px 0;">
                <h3 style="margin-top: 0;">💡 How it works:</h3>
                <ol style="margin-left: 20px;">
                    <li><strong>Export:</strong> Click "Export All Charts to CSV" to download a file containing all your charts</li>
                    <li><strong>Transfer:</strong> Copy the CSV file to your target WordPress site</li>
                    <li><strong>Import:</strong> Upload the CSV file using the import form above</li>
                    <li><strong>Done:</strong> Your charts will be recreated with all settings and data intact</li>
                </ol>
            </div>
        </div>
        
        <style>
        .wrap h2 {
            font-size: 18px;
            font-weight: 600;
        }
        .button-large {
            padding: 10px 20px;
            height: auto;
            font-size: 14px;
        }
        </style>
        <?php
    }
    
    public function handle_export_import() {
        // Handle CSV export
        if (isset($_GET['action']) && $_GET['action'] === 'export_charts_csv' && current_user_can('manage_options')) {
            $this->export_charts_csv();
        }
        
        // Handle CSV import
        if (isset($_POST['import_charts_csv']) && wp_verify_nonce($_POST['import_charts_nonce'], 'import_charts') && current_user_can('manage_options')) {
            $this->import_charts_csv();
        }
    }
    
    public function admin_notices() {
        if (isset($_GET['import_success'])) {
            $count = intval($_GET['import_success']);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Successfully imported %d charts.'), $count) . '</p></div>';
        }
        if (isset($_GET['import_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($_GET['import_error']) . '</p></div>';
        }
    }
    
    public function export_charts_csv() {
        $charts = get_posts(array(
            'post_type' => 'cnw_chart',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        if (empty($charts)) {
            wp_die('No charts found to export.');
        }
        
        $filename = 'cnw-charts-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, array(
            'Chart Title',
            'Chart Type', 
            'Chart Height',
            'Chart Data (JSON)'
        ));
        
        foreach ($charts as $chart) {
            $chart_type = get_post_meta($chart->ID, '_cnw_chart_type', true);
            $chart_height = get_post_meta($chart->ID, '_cnw_chart_height', true);
            $chart_data = get_post_meta($chart->ID, '_cnw_chart_data', true);
            
            fputcsv($output, array(
                $chart->post_title,
                $chart_type,
                $chart_height ? $chart_height : '400',
                $chart_data
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public function import_charts_csv() {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('edit.php?post_type=cnw_chart&page=cnw-charts-import-export&import_error=' . urlencode('Please select a valid CSV file.')));
            exit;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            wp_redirect(admin_url('edit.php?post_type=cnw_chart&page=cnw-charts-import-export&import_error=' . urlencode('Could not read CSV file.')));
            exit;
        }
        
        // Skip header row
        fgetcsv($handle);
        
        $imported_count = 0;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 4) continue;
            
            $chart_title = sanitize_text_field($data[0]);
            $chart_type = sanitize_text_field($data[1]);
            $chart_height = intval($data[2]);
            $chart_data = $data[3];
            
            // Validate chart type
            if (!in_array($chart_type, array('bar', 'grouped-bar', 'pie', 'doughnut'))) {
                continue;
            }
            
            // Create new chart post
            $post_id = wp_insert_post(array(
                'post_title' => $chart_title,
                'post_type' => 'cnw_chart',
                'post_status' => 'publish'
            ));
            
            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_cnw_chart_type', $chart_type);
                update_post_meta($post_id, '_cnw_chart_height', $chart_height ? $chart_height : 400);
                update_post_meta($post_id, '_cnw_chart_data', $chart_data);
                $imported_count++;
            }
        }
        
        fclose($handle);
        
        wp_redirect(admin_url('edit.php?post_type=cnw_chart&page=cnw-charts-import-export&import_success=' . $imported_count));
        exit;
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