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

<?php
echo '<?xml version="1.0" encoding="ISO-8859-1"?>'
 ?>

<?php
include 'common.php';

$sql = 'SELECT author, message, time, ip FROM '.$config[dbwall].' ORDER BY time DESC LIMIT 40';
$wall = MySQL_query($sql);			
?> 

<rss version="2.0">
<channel>

<?php
echo "\r\n";
echo '<title>'.$config[website_name].'</title>';
echo '<link>'.$config[web_address].'</link>';
echo '<description>Feed for comments on the '.$config[website_name].' wall</description>';
echo "\r\n";

while ($row = MySQL_fetch_array($wall))
	{
	echo '<item>';
	echo '<title>' . safe_html($row['author']) . '</title>';
	echo '<link> '.$config[web_address].'</link>';
	echo '<description>' . safe_html(stripslashes($row['message'])) . '</description>';
	echo '</item>';
	echo "\r\n";
	}
?>
 
</channel>
</rss>
