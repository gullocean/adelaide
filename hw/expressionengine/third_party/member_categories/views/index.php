<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=index'?>"><?=lang('members')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=settings'?>"><?=lang('settings')?></a>  </li> 
</ul> 
<div class="clear_left shun"></div> 


<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=index', array('id'=>'member_categories_search_form'))?>
	<div id="filterMenu">
		<fieldset>
			<legend><?=lang('total_members')?> <?=$total_count?></legend>

			<p>
				<?=form_label(lang('keywords').NBS, 'search', array('class' => 'field js_hide'))?>
				<?=form_input(array('id'=>'keyword', 'name'=>'keyword', 'class'=>'field', 'placeholder' => lang('keywords'), 'value'=>$selected['keyword']))?> 
                
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<?=form_label(lang('filter_by'), 'filter_by')?>&nbsp;
				<?=form_dropdown('filter_by', $filter_by, $selected['filter_by'], 'id="filter_by"')?> 
                
			</p>
			<p>
				<?=form_label(lang('member_group'), 'group_id')?>&nbsp;
				<?=form_dropdown('group_id', $member_groups, $selected['group_id'], 'id="group_id"')?> 
	
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                
                <?=form_label(lang('category'), 'category_id')?>&nbsp;
				<?=form_dropdown('category_id', $categories, $selected['category_id'], 'id="category_id"')?> 
	
				
	
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;


				<?=form_submit('submit', lang('search'))?>
			</p>
		</fieldset>
	</div>
<?=form_close()?>



<div style="padding: 10px;">

<?php if ($total_count == 0):?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_results')?></p>
	</div>
<?php else:?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading($table_headings);

		echo $this->table->generate($members);
	?>



<span class="pagination"><?=$pagination?></span>


<?php endif; /* if $total_count > 0*/?>

</div>


