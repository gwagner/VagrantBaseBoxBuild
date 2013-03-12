import "classes/*.pp"

Exec { path => [ "/bin/", "/sbin/" , "/usr/bin/", "/usr/sbin/" ] }

# Successfully run script location
$script_run_lock = "/var/lib/"

node "vagrant-centos63.vagrantup.com"
{

    include base_box
}