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
);
fwrite($logHandle, $log."\n");

# Provision the box
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant provision'
);
fwrite($logHandle, $log."\n");

# Clean yum to save space
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant ssh -c "sudo yum clean all"'
);
fwrite($logHandle, $log."\n");

# Clean the var cache to save space
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant ssh -c "sudo rm -rf /var/cache"'
);
fwrite($logHandle, $log."\n");

# Clean the locale archive to save space
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant ssh -c "sudo rm -rf /usr/lib/locale/locale-archive"'
);
fwrite($logHandle, $log."\n");

# Clean the doc archive to save space
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant ssh -c "sudo rm -rf /usr/share/doc/*"'
);
fwrite($logHandle, $log."\n");

# Write 0s to the HD to save space
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant ssh -c "sudo cat /dev/zero > zero.fill;sync;sleep 1;sync;rm -f zero.fill"'
);
fwrite($logHandle, $log."\n");

# Remvoe the .bash_history files
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
    .' && vagrant ssh -c "sudo [ -f /root/.bash_history ] && sudo rm /root/.bash_history"'
    .' && vagrant ssh -c "sudo [ -f /home/vagrant/.bash_history ] && sudo rm /home/vagrant/.bash_history"'
);
fwrite($logHandle, $log."\n");

# Stop the box
$log = shell_exec(
    'cd '.dirname(__FILE__).'/vagrant'
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

if(file_exists(dirname(__FILE__).'/centos63-64.box'))
{
    fwrite($logHandle, 'Remove old base box file'."\n");

    # drop the old box
    @unlink(dirname(__FILE__).'/centos63-64.box');
}

# Build the box for distribution
$log = shell_exec(
    'vagrant package --output centos63-64.box --base centos-63-64-'.$startTime
);
fwrite($logHandle, $log."\n");

# Remove the box we build against
$log = shell_exec('VBoxManage unregistervm '.$vmInfo['active']['default'].' --delete');
fwrite($logHandle, $log."\n");

# Replace your base box with this base box
$log = shell_exec(
    'vagrant box list'
);
fwrite($logHandle, $log."\n");

# Remove the box if it exists
if(strpos($log, 'centos63-64') !== false)
{
    fwrite($logHandle, 'Dropping old base box from vagrant'."\n");

    # Remove the old box
    $log = shell_exec(
        'vagrant box remove centos63-64'
    );
    fwrite($logHandle, $log."\n");
}

# Add in a new base box with the same name
$log = shell_exec(
    'vagrant box add centos63-64 centos63-64.box'
);
fwrite($logHandle, $log."\n");

# drop the old box
@unlink(dirname(__FILE__).'/centos63-64.box');

# Close the handle, done logging
fclose($logHandle);
