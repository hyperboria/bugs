#!/usr/bin/env sh
PIPE_NAME="client-core-$(tr -cd 'a-f0-9' < /dev/urandom | head -c 8)"
(sleep 1 && /usr/bin/env cjdroute core "$PIPE_NAME" > /dev/null) &
nc -lU "/tmp/cjdns_pipe_$PIPE_NAME"
