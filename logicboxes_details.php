<?php
namespace Addon\Logicboxes;

class LogicboxesDetails extends \App\Libraries\AddonDetails
{
    /**
     * addon details
     */
    protected static $details = array(
        'name' => 'LogicBoxes',
        'description' => 'Registrar Module for all LogicBoxes based providers (e.g ResellerClub, NetEarthOne, Resell.biz, etc)',
        'author' => array(
            'name' => 'WHSuite Dev Team',
            'email' => 'info@whsuite.com'
        ),
        'website' => 'http://www.whsuite.com',
        'version' => '1.1.0',
        'license' => 'http://whsuite.com/license/ The WHSuite License Agreement',
        'type' => 'registrar'
    );

    /**
     * get the addon details
     *
     * @param   int $addon_id   The addons ID within WHSuite database
     * @return  bool
     */
    public function uninstallCheck($addon_id)
    {
        return $this->addon_helper->domainAddonUninstallCheck($addon_id);
    }
}
