<!--
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
-->

<?php

function get_website_vars()
	{
	return parse_ini_file(exec('echo $HOME')."/.trufflepig");
	}

function connect_to_mysql()
	{
	global $config;		
	#establish connection to the database
	mysql_connect($config[host], $config[user], $config[password]) or die(mysql_error());
	mysql_select_db($config[database]) or die(mysql_error());
	}

function readable_size($size)
	{
	foreach (array('bytes', 'KB', 'MB', 'GB') as $suffix)
		{
		if ($size > 1024)
			{
			$size = $size / 1024;
			}
		else
			{
			return round($size, 0) . ' ' . $suffix;
			}
		}
	}


function safe_html($text)
	{
	return htmlspecialchars($text, ENT_QUOTES);
	}

/*unused right now
class Database_connection
	{
	function submit_query($sql_query)
		{
		$sql_query_reply = MySQL_query($sql_query);
		while ($row = MySQL_fetch_array($sql_query_reply)) 
			{
			foreach ($row as $value => $current_row)
				{
				$query_result[$query_result_row] = $row[$value];
				}
			};

		return $query_result;		
		}
	}
*/	
	
class User
	{
	/*
	have public variables
	$this->info gets created
	$this->can_see_computers
	$this->can_use_site
	*/
		
	function User()
		{
		global $config;
		
		$remote_ip = $_SERVER['REMOTE_ADDR'];
		if ($config['fake_ip'])
			{
			if (!$config['admin_ip'] or $remote_ip != $config['admin_ip']) #if faking ip dont want to let just anyone use the site, from the perspective of someone they are not
				{
				echo "You are not allowed to use this site, set your ip as admin_ip in the config file";
				exit;
				}
			$remote_ip = $config['fake_ip'];
			}		
		
		$sql = 'SELECT * from '.$config[dbhosts].' where ip like "'.$remote_ip.'";';
		$query = MySQL_query($sql);	
		$this->info = MySQL_fetch_array($query); #so $user->info gives a list of the sql line for this user
		
		#perhaps insert user into hosts
		$sql = 'select count(*) from '.$config[dbhosts].' where ip like "'.$this->info[ip].'";';
		$query = MySQL_query($sql);
		$result = MySQL_fetch_array($query);
		if ($result['count(*)'] == 0)#ie not in hosts 
			{
			$sql = 'insert into '.$config[dbhosts].' (ip) values("'.$this->info[ip].'");';
			$query = MySQL_query($sql);
			}
		
		$this->can_see_computers = false;
		$this->can_use_site = false;

		global $show_results;
		global $search_mode;
		
		if (($show_results==true) and ($search_mode != 'show_my_files'))
			{
			$this->change_hitcount();
			}

		#check permissions
		$this->check_permissions();#must be called after change_hitcount()
		
		}
	####
	
	function change_hitcount()
		{
		global $config;
		if ($this->info[hit_count] != -1) #whitelist ignored
			{
			if ($this->info[total_size] <= $config[cut_off])
				{
				if ($config[encourage] == true)
					{
					$sql = 'update '. $config[dbhosts] .' set hit_count = hit_count + 1 where ip like "'.$this->info[ip].'"';
					$res = MySQL_query($sql);
					$this->info[hit_count] = ($this->info[hit_count] + 1);
					}
				}
			else#sharing above cutoff
				{
				if ($this->info[hit_count] > round(($config[max_hit] / 2), 0)) #so if you share once, your hit count is reset to half, until you stop
					{
					$this->info[hit_count] = round(($config[max_hit] / 2), 0);
					$sql = 'update '. $config[dbhosts] .' set hit_count = '.$this->info[hit_count].' where ip like "'.$this->info[ip].'"';
					$res = MySQL_query($sql);
					}
				}
			}	
		}
		
	####		
	
	function check_permissions()#check permissions for what stuff can be seen
		#this needs to be called after change_hitcount()
		{
		global $config;
		if ($this->info[total_size] > $config[cut_off] or $this->info[hit_count] <= $config[max_hit] or $config[encourage] != true)
			{$this->can_use_site = true;}
		else
			{$this->can_use_site = false;}	

		if (($this->info[total_size] > $config[cut_off]) or ($this->info[hit_count] == -1) or ($config[encourage] != true))
			{$this->can_see_computers = true;}
		else	
			{$this->can_see_computers = false;}
		}
		
	function get_name()
		{
		$remote_name = ucfirst(strtolower($this->info[name]));
		if ($remote_name == '')#ie not sharing
			{$remote_name = gethostbyaddr($this->info[ip]);}
		return $remote_name;
		}	
	
	}	

function ip_restrictions_check()
	{
	global $config;

	if (!$config[override_htaccess] or $config[override_htaccess] != true)#if not set or set to anything other than true
		{
		if (!file_exists('.htaccess'))
			{
			echo "No .htaccess file. Please make one to restrict access to this site to just the local network. There is a sample file in the readme<br>";
			echo "Set override_htaccess in config to yes if you are sure you want this site to be accessible from the internet.";
			exit;
			}
		else #so .htaccess file is there
			{
			if (!$config[htaccess_contents_check])
				{
				echo "You havent set a string in the config file to match the .htaccess file<br>";
				echo "Setting this string ensures you have thought about who you want to be able to see this site";
				exit;
				}
		
			$htaccess_contents = fread(fopen(".htaccess", "r"), filesize(".htaccess"));
			if ($htaccess_contents != $config[htaccess_contents_check])#note if this should be working you probably need an extra return in the .trufflepig file
				{
				echo ".htaccess file contents doesnt agree with config file<br>";
				echo "Copy the contents of the .htaccess file into the config file under htaccess_contents_check possibly with an extra return";
				exit;	
				}
			}
		}
	
	}

function post_to_wall()
	{
	global $user;
	global $config;
	global $message;
	global $author;
	
	#santize sql keyword in case anyone reads xkcd...	
	$message = mysql_real_escape_string($_POST["message"]);
	$author = mysql_real_escape_string($_POST["author"]);

	if ($author == '')
		{
		$author = 'Anonymous';
		}
	
	$sql = 'INSERT INTO '.$config[dbwall].' (ip, message, author, time) values("'. (($config[record_wall_ip] == true) ? $user->info[ip] : "0.0.0.0") . '","' . $message . '","' . $author . '",current_timestamp());';
	
	#send query to mysql		
	MySQL_query($sql);

	$sql = 'delete from '.$config[dbwall].' where time not in (select * from (select time from '.$config[dbwall].' order by time desc limit 40) as foo);';
	MySQL_query($sql);#so only the last 40 entries are kept in the database. wysiwyg	
	}	


?>

<?php
$config = get_website_vars();
ip_restrictions_check();
connect_to_mysql();
$user = new User();

##clean wall
$sql = 'delete from '.$config[dbwall].' where unix_timestamp(now()) - unix_timestamp(time) > 30*24*60*60;'; #delete messages posted over 1 month ago
MySQL_query($sql);
?>





