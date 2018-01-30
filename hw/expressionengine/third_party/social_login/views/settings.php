<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login'.AMP.'method=save_settings');?>

<?php 
$this->table->set_template($cp_pad_table_template);

foreach ($settings as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();

$this->table->clear();
?>

<?=$providers?>

<p><?=form_submit('submit', lang('save'), 'class="submit"')?></p>

<?php
form_close();