<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010 Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2025 Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Script that handles editing of zone records
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2025 Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */

namespace Poweradmin\Application\Controller;

use Poweradmin\Application\Service\DnssecProviderFactory;
use Poweradmin\BaseController;
use Poweradmin\Domain\Model\DnssecAlgorithm;
use Poweradmin\Domain\Model\Permission;
use Poweradmin\Domain\Model\UserManager;
use Poweradmin\Domain\Model\ZoneTemplate;
use Poweradmin\Domain\Service\DnsIdnService;
use Poweradmin\Domain\Service\DnsRecord;
use Poweradmin\Domain\Service\Validator;

class DnssecController extends BaseController
{

    public function run(): void
    {
        if (!isset($_GET['id']) || !Validator::is_number($_GET['id'])) {
            $this->showError(_('Invalid or unexpected input given.'));
        }

        $zone_id = htmlspecialchars($_GET['id']);
        $perm_view = Permission::getViewPermission($this->db);
        $user_is_zone_owner = UserManager::verify_user_is_owner_zoneid($this->db, $zone_id);

        (UserManager::verify_permission($this->db, 'user_view_others')) ? $perm_view_others = "1" : $perm_view_others = "0";

        if ($perm_view == "none" || $perm_view == "own" && $user_is_zone_owner == "0") {
            $this->showError(_("You do not have the permission to view this zone."));
        }

        $dnsRecord = new DnsRecord($this->db, $this->getConfig());
        if ($dnsRecord->zone_id_exists($zone_id) == "0") {
            $this->showError(_('There is no zone with this ID.'));
        }

        $this->showDnsSecKeys($zone_id);
    }

    public function showDnsSecKeys(string $zone_id): void
    {
        $dnsRecord = new DnsRecord($this->db, $this->getConfig());
        $domain_name = $dnsRecord->get_domain_name_by_id($zone_id);
        if (str_starts_with($domain_name, "xn--")) {
            $idn_zone_name = DnsIdnService::toUtf8($domain_name);
        } else {
            $idn_zone_name = "";
        }

        $dnssecProvider = DnssecProviderFactory::create($this->db, $this->getConfig());
        $dnsRecord = new DnsRecord($this->db, $this->getConfig());
        $zone_templates = new ZoneTemplate($this->db, $this->getConfig());

        $this->render('dnssec.html', [
            'domain_name' => $domain_name,
            'idn_zone_name' => $idn_zone_name,
            'domain_type' => $dnsRecord->get_domain_type($zone_id),
            'keys' => $dnssecProvider->getKeys($domain_name),
            'pdnssec_use' => $this->config->get('dnssec', 'enabled', false),
            'record_count' => $dnsRecord->count_zone_records($zone_id),
            'zone_id' => $zone_id,
            'zone_template_id' => DnsRecord::get_zone_template($this->db, $zone_id),
            'zone_templates' => $zone_templates->get_list_zone_templ($_SESSION['userid']),
            'algorithms' => DnssecAlgorithm::ALGORITHMS,
        ]);
    }
}
