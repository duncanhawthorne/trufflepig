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

from __future__ import division #to give true division
import os, threading, time, sys, socket
import MySQLdb

if os.uname()[0] == "Linux":
	os_name = "Linux"
else:
	os_name = "OSX"

try:
	from configobj import ConfigObj
	configfile = os.path.join(os.getenv("HOME"), ".trufflepig")
	config = ConfigObj(configfile)

	#or just hardcode these:
	dbfiles = config["dbfiles"]
	dbhosts = config["dbhosts"]
	password = config["password"]
	database = config["database"]
	dbuser = config["user"]
	dbhost = config["host"]
	port = int(config["port"])
	
	ip1 = config["ip1"]
	ip2 = config["ip2"]
	ip3 = config["ip3"]
	ip4 = config["ip4"]
	if ip1 == "None":
		ip1 = list(range(256))
	if ip2 == "None":
		ip2 = list(range(256))	
	if ip3 == "None":
		ip3 = list(range(256))
	if ip4 == "None":
		ip4 = list(range(256))
		
except:
	print("need configobj")
	print("make the config file ~/.trufflepig")
	sys.exit()

db = MySQLdb.connect(host=dbhost, port=port, user=dbuser, passwd=password, db=database)
	
cursor = db.cursor()
print("connection opened to mysql")


#asynchronous mysql commands not currently used
#due to other mysql commands needing return values
#and mySQLdb getting confused with multiple threads in general
#if you do use this, make sure to stop the main thread doing sys.exit() before this thread is done
'''
end_of_program = {}
synchronous = True
mysql_commands_backlog = []
def run_on_mysql(text):
	if synchronous == True:
		cursor.execute(text)
	else:
		mysql_commands_backlog.append(text)
	
def mysql_cleanup():
	#run this in a seperate thread
	end_of_program["mysql thread"] = False
	while len(mysql_commands_backlog) != 0 or end_of_program["main"] == False:
		None
		#go through backlog list
		#doing cursor.execute(line)
		#removing from the backlog
	end_of_program["mysql thread"] = True
'''	

def ip_list_iterator():
	if len(sys.argv) >= 2 and ((sys.argv[1] == "scan" and len(sys.argv) >= 3 ) or (sys.argv[1] == "ping" and len(sys.argv) >= 3)):
		#ie run as python backend.py scan 192.168.0.1 192.168.0.3
		#or python backend.py ping 192.168.0.1 192.168.0.2
		
		if sys.argv[2] == "quick":
			print("just look at ips already in the mysql database")
			text = "select ip from "+dbhosts+" where total_size > 0 or num_files > 0 or hit_count != 0;"
			cursor.execute(text)
			alive_sql_ip_list = [] #a list of ips on sql
			for i in cursor.fetchall():
				alive_sql_ip_list.append(i[0])
			for i in alive_sql_ip_list:
				yield i
						
		else:
			for i in sys.argv[2:]:
				yield i
	else:
		#ie run as python backend.py, or python backend.py scan, or python backend.py ping
		#so uses config based list of ips
		for a in ip1:
			for b in ip2:
				for c in ip3:
					for d in ip4:	
						yield str(a)+"."+str(b)+"."+str(c)+"."+str(d)
						
def bash(command):		
	return os.popen(command).read().split("\n")[:-1]

def ping(ip):
	result = bash("ping -A -w 1 -W 1 -c 1 "+ip)# | grep \"1 received\"")#1 second to respond, or else
	#NOTE, watch out for "no buffer space available" errors
	if len(result) >= 5 and "1 received" in result[4]:
		return 1#online
	else:
		return 0#offline

def threader(function, arguments, limiter = None, limit = None):
		if limiter != None:
			while limiter >= limit:
				print("thread limiter"+str(limiter))
				time.sleep(2)
		thread = threading.Thread( target = function, args = arguments)
		thread.start()
	
def dict_to_sql_as_insert(line, sqltable):
	#FIXME vulnerable to sql injection
	#instead of coming up with a fixed command, i make my own mysql command on the fly. Whatever is in the dictionary gets inserted as one row in the db
	text = "insert into "+sqltable+" ("
	for bit in line:
		text += bit+", "

	text = text[:-2]#take off last comma and space
	text += ") values("
	for bit in line:
		text += "\""+MySQLdb.escape_string(str(line[bit]))+"\", "		
	text = text[:-2]#take off last comma and space
	text += ");"
	cursor.execute(text)
		
def dict_to_sql_as_update(line, sqltable, primary_key, maybe_from_scratch = False):
	#FIXME vulnerable to sql injection
	#instead of coming up with a fixed command, i make my own mysql command on the fly. Whatever is in the dictionary gets updated as one row in the db (based on primary key)
	if maybe_from_scratch == True:#ie like a first run #ie update commands wont work
		text = "select * from "+sqltable+" where "+primary_key+" like '"+line[primary_key]+"'"
		output = cursor.execute(text)
		if output == 0:#ip not in the database. then fill it in with blank data (then update later), other just update
			text = "replace into "+sqltable+" ("+primary_key+") values('"+line[primary_key]+"');"
			cursor.execute(text)
	
	text = "update "+sqltable+" set "			
	for item in line:
		if item != primary_key:#otherwise circular
			if line[item] is None:#so doesnt update things not definitely set
				continue
			else:				
				text += item+" = \""+MySQLdb.escape_string(str(line[item]))+"\" , "
	text = text[:-2]#remove last comma
	text += "where "+primary_key+" like \""+line[primary_key]+"\";"	
	cursor.execute(text)

def get_computer_state(ip, hosts_list, appendtohosts_list):
	appendtohosts_list.append(None) # a simple tracket to see how many of these functions are still runnning +1
		
	if justping == True:
		pingstate = ping(ip)
		smbname = None	
	else:
	#	state = bash("nmblookup -A "+ip)
	#	
	#	if len(state) > 3:
	#		pingstate2 = 1
	#	else:
	#		pingstate2 = 0
	#	
	#	if pingstate2 == 1:
	#		pingstate1 = ping(ip)
	#	else:#no point checking ping(ip) as need both to be 1 #and also pinging loads of ips causes weird "connect: No buffer space available" errors
	#		pingstate1 = 0
	#	
	#	if pingstate1 == 1 and pingstate2 == 1:#online and with an smb name (ie the only useful type of online)
	#		pingstate = 1
	#	else:
	#		pingstate = 0
	#	
	#	try:
	#		smbname = state[1].split("\t")[1].split(" ")[0]#except will revert to None
	#	except:
	#		smbname = None
	
		if ip in fping_output:
			state = bash("nmblookup -A "+ip)
			if len(state) > 3:
				pingstate = 1
			else:
				pingstate = 0
			try:
				smbname = state[1].split("\t")[1].split(" ")[0]#except will revert to None
			except:
				smbname = None
				
			'''	
			#this is perfect when run as python backend.py scan quick. not putting quick option makes this too slow, as host lookups that fail take ages. new people are taken care of by the current code
			hit_count = None
			try:
				host = socket.gethostbyaddr(ip)[0]
			except:
				host = None
			
			print 1
			
			text = "SELECT host_name, hit_count FROM "+dbhosts+" where ip like '"+ip+"';"
			cursor.execute(text)
			current_host_name, current_hit_count = cursor.fetchone()
			
			print 2
			
		#be careful	
		#	if (host != current_host_name and current_host_name != None) or host == None: 
		#	#if changed host, but if host currently unset, just set it
		#	#or host no longer registered
		#		if hit_count > 0:
		#			print("resetting "+ip)
		#			hit_count = 0
				
			print 3		
			'''		
					
		else:
			pingstate = 0
			smbname = None
	
	if pingstate == 1:
		lastseen = int(time.time())
	else:
		lastseen = None
	line = {"ip":ip, "name":smbname, "online":pingstate, "last_pinged":int(time.time()), "last_scanned":None, "last_seen":lastseen, "num_files":None, "total_size":None}
	hosts_list.append(line)
	
	appendtohosts_list.pop() # a simple tracket to see how many of these functions are still runnning -1

def get_network_computers():
	appendtohosts_list = []
	hosts_list = []
	for ip in ip_list_iterator():
		threader(get_computer_state, (ip, hosts_list, appendtohosts_list))
	tokill = 5
	while len(appendtohosts_list) != 0:
		tokill -= 1
		if tokill == 0:
			print("given up on these ips")
			break
		time.sleep(2)
		print(len(appendtohosts_list))	
	return hosts_list
	
def folder_contents(location, ip):
	list_of_files = []
	for root, folder, files  in os.walk(location):
		for item in files:
			full_file_path = os.path.join(root, item)
			try:#seems to only be necessary for scanning a hibernate file
				filesize = os.path.getsize(full_file_path)
			except:
				filesize = 0			
			submit_location = root.split(os.getenv("HOME")+"/trufflepig/"+ip+"/")[1]			
			list_of_files.append({"ip":ip, "path":submit_location, "size":filesize, "file":item, "last_seen":int(time.time())})
	return list_of_files
		
def scan_smb(ip):
		list_of_files = []		
		#print(ip)
		server = "smb://"+ip
		folderlist = []
		for item in bash("timeout 3 smbclient -L //"+ip+" -N -g 2>/dev/null"):
			if item[0:4] == "Disk":
				folderlist.append(item.split("|")[1])	
		
		if len(folderlist) > 0:
			for i in reversed(list(range(len(folderlist)))):#reverse, as we are removing elements, else would screw up position
				folder = folderlist[i]
				if ";" in folder or "&" in folder or "\"" in folder or "\\" in folder or folder[-1:] == "$":# ";" and "&" and "\"" stops bash injection. $ folders seem to always be junk
					folderlist.remove(folder)			
		print(folderlist)
	
		#mount main folders, and look inside
		for folder in folderlist:
			print(folder)
			bash("mkdir -p \""+os.getenv("HOME")+"/trufflepig/"+ip+"/"+folder+"\"")
			if os_name == "Linux":
				bash("smbmount \""+server[4:]+"/"+folder+"\" \""+os.getenv("HOME")+"/trufflepig/"+ip+"/"+folder+"\" -o guest 2>/dev/null")#FIXME pushing warning to /dev/null		
			else:#OSX
				bash("mount_smbfs \""+server[4:]+"/"+folder+"\" \""+os.getenv("HOME")+"/trufflepig/"+ip+"/"+folder+"\"")
			
			list_of_files += folder_contents(os.getenv("HOME")+"/trufflepig/"+ip+"/"+folder, ip)			

		print("umounting it all")
		for folder in folderlist:
			if os_name == "Linux":
				bash("umount.cifs -f -l \""+os.getenv("HOME")+"/trufflepig/"+ip+"/"+folder+"\"")
			else:#OSX
				bash("umount \""+os.getenv("HOME")+"/trufflepig/"+ip+"/"+folder+"\"")
		
		if len(folderlist) > 0:#otherwise folder was never created
			bash("timeout 10 /bin/rm -r \""+os.getenv("HOME")+"/trufflepig/"+ip+"\"")
		
		return list_of_files 	

def process_computer(hosts_list_line):
	##should we check this ip:
	ip = hosts_list_line["ip"]

	if hosts_list_line["online"] == 0:#ie offline
		return
	
	if forcedscan == False:
		text = "SELECT last_scanned FROM "+dbhosts+" where ip like '"+ip+"';"
		cursor.execute(text)
		last_scanned = cursor.fetchone()[0]
	
		text = "select total_size from "+dbhosts+" where ip like '"+ip+"';"
		cursor.execute(text)
		sharing_size = cursor.fetchone()[0]
	
		if (time.time() - last_scanned <= int(config["large_share_scan_delay"])) and sharing_size >= int(config["good_share"]):
		#big shares (10gb+) are only scanned weekly
			print("checked you out recently "+ip)
			return

	print("checking", hosts_list_line["ip"], hosts_list_line["name"])
	list_of_files = scan_smb(ip)###this is where it all goes down
	
	#print("bit of calculation")#put stuff back into hosts_list now we know more after scanning
	for i in range(len(hosts_list)):
		if hosts_list[i]["ip"] == ip:
		
			hosts_list[i]["num_files"] = len(list_of_files)
		#	if hosts_list[i]["num_files"] == 0:
		#		hosts_list[i]["num_files"] = None
		#		#dont want it over-riding a count of several files with 0 (and hence removing it from the hosts page) 
		#		#just for seeing it once not sharing anything (perhaps also by error)
			
			hosts_list[i]["total_size"] = sum(list_of_files[i]["size"] for i in range(len(list_of_files)))
		#	if hosts_list[i]["total_size"] == 0:
		#		print "over-riding 0 share size"
		#		hosts_list[i]["total_size"] = None
			
			hosts_list[i]["last_scanned"] = int(time.time())

	print("pushin fileline to sql") #sql files stuff. put in all the files for this ip
	for line in list_of_files:
		dict_to_sql_as_insert(line, dbfiles)
	
	print("deleting old stuff")#put here deleting old stuff
	text = "delete from "+dbfiles+" where ip like '"+ip+"' and "+str(int(time.time()))+" - last_seen > "+config["large_share_scan_delay"]+" + 24*60*60 * 3;"#delete files 1.5 weeks unseen, slightly lower than global
	cursor.execute(text)
	
	print("pushin hostline to sql")
	#sql hosts stuff. update the line for this ip
	for line in hosts_list:
		if line["ip"] == ip:
			dict_to_sql_as_update(line, dbhosts, "ip")
			break
			
#	#dont want to set people's share to be zero, just because you see that once
#	#however, for clean up, if all files have been removed (by other cleanup) from the files list
#	#then it is safe to set their share size as zero
#	#values will be None as we overrided 0 above
#	#note that hosts who never get to this point (havent been scanned in ages) are removed in the cleanup at the bottom
#	for line in hosts_list:
#		if line["ip"] == ip:
#			if line["total_size"] == None or line["num_files"] == None:
#				text = 'select count(*) from '+dbfiles+' where ip like "'+ip+'";'
#				cursor.execute(text)
#				number_of_files = cursor.fetchone()[0]
#				if number_of_files == 0:
#					print "no files left in files, so set share = 0 on hosts"
#					text = "update "+dbhosts+" set total_size = 0, num_files = 0 where ip like '"+ip+"';"
#					cursor.execute(text)			
#			break
#	
	print("done")


if len(sys.argv) > 1:
	if sys.argv[1] == "scan" and not (len(sys.argv) >= 3 and sys.argv[2] == "quick"):
		forcedscan = True
		print("scanning ips given on command line")
	else:
		forcedscan = False
	if sys.argv[1] == "ping":
		justping = True
		print("just pinging")
	else:
		justping = False
else:
	forcedscan = False
	justping = False

	
if __name__ == "__main__":
	if justping == False:
		print("pinging ips, this could take a minute or two")
		ip_list = ""
		for temp_ip in ip_list_iterator():
			ip_list += " "+temp_ip	
		fping_output = bash("fping -a -r 0 targets "+ip_list+" 2>&1") #FIXME hack
	
	hosts_list = get_network_computers()
			
	text = "select ip from "+dbhosts+";"
	cursor.execute(text)
	sql_ip_list = [] #a list of ips on sql
	for i in cursor.fetchall():
		sql_ip_list.append(i[0])
		
	first_run = False
	for line in hosts_list: 
		if not line["ip"] in sql_ip_list:
			first_run = True #if we have an ip that is not on sql
			#ie need to do insert command as well as update when updating dbhosts
			print("this is (like) a first run")
			break
		#otherwise first_run will remain as False		
	
	print("updating online/offline status")
	for line in hosts_list:
		dict_to_sql_as_update(line, dbhosts, "ip", first_run)

	if justping == False:
		print("start on files")
		for line in hosts_list:
			process_computer(line) #####this is the meat of the program
		
	print("deleting old stuff everywhere")
	text = "delete from "+dbfiles+" where "+str(int(time.time()))+" - last_seen > "+config["large_share_scan_delay"]+"*2 ;"
	#FIXME more clever statement here to remove files less than 2 weeks old, who have been scanned recently
	#delete files 2 weeks unseen
	cursor.execute(text)
	
	text = "update "+dbhosts+" set total_size = 0, num_files = 0, name = null where "+str(int(time.time()))+" - last_scanned > "+config["large_share_scan_delay"]+" + 24*60*60 * 3 or ("+str(int(time.time()))+" - last_scanned > 24*60*60 * 3 and total_size < "+config["good_share"]+");"
	#for large sharers wait 1 week between scans, so 10 days before reset
	#for small sharers rescan constantly, so 3 days before reset
	cursor.execute(text)
	
	text = "update "+dbhosts+" set total_size = 0, num_files = 0, name = null where "+str(int(time.time()))+" - last_seen > 24*60*60 * 3;"
	#if havent seen a host in 3 days, set their share to be zero
	cursor.execute(text)
		
	print("all done")
	bash("date >> ~/trufflelog")
	
	sys.exit()#cleans up any lingering threads
		
					
