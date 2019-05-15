#!/bin/sh

# Execute a command with a timeout

if [ "$#" -lt "2" ]; then
echo "Usage:   `basename $0` timeout_in_seconds command" >&2
echo "Example: `basename $0` 2 sleep 3 || echo timeout" >&2
exit 1
fi

cleanup()
{
trap - ALRM               #reset handler to default
kill -ALRM $a 2>/dev/null #stop timer subshell if running
kill $! 2>/dev/null &&    #kill last job
  exit 124                #exit with 124 if it was running
}

watchit()
{
trap "cleanup" ALRM
sleep $1& wait
kill -ALRM $$
}

watchit $1& a=$!         #start the timeout
shift                    #first param was timeout for sleep
trap "cleanup" ALRM INT  #cleanup after timeout
"$@"& wait $!; RET=$?    #start the job wait for it and save its return value
kill -ALRM $a            #send ALRM signal to watchit
wait $a                  #wait for watchit to finish cleanup
exit $RET                #return the value