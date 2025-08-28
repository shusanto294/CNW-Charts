# CNW Charts - WordPress Plugin

A powerful WordPress plugin that allows you to create beautiful, interactive charts using Chart.js and display them anywhere on your website using shortcodes.

## Features

- **Multiple Chart Types**: Bar, Line, Pie, Doughnut, Radar, and Polar Area charts
- **Custom Post Type**: Dedicated "Charts" post type for easy management
- **Shortcode Integration**: Display charts anywhere using simple shortcodes
- **Color Customization**: Set custom colors for each dataset
- **Responsive Design**: Charts automatically adapt to different screen sizes
- **Multiple Datasets**: Support for multiple data series (except pie/doughnut charts)
- **Easy Copy-Paste**: One-click shortcode copying from the admin interface

## Installation

1. Upload the `cnw-charts` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Charts' in your WordPress admin menu to start creating charts

## How to Use

### Creating a Chart

1. Go to **Charts** → **Add New Chart** in your WordPress admin
2. Enter a title for your chart
3. Configure your chart:
   - **Chart Type**: Choose from Bar, Line, Pie, Doughnut, Radar, or Polar Area
   - **Chart Dimensions**: Set width and height in pixels
   - **Labels**: Enter comma-separated labels (e.g., "January, February, March")
   - **Datasets**: Add one or more datasets with:
     - Dataset Label (legend name)
     - Data Values (comma-separated numbers)
     - Colors (comma-separated hex codes, optional)

### Chart Types

- **Bar Chart**: Perfect for comparing values across categories
- **Line Chart**: Great for showing trends over time
- **Pie Chart**: Ideal for showing parts of a whole (single dataset only)
- **Doughnut Chart**: Similar to pie chart with a hollow center (single dataset only)
- **Radar Chart**: Excellent for comparing multiple variables
- **Polar Area Chart**: Good for showing data on a circular scale (single dataset only)

### Using Shortcodes

1. After saving your chart, copy the shortcode from the "Shortcode" meta box
2. Paste the shortcode anywhere in your posts, pages, or widgets
3. The shortcode format is: `[cnw_chart id="123"]`

### Example Data Format

**Labels**: `January, February, March, April, May`

**Dataset Data**: `10, 19, 3, 5, 2`

**Colors**: `#ff6384, #36a2eb, #ffce56, #4bc0c0, #9966ff`

## Chart Configuration Examples

### Simple Bar Chart
- **Type**: Bar Chart
- **Labels**: `Product A, Product B, Product C, Product D`
- **Dataset Label**: `Sales 2023`
- **Data**: `120, 190, 30, 50`
- **Colors**: `#ff6384, #36a2eb, #ffce56, #4bc0c0`

### Multi-Dataset Line Chart
- **Type**: Line Chart
- **Labels**: `Jan, Feb, Mar, Apr, May`
- **Dataset 1**: 
  - Label: `2022 Sales`
  - Data: `65, 59, 80, 81, 56`
- **Dataset 2**: 
  - Label: `2023 Sales`
  - Data: `28, 48, 40, 19, 86`

### Pie Chart
- **Type**: Pie Chart
- **Labels**: `Desktop, Mobile, Tablet`
- **Dataset Label**: `Traffic Sources`
- **Data**: `55, 35, 10`
- **Colors**: `#ff6384, #36a2eb, #ffce56`

## Troubleshooting

### Charts Not Displaying
- Ensure Chart.js is loaded (the plugin loads it automatically)
- Check that the shortcode ID matches an existing chart
- Verify that the chart has data configured

### Styling Issues
- Charts are responsive by default
- You can override dimensions using the width/height settings
- Custom CSS can be applied to the chart container

### Data Not Saving
- Ensure you have proper permissions to edit posts
- Check that all required fields are filled
- Verify that data values are numeric and comma-separated

## Support

For support and bug reports, please contact the plugin developer.

## Version History

**Version 1.0.0**
- Initial release
- Multiple chart types support
- Shortcode functionality
- Custom post type implementation
- Color customization
- Responsive design