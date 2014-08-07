#!/bin/bash

PROGRAM_PATH="/usr/local/sbin/visca-daemon"
PROGRAM="visca-daemon"
SERIAL_DEVICE="/dev/cu.KeySerial1"
#SERIAL_DEVICE="/dev/null"
PROGRAM_ARGS="-s $SERIAL_DEVICE -v" 
WATCH_DELAY=30

# Get PID
function getpid() {
	PID=`ps axc|awk "{if (\\\$5==\"$PROGRAM\") print \\\$1}"`
}

# Stop
function stop() {
        SDAT=`date +"%Y_%m_%d_%H:%M:%S"`
	echo "$SDAT Stopping $PROGRAM .."

	getpid
	kill $PID >/dev/null 2>&1

	return 0
}

# Start
function start() {
        SDAT=`date +"%Y_%m_%d_%H:%M:%S"`

	# Register signal handler
	trap "stop" SIGKILL SIGTERM SIGQUIT SIGINT
        
	# Execute command if it is not already running
        GREPLINES=`ps -ef|grep '/usr/local/sbin/visca-daemon -s'|wc -l`
	if [ $GREPLINES -lt 2 ]; then
	  echo "$SDAT Starting $PROGRAM .."
	  $PROGRAM_PATH $PROGRAM_ARGS >>/Library/Logs/visca-daemon.log 2>&1 
         else
          echo "$SDAT tried to start $PROGRAM but it is already running"
	fi

	# Watch PID
	getpid
	echo "Watching for PID=$PID .."

	# Loop as long as the process is running
	while [ 1 ]; do
		# Test if process is still running
                GREPLINES=`ps -ef|grep '/usr/local/sbin/visca-daemon -s'|wc -l`
		if [ $GREPLINES -eq 1 ]; then
			break;
		fi

		# Wait
		sleep $WATCH_DELAY
	done

	return 0
}

case "$1" in
	start|load)
		# Make sure it is not still running
#		stop
		start
		;;
	stop|unload)
		stop
		;;
	status)
		getpid
		echo "PID = $PID"
		;;
	*)
		start
esac

exit $?

