# monitor
an easy tool with bash shell to monitor linux server status, auto post info to a PHP server .

shell

```
#!/bin/bash

# 保存参数
P1=$1

# 每5分钟
CRONCMD="*/5 * * * * "

# 记录最后一次运行的时间
now_time=`date '+%F %T'`
echo "lasttime $now_time $P1" > /usr/cron.log

DOMAIN='monitor.gshyj.com'

# 检查并置入定时器
function check_cron(){
    RET=`crontab -l| grep "/usr/monitor.sh"` 
    if [[ "$RET" != "" ]];then #发现了任务
        echo "monitor任务已存在$CRONPATH"
    else
        echo "monitor任务不存在"
        basepath=$(cd `dirname $0`; pwd)
        FILEPATH=$basepath/monitor.sh
        if [ -f $FILEPATH ];then
            echo "发现脚本位置$FILEPATH"
            if [ "$FILEPATH" != "/usr/monitor.sh" ];then
                mv $FILEPATH  /usr/monitor.sh
                chmod 777 /usr/monitor.sh
            fi    
            echo "增加cron任务->/var/spool/cron/monitor"
            echo "$CRONCMD /usr/monitor.sh"
            echo "$CRONCMD /usr/monitor.sh -crontab" >> /var/spool/cron/root
            echo "定时已完成"
        else
            echo "脚本不存在，位置:"$FILEPATH
        fi              
    fi
}

# 监控数据并发送到服务器
function monitor_main(){
        # 获取设备名称
        hostname=`hostname`
        # 获取mac地址
        mac=`/sbin/ifconfig |grep -o  "[a-f0-9A-F]\\([a-f0-9A-F]\\:[a-f0-9A-F]\\)\\{5\\}[a-f0-9A-F]"|head -n 1`
        if [[ "$mac" == "" ]];then 
            mac=`/sbin/ip address |grep -o  "[a-f0-9A-F]\\([a-f0-9A-F]\\:[a-f0-9A-F]\\)\\{5\\}[a-f0-9A-F]"`;
            mac=${mac//00:00:00:00:00:00};
            mac=${mac//ff:ff:ff:ff:ff:ff};  
            mac=`echo $mac |grep -o  "[a-f0-9A-F]\\([a-f0-9A-F]\\:[a-f0-9A-F]\\)\\{5\\}[a-f0-9A-F]"|head -n 1`
        fi
        #获取cpu使用率
        cpuUsage=`top -b -n1 | awk -F '[ %]+' 'NR==3 {print $2}'`
        # Cpu(s):
        RET=`echo $cpuUsage| grep "Cpu"` 
        if [[ "$RET" != "" ]];then #发现了任务
            cpuUsage=`top -b -n1 | awk -F '[ %]+' 'NR==3 {print $3}'`
        fi
        #获取磁盘使用率
        data_name="/"
        diskUsage=`df -h | grep $data_name | awk -F '[ %]+' '{print $5}'`
        #logFile=/tmp/monitor.log
        #获取内存情况
        mem_total=`free -m | awk -F '[ :]+' 'NR==2{print $2}'`
        #获取内存使用
        mem_used=`free -m | awk -F '[ :]+' 'NR==2{print $3}'`
        #统计内存使用率
        mem_used_persent=`awk 'BEGIN{printf "%.0f\n",('$mem_used'/'$mem_total')*100}'`
        #获取报警时间
        now_time=`date '+%F %T'`

        URLPATH="http://$DOMAIN/Monitor/API/push"
        PARAMS="hostname=$hostname"
        PARAMS="$PARAMS&mac=$mac"
        PARAMS="$PARAMS&cpu=$cpuUsage"
        PARAMS="$PARAMS&disk=$diskUsage"
        PARAMS="$PARAMS&memsum=$mem_total"
        PARAMS="$PARAMS&memused=$mem_used"
        PARAMS="$PARAMS&mem=$mem_used_persent"
        PARAMS="$PARAMS&ltime=$now_time"

        echo $URLPATH
        echo $PARAMS
        ret=`curl $URLPATH -H 'Accept: application/json, text/plain, */*' -H 'Referer: http://127.0.0.1/' -H 'Origin: http://127.0.0.1:8080' -H 'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Mobile Safari/537.36' -H 'Content-Type: application/x-www-form-urlencoded' --data "$PARAMS" --compressed`
        echo $ret
}

function main(){
        # 根据参数，确定是否需要校验定时器cron运行
        if [ "$P1" == "-uninstall" ];then
                echo "卸载定时器"
                RET=`crontab -l| grep "/usr/monitor.sh"` 
                if [[ "$RET" != "" ]];then #发现了任务
                        sed -i '/monitor.sh/d' /var/spool/cron/root 
                        echo "已删除任务 /usr/monitor.sh"
                else
                        echo "未发现任务"
                fi
                exit
        fi
        if [ "$P1" == "-update" ];then
            if [ -f '/usr/monitor.sh' ];then
                rm -f '/usr/monitor.sh'
            fi
            cd /usr/
            wget http://$DOMAIN/Public/shell/monitor.sh
            sh /usr/monitor.sh -uninstall
            sh /usr/monitor.sh
            exit
        fi
        if [ "$P1" == "-h" ];then
            echo "sh monitor.sh -update"
            echo "sh monitor.sh -uninstall"
            echo "sh monitor.sh -crontab"
            exit
        fi
        if [ "$P1" == "-crontab" ];then
                echo "on crontab now"
                monitor_main
                exit 
        else
                monitor_main
                check_cron
                exit
        fi
}

main





```
