<?php

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('documentation'), 'colspan' => '2')
);

foreach ($docs as $key => $val)
{
  $this->table->add_row(array('data' => lang($key), 'style' => 'width:30%;'), $val);
}

echo $this->table->generate();

?>

<?php $this->table->clear()?>
