<ul>
<?php foreach($categories as $id => $category):?>

<?php
for ($i=0; $i<$category->start_tag; $i++)
{
    echo '<ul>';
}
for ($i=0; $i<$category->end_tag; $i++)
{
    echo '</ul>';
}
?>
<li>
    <label>
    	<?=repeater(NBS.NBS.NBS.NBS, $category->level - 1)?>

    	<?=form_checkbox('category[]', $id, $category->selected).NBS.NBS.$category->name?>
    </label>
</li>		
<?php
for ($i=0; $i<$category->last_tag; $i++)
{
    echo '</ul>';
}
?>
<?php endforeach;?>
</ul>