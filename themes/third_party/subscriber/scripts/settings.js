$.subscriber = {};

$.subscriber.field_check = function() {
	var switch_visible = function() {
		if ($('input[name=provider]:checked').val() == 'mailchimp') {
			$('input.campaignmonitor').parents('tr').siblings().show().end().hide();
		} else if ($('input[name=provider]:checked').val() == 'campaign_monitor') {
			$('input.mailchimp').parents('tr').siblings().show().end().hide();
		};	
	};
	
	switch_visible();
	
	$('input[name=provider]').change(function() {
		switch_visible();
	});
};

$.subscriber.custom_fields_add = function() {
	var $custom_field_table = $('#mainContent table.mainTable:last');
	
	$('.add').click(function(event) {
		event.preventDefault();

		var $last_row = $custom_field_table.find('tr:last'),
			row_count = $custom_field_table.find('tbody tr:visible').size(),
			$new_row = $last_row.clone();

		// Remove the values from the fields and rename the name attribute
		$new_row.find('input[type=text]').each(function(index) {
			$input = $(this);
			$input.val('').attr('name', $input.attr('name').replace(/(.*?)\[[0-9]+\](.*?)/, '$1[' + row_count + ']$2'));
		});

		// Add the new row
		$custom_field_table.find('tbody').append($new_row);
	});
};

$.subscriber.custom_fields_delete = function() {
	var $custom_field_table = $('#mainContent table.mainTable:last');
	
	$('.delete').click(function(event) {
		event.preventDefault();

		var row_count = $custom_field_table.find('tbody tr:visible').size();
		
		if (row_count > 1) {
			$(this).parents('tr').remove();
		} else {
			$(this).parents('tr').find('input[type=text]').val('');
		};

		// Renumber rows
		$custom_field_table.find('tr').each(function(index) {
			$(this).find('input[type=text]').each(function(input_index) {
				$(this).prop('name', $(this).attr('name').replace(/(.*?)\[[0-9]+\](.*?)/, '$1[' + index + ']$2'));
			});
		});
	});
};

$.subscriber.validate = function() {
	$('#api_key').blur(function() {
		validate(EE.subscriber.lang.api_key_missing, $(this));
	});

	$('#list_id').blur(function() {
		validate(EE.subscriber.lang.list_id_missing, $(this));
	});

	$('#switch_field, #switch_value').blur(function() {
		if ($('#method_switch:checked').size()) {
			validate(EE.subscriber.lang.switch_field_missing, $(this));
		};
	});

	var validate = function(error_message, $field) {
		if ($field.val() == "") {
			$.ee_notice(error_message, {"type": "error"});
			$field.addClass('error').data('error', 'y');
		} else if ($field.data('error') == 'y') {
			$.ee_notice.destroy();
			$field.removeClass('error').data('error', 'n');
		};
	};
};

$.subscriber.multiple_display = function() {
	$('input[name=provider]').click(function(event) {
		$('.mailchimp, .campaign_monitor')
			.removeClass('mailchimp campaign_monitor')
			.addClass($(this).val());
	});
};

$.subscriber.field_check();
$.subscriber.validate();
$.subscriber.custom_fields_add();
$.subscriber.custom_fields_delete();
$.subscriber.multiple_display();
