			<fieldset class="options">
				<table class="widefat" width="100%" cellpadding="5px">
					<tr>
						<th width="20%"><?php echo "IP";?></th>
						<th width="30%"><?php echo "Name";?></th>
						<th width="10%"><?php echo "Ban type";?></th>
						<th width="15%"><?php echo "Start date";?></th>
						<th width="5%"><?php echo "Active";?></th>
						<th width="20%"><?php echo "Options";?></th>
					</tr>
<?php $alt = true;
foreach($current_bans as $key => $ban) {
	if ($alt) {
		$class=" class='alternate'";
		$alt = false;
	} else {
		$class="";
		$alt = true;
	}?>
					<tr<?php echo $class ?>>
						<td><?php echo $ban['ip']; ?></td>
						<td><?php echo $ban['name']; ?></td>
						<td><?php echo $ban['ban_type']; ?></td>
						<td><?php echo $ban['start_date']; ?></td>
						<td><?php echo $ban['is_active']; ?></td>
						<td>
							<a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&edit_ban=<?php echo $key ?>">edit</a> |
							<a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&unban=<?php echo $key ?>">unban</a> |
							<a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&delete_ban=<?php echo $key ?>">delete</a>
						</td>
					</tr>
<?php } ?>
				<tr>
					<td colspan="2"><a href="<?php echo site_url();?>/wp-admin/admin.php?page=<?php echo $base_tab?>&add_ban">Add ban</a></td>
				</tr>
				</table>
			</fieldset>