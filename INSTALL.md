# How to install EZrecorder ?

## Requirements

In order to use EZrecorder, you need to install / enable several components:

- Apache 
- PHP5
-- LIB SIMPLEXML for PHP5
-- LIB CURL for PHP5
- SSH
- AT

Some modules may require more components (for instance, FFMPEG is required for local_qtb module).

## Detailed installation 

1. Download the source code

Open your terminal and call Git in command line to download the source code of EZrecorder

2. Move the 'ezrecorder' directory

Now you have to decide where you want to install EZrecorder. We recommend you to install it under '/Library/ezrecorder' directory.
Note that the recorder(s) installation path must be configured in ezadmin as well, so all your recorders must use the same filepath.

```
cd
#change user to be root (may be ‘sudo’ depending on the distribution)
su
#moves the ezrecorder directory from current user’s home dir to /Library
#change the following path if you want to install ezrecorder somewhere else
mv ezrecorder /Library
```

3. Execute the 'install.sh' script for installing EZrecorder

Go in the 'ezrecorder' folder. Make sure the file 'install.sh' can be executed. 
Launch the 'install.sh' script as root and follow the instructions on the screen.

4. Configure the SSH link 

You are now going to configure the SSH link between EZmanager and EZrecorder. For this part of the installation, you will need an access to EZcast server.

On the remote EZcast server, copy the public SSH key.

On EZrecorder, generate a SSH key and add the public key from EZmanager in the authorized keys of EZrecorder

```
cd
# generate a key pair
ssh-keygen –t dsa 
cd .ssh
vi authorized_keys # may be authorized_keys2 
# paste the EZmanager public key in authorized_keys
```

5. Edit "commons/config.inc" file of EZcast

On the remote EZcast server, edit the 'config.inc' file in the 'commons' subdirectory

```
<?php
//…
//[line 45]
// Paths for the remote recorder
$recorder_user = "podclient";
// Path from the root to the recorder
$recorder_basedir = '/Library/ezrecorder/'; 
// Path from basedir to the admin.inc file
$recorder_subdir = '/etc/users/'; 
// Path from the root to the recorder local repository
$recorder_recorddir = "~$recorder_user/"; 
?>
```

6. Configure the recorder

* Activate Apache and load php5 module

```
sudo vi /etc/apache2/httpd.conf
set AllowOverride All for <Directory /Library/WebServer/Documents>
uncomment #LoadModule php5_module libexec/apache2/libphp5.so
sudo apachectl graceful 
``` 
**NOTE** : On Mac OS 10.8 and above, the apache server is not activated by default.

```
Sudo launchctl load –w /System/Library/LaunchDaemons/org.apache.httpd.plist
``` 

* Activate AT job

Change <true/> tag at Disabled key

```
sudo launchctl load –w /System/Library/LaunchDaemons/com.apple.atrun.plist 
``` 

* Activate remote control

It can be useful to enable remote control for managing the recorder remotely. 

Go in ‘System preferences’ >  ‘Sharing ‘ and activate ‘Remote login’ and ‘Remote management’.

* Activate Internet Sharing

Depending on your needs, you may have to use Internet Sharing to provide an access to the recorder.

Go in ‘System preferences’ > ‘Sharing’ and activate ‘Web Sharing’ and ‘Internet Sharing’.

**NOTE** : Since Mac OS 10.8, we have encountered many Wifi problems. Sometimes, the Internet sharing is enabled but the Mac doesn’t share its Wifi. Sometimes, the connection delay expires and a timeout occurs.

Activate the script that checks every minute if the Internet sharing is still enabled and functional : 

```
# enable ‘Access for assistive devices’ in ‘System Preferences’ > ‘Accessibility’
# copy ./sbin/be.ac.ulb.websharingfix.plist (from ezrecorder basedir) in /Library/LaunchAgents/ to be loaded at reboot
sudo cp ./sbin/be.ac.ulb.websharingfix.plist /Library/LaunchAgents/.
# load websharingfix agent 
sudo launchctl load –w /Library/LaunchAgents/be.ac.ulb.websharingfix.plist
```

Activate the script that checks every 10 minutes that Internet sharing is not in timeout (only possible if there is a second Mac mini with the recorder)

```
sudo cp ./sbin./be.ac.ulb.checkinternetsharing.plist /Library/LaunchAgents/.
# load checkinternetsharing agent
sudo launchctl load –w /Library/LaunchAgents/be.ac.ulb.checkinternetsharing.plist
``` 

* Activate 'mail'

The ‘mail’ command line is not activated by default on Mac OS X. Here is how to activate it.

```
sudo vi /etc/postfix/main.cf
# add ‘myorigin = ip address’
# add ‘relayhost = smtp server’
sudo postfix start 
# if an error occurs : sudo postfix reload
sudo vi /System/Library/LaunchDaemons/org.postfix.master.plist
# add 
<key>RunAtLoad</key>
<true/>
```

* Allow bash scripts to run as user

```
sudo visudo
# Enter following lines in Cmnd Alias specification:
# (Replace the path by the location of your ezrecorder directory)
Cmnd_Alias EZCAST = /Library/ezrecorder/modules/*/bash/* 
# and this at the end : (replace podclient by your username and _www by apache’s username)
_www 	ALL=(podclient) NOPASSWD: ALL
```

7. Add the recorder to EZadmin

Now that your recorder is ready to work, just add it to EZadmin via the web interface and save the modification for it to be recognized by EZmanager. You may have to enable the classroom management for EZadmin in the web interface of EZadmin (left menu > configure > enable management for classroom). 

You can now use your recorder to record in classrooms. 