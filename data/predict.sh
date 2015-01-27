#!/bin/bash
# @describe:
# @author:   Jerry Yang(hy0kle@gmail.com)

#set -x

##############
# 需要个修改 #
##############
# 预计发售日期
sell_date="20150129"
# 发售的期数
number="2015013"
##############

Usage="$0 <blue-ball>"
if [ $# -lt 1 ];then
    echo "$Usage"
    exit 1
fi

blue=$1

date_str=`echo $sell_date | awk '{print substr($1, 1, 6)}'`
model_file="model_file"
test_file="test_file.$blue"
output_file="output_file.$blue"
tmp_output="tmp_output.$blue"
lucky="lucky.$blue"

> $lucky

# 蓝球和所有组合暴力组合测试
while read line
do
    #echo $line
    all_red=""
    lucky_dt="$date_str $sell_date"
    i=3;
    for red in $line
    do
        all_red="${all_red} ${i}:${red} "
        lucky_dt="${lucky_dt} ${red}"
        i=$((i + 1))
    done
    #echo $all_red

    test_data=$date_str' 1:'$sell_date' 2:'$number' '$all_red' 9:'$blue
    echo $test_data > $test_file

    svm-predict  $test_file $model_file $output_file > $tmp_output

    lucky_test=$(awk '{
        #print $3;
        split($3, container, "%");
        print container[1];
    }' $tmp_output)
    #echo $lucky_test

    if [[ $lucky_test > 0 ]]
    then
        lucky_dt="${lucky_dt} ${blue} ${lucky_test}%"
        echo $lucky_dt >> $lucky
    fi

    #exit -11
done < ssq.33.6

#exit -1
