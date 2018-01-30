<?=form_open($form_base.AMP.'method=save');?>
<?php
if (isset($form_id))
{
	echo form_hidden('form_id', $form_id);
}
?>
<h3>Main Settings</h3>
	<?php
	// Main Settings Table
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('preference'), 'style' => 'width:50%;'),
	    lang('setting')
	);
	foreach ($settings_main as $lang_key => $field_settings)
	{
		$this->table->add_row(lang("settings_{$lang_key}", $lang_key), $field_settings);
	}
	echo $this->table->generate();
	$this->table->clear();
	?>

	<h3>Switch Field Settings</h3>
	<?php
	// Switch Field Settings Table
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('preference'), 'style' => 'width:50%;'),
	    lang('setting')
	);
	foreach ($settings_switch as $key => $val)
	{
		$this->table->add_row(lang("settings_{$key}", $key), $val);
	}
	echo $this->table->generate();
	$this->table->clear();
	?>

	<h3>Custom Fields</h3>
	<p style="margin: -6px 0 6px; line-height: 1.6"><?= lang('settings_custom_field_help') ?></p>
	<div class="custom-fields <?= $form['provider'] ?>">
		<?php
		// Custom Field Settings Table
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
		    lang('settings_custom_field'),
		    lang('settings_custom_field_tag'),
		    array('data' => lang('settings_custom_field_multiple'), 'class' => 'multiple'),
			lang('delete')
		);
		if (isset($custom_fields) AND count($custom_fields))
		{
			foreach ($custom_fields as $index => $field)
			{
				$this->table->add_row(
					form_input("custom_fields[{$index}][name]", $field['name']),
					form_input("custom_fields[{$index}][tag]", $field['tag']),
					array('data' => form_checkbox("custom_fields[{$index}][multiple]", 'yes', $field['multiple']), 'class' => 'multiple'),
					anchor($base.AMP.'method=delete_custom_field', 'Delete', 'class="delete"')
				);
			}
		}
		else
		{
			$this->table->add_row(
				form_input('custom_fields[0][name]'),
				form_input('custom_fields[0][tag]'),
				array('data' => form_checkbox("custom_fields[0][multiple]", 'yes', FALSE), 'class' => 'multiple'),
				anchor($base.AMP.'method=delete_custom_field', 'Delete', 'class="delete"')
			);
		}
		echo $this->table->generate();
		$this->table->clear();
		
		echo anchor('#', lang('add_custom_field'), 'class="add"');
		?>
	</div> <!-- .custom-fields -->

	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
	<?php $this->table->clear() ?>
<?=form_close()?>
<?php
/* End of file index.php */
/* Location: ./system/expressionengine/third_party/link_truncator/views/index.php */
