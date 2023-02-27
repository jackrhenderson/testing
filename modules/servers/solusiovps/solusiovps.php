<?php

// Copyright 2020. Plesk International GmbH.

include_once(__DIR__ . DIRECTORY_SEPARATOR . '../../../vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use WHMCS\Module\Server\SolusIoVps\Exceptions\SolusException;
use WHMCS\Module\Server\SolusIoVps\Helpers\Arr;
use WHMCS\Module\Server\SolusIoVps\Helpers\Unit;
use WHMCS\Module\Server\SolusIoVps\Logger\Logger;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Helpers\Strings;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Requests\ConfigOptionExtractor;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Requests\ServerCreateRequestBuilder;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Requests\ServerResizeRequestBuilder;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Requests\UserRequestBuilder;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\ApplicationResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\LimitGroupResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\LocationResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\OsImageResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\PlanResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\ProjectResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\RoleResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\ServerResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\UserResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\BackupResource;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Resources\UsageResource;
use WHMCS\Module\Server\SolusIoVps\Database\Migrations\Servers;
use WHMCS\Module\Server\SolusIoVps\Database\Migrations\SshKeys;
use WHMCS\Module\Server\SolusIoVps\Database\Models\Hosting;
use WHMCS\Module\Server\SolusIoVps\Database\Models\ProductConfigOption;
use WHMCS\Module\Server\SolusIoVps\Database\Models\Server;
use WHMCS\Module\Server\SolusIoVps\Database\Models\SolusServer;
use WHMCS\Module\Server\SolusIoVps\Database\Models\SolusSshKey;
use WHMCS\Module\Server\SolusIoVps\SolusAPI\Connector;
use WHMCS\Module\Server\SolusIoVps\WhmcsAPI\Config;
use WHMCS\Module\Server\SolusIoVps\WhmcsAPI\Crypt;
use WHMCS\Module\Server\SolusIoVps\WhmcsAPI\Language;
use WHMCS\Module\Server\SolusIoVps\WhmcsAPI\SshKey;
use WHMCS\Module\Server\SolusIoVps\WhmcsAPI\User;
use WHMCS\Module\Server\SolusIoVps\WhmcsAPI\Product;
use WHMCS\Service\Status;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// Run the migrations
if (!defined('SKIP_MIGRATIONS')) {
    Servers::run();
    SshKeys::run();
}

// Load translations
Language::load();

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 */
function solusiovps_MetaData(): array
{
    return [
        'DisplayName' => 'SolusIO VPS',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'ServiceSingleSignOnLabel' => false,
        'AdminSingleSignOnLabel' => false,
        'ListAccountsUniqueIdentifierDisplayName' => 'Domain',
        'ListAccountsUniqueIdentifierField' => 'domain',
        'ListAccountsProductField' => 'configoption1',
    ];
}

/**
 * @return array
 */
function solusiovps_ConfigOptions(): array
{
    return [
        'plan' => [ // configoption1
            'FriendlyName' => Language::trans('solusiovps_config_option_plan'),
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'solusiovps_PlanLoader',
            'SimpleMode' => true,
        ],
        'location' => [ // configoption2
            'FriendlyName' => Language::trans('solusiovps_config_option_default_location'),
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'solusiovps_LocationLoader',
            'SimpleMode' => true,
        ],
        'os_image' => [ // configoption3
            'FriendlyName' => Language::trans('solusiovps_config_option_default_operating_system'),
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'solusiovps_OsImageLoader',
            'SimpleMode' => true,
        ],
        'application' => [ // configoption4
            'FriendlyName' => Language::trans('solusiovps_config_option_application'),
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'solusiovps_ApplicationLoader',
            'SimpleMode' => true,
        ],
        'user_data' => [ // configoption5
            'FriendlyName' => Language::trans('solusiovps_config_option_user_data'),
            'Type' => 'textarea',
            'Rows' => 5,
            'Cols' => 25,
            'SimpleMode' => true,
        ],
        'backup_enabled' => [ // configoption6
            'FriendlyName' => Language::trans('solusiovps_config_option_backup_enabled'),
            'Type' => 'yesno',
            'SimpleMode' => true,
        ],
        'role' => [ // configoption7
            'FriendlyName' => Language::trans('solusiovps_config_option_default_role'),
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'solusiovps_RoleLoader',
            'SimpleMode' => true,
        ],
        'limit_group' => [ // configoption8
            'FriendlyName' => Language::trans('solusiovps_config_option_default_limit_group'),
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'solusiovps_LimitGroupLoader',
            'SimpleMode' => true,
        ],
    ];
}


/**
 * @throws Exception
 */
function solusiovps_PlanLoader(array $params): array
{
    try {
        $planResource = new PlanResource(Connector::create($params));
        $result = [];

        foreach ($planResource->list() as $item) {
            $result[Arr::get($item, 'id')] = Arr::get($item, 'name');
        }

        return $result;
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        throw $e;
    }
}

/**
 * @throws Exception
 */
function solusiovps_OsImageLoader(array $params): array
{
    try {
        $osImageResource = new OsImageResource(Connector::create($params));

        $result = [
            0 => Language::trans('solusiovps_config_option_none'),
        ];

        foreach ($osImageResource->list() as $item) {
            foreach ($item['versions'] as $version) {
                $result[Arr::get($version, 'id')] = Arr::get($item, 'icon.name', Arr::get($item, 'name')) . ' ' . Arr::get($version, 'version');
            }
        }

        return $result;
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        throw $e;
    }
}

/**
 * @throws Exception
 */
function solusiovps_LocationLoader(array $params): array
{
    try {
        $locationResource = new LocationResource(Connector::create($params));
        $result = [];

        foreach ($locationResource->list() as $item) {
            $result[Arr::get($item, 'id')] = Arr::get($item, 'name');
        }

        return $result;
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        throw $e;
    }
}

/**
 * @throws Exception
 */
function solusiovps_ApplicationLoader(array $params): array
{
    try {
        $applicationResource = new ApplicationResource(Connector::create($params));

        $result = [
            0 => Language::trans('solusiovps_config_option_none'),
        ];

        foreach ($applicationResource->list() as $item) {
            $result[Arr::get($item, 'id')] = Arr::get($item, 'name');
        }

        return $result;
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        throw $e;
    }
}

/**
 * @throws Exception
 */
function solusiovps_RoleLoader(array $params): array
{
    try {
        $roleResource = new RoleResource(Connector::create($params));

        $result = [
            0 => Language::trans('solusiovps_config_option_none'),
        ];

        foreach ($roleResource->list() as $item) {
            $result[Arr::get($item, 'id')] = Arr::get($item, 'name');
        }

        return $result;
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        throw $e;
    }
}

/**
 * @throws Exception
 */
function solusiovps_LimitGroupLoader(array $params): array
{
    try {
        $limitGroupResource = new LimitGroupResource(Connector::create($params));

        $result = [
            0 => Language::trans('solusiovps_config_option_none'),
        ];

        foreach ($limitGroupResource->list() as $item) {
            $result[Arr::get($item, 'id')] = Arr::get($item, 'name');
        }

        return $result;
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        throw $e;
    }
}

/**
 * @param array $params
 * @return string
 * @throws SolusException
 */
function solusiovps_CreateAccount(array $params): string
{
    if ($params['status'] !== Hosting::STATUS_PENDING) {
        return Language::trans('solusiovps_error_server_already_created');
    }

    try {
        $connector = Connector::create($params);
        $serviceId = (int)$params['serviceid'];
        $params['password'] = Strings::generatePassword();

        $userResource = new UserResource($connector);

        $solusUserId = User::syncWithSolusUser(
            $userResource,
            UserRequestBuilder::fromWHMCSCreateAccountParams($params),
        );

        $serverData = ServerCreateRequestBuilder::fromWHMCSCreateAccountParams($params);
        $serverData->withUser($solusUserId);
        $sshKey = Strings::convertToSshKey($params['customfields'][SolusSshKey::CUSTOM_FIELD_SSH_KEY] ?? '');

        if (!empty($sshKey)) {
            $sshKeyId = SshKey::create($params, $sshKey, $solusUserId);
            $serverData->withSshKeys([ $sshKeyId ]);
        }

        $serverResource = new ServerResource(Connector::create($params));

        $response = $serverResource->create($serverData->get());
        $data = Arr::get($response, 'data', []);

        Hosting::updateByServiceId(
            $serviceId,
            ['password' => Crypt::encrypt($params['password'])]
        );
        Hosting::syncWithSolusServer($serviceId, $data, !empty($params['domain']));
        SolusServer::create([
            'service_id' => $serviceId,
            'server_id' => (int)Arr::get($response, 'data.id'),
            'payload' => json_encode($data),
        ]);

        return 'success';
    } catch (RequestException $e) {
        Logger::log($params, $e->getResponse()->getBody()->getContents());
    } catch (Exception $e) {
        Logger::log($params, $e->getMessage());

        return $e->getMessage();
    }

    throw new SolusException('Failed to place new order, something went wrong');
}

/**
 * @param array $params
 * @return string
 */
function solusiovps_TerminateAccount(array $params): string
{
    try {
        $serverResource = new ServerResource(Connector::create($params));

        if ($server = SolusServer::getByServiceId((int)Arr::get($params, 'serviceid'))) {
            $serverResource->delete($server->server_id);

            SolusServer::deleteByServerId($server->server_id);

            return 'success';
        }

        return Language::trans('solusiovps_error_server_not_found');
    } catch (Exception $e) {
        Logger::log($params, $e->getMessage());

        return $e->getMessage();
    }
}

/**
 * @param array $params
 * @return string
 */
function solusiovps_SuspendAccount(array $params): string
{
    try {
        $serverResource = new ServerResource(Connector::create($params));

        if ($server = SolusServer::getByServiceId((int)Arr::get($params, 'serviceid'))) {
            $serverResource->suspend($server->server_id);

            return 'success';
        }

        return Language::trans('solusiovps_error_server_not_found');
    } catch (Exception $e) {
        Logger::log($params, $e->getMessage());

        return $e->getMessage();
    }
}

/**
 * @param array $params
 * @return string
 */
function solusiovps_UnsuspendAccount(array $params): string
{
    try {
        $serverResource = new ServerResource(Connector::create($params));

        if ($server = SolusServer::getByServiceId((int)Arr::get($params, 'serviceid'))) {
            $serverResource->resume($server->server_id);

            return 'success';
        }

        return Language::trans('solusiovps_error_server_not_found');
    } catch (Exception $e) {
        Logger::log($params, $e->getMessage());

        return $e->getMessage();
    }
}

/**
 * @param array $params
 * @return array
 */
function solusiovps_ClientArea(array $params): array
{
    if (isset($_GET['a'])) {
        $functionName = 'solusiovps_' . $_GET['a'];
        if (function_exists($functionName)) {
            $functionName($params);
        } else {
            $result = (object)array(
                'success' => false,
                'msg' => $functionName . ' not found',
            );
            exit(json_encode($result));
        }
    }

    try {
        solusiovps_syncAccount($params);
        $serverResource = new ServerResource(Connector::create($params));
        $server = SolusServer::getByServiceId((int)Arr::get($params, 'serviceid'));

        if ($server === null) {
            throw new Exception(Language::trans('solusiovps_error_server_not_found'));
        }

        $serverResponse = $serverResource->get($server->server_id);
        $productId = (int)$params['pid'];
        $defaultOsId = (int)Arr::get($params, 'configoption3');
        $defaultApplicationId = (int)Arr::get($params, 'configoption4');

        $applicationOptions = ProductConfigOption::getProductOptions($productId, ProductConfigOption::APPLICATION);

        $applicationResource = new ApplicationResource(Connector::create($params));
        $applications = [];

        $client = Menu::context('client');
        $group = 4;

        foreach ($applicationResource->list() as $item) {
            $id = (int)Arr::get($item, 'id');
            if (isset($applicationOptions[$id]) || $id === $defaultApplicationId) {
                $schema = json_decode(Arr::get($item, 'json_schema'), true);
                foreach ($schema['required'] as $property) {
                    $schema['properties'][$property]['required'] = true;
                }
                $applications[$id] = [
                    'name' => Arr::get($item, 'name'),
                    'schema' => $schema,
                ];
            }
        }

        $totalTraffic = Unit::convert(
            Arr::get($serverResponse, 'data.usage.network.incoming.value') +
            Arr::get($serverResponse, 'data.usage.network.outgoing.value'),
            Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.unit')
        );

        if ($client->groupid == $group) {
            return [
                'tabOverviewReplacementTemplate' => 'templates/overview-wip.tpl',
                'templateVariables' => [
                    'data' => [
                        'ip' => Arr::get($serverResponse, 'data.ip_addresses.ipv4.0.ip'),
                        'status' => Arr::get($serverResponse, 'data.status'),
                        'operating_systems' => json_encode(
                            ProductConfigOption::getProductOptions($productId, ProductConfigOption::OPERATING_SYSTEM)
                        ),
                        'default_os_id' => $defaultOsId,
                        'applications' => json_encode($applications),
                        'default_application_id' => $defaultApplicationId,
                        'domain' => $params['domain'],
    
                        'os_name' => Arr::get($serverResponse, 'data.settings.os_image.name'),
                        'vcpu' => Arr::get($serverResponse, 'data.specifications.vcpu'),
                        'ramsize' => Arr::get($serverResponse, 'data.plan.params.ram'),
                        'disksize' => Arr::get($serverResponse, 'data.specifications.disk'),
                        'disksize' => Arr::get($serverResponse, 'data.specifications.disk'),
                        'backupenabled' => Arr::get($serverResponse, 'data.backup_settings.enabled'),
                        
                        'iprange' => Arr::get($serverResponse, 'data.ip_addresses.ipv4'),
                        'reversedns' => Arr::get($serverResponse, 'data.ip_addresses.ipv4.0.reverse_dns.domain'),
    
                        'boot_mode' => Arr::get($serverResponse, 'data.boot_mode'),
                        'traffic_current' => $totalTraffic,
                        'traffic_limit' => Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.is_enabled')
                            ? Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.limit')
                            : null,
                        'traffic_unit' => Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.unit'),
                    ],
                ],
            ];
        }

        return [
            'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
            'templateVariables' => [
                'data' => [
                    'ip' => Arr::get($serverResponse, 'data.ip_addresses.ipv4.0.ip'),
                    'status' => Arr::get($serverResponse, 'data.status'),
                    'operating_systems' => json_encode(
                        ProductConfigOption::getProductOptions($productId, ProductConfigOption::OPERATING_SYSTEM)
                    ),
                    'default_os_id' => $defaultOsId,
                    'applications' => json_encode($applications),
                    'default_application_id' => $defaultApplicationId,
                    'domain' => $params['domain'],
                    //new stuff
                    'os_name' => Arr::get($serverResponse, 'data.settings.os_image.name'),
                    'vcpu' => Arr::get($serverResponse, 'data.specifications.vcpu'),
                    'ramsize' => Arr::get($serverResponse, 'data.plan.params.ram'),
                    'disksize' => Arr::get($serverResponse, 'data.specifications.disk'),
                    'reversedns' => Arr::get($serverResponse, 'data.ip_addresses.ipv4.0.reverse_dns.domain'),
                    'backupenabled' => Arr::get($serverResponse, 'data.backup_settings.enabled'),
                    //end new stuff
                    'boot_mode' => Arr::get($serverResponse, 'data.boot_mode'),
                    'traffic_current' => $totalTraffic,
                    'traffic_limit' => Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.is_enabled')
                        ? Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.limit')
                        : null,
                    'traffic_unit' => Arr::get($serverResponse, 'data.plan.limits.network_total_traffic.unit'),
                ],
            ],
        ];
    } catch (Exception $exception) {
        Logger::log($params, $exception->getMessage());

        $title = Language::trans('solusiovps_exception_page_default_title');
        $message = Language::trans('solusiovps_exception_page_default_message');

        if ($params['status'] === 'Pending') {
            $title = Language::trans('solusiovps_exception_page_pending_title');
            $message = Language::trans('solusiovps_exception_page_pending_message');
        } elseif ($params['status'] === 'Cancelled') {
            $title = Language::trans('solusiovps_exception_page_cancelled_title');
            $message = Language::trans('solusiovps_exception_page_cancelled_message');
        }

        return [
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => [
                'title' => $title,
                'message' => $message,
            ],
        ];
    }
}

/**
 * @param array $params
 * @return array
 */
function solusiovps_TestConnection(array $params)
{
    try {
        $projectResource = new ProjectResource(Connector::create($params));

        $projectResource->list();

        return ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        Logger::log([], $e->getMessage());

        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function solusiovps_AdminCustomButtonArray(array $params): array
{
    $vncUrl = Config::getSystemUrl() . 'modules/servers/solusiovps/pages/vnc.php?serviceId=' . $params['serviceid'];

    return [
        Language::trans('solusiovps_button_restart') => 'restart',
        Language::trans('solusiovps_button_vnc') => [
            'href' => "javascript:window.open('{$vncUrl}', '', 'menubar=no,location=no,resizable=yes,scrollbars=yes,status=no,width=800,height=450');",
        ],
        Language::trans('solusiovps_button_sync') => 'syncAccount',
    ];
}

function solusiovps_ListAccounts(array $params)
{
    try {
        $accounts = [];

        $serverParams = Server::getParams((int)$params['serverid']);
        $serverResource = new ServerResource(Connector::create($serverParams));

        $servers = $serverResource->list();

        foreach ($servers as $server) {
            $accounts[] = [
                'email' => $server['user']['email'],
                'username' => $server['user']['email'],
                'domain' => $server['name'],
                'uniqueIdentifier' => $server['name'],
                'product' => $server['plan']['name'],
                'primaryip' => $server['ip_addresses']['ipv4'][0]['ip'],
                'created' => Carbon::parse($server['created_at'])->format('Y-m-d H:i:s'),
                'status' => !$server['is_suspended'] ? Status::ACTIVE : Status::SUSPENDED,
            ];
        }

        return [
            'success' => true,
            'accounts' => $accounts,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function solusiovps_syncAccount(array $params)
{
    try {
        if (!empty(SolusServer::getByServiceId($params['serviceid']))) {
            return [
                'success' => "Account is already synced",
            ];
        }

        $connector = Connector::create(Server::getParams((int)$params['serverid']));
        $userResource = new UserResource($connector);
        $solusUser = $userResource->getUserByEmail($params['clientsdetails']['email']);
        if (!$solusUser) {
            throw new Exception(Language::trans('solusiovps_error_user_not_found'));
        }

        $serverResource = new ServerResource($connector);
        $allServersOfUser = $serverResource->getAllByUser($solusUser['id']);
        foreach ($allServersOfUser as $server) {
            if ($server['name'] == $params['domain']) {
                SolusServer::create([
                    'service_id' => $params['serviceid'],
                    'server_id' => (int)Arr::get($server, 'id', []),
                    'payload' => json_encode($server),
                ]);
                return [
                    'success' => "Account has been synced correctly"
                ];
            }
        }
        return [
            'Success' => "Unable to find the service in SolusIO"
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function solusiovps_ChangePackage(array $params)
{
    try {
        if ($_REQUEST['type'] !== 'configoptions') {
            return 'success';
        }

        $server = SolusServer::getByServiceId((int)$params['serviceid']);
        if ($server === null) {
            return Language::trans('solusiovps_error_server_not_found');
        }
        $solusServerId = $server->server_id;
        $serverResource = new ServerResource(Connector::create($params));

        // Handle plan params
        $requestBuilder = ServerResizeRequestBuilder::fromWHMCSUpgradeDowngradeParams($params);
        $serverResource->resize($solusServerId, $requestBuilder->get());

        // Handle additional IPs
        $additionalIpCount = ConfigOptionExtractor::extractFromModuleParams($params, ProductConfigOption::EXTRA_IP_ADDRESS);

        if ($additionalIpCount !== null) {
            $additionalIpCount = (int)$additionalIpCount;
            $solusServer = $serverResource->get($solusServerId);
            $serverAdditionalIps = array_filter(Arr::get($solusServer, 'data.ip_addresses.ipv4'), function (array $ip) {
                return $ip['is_primary'] === false;
            });
            $serverAdditionalIpCount = count($serverAdditionalIps);

            if ($additionalIpCount > $serverAdditionalIpCount) {
                $needIpCount = $additionalIpCount - $serverAdditionalIpCount;

                $serverResource->createAdditionalIps($solusServerId, $needIpCount);
            } elseif ($additionalIpCount < $serverAdditionalIpCount) {
                // Remove IPs from the end
                $reversedServerAdditionalIps = array_reverse($serverAdditionalIps);
                $reversedServerAdditionalIpsForDelete = array_slice(
                    $reversedServerAdditionalIps,
                    0,
                    $serverAdditionalIpCount - $additionalIpCount
                );
                $ipIdsForDelete = array_map(static function (array $additionalIp) {
                    return $additionalIp['id'];
                }, $reversedServerAdditionalIpsForDelete);

                $serverResource->deleteAdditionalIps($solusServerId, $ipIdsForDelete);
            }
        }

        return 'success';
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function solusiovps_ChangeHostName(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $hostname = $_GET['hostname'];
    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));
    try {
        $serverResource->changeHostname($server->server_id, $hostname);
        Product::updateDomain($serviceId, $hostname);

        exit(Language::trans('solusiovps_hostname_changed'));
    } catch (Exception $e) {
        exit(Language::trans('solusiovps_error_change_hostname') . "\n" . 'Error: ' . $e->getMessage());
    }
}

function solusiovps_ResetRootPass(array $params)
{

    $serviceId = (int) $params['serviceid'];

    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $payload = json_decode($server->payload, true);
    $solusUserId = (int) $payload['user']['id'];
    $userResource = new UserResource(Connector::create($serverParams));
    $userApiToken = $userResource->createToken($solusUserId);
    $serverResource = new ServerResource(Connector::create($serverParams, $userApiToken));

    $serverResource->resetPassword($server->server_id);
    exit(Language::trans('solusiovps_password_reset_success'));
}

function solusiovps_ChangeBootMode(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $bootMode = $_GET['bootMode'];

    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));
    $serverResource->changeBootMode($server->server_id, $bootMode);
}

function solusiovps_CreateBackup(array $params)
{
    $serviceId = (int) $params['serviceid'];

    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $backupResource = new BackupResource(Connector::create($serverParams));

    $backupResource->create($server->server_id);
}

function solusiovps_GetBackups(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $backupResource = new BackupResource(Connector::create($serverParams));
    $response = $backupResource->getAll($server->server_id);
    $backups = [];

    if (isset($response['data']) && is_array($response['data'])) {
        foreach ($response['data'] as $item) {
            $progress = (int) $item['backup_progress'];
            $status = $item['status'];

            if ($progress > 0) {
                $status .= " {$progress}%";
            }

            $time = new DateTimeImmutable($item['created_at']);

            $backups[] = [
                'id' => $item['id'],
                'status' => $status,
                'message' => $item['backup_fail_reason'] ?? '',
                'time' => $time->format('Y-m-d H:i'),
            ];
        }
    }
    exit(json_encode($backups));
}

function solusiovps_RestoreBackup(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $backupId = (int) $_GET['backupId'];

    $hosting = Hosting::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $backupResource = new BackupResource(Connector::create($serverParams));

    $backupResource->restore($backupId);
}


function solusiovps_Reinstall(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $osId = (int) $_GET['osId'];
    $applicationId = (int) $_GET['applicationId'];
    $applicationData = $_GET['applicationData'] ?? [];

    $hosting = Hosting::getByServiceId($serviceId);
    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));

    $serverResource->reinstall($server->server_id, $osId, $applicationId, $applicationData);
}

function solusiovps_Stop(array $params)
{
    $serviceId = (int) $params['serviceid'];

    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int)$hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));

    $serverResource->stop($server->server_id);
}

function solusiovps_Start(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));

    $serverResource->start($server->server_id);
}

function solusiovps_Restart(array $params)
{
    try {
        $serviceId = (int) $params['serviceid'];
        $hosting = Hosting::getByServiceId($serviceId);

        $server = SolusServer::getByServiceId($serviceId);
        $serverId = (int) $hosting->server;
        $serverParams = Server::getParams($serverId);
        $serverResource = new ServerResource(Connector::create($serverParams));

        $serverResource->restart($server->server_id);

        return 'success';
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function solusiovps_Usage(array $params)
{
    $serviceId = (int) $params['serviceid'];

    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $payload = json_decode($server->payload, true);
    $uuid = $payload['uuid'];

    $usageResource = new UsageResource(Connector::create($serverParams));
    $cpuUsage = $usageResource->cpu($uuid);
    $networkUsage = $usageResource->network($uuid);
    $diskUsage = $usageResource->disks($uuid);
    $memoryUssage = $usageResource->memory($uuid);

    $usage = [
        'cpu' => [],
        'network' => [],
        'disk' => [],
        'memory' => [],
    ];

    foreach ($cpuUsage['data']['items'] as $item) {
        $usage['cpu'][] = [
            'second' => date('H:i:s', strtotime($item['time'])),
            'load_average' => $item['load_average'],
        ];
    }

    foreach ($networkUsage['data']['items'] as $item) {
        $usage['network'][] = [
            'second' => date('H:i:s', strtotime($item['time'])),
            'read_kb' => $item['derivative']['read_kb'],
            'write_kb' => $item['derivative']['write_kb'],
        ];
    }

    foreach ($diskUsage['data']['items'] as $item) {
        $usage['disk'][] = [
            'second' => date('H:i:s', strtotime($item['time'])),
            'read_kb' => $item['derivative']['read_kb'],
            'write_kb' => $item['derivative']['write_kb'],
        ];
    }

    foreach ($memoryUssage['data']['items'] as $item) {
        $usage['memory'][] = [
            'second' => date('H:i:s', strtotime($item['time'])),
            'memory' => $item['memory'],
        ];
    }

    exit(json_encode($usage));
}

function solusiovps_VNC(array $params)
{
    $serviceId = (int) $params['serviceid'];

    $hosting = Hosting::getByServiceId($serviceId);
    $solusServer = SolusServer::getByServiceId($serviceId);
    $serverId = (int)$hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));
    $server = $serverResource->get($solusServer->server_id);
    $password = $server['data']['settings']['vnc_password'] ?? $server['data']['settings']['vnc']['password'];
    $response = $serverResource->vncUp($solusServer->server_id);
    $url = 'wss://' . $serverParams['serverhostname'] . '/vnc?url=' . $response['url'];

    ?>
<!DOCTYPE html>
<html lang="en" class="noVNC_loading">
<head>
    <title>noVNC</title>

    <meta charset="utf-8">
    <link rel="icon" sizes="16x16" type="image/png" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/icons/novnc-16x16.png">

    <!-- Apple iOS Safari settings -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <!-- Home Screen Icons (favourites and bookmarks use the normal icons) -->
    <link rel="apple-touch-icon" sizes="60x60" type="image/png" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/icons/novnc-60x60.png">
    <link rel="apple-touch-icon" sizes="76x76" type="image/png" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/icons/novnc-76x76.png">
    <link rel="apple-touch-icon" sizes="120x120" type="image/png" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/icons/novnc-120x120.png">
    <link rel="apple-touch-icon" sizes="152x152" type="image/png" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/icons/novnc-152x152.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/styles/base.css">

    <!-- Images that will later appear via CSS -->
    <link rel="preload" as="image" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/info.svg">
    <link rel="preload" as="image" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/error.svg">
    <link rel="preload" as="image" href="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/warning.svg">

    <script src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/error-handler.js"></script>
    <script type="module" crossorigin="anonymous" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/ui.js"></script>
</head>

<body>

    <div id="noVNC_fallback_error" class="noVNC_center">
        <div>
            <div>noVNC encountered an error:</div>
            <br>
            <div id="noVNC_fallback_errormsg"></div>
        </div>
    </div>

    <!-- noVNC Control Bar -->
    <div id="noVNC_control_bar_anchor" class="noVNC_vcenter">

        <div id="noVNC_control_bar">
            <div id="noVNC_control_bar_handle" title="Hide/Show the control bar"><div></div></div>

            <div class="noVNC_scroll">

            <h1 class="noVNC_logo" translate="no"><span>no</span><br>VNC</h1>

            <!-- Drag/Pan the viewport -->
            <input type="image" alt="Drag" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/drag.svg"
                id="noVNC_view_drag_button" class="noVNC_button noVNC_hidden"
                title="Move/Drag Viewport">

            <!--noVNC Touch Device only buttons-->
            <div id="noVNC_mobile_buttons">
                <input type="image" alt="Keyboard" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/keyboard.svg"
                    id="noVNC_keyboard_button" class="noVNC_button" title="Show Keyboard">
            </div>

            <!-- Extra manual keys -->
            <input type="image" alt="Extra keys" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/toggleextrakeys.svg"
                id="noVNC_toggle_extra_keys_button" class="noVNC_button"
                title="Show Extra Keys">
            <div class="noVNC_vcenter">
            <div id="noVNC_modifiers" class="noVNC_panel">
                <input type="image" alt="Ctrl" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/ctrl.svg"
                    id="noVNC_toggle_ctrl_button" class="noVNC_button"
                    title="Toggle Ctrl">
                <input type="image" alt="Alt" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/alt.svg"
                    id="noVNC_toggle_alt_button" class="noVNC_button"
                    title="Toggle Alt">
                <input type="image" alt="Windows" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/windows.svg"
                    id="noVNC_toggle_windows_button" class="noVNC_button"
                    title="Toggle Windows">
                <input type="image" alt="Tab" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/tab.svg"
                    id="noVNC_send_tab_button" class="noVNC_button"
                    title="Send Tab">
                <input type="image" alt="Esc" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/esc.svg"
                    id="noVNC_send_esc_button" class="noVNC_button"
                    title="Send Escape">
                <input type="image" alt="Ctrl+Alt+Del" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/ctrlaltdel.svg"
                    id="noVNC_send_ctrl_alt_del_button" class="noVNC_button"
                    title="Send Ctrl-Alt-Del">
            </div>
            </div>

            <!-- Shutdown/Reboot -->
            <input type="image" alt="Shutdown/Reboot" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/power.svg"
                id="noVNC_power_button" class="noVNC_button"
                title="Shutdown/Reboot...">
            <div class="noVNC_vcenter">
            <div id="noVNC_power" class="noVNC_panel">
                <div class="noVNC_heading">
                    <img alt="" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/power.svg"> Power
                </div>
                <input type="button" id="noVNC_shutdown_button" value="Shutdown">
                <input type="button" id="noVNC_reboot_button" value="Reboot">
                <input type="button" id="noVNC_reset_button" value="Reset">
            </div>
            </div>

            <!-- Clipboard -->
            <input type="image" alt="Clipboard" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/clipboard.svg"
                id="noVNC_clipboard_button" class="noVNC_button"
                title="Clipboard">
            <div class="noVNC_vcenter">
            <div id="noVNC_clipboard" class="noVNC_panel">
                <div class="noVNC_heading">
                    <img alt="" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/clipboard.svg"> Clipboard
                </div>
                <textarea id="noVNC_clipboard_text" rows=5></textarea>
                <br>
                <input id="noVNC_clipboard_paste_button" type="button"
                    value="Paste" class="noVNC_submit">
                <input id="noVNC_clipboard_clear_button" type="button"
                    value="Clear" class="noVNC_submit">
            </div>
            </div>

            <!-- Toggle fullscreen -->
            <input type="image" alt="Fullscreen" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/fullscreen.svg"
                id="noVNC_fullscreen_button" class="noVNC_button noVNC_hidden"
                title="Fullscreen">

            <!-- Settings -->
            <input type="image" alt="Settings" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/settings.svg"
                id="noVNC_settings_button" class="noVNC_button"
                title="Settings">
            <div class="noVNC_vcenter">
            <div id="noVNC_settings" class="noVNC_panel">
                <ul>
                    <li class="noVNC_heading">
                        <img alt="" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/settings.svg"> Settings
                    </li>
                    <li>
                        <label><input id="noVNC_setting_shared" type="checkbox"> Shared Mode</label>
                    </li>
                    <li>
                        <label><input id="noVNC_setting_view_only" type="checkbox"> View Only</label>
                    </li>
                    <li><hr></li>
                    <li>
                        <label><input id="noVNC_setting_view_clip" type="checkbox"> Clip to Window</label>
                    </li>
                    <li>
                        <label for="noVNC_setting_resize">Scaling Mode:</label>
                        <select id="noVNC_setting_resize" name="vncResize">
                            <option value="off">None</option>
                            <option value="scale">Local Scaling</option>
                            <option value="remote">Remote Resizing</option>
                        </select>
                    </li>
                    <li><hr></li>
                    <li>
                        <div class="noVNC_expander">Advanced</div>
                        <div><ul>
                            <li>
                                <label for="noVNC_setting_quality">Quality:</label>
                                <input id="noVNC_setting_quality" type="range" min="0" max="9" value="6">
                            </li>
                            <li>
                                <label for="noVNC_setting_compression">Compression level:</label>
                                <input id="noVNC_setting_compression" type="range" min="0" max="9" value="2">
                            </li>
                            <li><hr></li>
                            <li>
                                <label for="noVNC_setting_repeaterID">Repeater ID:</label>
                                <input id="noVNC_setting_repeaterID" type="text" value="">
                            </li>
                            <li style="display: none;">
                                <div class="noVNC_expander">WebSocket</div>
                                <div><ul>
                                    <li>
                                        <label><input id="noVNC_setting_encrypt" type="checkbox"> Encrypt</label>
                                    </li>
                                    <li>
                                        <label for="noVNC_setting_url">URL:</label>
                                        <input id="noVNC_setting_url" type="hidden" value="<?php echo $url; ?>">
                                    </li>
                                    <li>
                                        <label for="noVNC_setting_host">Host:</label>
                                        <input id="noVNC_setting_host" value="">
                                    </li>
                                    <li>
                                        <label for="noVNC_setting_port">Port:</label>
                                        <input id="noVNC_setting_port" type="number" value="">
                                    </li>
                                    <li>
                                        <label for="noVNC_setting_password">Password:</label>
                                        <input id="noVNC_setting_password" value="<?php echo $password; ?>">
                                    </li>
                                    <li>
                                        <label for="noVNC_setting_path">Path:</label>
                                        <input id="noVNC_setting_path" type="text" value="">
                                    </li>
                                </ul></div>
                            </li>
                            <li><hr></li>
                            <li>
                                <label><input id="noVNC_setting_reconnect" type="checkbox"> Automatic Reconnect</label>
                            </li>
                            <li>
                                <label for="noVNC_setting_reconnect_delay">Reconnect Delay (ms):</label>
                                <input id="noVNC_setting_reconnect_delay" type="number">
                            </li>
                            <li><hr></li>
                            <li>
                                <label><input id="noVNC_setting_show_dot" type="checkbox"> Show Dot when No Cursor</label>
                            </li>
                            <li><hr></li>
                            <!-- Logging selection dropdown -->
                            <li style="display: none;">
                                <label>Logging:
                                    <select id="noVNC_setting_logging" name="vncLogging">
                                    </select>
                                </label>
                            </li>
                        </ul></div>
                    </li>
                    <li class="noVNC_version_separator"><hr></li>
                    <li class="noVNC_version_wrapper">
                        <span>Version:</span>
                        <span class="noVNC_version"></span>
                    </li>
                </ul>
            </div>
            </div>

            <!-- Connection Controls -->
            <input type="image" alt="Disconnect" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/disconnect.svg"
                id="noVNC_disconnect_button" class="noVNC_button"
                title="Disconnect">

            </div>
        </div>

        <div id="noVNC_control_bar_hint"></div>

    </div> <!-- End of noVNC_control_bar -->

    <!-- Status Dialog -->
    <div id="noVNC_status"></div>

    <!-- Connect button -->
    <div class="noVNC_center">
        <div id="noVNC_connect_dlg">
            <div class="noVNC_logo" translate="no"><span>no</span>VNC</div>
            <div id="noVNC_connect_button"><div>
                <img alt="" src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/images/connect.svg"> Connect
            </div></div>
        </div>
    </div>

    <!-- Password Dialog -->
    <div class="noVNC_center noVNC_connect_layer">
    <div id="noVNC_credentials_dlg" class="noVNC_panel"><form>
        <ul>
            <li id="noVNC_username_block">
                <label>Username:</label>
                <input id="noVNC_username_input">
            </li>
            <li id="noVNC_password_block">
                <label>Password:</label>
                <input id="noVNC_password_input" type="password">
            </li>
            <li>
                <input id="noVNC_credentials_button" type="submit" value="Send Credentials" class="noVNC_submit">
            </li>
        </ul>
    </form></div>
    </div>

    <!-- Transition Screens -->
    <div id="noVNC_transition">
        <div id="noVNC_transition_text"></div>
        <div>
        <input type="button" id="noVNC_cancel_reconnect_button" value="Cancel" class="noVNC_submit">
        </div>
        <div class="noVNC_spinner"></div>
    </div>

    <!-- This is where the RFB elements will attach -->
    <div id="noVNC_container">
        <!-- Note that Google Chrome on Android doesn't respect any of these,
             html attributes which attempt to disable text suggestions on the
             on-screen keyboard. Let's hope Chrome implements the ime-mode
             style for example -->
        <textarea id="noVNC_keyboardinput" autocapitalize="off"
            autocomplete="off" spellcheck="false" tabindex="-1"></textarea>
    </div>

    <audio id="noVNC_bell">
        <source src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/sounds/bell.oga" type="audio/ogg">
        <source src="modules/servers/solusiovps/node_modules/@novnc/novnc/app/sounds/bell.mp3" type="audio/mpeg">
    </audio>
 </body>
</html>
    <?php
    exit();
}

function solusiovps_Status(array $params)
{
    $serviceId = (int) $params['serviceid'];
    $hosting = Hosting::getByServiceId($serviceId);

    $server = SolusServer::getByServiceId($serviceId);
    $serverId = (int) $hosting->server;
    $serverParams = Server::getParams($serverId);
    $serverResource = new ServerResource(Connector::create($serverParams));
    $serverResponse = $serverResource->get($server->server_id);

    exit($serverResponse['data']['status']);
}
