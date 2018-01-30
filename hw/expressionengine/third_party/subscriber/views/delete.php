<?=form_open($form_base.AMP.'method=delete', '', array('form_id' => $form_id))?>
	<p><strong><?=lang('form_delete_confirm')?></strong></p>
	<p class="notice"><?=lang('action_can_not_be_undone')?></p>
	<p><?=form_submit('form_delete', lang('form_delete'), 'class="submit"')?></p>
<?=form_close()?>