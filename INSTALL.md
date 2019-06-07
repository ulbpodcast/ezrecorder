# How to install EZrecorder ?

## Requirements

In order to use EZrecorder, you need to install / enable several components:

- Apache (nginx may be okay but not tested)
- PHP5 or PHP7 with modules:
    - simplexml
    - curl
    - pdo_sqlite
- SSH
- AT
- FFMPEG 3.x

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
# generate a key pair for ssh access to 127.0.0.1
ssh-keygen 
cd .ssh
cat id_rsa.pub >> authorized_keys
vi authorized_keys # may be authorized_keys2  on older MacOS X
# paste the EZmanager public key in authorized_keys
# paste the ezrecorder public key (id_rsa.pub) in authorized_keys (if you have only one recorder for cam and slide)
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

* Activate Apache and load php7 module

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

Change <true/> tag at Disabled key in file /System/Library/LaunchDaemons/com.apple.atrun.plist
    
```
sudo launchctl load –w /System/Library/LaunchDaemons/com.apple.atrun.plist 
``` 

**NOTE** : Since  MacOS 10.12 High Sierra, the file is read-only (cannot be overriden) do this:
```
sudo sh -c "sed -e 's_atrun_atrunlocal_g' /System/Library/LaunchDaemons/com.apple.atrun.plist >/Library/LaunchDaemons/com.apple.atrunlocal.plist"
#Launch at daemon
sudo launchctl load  /Library/LaunchDaemons/com.apple.atrunlocal.plist
``` 
``` 
# check AT works with typing:
at now
touch /tmp/at.test
<ctrl>+D
#wait up to a minute and /tmp/at.test file should appear
``` 

* (MacOS Optional) Activate Internet Sharing

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
Mail are used by the recorders to warn about some uploading issues.

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
# add this at the end : (replace podclient by your username and _www by apache’s username)
_www 	ALL=(podclient) NOPASSWD: ALL
```

7. Add the recorder to EZadmin

Now that your recorder is ready to work, just add it to EZadmin via the web interface and save the modification for it to be recognized by EZmanager. You may have to enable the classroom management for EZadmin in the web interface of EZadmin (left menu > configure > enable management for classroom). 

You can now use your recorder to record in classrooms. 
