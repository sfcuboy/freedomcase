#!/bin/bash
for f in `ls tests/`;do
	php punitFrame.php tests/$f;
	echo "";
done;
