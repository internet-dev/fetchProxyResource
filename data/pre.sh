#!/bin/bash
# @describe:
# @author:   Jerry Yang(hy0kle@gmail.com)

#set -x

awk -F "\t" '{
    str = "" substr($1, 1, 6) " ";
    for (i = 1; i <= NF; i++)
    {
        str = str  i ":" $i " ";
    }
    print str;
}' ssq.txt
