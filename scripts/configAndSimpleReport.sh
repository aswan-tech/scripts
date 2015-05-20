#!/bin/bash
exec &>> /tmp/configAndSimpleReport.LOG
DATE=`date +"%Y-%m-%d-%H-%M-%S"`
ZIPFILE="report_simple_config_${DATE}.zip"
MAILRECEIPENTS="as-support@intelligrape.com,deepak.kumar@taslc.com"
#MAILRECEIPENTS="navjots@intelligrape.com"
php get_stock_report_config.php > /tmp/config.csv
php get_stock_report_simple.php > /tmp/simple.csv
cd /tmp && zip $ZIPFILE config.csv simple.csv
scp -i /home/ubuntu/.ssh/Utility.pem /tmp/${ZIPFILE} ubuntu@10.0.0.109:/tmp/
ssh  -i /home/ubuntu/.ssh/Utility.pem ubuntu@10.0.0.109 sudo aws s3 cp /tmp/${ZIPFILE}  s3://qa-americanswan/dump/
echo -e "Hi Deepak,\n\nPlease download simple and config reports from below link:\n#https://s3-ap-southeast-1.amazonaws.com/qa-americanswan/dump/${ZIPFILE} .\n\nThanks,\nAS Support Team\n\n\n" | mailx -s "Simple and Config Reports" $MAILRECEIPENTS -aFrom:as-support@intelligrape.com
