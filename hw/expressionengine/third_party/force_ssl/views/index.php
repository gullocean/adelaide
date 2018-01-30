<?php
echo form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=force_ssl');

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
	array('data' => lang('preferences'), 'style' => 'width:50%;'),
	lang('setting')
);

foreach ($settings as $key => $val) {
	$this->table->add_row(lang($key, $key), $val);
}

$this->table->add_row('', '<a href="#" onclick="$(\'#advanced\').slideToggle();$(this).closest(\'tr\').remove();return false;">'.lang('show_advanced_preferences').'</a>');

echo $this->table->generate();
?>
<div id="advanced" style="display: none;">
<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
	array('data' => lang('advanced_preferences'), 'style' => 'width:50%;'),
	lang('setting')
);

foreach ($advanced as $key => $val) {
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();
?>
</div>
<script type="text/javascript">
$('[name=license]').change(function() {
	var val = $(this).val();
	if (val.match(/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/i)) {
		$(this).css('border-color', '#0b0');
	} else {
		$(this).css('border-color', '#c00');
	}
});
</script>
<p><?php echo form_submit('submit', lang('submit'), 'class="submit"'); ?></p>
<?php $this->table->clear(); ?>
<?php echo form_close();
/* End of file index.php */
/* Location: ./system/expressionengine/third_party/force_ssl/views/index.php */
