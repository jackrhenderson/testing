<style>
.rescue-container {
    display: flex;
    margin-bottom: 10px;
}

.boot-mode-button {
    border: 1px solid silver;
    cursor: pointer;
    display: grid;
    flex: 1;
    grid-template-columns: 50px 1fr;
    grid-template-rows: auto;
    grid-column-gap: 0px;
    grid-row-gap: 0px;
    margin: 5px;
}

.boot-mode-button--pushed {
    background-color: #ececec;
}

.boot-mode-button-image {
    grid-area: 1 / 1 / 3 / 2;
    text-align: center;
}

.boot-mode-button-title {
    font-size: 17px;
    font-weight: bold;
    grid-area: 1 / 2 / 2 / 3;
}

.boot-mode-button-description {
    grid-area: 2 / 2 / 3 / 3;
}
</style>

<script src="modules/servers/solusiovps/node_modules/chart.js/dist/Chart.js"></script>
<script type="text/javascript" src="modules/servers/solusiovps/node_modules/jsonform/deps/underscore.js"></script>
<script type="text/javascript" src="modules/servers/solusiovps/node_modules/jsonform/deps/opt/jsv.js"></script>
<script type="text/javascript" src="modules/servers/solusiovps/node_modules/jsonform/lib/jsonform.js"></script>
<script type="text/javascript" src="modules/servers/solusiovps/js/jquery.validate.min.js"></script>

<div id="dlg-reinstall-selector" class="modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body" style="padding-top: 0px; padding-left: 10px; padding-right: 10px;">
            	<div class="panel panel-default" style="box-shadow: none">
                    <div class="panel-nav">
                        <ul class="nav nav-tabs">
                            <li>
                                <a class="active" href="#operating-system" data-toggle="tab">Operating Systems</a>
                            </li>
                            <li>
                                <a class="" href="#application-install" data-toggle="tab">Applications</a>
                            </li>
                        </ul>
                    </div>

				    <div class="tab-content">
					    <div class="tab-pane active" id="operating-system">
                            <div id="os-id-block">
                                <p style="padding-top: 10px;">Please select your preferred Operating System:</p>
                                <p>
                                    <select id="fld-os-id" class="form-control" onchange="onChangeOs();">
                                    <option value="1">AlmaLinux 8.3</option>
                                    <option value="2">AlmaLinux 8.4</option>
                                    <option value="3">AlmaLinux 9.0</option>
                                    <option value="4">AlpineLinux 3.15</option>
                                    <option value="5">CentOS 7</option>
                                    <option value="6">CentOS 8</option>
                                    <option value="7">CentOS Stream</option>
                                    <option value="8">Debian 9</option>
                                    <option value="9">Debian 10</option>
                                    <option value="10">Debian 11</option>
                                    <option value="11">RockyLinux 8.4</option>
                                    <option value="12">Ubuntu 18.04</option>
                                    <option value="13">Ubuntu 20.04</option>
                                    <option value="14">Ubuntu 22.04</option> 
                                    {if $configurableoptions}
                                        {foreach from=$configurableoptions item=configoption}
                                            {if $configoption.selectedoption|strstr:"Windows"}
                                                <option value="15">Windows Server 2019</option>
                                                <option value="16">Windows Server 2022 Evaluation</option>
                                                {$windowcheck=true}
                                            {/if}
                                        {/foreach}
                                    {/if}
                                    </select>
                                </p>
                            </div>
					    </div>
                        <div class="tab-pane" id="application-install">
                            <div id="application-id-block">
                                <p style="padding-top: 10px">Please select your preferred Application:</p>
                                <p>
                                    <select id="fld-application-id" class="form-control" onchange="onChangeApplication(this.value);">
                                        <option value="0"></option>
                                    </select>
                                </p>
                                <form id="reinstall-form"></form>
                            </div>
                        </div>
                    </div>
                </div>                
                <p style="text-align: right;">
                    <button class="btn btn-danger" onclick="reinstallServerConfirm();">
                        {$LANG.solusiovps_button_reinstall}
                    </button>
                    <button class="btn btn-secondary" onclick="reinstallServerCancel();">
                        {$LANG.solusiovps_button_cancel}
                    </button>
                </p>
            </div>
        </div>
    </div>
</div>

<div id="dlg-rescue-mode" class="modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <p>
                    {$LANG.solusiovps_rescue_mode_summary}
                </p>
                <div class="rescue-container">
                    <div id="btn-boot-mode-disk" class="boot-mode-button boot-mode-button--pushed" onclick="setBootMode('disk');">
                        <div class="boot-mode-button-image">
                            <img src="modules/servers/solusiovps/img/hdd.png" />
                        </div>
                        <div class="boot-mode-button-title">{$LANG.boot_mode_button_title1}</div>
                        <div class="boot-mode-button-description">{$LANG.boot_mode_button_description1}</div>
                    </div>
                    <div id="btn-boot-mode-rescue" class="boot-mode-button" onclick="setBootMode('rescue');">
                        <div class="boot-mode-button-image">
                            <img src="modules/servers/solusiovps/img/cd.png" />
                        </div>
                        <div class="boot-mode-button-title">{$LANG.boot_mode_button_title2}</div>
                        <div class="boot-mode-button-description">{$LANG.boot_mode_button_description2}</div>
                    </div>
                </div>
                <p>
                    {$LANG.solusiovps_rescue_mode_description}
                </p>
                <p style="text-align: right;">
                    <button class="btn btn-info" onclick="rescueModeClose();">
                        {$LANG.solusiovps_button_close}
                    </button>
                </p>
            </div>
        </div>
    </div>
</div>

<div id="login-details" class="modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <p>{$LANG.keep_safe}</p>
                <p>{$LANG.username}: <span class="list-info-text">
                {if $data['os_name']|strstr:"Windows"}Administrator{else}root{/if}</span><br>Password: <span class="list-info-text" data-hidden-value="{$password}"><span class="display">•••••••••••</span><a class="toggle"> <i class="far fa-eye" onclick="hideshow(this)"></i></a></span></p>

                <p style="text-align: right;">
                    <button onclick="resetPassword();" id="btn-reset-pw" class="btn btn-info">
                            <i class="fas fa-key"></i> {$LANG.solusiovps_button_reset_pw}
                    </button>
                    <button class="btn btn-info" onclick="loginDetailsClose();">
                        {$LANG.solusiovps_button_close}
                    </button>
                </p>
            </div>
        </div>
    </div>
</div>

<div id="rdns-details" class="modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <p>Please contact us to have this changed!</p>
                <p>Reverse DNS: <span class="list-info-text">{$data['reversedns']}</span></p>

                <p style="text-align: right;">
                    <button class="btn btn-info" onclick="rDNSclose();">
                        {$LANG.solusiovps_button_close}
                    </button>
                </p>
            </div>
        </div>
    </div>
</div>

{if $suspendreason}
<div class="alert alert-lagom alert-danger" id="alertSuspended">
        <div class="alert-body">
            {$LANG.suspendtext}: "<strong>{$suspendreason}</strong>".
        </div>
        <div class="alert-actions pull-right">
        <a href="https://my.catalystvm.com/submitticket.php?step=2&deptid=1&subject=My%20service%20has%20been%20suspended&message=Your%20message%20here..." class="btn btn-xs btn-danger">
            {$LANG.contact_support}
        </a>
    </div>
</div>
{/if}

<div class="product-details">
        <div class="row row-eq-height row-eq-height-sm">
            <div class="col-md-6">
                <div class="product-icon" id="cPanelPackagePanel">
                    <div class="product-content">
                        <div class="product-image">
                            {include file="$template/includes/common/svg-icon.tpl" icon="web-hosting" onDark=true}                         
                        </div>
                        <h2 class="product-name">{$groupname} - {$product}</h2>
                        <div class="product-status">Server {$LANG.status}:
                            <span id="server-status" class="label label-success">STARTED</span>
                        </div>
                    </div>
                    <a class="product-footer" href="#" onclick="loginDetailsOpen();"><i class="fas fa-sign-in"></i> View login details</a>
                </div>
            </div>
        <div class="col-md-6">
            <div class="product-info">
                <ul class="list-info list-info-v">
                    <li>
                        <span class="list-info-title">{$LANG.status}</span>
                    {if $status eq 'Active'}
                        <span class="list-info-text"><span class="status status-active">{$status}</span></span>
                    {else if $status eq 'Pending'}
                        <span class="list-info-text"><span class="status status-pending">{$status}</span></span>
                    {else if $status eq 'Terminated'}
                        <span class="list-info-text"><span class="status status-terminated">{$status}</span></span>
                    {else if $status eq 'Suspended'}
                        <span class="list-info-text"><span class="status status-suspended">{$status}</span></span>
                    {/if}
                    </li>
                    <li>
                        <span class="list-info-title">{$LANG.reg_date}</span>
                        <span class="list-info-text">{$regdate}</span>
                    </li>
                    <li>
                        <span class="list-info-title">{$LANG.recurring_amount}</span>
                        <span class="list-info-text">{$recurringamount}</span>
                    </li>
                    <li>
                        <span class="list-info-title">{$LANG.billing_cycle}</span>
                        <span class="list-info-text">{$billingcycle}</span>
                    </li>
                    <li>
                        <span class="list-info-title">{$LANG.next_due}</span>
                        <span class="list-info-text">{$nextduedate}</span>
                    </li>
                    <li>
                        <span class="list-info-title">Payment Method</span>
                        <span class="list-info-text">{$paymentmethod}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
{if $status neq 'Suspended'}
<div class="section">
    <div class="section-header">
        <h2 class="section-title">Service Actions</h2>
    </div>
    <div class="section-body" id="servactions" style="display: none">
        <div class="tiles swiper-container">
            <div class="row swiper-wrapper">
                <div class="col-md-3 swiper-slide" id="startserv" onclick="startServer();" disabled="">
                    <a class="tile">
                        <div class="tile-stat"><img src="modules/servers/solusiovps/templates/assets/img/serviceActions/startButton.webp"></div>
                        <div class="tile-title">Start</div>
                    </a>
                </div>
                <div class="col-md-3 swiper-slide" id="stopserv" onclick="stopServer();">
                    <a class="tile">
                        <div class="tile-stat"><img src="modules/servers/solusiovps/templates/assets/img/serviceActions/stopButton.webp"></div>
                        <div class="tile-title">Stop</div>
                    </a>
                </div>
                <div class="col-md-3 swiper-slide" id="restartserv" onclick="restartServer();">
                    <a class="tile">
                        <div class="tile-stat"><img src="modules/servers/solusiovps/templates/assets/img/serviceActions/rebootButton.webp"></div>
                        <div class="tile-title">Reboot</div>
                    </a>
                </div>
                <div class="col-md-3 swiper-slide" id="reinstallserv" onclick="reinstallServer();">
                    <a class="tile">
                        <div class="tile-stat"><img src="modules/servers/solusiovps/templates/assets/img/serviceActions/reinstall.webp"></div>
                        <div class="tile-title">Reinstall</div>
                    </a>
                </div>
                <div class="col-md-3 swiper-slide" id="vncserv" onclick="openVncDialog();">
                    <a class="tile">
                        <div class="tile-stat"><img src="modules/servers/solusiovps/templates/assets/img/serviceActions/vnc.webp"></div>
                        <div class="tile-title">Console</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<br>
{/if}


<div class="media">
	<div class="media-body">
		<div class="section-body">
			<div class="panel panel-default">
				<div class="panel-nav">
					<ul class="nav nav-tabs">
						<li>
							<a class="active" href="#domain" data-toggle="tab"><i class="fas fa-info-circle"></i> Server Information</a>
						</li>
                        {assign var="ips" value=(","|explode:$assignedips)}
                        {if $ips|count > 1}
                            <li>
                                <a href="#networkinformation" data-toggle="tab"><i class="fas fa-globe"></i> Network Information</a> 
                            </li>
                        {/if}
						<li>
							<a class="" href="#usage" data-toggle="tab"><i class="fas fa-chart-area"></i> Usage Graphs</a>
						</li>
                        {if $data['backupenabled'] eq '1'}
						<li>
							<a class="" href="#backups" data-toggle="tab"><i class="fas fa-download"></i> Backups</a>
						</li>
                        {/if}
					</ul>
				</div>

				<div class="tab-content">
					<div class="tab-pane active" id="domain">
						<ul class="list-info list-info-50 list-info-bordered">
                        	<li>
								<span class="list-info-title">IP Address</span>
								<span class="list-info-text"><span id="myString">{$data['ip']}</span><a class=""> <i class="fas fa-copy" onclick="copyToClipboard('#myString', this)"></i> <i class="fas fa-info-circle" onclick="rDNSopen();"></i></a></span> 
							</li>
							<li>
								<span class="list-info-title">Hostname</span>
								<span class="list-info-text">{$data['domain']} <a class=""> <i class="fad fa-edit" onclick="changeHostname();" title="{$LANG.solusiovps_button_change_hostname}"></i></a></span>
							</li>
                            <li>
								<span class="list-info-title">Operating System</span>
								<span class="list-info-text">
                                {assign var=oslookup value=[
                                    'CentOS' => 'centos',
                                    'Ubuntu' => 'ubuntu',
                                    'AlmaLinux' => 'alma',
                                    'AlpineLinux' => 'alpine',
                                    'Debian' => 'debian',
                                    'RockyLinux' => 'rocky',
                                    'Windows' => 'windows',
                                    
                                    'Uvodo' => 'uvodo',
                                    'Cloudron' => 'cloudron',
                                    'Gitlab' => 'gitlab',
                                    'NextCloud' => 'nextcloud',
                                    'OpenVPN' => 'openvpn',
                                    'NGINX' => 'nginx',
                                    'Jenkins' => 'jenkins',
                                    'Node.js' => 'nodejs',
                                    'MariaDB' => 'mariadb'
                                ]}
                                {assign var=osimage value="cloud"}
                                {foreach key=key item=os from=$oslookup}
                                    {if $data['os_name']|strstr:$key}
                                        {$osimage = $os}
                                    {/if}
                                {/foreach}
                                <img src="modules/servers/solusiovps/templates/assets/img/{$osimage}.svg" style="height: 20px">
                                {$data['os_name']}
                                </span>
							</li>
                            <li>
								<span class="list-info-title">Machine Specs</span>
								<span class="list-info-text">vCPU: {$data['vcpu']} Cores | Memory: {$data['ramsize']/1048576/1024}GB | Storage: {$data['disksize']}GB <a class="" href="upgrade.php?type=package&amp;id={$id}"> <i class="fas fa-level-up"  title="Upgrade this Virtual machine"></i></a></span> 
							</li>				
							<li>
								<span class="list-info-title">Total Used Traffic</span>
                                <span class="list-info-text">
                                    {$data['traffic_current']} {$data['traffic_unit']} / Unmetered
								</span>
							</li>
						</ul>
					</div>
                    {if $ips|count > 1}
                        <div class="tab-pane" id="networkinformation">
                            <ul class="list-info list-info-50 list-info-bordered">
                                <li>
                                    <span class="list-info-title">Additional IP Addresses</span>
                                    <span class="list-info-text">
                                        {foreach $ips as $ip}
                                            {if !$ip@first}
                                                {$ip}<br>
                                            {/if}
                                        {/foreach}
                                    
                                    </span> 
                                </li>
                            </ul>
                        </div>
                    {/if}

					<div class="tab-pane" id="usage">
						<div class="row" style="display: -webkit-flex; display: flex;">
							<div class="col-sm-12">
								<div class="panel panel-default" style="border: none;">
									<div class="panel-body">
										<h4>{$LANG.solusiovps_chart_cpu_title}</h4>
										<canvas id="cpuChart" style="height: 200px; width: 100%; display: block;" width="880" height="200"></canvas>
									</div>
								</div>
							</div>
						</div>
                        <div class="row" style="display: -webkit-flex; display: flex;">
							<div class="col-sm-12">
								<div class="panel panel-default" style="border: none;">
									<div class="panel-body">
										<h4>{$LANG.solusiovps_chart_memory_title}</h4>
										<canvas id="memoryChart" style="height: 200px; width: 100%; display: block;" width="880" height="200"></canvas>
									</div>
								</div>
							</div>
						</div>
						<div class="row" style="display: -webkit-flex; display: flex;">
							<div class="col-sm-12">
								<div class="panel panel-default" style="border: none;">
									<div class="panel-body">
										<h4>{$LANG.solusiovps_chart_network_title}</h4>
										<canvas id="networkChart" style="height: 200px; width: 100%; display: block;" width="880" height="200"></canvas>
									</div>
								</div>
							</div>
						</div>
						<div class="row" style="display: -webkit-flex; display: flex;">
							<div class="col-sm-12">
								<div class="panel panel-default" style="border: none;">
									<div class="panel-body">
										<h4>{$LANG.solusiovps_chart_disk_title}</h4>
										<canvas id="diskChart" style="height: 200px; width: 100%; display: block;" width="880" height="200"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane" id="backups">
                    <button style="float: right; margin: 10px 10px 10px 0;" onclick="rescueMode();" id="btn-rescue-mode" class="btn btn-danger">
					    <i class="fas fa-fire-extinguisher"></i> {$LANG.solusiovps_button_rescue_mode}
					</button>
                    <button style="float: right; margin: 10px 10px 10px 0;" onclick="createBackup();" id="btn-vnc" class="btn btn-info">
                        <i class="fas fa-hdd"></i> {$LANG.solusiovps_button_create_backup}
                    </button>
						<table id="tbl_backups" class="table table-striped">
                            <thead>
                                <tr>
                                    <td style="padding: var(--ui-block-padding-base-v) var(--ui-block-padding-base);">Time</td>
                                    <td>Status</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{if $availableAddonProducts}
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">{lang key='addonsExtras'}</h3>
        </div>
        <div class="section-body">
            <div class="panel panel-form" id="cPanelExtrasPurchasePanel">
                <div class="panel-body">
                    <form method="post" action="{$WEB_ROOT}/cart.php?a=add">
                        <input type="hidden" name="serviceid" value="{$serviceid}" />
                        <div class="row row-sm">
                            <div class="col-sm-8">
                            <select name="aid" class="form-control">
                                {foreach $availableAddonProducts as $addonId => $addonName}
                                    <option value="{$addonId}">{$addonName}</option>
                                {/foreach}
                            </select>
                            </div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {lang key='purchaseActivate'}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>    
{/if}

<script>
const operatingSystems = {$data['operating_systems']};
const defaultOsId = {$data['default_os_id']};
const applications = {$data['applications']};
const defaultApplicationId = {$data['default_application_id']};

let domain = '{$data['domain']}';
let bootMode = '{$data['boot_mode']}';

// start show/hide password
$('[data-hidden-value] > .toggle').on('click', function () {
  var
    $wrapper = $(this).parent(),
    $display = $wrapper.find('.display'),
    revealed = $wrapper.data('revealed'),
    hiddenValue = String($wrapper.data('hidden-value'))
  ;
    
  $display.html(revealed ? hiddenValue.replace(/./g, '•') : hiddenValue);
  $wrapper.data('revealed', !revealed);
});
function hideshow(x) {
  x.classList.toggle("fa-eye-slash");
}
// end show/hide password
function copyToClipboard(element, icon) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(element).text()).select();
    document.execCommand("copy");
    $(icon).removeClass("fa-copy").addClass("fa-check");
    setTimeout(function(){
        $(icon).removeClass("fa-check").addClass("fa-copy");
    }, 1000);
    $temp.remove();
}

const statusUpdate = status => {
    $('#btn-start-server').prop('disabled', (status !== 'stopped'));
    $('#btn-stop-server').prop('disabled', (status !== 'started'));
    $('#btn-restart-server').prop('disabled', (status !== 'started'));
    $('#btn-reinstall-server').prop('disabled', ((status !== 'stopped') && (status !== 'started')));
    $('#btn-vnc').prop('disabled', (status !== 'started'));
    $('#btn-reset-pw').prop('disabled', (status !== 'started'));
    $('#btn-change-hostname').prop('disabled', ((status !== 'stopped') && (status !== 'started')));
    $('#btn-rescue-mode').prop('disabled', ((status !== 'stopped') && (status !== 'started')));
}

const checkStatus = () => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "Status"
        }
    }).done(function (status) {
        $("#server-status").text(status);
        $("#server-status").removeClass();

        $("#server-status").addClass('label');
        $("#servactions").show();
        $("#startserv").hide();
        $("#stopserv").hide();
        $("#reinstallserv").hide();
        $("#restartserv").hide();
        $("#vncserv").hide();

        if (status == "creating the backup" || status == "restarting" || status == "reinstalling") {
            //change to label label-warning
            $("#server-status").addClass('label-warning');
        } else if (status == "stopped") {
            //change to label label-danger
            $("#server-status").addClass('label-danger');

            $("#startserv").show();
            $("#stopserv").hide();
            $("#restartserv").hide();
            $("#vncserv").hide();
            $("#reinstallserv").hide();
        } else if (status == 'started') {
            $("#server-status").addClass('label-success');

            $("#startserv").hide();
            $("#stopserv").show();
            $("#restartserv").show();
            $("#vncserv").show();
            $("#reinstallserv").show();
        } else if (status == 'creating') {
            $("#server-status").addClass('label-info');
        } else {
            $("#server-status").addClass('label-danger');
        }

        statusUpdate(status);

        setTimeout(checkStatus, 1000);
    });
}

const startServer = () => {
    $("#server-status").text('starting');
    $("#server-status").removeClass();
    $("#server-status").addClass('label label-success');
    
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "Start"
        }
    });
}

const stopServer = () => {
    $("#server-status").text('stopping');
    $("#server-status").removeClass();
    $("#server-status").addClass('label label-danger');
    
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "Stop"
        }
    }).done(function() {
        $("#stopserv").hide();
        $("#startserv").show();
    });
}

const restartServer = () => {
    $("#server-status").text('restarting');
    $("#server-status").removeClass();
    $("#server-status").addClass('label label-warning');

    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "Restart"
        }
    });
}

const onChangeOs = () => {
    let $select = $('#fld-application-id');
    $select.val(0);
    $('#reinstall-form').html("");
}

const onChangeApplication = (value) => {
    let $select = $('#fld-os-id');
    $select.val(0);

    if (applications[value] !== undefined) {
        $('#reinstall-form').html("").jsonForm({
            schema: applications[value]['schema'],
            form: ["*"],
        });
    }
}

const reinstallServer = () => {
    if (!window.confirm('{$LANG.solusiovps_confirm_reinstall}')) {
        return;
    }

    if (Object.keys(operatingSystems).length === 0 && Object.keys(applications).length === 0) {
        reinstallServerContinue(defaultOsId, 0);
        return;
    }

    if (Object.keys(operatingSystems).length > 0) {
        $('#reinstall-form').html("");
        let $select = $('#fld-os-id');

        //$select.empty();

        //for (const [id, name] of Object.entries(operatingSystems)) {
            //$select.append($('<option>', {
            //   value: id,
            //   text: name
           // }));
        //}

        $select.val(defaultOsId);
    } else {
        $('#os-id-block').hide();
    }

    if (Object.keys(applications).length > 0) {
        let $select = $('#fld-application-id');

        $select.empty();

        for (const [id, application] of Object.entries(applications)) {
            $select.append($('<option>', {
                value: id,
                text: application['name']
            }));
        }

        if (Object.keys(operatingSystems).length === 0 || defaultOsId === 0) {
            $select.val(defaultApplicationId);
            if (defaultApplicationId > 0) {
                onChangeApplication(defaultApplicationId);
            }
        } else {
            $select.val(0);
        }
    } else {
        $('#application-id-block').hide();
    }

    $('#dlg-reinstall-selector').modal('show');
}

const reinstallServerContinue = (osId, applicationId = 0, applicationData = null) => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "Reinstall",
            osId: osId,
            applicationId: applicationId,
            applicationData: applicationData
        }
    });
}

const reinstallServerConfirm = () => {
    const osId = $('#fld-os-id').val();
    const applicationId = $('#fld-application-id').val();
    const form = $('#reinstall-form');
    let applicationData = new Object();

    if(applicationId > 0 && $(form).valid()) {
        form.serializeArray().map(field => applicationData[field.name] = field.value);
    }
    
    if (osId == "15" || osId == "16"){
        {if $windowcheck eq true}
            reinstallServerContinue(osId, applicationId, applicationData);
        {else}
            alert("You do not have a windows license purchased. This will be reported.");
            reinstallServerContinue(1, applicationId, applicationData);
        {/if}
    } else {
        reinstallServerContinue(osId, applicationId, applicationData);
    }

    
    $('#dlg-reinstall-selector').modal('hide');
}

const reinstallServerCancel = () => {
    $('#dlg-reinstall-selector').modal('hide');
}

const openVncDialog = () => {
    const width = 800;
    const height = 450;
    const top = (screen.height / 2) - (height / 2);
    const left = (screen.width / 2) - (width / 2);
    const url = 'clientarea.php?action=productdetails&id={$serviceid}&a=VNC';
    const features = "menubar=no,location=no,resizable=yes,scrollbars=yes,status=no,width=" + width + ",height=" + height + ",top=" + top + ",left=" + left;

    window.open(url, '', features);
}

const resetPassword = () => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "ResetRootPass"
        },
        success: function (response) {
            alert(response);
        }
    });
}

const changeHostname = () => {
    const hostname = prompt('{$LANG.solusiovps_new_hostname}', domain);

    if ((hostname === null) || (hostname === '') || (hostname === domain)) {
        return;
    }

    if (!confirm('{$LANG.solusiovps_confirm_change_hostname}')) {
        return;
    }

    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            hostname: hostname,
            a: "ChangeHostName"
        },
        success: function(response) {
            domain = hostname;

            restartServer();

            if (response.includes("Invalid domain")){
                alert("Error: Please provide a valid domain name.");
            } else {
                alert("Error: Something went wrong, please contact support.");
            }  
        }
    });
}

const rescueMode = () => {
    updateBootMode();

    $('#dlg-rescue-mode').modal('show');
}

const rescueModeClose = () => {
    $('#dlg-rescue-mode').modal('hide');
}

const loginDetailsOpen = () => {
    $('#login-details').modal('show');
}

const loginDetailsClose = () => {
    $('#login-details').modal('hide');
}

const rDNSopen = () => {
    $('#rdns-details').modal('show');
}

const rDNSclose = () => {
    $('#rdns-details').modal('hide');
}

const updateBootMode = () => {
    $('.boot-mode-button').removeClass('boot-mode-button--pushed');

    if (bootMode === 'disk') {
        $('#btn-boot-mode-disk').addClass('boot-mode-button--pushed');
    } else {
        $('#btn-boot-mode-rescue').addClass('boot-mode-button--pushed');
    }
}

const setBootMode = mode => {
    if (bootMode === mode) {
        return;
    }

    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            bootMode: mode,
            a: "ChangeBootMode"
        }
    });

    bootMode = mode;

    updateBootMode();
}

const getBackups = () => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "GetBackups"
        },
        dataType: 'json'
    }).done(function (backups) {
        let $tbody = $("#tbl_backups > tbody");

        $tbody.empty();

        backups.forEach(function (backup) {
            let restore = '';

            if (backup.status === 'created') {
                restore = '<a href="javascript:;" onclick="restoreBackup(' + backup.id + ');">{$LANG.solusiovps_button_restore_backup}</a>';
            }

            let html = '<tr>';
            html += '<td>' + backup.time + '</td>';
            html += '<td>' + backup.status + '</td>';
            html += '<td>' + restore + '</td>';
            html += '</tr>';

            $tbody.append(html);
        });

        setTimeout(getBackups, 3000);
    });
}

const createBackup = () => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "CreateBackup"
        }
    });
}

const restoreBackup = backupId => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "RestoreBackup",
            backupId: backupId
        }
    });
}

const getUsage = () => {
    $.get({
        url: 'clientarea.php?action=productdetails',
        data: {
            id: {$serviceid},
            a: "Usage"
        },
        dataType: 'json'
    }).done(function (usage) {
        cpuChartData.labels = [];
        cpuChartData.datasets[0].data = [];

        usage.cpu.forEach(item => {
            cpuChartData.labels.push(item.second);
            cpuChartData.datasets[0].data.push(item.load_average);
        });

        cpuChart.update();

        networkChartData.labels = [];
        networkChartData.datasets[0].data = [];
        networkChartData.datasets[1].data = [];

        usage.network.forEach(item => {
            networkChartData.labels.push(item.second);
            networkChartData.datasets[0].data.push(item.read_kb);
            networkChartData.datasets[0].data.push(item.write_kb);
        });

        networkChart.update();

        diskChartData.labels = [];
        diskChartData.datasets[0].data = [];
        diskChartData.datasets[1].data = [];

        usage.disk.forEach(item => {
            diskChartData.labels.push(item.second);
            diskChartData.datasets[0].data.push(item.read_kb);
            diskChartData.datasets[0].data.push(item.write_kb);
        });

        diskChart.update();

        memoryChartData.labels = [];
        memoryChartData.datasets[0].data = [];

        usage.memory.forEach(item => {
            memoryChartData.labels.push(item.second);
            memoryChartData.datasets[0].data.push(item.memory);
        });

        memoryChart.update();

        setTimeout(getUsage, 5000);
    });
}

const cpuChartData = {
    labels: [],
    datasets: [{
        label: '{$LANG.solusiovps_chart_cpu_label_load}',
        data: [],
        fill: true,
        backgroundColor: 'rgba(138,173,65,0.5)',
        borderColor: 'rgba(138,173,65,1)',
        borderWidth: 2,
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        pointRadius: 1,
        pointHitRadius: 10,
        pointHoverBackgroundColor: 'rgba(138,173,65,1)',
        pointHoverBorderColor: 'rgba(138,173,65,0.5)'
    }]
};

const cpuChart = new Chart($('#cpuChart'), {
    type: 'line',
    data: cpuChartData,
    options: {
        animation: false,
        responsive: false,
        scales: {
            xAxes: [{
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 20
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    min: 0,
                    precision: 0
                }
            }]
        }
    }
});

const networkChartData = {
    labels: [],
    datasets: [{
        label: '{$LANG.solusiovps_chart_network_label_read}',
        data: [],
        fill: true,
        backgroundColor: 'rgba(40,170,222,0.5)',
        borderColor: 'rgba(40,170,222,1)',
        borderWidth: 2,
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        pointRadius: 1,
        pointHitRadius: 10,
        pointHoverBackgroundColor: 'rgba(40,170,222,1)',
        pointHoverBorderColor: 'rgba(40,170,222,0.5)'
    },{
        label: '{$LANG.solusiovps_chart_network_label_write}',
        data: [],
        fill: true,
        backgroundColor: 'rgba(138,173,65,0.5)',
        borderColor: 'rgba(138,173,65,1)',
        borderWidth: 2,
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        pointRadius: 1,
        pointHitRadius: 10,
        pointHoverBackgroundColor: 'rgba(138,173,65,1)',
        pointHoverBorderColor: 'rgba(138,173,65,0.5)'
    }]
};

const networkChart = new Chart($('#networkChart'), {
    type: 'line',
    data: networkChartData,
    options: {
        animation: false,
        responsive: false,
        scales: {
            xAxes: [{
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 20
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    min: 0,
                    precision: 0
                }
            }]
        }
    }
});

const diskChartData = {
    labels: [],
    datasets: [{
        label: '{$LANG.solusiovps_chart_disk_label_read}',
        data: [],
        fill: true,
        backgroundColor: 'rgba(40,170,222,0.5)',
        borderColor: 'rgba(40,170,222,1)',
        borderWidth: 2,
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        pointRadius: 1,
        pointHitRadius: 10,
        pointHoverBackgroundColor: 'rgba(40,170,222,1)',
        pointHoverBorderColor: 'rgba(40,170,222,0.5)'
    },{
        label: '{$LANG.solusiovps_chart_disk_label_write}',
        data: [],
        fill: true,
        backgroundColor: 'rgba(138,173,65,0.5)',
        borderColor: 'rgba(138,173,65,1)',
        borderWidth: 2,
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        pointRadius: 1,
        pointHitRadius: 10,
        pointHoverBackgroundColor: 'rgba(138,173,65,1)',
        pointHoverBorderColor: 'rgba(138,173,65,0.5)'
    }]
};

const diskChart = new Chart($('#diskChart'), {
    type: 'line',
    data: diskChartData,
    options: {
        animation: false,
        responsive: false,
        scales: {
            xAxes: [{
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 20
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    min: 0,
                    precision: 0
                }
            }]
        }
    }
});

const memoryChartData = {
    labels: [],
    datasets: [{
        label: '{$LANG.solusiovps_chart_memory_label_usage}',
        data: [],
        fill: true,
        backgroundColor: 'rgba(138,173,65,0.5)',
        borderColor: 'rgba(138,173,65,1)',
        borderWidth: 2,
        pointBorderWidth: 1,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        pointRadius: 1,
        pointHitRadius: 10,
        pointHoverBackgroundColor: 'rgba(138,173,65,1)',
        pointHoverBorderColor: 'rgba(138,173,65,0.5)'
    }]
};

const memoryChart = new Chart($('#memoryChart'), {
    type: 'line',
    data: memoryChartData,
    options: {
        animation: false,
        responsive: false,
        scales: {
            xAxes: [{
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 20
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    min: 0,
                    precision: 0
                }
            }]
        }
    }
});

statusUpdate('{$data['status']}');
checkStatus();
getUsage();
getBackups();
</script>