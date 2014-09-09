#!/bin/bash
 
pid=$(ps axuf | grep php | grep :33380 | awk '{print $2}')
if [ ! -z "$pid" ]; then
	echo 'Found process to kill: '$(ps $pid)
	kill $pid;
fi
