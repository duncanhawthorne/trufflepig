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

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<?php
#un hardcode the file name.
$whoami = split('/',$_SERVER['PHP_SELF']);
$page_name = $whoami[count($whoami)-1];

#grab query string values.
$show_results = $_GET['show_results'];
$show_hosts = $_GET['show_hosts'];
$keyword = $_GET['keyword'];
$search_mode = $_GET['mode'];
?>

<?php
include 'common.php';
include 'html_samples.php';	
?>

<?php
head();
?>

<body>

<?php
help_and_nag();
show_banner();
?>

<?php
if (true)#($user->can_use_site)
	{
	search_box();
	}
?>

<br><br>

<?php
if ($show_results != true and $show_hosts != true) { 
 	if(sizeof($_POST))#now be careful if we later do multiple posting #FIXME
		{		
		post_to_wall();
		}
	show_wall()	;
}
?>


<?php
if ($show_results==true)#(($show_results==true) and ($user->can_use_site) )
	{
	show_search_results();
	}
	
		
if ($show_hosts == true) 	
	{
	show_hosts_page();
	}				
?> 

<?php
show_footer();
?>

</body>
</html>
