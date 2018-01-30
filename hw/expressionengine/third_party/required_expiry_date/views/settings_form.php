<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=required_expiry_date');?>

<?php

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('channel'), 'style' => 'width:30%;'),
    lang('expiry_question')
);

foreach ($channels as $channel_info)
{
  if ($channel_info['selected'])
  {
    $this->table->add_row($channel_info['channel_title'], lang('yes').NBS.NBS.form_radio($channel_info['channel_id'], 'yes', TRUE).NBS.NBS.NBS.NBS.lang('no').NBS.NBS.form_radio($channel_info['channel_id'], 'no', FALSE));
  }
  else
  {
    $this->table->add_row($channel_info['channel_title'], lang('yes').NBS.NBS.form_radio($channel_info['channel_id'], 'yes', FALSE).NBS.NBS.NBS.NBS.lang('no').NBS.NBS.form_radio($channel_info['channel_id'], 'no', TRUE));
  }
}

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>