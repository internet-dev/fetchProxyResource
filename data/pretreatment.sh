#!/bin/bash
# @describe:
# @author:   Jerry Yang(hy0kle@gmail.com)

#set -x

# 预计处理原始数据,生成 svm 向量机能识别的数据格式
awk -F "\t" '{
    split($1, date_array, "-");
    date = date_array[1] date_array[2] date_array[3];
    str = "" substr(date, 1, 6) " " "1:" date " ";
    for (i = 2; i <= NF; i++)
    {
        str = str  i ":" $i " ";
    }
    print str;
}' ssq.txt > svm-org
