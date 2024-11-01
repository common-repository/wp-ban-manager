			<fieldset class="options">
				<table class="widefat" width="100%" cellpadding="5px">
					<tr>
						<th width="40%"><?php _e("Ban name");?></th>
						<th width="20%"><?php _e("Duration");?></th>
						<th width="20%"><?php _e("Period");?></th>
						<th width="20%"><?php _e("Options");?></th>
					</tr>
<?php $alt = true;
foreach($ban_definitions as $key => $definition) {
	if ($alt) {
		$class=" class='alternate'";
		$alt = false;
	} else {
		$class="";
		$alt = true;
	}?>
					<tr<?php echo $class ?>>
						<td><?php echo $definition['name']; ?></td>
						<td><?php echo $definition['duration']; ?></td>
						<td><?php echo WP_Ban_Manager::get_timeframe_name($definition['duration_period']); ?></td>
						<td>
							<a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&edit_definition=<?php echo $key ?>">Edit</a> |
							<a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&delete_definition=<?php echo $key ?>">Delete</a>
						</td>
					</tr>
<?php } ?>
					<tr>
						<td colspan="2"><a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&add_definition">Add ban type</a></td>
					</tr>
				</table>
			</fieldset>