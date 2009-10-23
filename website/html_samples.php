<?
#	Copyright (C) 2009  Duncan Hawthorne, Oliver Stevens
#
#	This file is part of Trufflepig.
#
#	Trufflepig is free software: you can redistribute it and/or modify
#	it under the terms of the GNU Affero General Public License as published by
#	the Free Software Foundation, either version 3 of the License, or
#	(at your option) any later version.
#
#	Trufflepig is distributed in the hope that it will be useful,
#	but WITHOUT ANY WARRANTY; without even the implied warranty of
#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#	GNU Affero General Public License for more details.
#
#	You should have received a copy of the GNU Affero General Public License
#	along with Trufflepig.  If not, see <http://www.gnu.org/licenses/>.
?>

<?
function head()
	{
	global $config;
	?>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="StyleSheet" href="style.css" type="text/css"/>
	<link rel="shortcut icon" href="./favicon.ico" type="image/png"/>
	<link rel="alternate" type="application/rss+xml" title="RSS" href="feed.php" />
	<?php
	echo '<title>'.$config[website_name].'</title>';
	?>
	</head>
	<?
	}
	
function help_and_nag()
	{
	global $config;
	global $user;
	echo '<div id="helpbutton">';
	echo '<a href="wiki">Help</a>';
	if ($config[motd])
		{
		echo "<br>".$config[motd];
		}
	if ($user->info[total_size] > $config[good_share])
		{
		null;
		}
	else#small share
		{
		if ($user->info[total_size] <= $config[cut_off])#essentially no share
			{
			
			if ($user->info[hit_count] != -1)
				{
				if ($user->info[hit_count] >= $config[max_hit] / 2 and $config[encourage] == true)#so new comers dont see the limits
					{
					if ($user->info[hit_count] < $config[max_hit])#when they are equal cant make another search, so dont show "0 searches left"
						{
						echo '<br>'.($config[max_hit] - $user->info[hit_count]).' searches before you must <a href="wiki/index.php?title=Sharing">share</a> to search';
						}
					else
						{
						echo '<br><a href="wiki/index.php?title=Sharing">Share</a> your files to use the search';
						}
					}
				if ($config[encourage] == true)#so everyone always sees what is wrong with their share
					{
					echo '<br><a href="search.php?show_hosts=true">Share size = '.readable_size($user->info[total_size]).'</a>';
					if ($user->info[total_size] > 0)#but still seeeing this so must be below cut_off
						{						
						echo '. You need to share more';
						} 
					echo '<br>Please share as much as you can';
					if ((time() - $user->info[last_seen]) > 3*24*60*60)#not seen your computer in the last week
						{echo '<br><a href="wiki/index.php?title=Firewall">Your firewall is blocking your share</a>';}
					else
						{echo '<br>Firewall trouble? no';}
					; echo '';
					}
				if ($config[encourage] == false)
					{
					echo '<br>You are not <a href="wiki/index.php?title=Sharing">sharing</a>';
					}
				}
			else #below the share cutoff, but on a whitelist. so let them know
				{
				echo '<br>Your share is low';
				}	
			}
		else #ie between cut_off and good_share
			{
			echo '<br>Can you <a href="wiki/index.php?title=Sharing">share</a> more?';
			}
		}
	if (($user->info[total_size] > $config[cut_off]) and ($user->info[hit_count] > round(($config[max_hit] / 2), 0)))#ie first hit only after sharing	
		{
		echo '<br>Thank you for sharing';
		}	
	echo '</div>';
	}
	
function show_banner()
	{
	global $config;	
	if ($config[website_has_banner] == false)
		{
		echo '<a href="." style="text-decoration:none; font-family:sans-serif; font-size:60pt; color:#323232">';
		echo $config[website_name];
		echo '</a>';
		}
	else
		{
		echo '<a href="."><img align="top" src="'.$config[website_banner].'"/></a>';
		}
	echo '<br><br>';
	}

function search_box()
	{
	global $user, $config, $keyword, $search_mode, $page_name;
	?>
	<form style="display:inline;margin:0;padding:0" action="<?php echo $page_name ?>">
	<input type="text" name="keyword" style="width:300px" value='<?php echo safe_html(stripslashes($keyword)) ?>'> 
	<select name="mode">
	<option value="all" <?php if ($search_mode == 'all') echo 'selected="selected"' ?>>All files</option>
	<option value="video" <?php if ($search_mode == 'video') echo 'selected="selected"' ?>>Video</option>
	<option value="music" <?php if ($search_mode == 'music') echo 'selected="selected"' ?>>Music</option>
	<option value="documents" <?php if ($search_mode == 'documents') echo 'selected="selected"' ?>>Documents</option>
	<option value="all_films" <?php if ($search_mode == 'all_films') echo 'selected="selected"' ?>>Show all films</option>
	<option value="show_my_files" <?php if ($search_mode == 'show_my_files') echo 'selected="selected"' ?>>Show my files</option>
	</select>
	<br>
	<input type="submit" value="Search!">
	<input type="hidden" name="show_results" value=true>
	<input type="hidden" name="spamfilter" value=true>
	</form>
	
	<form action="search.php" style="display:inline;margin:0;padding:0">
	<input type="submit" value="List Computers">
	<input type="hidden" name="show_hosts" value=true>
	</form>
	<?	
	}

function show_footer()
	{
	global $config;
	?>
	<br><br>
	<font size=-2>
	<?php
	$sql = 'select sum(num_files) from '.$config[dbhosts].' union select sum(total_size) from '.$config[dbhosts].'';

	$res = MySQL_query($sql);

	$file_count = MySQL_fetch_array($res);
	$grand_total_size = MySQL_fetch_array($res);

	echo 'Searching ' . number_format($file_count['sum(num_files)'],',') . ' tasty '.$config[lan_nickname].' files (' . round($grand_total_size['sum(num_files)']/1000000000,0) . ' gigabytes)';

	?>
	<br>
	powered by <a href='http://code.google.com/p/trufflepig/'>trufflepig </a><a href='http://code.google.com/p/trufflepig/'><img src='./trufflepig.gif' height='15' width='20' /></a>
	<br><br>
	</font>	
	<?php
	}

function show_wall()
	{
	global $user, $config, $keyword, $search_mode, $page_name;
	?>
	<table>
		 	<tr>
				<td align="center"><b>Name</b></td>
				<td align="center"><b>Message</b></td>
				<td align="center"><b> </b></td>
			</tr>

			<form action="<?php echo $page_name ?>" method="post">	
				<td align="center"><b><input type="text" value="<?php echo $user->get_name() ?>" name="author"></td>
				<td align="center"><b><textarea rows="2" name="message" style="width:100%"></textarea></td>
				<td align="center"><input type="submit" value="Post"><input type="hidden" name="post_message" value=true></td>
				<input type="hidden" name="post_message" value=true>
			</form>


			<?php		
			$author_sql = 'select distinct ip,author from ( select * from '.$config[dbwall].' ORDER BY time DESC limit 40) as foo ;';		
		
			$sql = 'SELECT author, message, time, ip FROM '.$config[dbwall].' ORDER BY time DESC LIMIT 40';

			#send query to mysql		
			$wall = MySQL_query($sql);			
	
				$wall_line_number = 0;

				#show messages on wall
				while ($row = MySQL_fetch_array($wall)) {
	
					$class = ($wall_line_number%2) ? 'class="online_even"' : 'class="online_odd"';
				
					$i = 0;
					$query = MySQL_query($author_sql);
					while ($author_row = MySQL_fetch_array($query))#very wasteful way of doing things #FIXME
						{
						if ((strcasecmp($author_row['author'], $row['author']) == 0) and ($author_row['ip'] == $row['ip']))#mysql is case insensitive on distinct, so make php too
							{	
							$author_number = $i;#so one color for each author
							break;
							}
						$i++;	
						}

					echo "<tr $class>";
					echo "<td class='wallname wallcolour" . $author_number%13 . "' >" . safe_html(stripslashes($row['author'])) . "</td>";
					echo "<td align='center' >" . safe_html(stripslashes($row['message'])) . "</td>";
					echo "<td align='center' style='font-size:x-small'>" . date("H:i", strtotime(stripslashes($row['time']))) . "<br>" . date("M d", strtotime(stripslashes($row['time']))) . "</td>";
					echo "</tr>";
					#produce readable HTML by having a new line here
					echo "\r\n";			
					$wall_line_number++;
					}
			?>
		</table>	
	<?
	}

function low_sharer_hosts_page_help()
	{
	global $user, $config, $keyword, $search_mode, $page_name;
	?>
	<a href="wiki/index.php?title=Sharing">Share</a> your files to see the other computers on the network<br>
	<br>
	<?
	if ($user->info[last_scanned] != 0)
		{
		echo 'Your computer was last seen ready for scanning and then scanned on '. date("H:i M d", strtotime(stripslashes(date(r, $user->info[last_scanned])))) .'<br>';
		}
	else
		{
		echo 'Your computer has never been seen ready for scanning<br>';			
		}
	$sql = 'select max(last_scanned) from '.$config[dbhosts];
	$query = MySQL_query($sql);
	$result = MySQL_fetch_array($query);
	$last_ran = $result['max(last_scanned)'];			
	echo 'The scanner last ran on '. date("H:i M d", strtotime(stripslashes(date(r, $last_ran)))).'<br>';	
	echo '<br>';
	?>
	<form action="search.php" style="display:inline;margin:0;padding:0">
	<input type="submit" value="Show my shared files">
	<input type="hidden" name="mode" value='show_my_files'>
	<input type="hidden" name="show_results" value=true>
	</form>
	<?
	echo '<br><br>';	
	}

function show_hosts_page()
	{
	global $user, $config, $keyword, $search_mode, $page_name;
	if ($user->can_see_computers == false)
			{
			low_sharer_hosts_page_help();
			}
		?>		
		<table>
			<tr>
				<td align="center"><b>Computer</b></td>
				<td align="center"><b>No. of files</b></td>
				<td align="center"><b>Total size (GB)</b></td>
			</tr>

			<?php
			if ($user->can_see_computers == true)
				{$data = mysql_query('SELECT ip, name, online, total_size/1024/1024/1024, num_files FROM '.$config[dbhosts].' WHERE num_files > 0 or total_size > 0 ORDER BY total_size DESC');}
			else
				{$data = mysql_query('SELECT ip, name, online, total_size/1024/1024/1024, num_files FROM '.$config[dbhosts].' WHERE ip like "'.$user->info[ip].'" ');}		
			
			while($row = mysql_fetch_array( $data ))
			{					#colour the table rows alternately, and grey out offline computers
			$online_status = ($row['online']) ? 'online' : 'offline';			
			$even_or_odd = ($table_row_number%2) ? 'even' : 'odd';
			$class = 'class = "' . $online_status . '_' . $even_or_odd . '"';

			echo "<tr $class >";
				if (eregi("windows", $_SERVER['HTTP_USER_AGENT']))
					{
					echo '<td align="center"><a class='.$online_status.' href="' . 'file:///\\\\' . $row['ip'] . '"> ' . $row['name'] . ' </a></td>';
					}
				else
					{
					echo '<td align="center"><a class='.$online_status.' href="' . 'smb://' . $row['ip'] . '"> ' . $row['name'] . ' </a></td>';
					}
				#echo "<td>" . gethostbyaddr($row['ip']) . " </td>";
				echo '<td align="center">' . $row['num_files'] . ' </td>';
				echo '<td align="center">' . round($row['total_size/1024/1024/1024'], 3) . '</td>';
			echo '</tr>';

			#produce readable HTML by having a new line here
			echo "\r\n";			

			#increment row count
			$table_row_number++;
			}
			?>
	
		</table>
		<?php	
	}


function show_search_results()
	{
	global $user, $config, $keyword, $search_mode, $page_name;
	#santize sql keyword in case anyone reads xkcd...	
	$keyword = mysql_real_escape_string($keyword);

	
	#perform the search
	#full text search in boolean mode
	
	#$sql = "SELECT * from files where MATCH(path, file) AGAINST('$keyword' in boolean mode)";    #<<----- orignal search command 
	$sql = 'SELECT DISTINCT '.$config[dbfiles].'.ip, '.$config[dbhosts].'.name, '.$config[dbhosts].'.online, path, file, size, MATCH(path, file) AGAINST("' . $keyword . '") AS relevance
	
	FROM '.$config[dbfiles].' 
	
	JOIN '.$config[dbhosts].' ON '.$config[dbhosts].'.ip = '.$config[dbfiles].'.ip
	
	WHERE MATCH(path, file) AGAINST("' . $keyword . '" IN BOOLEAN MODE)';
	#add file type filters if requested
	#video needs to be bigger than 1MB, this eliminates most spurious results. I don't think a filesize filter can be applied to music though without
	#adverse effects
	if ($search_mode == 'video') 
		{
	$sql = $sql . ' AND (file LIKE "%.avi" OR file LIKE "%.mpg" OR file LIKE "%.mpeg" OR file LIKE "%.ogm" OR file LIKE "%.wmv" OR file LIKE "%.mkv" OR file LIKE "%.mp4" OR file LIKE "%.ogg" OR file LIKE "%.ogv") AND (size > 1000000)';
		}
	if ($search_mode == 'music') 
		{
	$sql = $sql . ' AND (file LIKE "%.mp3" OR file LIKE "%.ogg" OR file LIKE "%.aac" OR file LIKE "%.mp4" OR file LIKE "%.wav" OR file LIKE "%.flac" OR file LIKE "%.m4a" OR file LIKE "%.oga")';
		}
	
	if ($search_mode == 'documents') 
		{
		$sql = $sql . ' AND (file LIKE "%.doc" OR file LIKE "%.xls" OR file LIKE "%.ppt" OR file LIKE "%.pdf" OR file LIKE "%.odt" OR file LIKE "%.txt" OR file LIKE "%.rtf" OR file LIKE "%.ods" OR file LIKE "%.odp")';
		}
		
	#remove nuisance files (thumbs.db, .lnk files, etc.)
	#I doubt anyone wants to look for these
	#add another condition if theres a type of usless file that's spoiling search results
	#we have to be careful not to make this too strict though as there is no intuitive option to disable it yet
	if ($_GET['spamfilter']) 
		{
		$sql = $sql . ' AND (file NOT LIKE "%.db" AND file NOT LIKE "%.ini" AND file NOT LIKE "%.dll" AND file NOT LIKE "%.lnk" AND file NOT LIKE "%.scf" AND file NOT LIKE "%.DS_Store%" AND file NOT LIKE ".directory" AND file NOT LIKE "folder.jpg" AND file NOT LIKE "AlbumArt_%" AND file NOT LIKE "AlbumArtSmall.jpg" )';
		
		}
	
	#order the results with preferance for online hosts	
	#then by relevance
	$sql = $sql . ' ORDER BY ('.$config[dbhosts].'.online = 1) DESC, relevance DESC';
	
	#dispaly all films on network if this is requested. have moved this after the order by line as having it before causes errors (relevance DESC is
	#meaningless as this query string doesn't have a relevance variable)
	if ($search_mode == 'all_films') 
		{
		$sql = 'SELECT DISTINCT '.$config[dbfiles].'.ip, '.$config[dbhosts].'.name, '.$config[dbhosts].'.online, path, file, size 
	
		FROM '.$config[dbfiles].' 
	
		JOIN '.$config[dbhosts].' ON '.$config[dbhosts].'.ip = '.$config[dbfiles].'.ip

		WHERE (size > 600000000) AND (file LIKE "%.avi" OR file LIKE "%.mpg" OR file LIKE "%.mpeg" OR file LIKE "%.ogm" OR file LIKE "%.wmv" OR file LIKE "%.mkv" OR file LIKE "%.mp4" OR file LIKE "%.ogg" OR file LIKE "%.ogv") ORDER BY ('.$config[dbhosts].'.online = 0), ('.$config[dbfiles].'.file)
		'; #online then offline, each alphabetical
		}
	
	if ($search_mode == 'show_my_files')#leave this last, as it doesnt count for hits, so needs to override others
		{
		$sql = 'SELECT DISTINCT '.$config[dbfiles].'.ip, '.$config[dbhosts].'.name, '.$config[dbhosts].'.online, path, file, size 
	
		FROM '.$config[dbfiles].' 
	
		JOIN '.$config[dbhosts].' ON '.$config[dbhosts].'.ip = '.$config[dbfiles].'.ip

		WHERE '.$config[dbfiles].'.ip like "'.$user->info[ip].'"';
		}
	
	#send query to mysql		
	$res = MySQL_query($sql);
	?> 
	
	<?
	if (mysql_num_rows($res) > 0)#else looks silly
		{
		if ($search_mode != 'show_my_files')
			{
			if ($user->can_use_site == false)
				{echo '<a href="wiki/index.php?title=Sharing">Share</a> your files to activate these links<br><br>';}
			if (($user->can_use_site == true) and ($user->can_see_computers == false))
				{echo '<a href="wiki/index.php?title=Sharing">Share</a> your files to see the computer names<br><br>';}
			}
		}
	?>
	<table>
		<tr>
			<td align="left"><b>File</b></td>
			<td align="center"><b>Computer</b></td>
			<td align="center"><b>Size (MB)</b></td>
		</tr>

		<?php	#put the results into a table, with clickable links for each file
		$table_row_number = 0;
		while($row = MySQL_fetch_array($res)) 
			{
			#colour the table rows alternately, and grey out offline computers
			$online_status = ($row['online']) ? 'online' : 'offline';			
			$even_or_odd = ($table_row_number%2) ? 'even' : 'odd';
			$class = 'class = "' . $online_status . '_' . $even_or_odd . '"';

			if (eregi("windows", $_SERVER['HTTP_USER_AGENT']))
				{$file_link = 'file:///\\\\' . $row['ip'] . '\\' . str_replace('/','\\',$row['path']) . '\\';
				$computer_link = 'file:///\\\\' . $row['ip'] . '/';}
			else
				{$file_link = 'smb://' . $row['ip'] . '/' . $row['path'] . '/';
				$computer_link = 'smb://' . $row['ip'] . '/';}
 
			echo "<tr $class >";
				#link to file
				echo '<td>';
				if (($user->can_use_site) or ($search_mode == 'show_my_files'))
					{echo '<a class='.$online_status.' href="' . $file_link . '">' . $row['file'] . '</a>';}
				else
					{echo $row['file'];}
				echo '</td>';
	
				#link to computer
				echo '<td align="center">';
				if ((($user->can_use_site) and ($user->can_see_computers)) or ($search_mode == 'show_my_files'))
					{echo '<a class='.$online_status.' href="' . $computer_link . '">' . $row['name'] . '</a>';}
				else
					{echo '?';}
				echo '</td>';
			
				#size of file
				echo '<td align="center">'; 
				echo round($row['size']/1000000, 2);
				echo '</td>';
			echo '</tr>';
	
			#produce readable HTML by having a new line here
			echo "\r\n";			

			#increment row count
			$table_row_number++;
		}
	echo '</table>';
	#show the number of files
	if (mysql_num_rows($res) == 0)
		{
		echo '<h3 align="center">';
		echo '0 files found';
		echo '</h3>';
		}		
	}

?>
