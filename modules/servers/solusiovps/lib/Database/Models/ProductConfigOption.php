<?php

// Copyright 2020. Plesk International GmbH.

namespace WHMCS\Module\Server\SolusIoVps\Database\Models;

use WHMCS\Database\Capsule as DB;

/**
 * @package WHMCS\Module\Server\SolusIoVps\Database\Models
 */
class ProductConfigOption
{
    const LOCATION = 'Location';
    const OPERATING_SYSTEM = 'Operating System';
    const MEMORY = 'Memory';
    const DISK_SPACE = 'Disk Space';
    const VCPU = 'VCPU';
    const VCPU_UNITS = 'VCPU Units';
    const VCPU_LIMIT = 'VCPU Limit';
    const IO_PRIORITY = 'IO Priority';
    const SWAP = 'Swap';
    const TOTAL_TRAFFIC_LIMIT_MONTHLY = 'Total traffic limit monthly';

    public static function getProductOptions(int $productId, string $optionName): array
    {
        $rows = Db::table('tblproductconfiglinks')
            ->select('tblproductconfigoptionssub.optionname')
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.gid', '=', 'tblproductconfiglinks.gid')
            ->join('tblproductconfigoptionssub', 'tblproductconfigoptionssub.configid', '=', 'tblproductconfigoptions.id')
            ->where('tblproductconfiglinks.pid', $productId)
            ->where('tblproductconfigoptions.optionname', $optionName)
            ->get();

        return self::extractProductOptions($rows);
    }

    public static function extractProductOptions($rows): array
    {
        $options = [];

        foreach ($rows as $row) {
            $pair = explode('|', $row->optionname);

            if (count($pair) !== 2) {
                continue;
            }

            $options[trim($pair[0])] = trim($pair[1]);
        }

        return $options;
    }
}
