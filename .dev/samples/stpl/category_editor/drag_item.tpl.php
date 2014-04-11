	<li id="item_<?php echo $replace['item_id']; ?>">
		<div class="dropzone"></div>
		<dl>
			<a href="#" class="expander"><i class="icon"></i></a>
<?php if ($replace['link'] != '') { ?>
			<a href="<?php echo $replace['link']; ?>"><?php if ($replace['icon_class'] != '') { ?><i class="icon icon-<?php echo $replace['icon_class']; ?>"></i> <?php } ?><?php echo $replace['name']; ?></a>
<?php } else { ?>
			<?php if ($replace['icon_class'] != '') { ?><i class="icon icon-<?php echo $replace['icon_class']; ?>"></i> <?php } ?><?php echo $replace['name']; ?>
<?php } ?>
			<span class="move" title="<?php echo t('Move'); ?>"><i class="icon icon-move"></i></span>
			<div style="float:right;display:none;" class="controls_over">
<?php echo _class("form2")->tpl_row('tbl_link_edit',$replace,'','',''); ?>
<?php echo _class("form2")->tpl_row('tbl_link_delete',$replace,'','',''); ?>
<?php echo _class("form2")->tpl_row('tbl_link_clone',$replace,'','',''); ?>
<?php echo _class("form2")->tpl_row('tbl_link_active',$replace,'','',''); ?>
			</div>
		</dl>
<?php if ($replace['have_children'] == '1') { ?>
		<ul>
<?php } else { ?>
	</li>
	<?php if ($replace['next_level_diff'] != '0') { ?>
		<?php $__f_total = count($replace['next_level_diff']); foreach (is_array($replace['next_level_diff']) ? $replace['next_level_diff'] : range(1, (int)$replace['next_level_diff']) as $_k => $_v) {$__f_counter++; ?></ul><?php } ?>
	<?php } ?>
<?php } ?>