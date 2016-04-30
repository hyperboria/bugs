#!/bin/sh -
nohup cjdroute --nobg < /etc/cjdroute.conf &
PID=$!
while ! ip -6 a | grep 'inet6 fc' >/dev/null; do
	if ! jobs -p | grep $PID >/dev/null; then
		echo 'cjdroute failed' >2
		exit 2
	fi
	sleep 1
done
systemd-notify READY=1
# this trick is so systemd doesn't think that cjdroute is alien
# this process has to exit so cjdroute process is direct child of
# systemd runner, warning about alien will still show but
# it isn't important in any way as alien process exits
# right away
nohup sh -c "sleep 1 && systemd-notify MAINPID=$PID" &
systemd-notify MAINPID=$!
