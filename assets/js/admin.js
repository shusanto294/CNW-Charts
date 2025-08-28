jQuery(document).ready(function($) {
    // Copy shortcode functionality
    $(document).on('click', '#copy-shortcode', function() {
        var shortcodeInput = $('#chart-shortcode');
        shortcodeInput.select();
        shortcodeInput[0].setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        $(this).text('Copied!');
        setTimeout(function() {
            $('#copy-shortcode').text('Copy Shortcode');
        }, 2000);
    });
    
    // Chart type change handler
    $('#cnw_chart_type').on('change', function() {
        toggleChartDataSection();
    });
    
    // Add data item functionality
    $(document).on('click', '#add-data-item', function() {
        var container = $('#data-items-container');
        
        // Remove empty state message if it exists
        container.find('> p').remove();
        
        var index = container.children('.data-item').length;
        var newItem = $('<div class="data-item" data-index="' + index + '" style="display: flex; align-items: center; gap: 10px; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9;">' +
            '<div style="flex: 1;">' +
                '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Label:</label>' +
                '<input type="text" name="data_items[' + index + '][label]" placeholder="Item label" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
            '</div>' +
            '<div style="flex: 0 0 80px;">' +
                '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Prefix:</label>' +
                '<input type="text" name="data_items[' + index + '][prefix]" placeholder="$" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
            '</div>' +
            '<div style="flex: 1;">' +
                '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Value:</label>' +
                '<input type="number" step="0.01" name="data_items[' + index + '][value]" placeholder="Item value" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
            '</div>' +
            '<div style="flex: 0 0 80px;">' +
                '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Postfix:</label>' +
                '<input type="text" name="data_items[' + index + '][postfix]" placeholder="%" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
            '</div>' +
            '<div style="flex: 0 0 100px;">' +
                '<label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px; color: #666;">Color:</label>' +
                '<div style="display: flex; align-items: center; gap: 5px;">' +
                    '<input type="color" name="data_items[' + index + '][color]" value="#007cba" style="width: 40px; height: 32px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; padding: 0;" />' +
                    '<input type="text" name="data_items[' + index + '][color_text]" value="#007cba" placeholder="#007cba" style="width: 55px; padding: 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 11px;" />' +
                '</div>' +
            '</div>' +
            '<div style="flex: 0 0 auto;">' +
                '<button type="button" class="remove-data-item" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-top: 22px;">Remove</button>' +
            '</div>' +
            '</div>');
        container.append(newItem);
    });
    
    // Remove data item functionality
    $(document).on('click', '.remove-data-item', function() {
        var item = $(this).closest('.data-item');
        var container = $('#data-items-container');
        item.remove();
        
        // If no more data items, show empty state message
        if (container.children('.data-item').length === 0) {
            container.append('<p style="text-align: center; color: #666; margin: 20px 0;">No data items added yet. Click "Add Data Item" to get started.</p>');
        }
    });
    
    // Add chart group functionality
    $(document).on('click', '#add-chart-group', function() {
        var container = $('#chart-groups-container');
        
        // Remove empty state message if it exists
        container.find('> p').remove();
        
        var index = container.children('.chart-group').length;
        var newGroup = $('<div class="chart-group" data-group-index="' + index + '" style="border: 2px solid #007cba; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f0f8ff;">' +
            '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">' +
                '<h4 style="margin: 0; color: #007cba;">Group ' + (index + 1) + '</h4>' +
                '<div style="display: flex; gap: 8px;">' +
                    '<button type="button" class="duplicate-chart-group" data-group-index="' + index + '" style="background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Duplicate</button>' +
                    '<button type="button" class="remove-chart-group" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Remove Group</button>' +
                '</div>' +
            '</div>' +
            '<div style="display: flex; gap: 15px; margin-bottom: 15px;">' +
                '<div style="flex: 1;">' +
                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Group Name:</label>' +
                    '<input type="text" name="chart_groups[' + index + '][group_name]" placeholder="Enter group name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />' +
                '</div>' +
                '<div style="flex: 0 0 140px;">' +
                    '<label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Group Color:</label>' +
                    '<div style="display: flex; align-items: center; gap: 5px;">' +
                        '<input type="color" name="chart_groups[' + index + '][color]" value="#007cba" style="width: 40px; height: 32px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; padding: 0;" />' +
                        '<input type="text" name="chart_groups[' + index + '][color_text]" value="#007cba" placeholder="#007cba" style="width: 90px; padding: 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px;" />' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="group-data-items" data-group="' + index + '">' +
                '<h5 style="margin-bottom: 10px; color: #555;">Data Items:</h5>' +
                '<div class="group-items-container">' +
                    '<p style="text-align: center; color: #666; margin: 15px 0; font-style: italic;">No data items in this group yet. Click "Add Data Item" below.</p>' +
                '</div>' +
                '<button type="button" class="add-group-item" data-group="' + index + '" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 12px;">Add Data Item</button>' +
            '</div>' +
            '</div>');
        container.append(newGroup);
    });
    
    // Add group item functionality
    $(document).on('click', '.add-group-item', function() {
        var button = $(this);
        var groupIndex = button.data('group');
        var itemsContainer = button.siblings('.group-items-container');
        
        // Remove empty state message if it exists
        itemsContainer.find('p').remove();
        
        var itemIndex = itemsContainer.children('.group-data-item').length;
        var newItem = $('<div class="group-data-item" data-item-index="' + itemIndex + '" style="display: flex; align-items: center; gap: 10px; padding: 10px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 6px; background: white;">' +
            '<div style="flex: 1;">' +
                '<label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Label:</label>' +
                '<input type="text" name="chart_groups[' + groupIndex + '][items][' + itemIndex + '][label]" placeholder="Item label" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />' +
            '</div>' +
            '<div style="flex: 0 0 60px;">' +
                '<label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Prefix:</label>' +
                '<input type="text" name="chart_groups[' + groupIndex + '][items][' + itemIndex + '][prefix]" placeholder="$" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />' +
            '</div>' +
            '<div style="flex: 1;">' +
                '<label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Value:</label>' +
                '<input type="number" step="0.01" name="chart_groups[' + groupIndex + '][items][' + itemIndex + '][value]" placeholder="Item value" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />' +
            '</div>' +
            '<div style="flex: 0 0 60px;">' +
                '<label style="display: block; margin-bottom: 3px; font-weight: bold; font-size: 11px; color: #666;">Postfix:</label>' +
                '<input type="text" name="chart_groups[' + groupIndex + '][items][' + itemIndex + '][postfix]" placeholder="%" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;" />' +
            '</div>' +
            '<div style="flex: 0 0 auto;">' +
                '<button type="button" class="remove-group-item" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 3px; cursor: pointer; font-size: 11px; margin-top: 15px;">Remove</button>' +
            '</div>' +
            '</div>');
        itemsContainer.append(newItem);
    });
    
    // Remove group item functionality
    $(document).on('click', '.remove-group-item', function() {
        var item = $(this).closest('.group-data-item');
        var itemsContainer = item.parent();
        item.remove();
        
        // If no more items in group, show empty state message
        if (itemsContainer.children('.group-data-item').length === 0) {
            itemsContainer.append('<p style="text-align: center; color: #666; margin: 15px 0; font-style: italic;">No data items in this group yet. Click "Add Data Item" below.</p>');
        }
    });
    
    // Duplicate chart group functionality
    $(document).on('click', '.duplicate-chart-group', function() {
        var originalGroup = $(this).closest('.chart-group');
        var container = $('#chart-groups-container');
        var newIndex = container.children('.chart-group').length;
        
        // Clone the group
        var clonedGroup = originalGroup.clone();
        
        // Update group index and title
        clonedGroup.attr('data-group-index', newIndex);
        clonedGroup.find('h4').text('Group ' + (newIndex + 1));
        clonedGroup.find('.duplicate-chart-group').attr('data-group-index', newIndex);
        
        // Update all input names in the cloned group
        clonedGroup.find('input, select, textarea').each(function() {
            var name = $(this).attr('name');
            if (name) {
                // Replace the group index in the name
                var newName = name.replace(/\[(\d+)\]/, '[' + newIndex + ']');
                $(this).attr('name', newName);
            }
        });
        
        // Update group name input specifically
        var groupNameInput = clonedGroup.find('input[name*="group_name"]');
        var originalName = groupNameInput.val();
        groupNameInput.val(originalName + ' (Copy)');
        
        // Update data-group attributes for nested elements
        clonedGroup.find('.group-data-items').attr('data-group', newIndex);
        clonedGroup.find('.add-group-item').attr('data-group', newIndex);
        
        // Append the cloned group
        container.append(clonedGroup);
    });
    
    // Remove chart group functionality
    $(document).on('click', '.remove-chart-group', function() {
        var group = $(this).closest('.chart-group');
        var container = $('#chart-groups-container');
        group.remove();
        
        // If no more groups, show empty state message
        if (container.children('.chart-group').length === 0) {
            container.append('<p style="text-align: center; color: #666; margin: 20px 0;">No groups added yet. Click "Add Group" to get started.</p>');
        }
    });
    
    // Initialize on page load
    if ($('#cnw_chart_type').length) {
        toggleChartDataSection();
    }
});

function toggleChartDataSection() {
    var chartType = jQuery('#cnw_chart_type').val();
    var barDataSection = jQuery('#bar-chart-data');
    var groupedDataSection = jQuery('#grouped-bar-chart-data');
    
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