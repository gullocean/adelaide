<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
	lang('subscriber_form_name'),
	lang('subscriber_token'),
	lang('duplicate'),
	lang('delete')
);
foreach ($this->subscriber_forms_model->get() as $row) 
{ 
	$this->table->add_row(
		anchor($base.AMP.'method=view'.AMP.'form_id='.$row->id, $row->form_name),
		array(
			'data' => '{exp:subscriber:form form_id="' . $row->id . '"}',
			'class' => 'code'
		),
		anchor($base.AMP.'method=duplicate'.AMP.'form_id='.$row->id, lang('duplicate'), 'class="duplicate"'),
		anchor($base.AMP.'method=delete'.AMP.'form_id='.$row->id, lang('delete'), 'class="delete"')
	);
}
echo $this->table->generate();
?>