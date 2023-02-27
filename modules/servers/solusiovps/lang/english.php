<?php

// Copyright 2020. Plesk International GmbH.

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$_LANG['solusiovps_config_option_plan'] = 'Plan';
$_LANG['solusiovps_config_option_operating_system'] = 'Operating System';
$_LANG['solusiovps_config_option_application'] = 'Application';
$_LANG['solusiovps_config_option_default_operating_system'] = 'Default Operating System';
$_LANG['solusiovps_config_option_application'] = 'Application';
$_LANG['solusiovps_config_option_default_location'] = 'Default Location';
$_LANG['solusiovps_config_option_backup_enabled'] = 'Enable Backups';
$_LANG['solusiovps_config_option_user_data'] = 'User Data';
$_LANG['solusiovps_config_option_none'] = '(None)';
$_LANG['solusiovps_config_option_default_role'] = 'Default Role';
$_LANG['solusiovps_config_option_default_limit_group'] = 'Default Limit Group';

$_LANG['solusiovps_button_restart'] = 'Reboot';
$_LANG['solusiovps_button_sync'] = 'Sync account';
$_LANG['solusiovps_button_vnc'] = 'Console';
$_LANG['solusiovps_button_reinstall'] = 'Reinstall';
$_LANG['solusiovps_button_cancel'] = 'Cancel';
$_LANG['solusiovps_button_close'] = 'Close';
$_LANG['solusiovps_button_start'] = 'Start';
$_LANG['solusiovps_button_stop'] = 'Stop';
$_LANG['solusiovps_button_reset_pw'] = 'Reset Root Password';
$_LANG['solusiovps_button_change_hostname'] = 'Change Hostname';
$_LANG['solusiovps_button_rescue_mode'] = 'Rescue';
$_LANG['solusiovps_button_create_backup'] = 'Create Backup';
$_LANG['solusiovps_button_restore_backup'] = 'Restore';

$_LANG['solusiovps_confirm_reinstall'] = 'Reinstall the server?';
$_LANG['solusiovps_password_reset_success'] = 'The root password has been reset. Please check your mailbox for an email with a password-reset link.';
$_LANG['solusiovps_new_hostname'] = 'Change hostname to:';
$_LANG['solusiovps_confirm_change_hostname'] = 'To change the hostname, the machine needs to be rebooted. Continue?';
$_LANG['solusiovps_hostname_changed'] = 'The hostname has been changed';

$_LANG['solusiovps_error_server_already_created'] = 'A server can be created only once.';
$_LANG['solusiovps_error_server_not_found'] = 'The server was not found.';
$_LANG['solusiovps_error_change_hostname'] = 'Failed to change the hostname';
$_LANG['solusiovps_error_user_not_found'] = 'User with such email not found in SolusIO';

$_LANG['boot_mode_button_title1'] = 'Boot from Disk';
$_LANG['boot_mode_button_description1'] = 'Select this option to boot your server from the disk the next time the server is restarted.';
$_LANG['boot_mode_button_title2'] = 'Boot from Rescue ISO';
$_LANG['boot_mode_button_description2'] = 'Select this option to boot your server from the rescue ISO the next time the server is restarted.';

$_LANG['solusiovps_rescue_mode_summary'] = 'Booting from the rescue ISO helps you fix kernel mismatches and corrupted file systems.';
$_LANG['solusiovps_rescue_mode_description'] = 'By default, a server is booted from its disk. If your server was booted from the rescue ISO and you want to boot the server from the disk again, do the following:<br /><br />1. Shut down or reboot your server. To shut down the server, click the Stop button above or use the command line.<br /><br />2. Power on your server. To do so, click the Start button above or boot the server from its disk.';

$_LANG['solusiovps_exception_page_default_title'] = 'Oops! Something went wrong.';
$_LANG['solusiovps_exception_page_default_message'] = 'Please go back and try again. If the problem persists, please contact technical support.';
$_LANG['solusiovps_exception_page_pending_title'] = 'The service is being provisioned';
$_LANG['solusiovps_exception_page_pending_message'] = 'Your service is being provisioned. If you have any questions, please contact technical support.';
$_LANG['solusiovps_exception_page_cancelled_title'] = 'The service was terminated';
$_LANG['solusiovps_exception_page_cancelled_message'] = 'This service was already terminated. If you have any questions, please contact technical support.';

$_LANG['solusiovps_chart_cpu_title'] = 'CPU usage';
$_LANG['solusiovps_chart_cpu_label_load'] = 'Load average (%)';

$_LANG['solusiovps_chart_network_title'] = 'Network usage';
$_LANG['solusiovps_chart_network_label_read'] = 'Read KiB';
$_LANG['solusiovps_chart_network_label_write'] = 'Write KiB';

$_LANG['solusiovps_chart_disk_title'] = 'Disk usage';
$_LANG['solusiovps_chart_disk_label_read'] = 'Read KiB';
$_LANG['solusiovps_chart_disk_label_write'] = 'Write KiB';

$_LANG['solusiovps_chart_memory_title'] = 'Memory usage';
$_LANG['solusiovps_chart_memory_label_usage'] = 'Usage MiB';

$_LANG['solusiovps_traffic'] = 'Total traffic';


$_LANG['keep_safe'] = 'Make sure to keep this safe!';
$_LANG['username'] = 'Username';

$_LANG['suspendtext'] = 'Your service has been suspended for the following reasons';

$_LANG['contact_support'] = 'Contact Support';

$_LANG['status'] = 'Status';

$_LANG['reg_date'] = 'Registration Date';
$_LANG['recurring_amount'] = 'Recurring Amount';
$_LANG['billing_cycle'] = 'Billing Cycle';
$_LANG['next_due'] = 'Next Due Date';
$_LANG['payment_method'] = 'Payment Method';

$_LANG['service_actions'] = 'Service Actions';

$_LANG['service_status_started'] = 'STARTED';

//Tabs

$_LANG['server_information'] = 'Server Information';
$_LANG['network_information'] = 'Network Information';
$_LANG['server_credentials'] = 'Server Credentials';
$_LANG['usage_graphs'] = 'Usage Graphs';
$_LANG['backups_tab'] = 'Backups';


$_LANG['ip_address'] = 'IP Address';
$_LANG['hostname_info'] = 'Hostname';
$_LANG['operating_system'] = 'Operating System';
$_LANG['machine_specs'] = 'Machine Specs';
$_LANG['vcpu'] = 'vCPU';
$_LANG['cores'] = 'Cores';
$_LANG['memory'] = 'Memory';
$_LANG['storage'] = 'Storage';
$_LANG['total_used_traffic'] = 'Total Used Traffic';
$_LANG['unmetered'] = 'Unmetered';
$_LANG['additional_ip_addresses'] = 'Additional IP Addresses';
$_LANG['login_details'] = 'Login Details';
$_LANG['view_login_details'] = 'Click me to view login details';


$_LANG['backup_time'] = 'Time';
$_LANG['backup_status'] = 'Status';
$_LANG['backup_action'] = 'Action';
$_LANG['upgrade'] = 'Upgrade this Virtual machine';





