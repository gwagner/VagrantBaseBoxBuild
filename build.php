<?php

# Auto build a base box

$startTime = time();

# Get the log file
$logFile = dirname(__FILE__).'/log/log_'.$startTime.'_build.log';

$logHandle = fopen($logFile, 'a+');

# Clear out any of the old files except for the base_box file
//$log = shell_exec('find vagrant/modules -type d -not -name "base_box" -maxdepth 1 | grep modules/ | xargs rm -rf');
//fwrite($logHandle, $log."\n");

# Build the box
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant up --no-provision'
    .' && vagrant provision'
    .' && vagrant ssh -c "sudo yum clean all"'
    .' && vagrant halt'
);
fwrite($logHandle, $log."\n");

# Find out the ID of the box
$vmInfo = json_decode(file_get_contents(dirname(__FILE__).'/vagrant/.vagrant'), true);
$machineInfo = shell_exec('VBoxManage showvminfo '.$vmInfo['active']['default']);
fwrite($logHandle, $machineInfo."\n");

# Rename the box so that vagrant can boxitize it correctly
$log = shell_exec('VBoxManage modifyvm '.$vmInfo['active']['default'].' --name centos-63-64-'.$startTime);
fwrite($logHandle, $log."\n");

# Find all the shared folders
preg_match_all('/name\:\s\'([^\']+)\',\shost\spath\:\s\'([^\']+)\'/i', $machineInfo, $matches);

# Drop all of the shared folders
foreach($matches[1] as $key => $match)
{
    fwrite($logHandle, 'Removing Shared Folder: '.$match.' ('.$matches[2][$key].')'."\n");
    $log = shell_exec('VBoxManage sharedfolder remove '.$vmInfo['active']['default'].' --name "'.$match.'"'."\n");
    fwrite($logHandle, $log);
}

# Build the box for distribution
$log = shell_exec(
    'vagrant package --output centos63-64.box --base centos-63-64-'.$startTime
);
fwrite($logHandle, $log."\n");

# Remove the box we build against
$log = shell_exec('VBoxManage unregistervm '.$vmInfo['active']['default'].' --delete');
fwrite($logHandle, $log."\n");

# Close the handle, done logging
fclose($logHandle);
