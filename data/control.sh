#!/bin/bash
# @describe:
# @author:   Jerry Yang(hy0kle@gmail.com)

#set -x

# 控制脚本

# 1. 执行预处理
./pretreatment.sh

# 2. 学习
svm-train svm-org model_file

# 3. 遍历蓝球
blue_ball="01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16"

for blue in $blue_ball
do
    # 4. 预测
    nohup ./predict.sh $blue &
done
