<?php
namespace Addon\Logicboxes\Libraries\Api;

class DomainCom_Au extends LogicboxesApi {

    public $forms;

    public function __construct()
    {
        parent::__construct();

        $this->forms = new \Whsuite\Forms\Forms();

    }

    /**
     * Get Contact Fields
     *
     * This is used to display custom contact-related fields when registering or
     * transferring a domain that is specific to this TLD.
     *
     * @return string Returns the raw form data or a view that contains that form data.
     */
    public function getContactFields()
    {
        return null;
    }

    /**
     * Set Contact Params
     *
     * Sets the custom contact form fields into appropriate params after it's been
     * posted.
     *
     * @param  array $data The array of post data, which we'll then  use to pull out the fields we need to modify.
     * @return array Returns the array of modified form post data values.
     */
    public function setContactParams($data)
    {
        return array();
    }

    /**
     * Get Registration Fields
     *
     * This is used to display custom domain-related fields when registering or
     * transferring a domain that is specific to this TLD.
     *
     * @param  array $data The array of post data, which we'll then  use to pull out the fields we need to modify.
     * @return string Returns the raw form data or a view that contains that form data.
     */
    public function getRegistrationFields()
    {
        $view = \App::get('view');

        $eligibility_types = array(
            'ACN' => \App::get('translation')->get('logicboxes_australian_company_number'),
            'ABN' => \App::get('translation')->get('logicboxes_australian_bus_no'),
            'VIC BN' => \App::get('translation')->get('logicboxes_victoria_bus_no'),
            'NSW BN' => \App::get('translation')->get('logicboxes_new_south_wales_bus_no'),
            'SA BN' => \App::get('translation')->get('logicboxes_south_australia_bus_no'),
            'NT BN' => \App::get('translation')->get('logicboxes_northern_territory_bus_no'),
            'WA BN' => \App::get('translation')->get('logicboxes_western_australia_bus_no'),
            'TAS BN' => \App::get('translation')->get('logicboxes_tasmania_bus_no'),
            'ACT BN' => \App::get('translation')->get('logicboxes_australian_capital_territory_bus_no'),
            'QLD BN' => \App::get('translation')->get('logicboxes_queensland_bus_no'),
            'TM' => \App::get('translation')->get('logicboxes_trademark_no'),
            'ARBN' => \App::get('translation')->get('logicboxes_australian_registered_body_no'),
            'Other' => \App::get('translation')->get('logicboxes_other')
        );

        $trademark_options = array(
            'Trademark Owner' => \App::get('translation')->get('logicboxes_trademark_owner'),
            'Pending TM Owner' => \App::get('translation')->get('logicboxes_pending_trademark_owner'),
        );

        $view->set('eligibility_types', $eligibility_types);
        $view->set('trademark_options', $trademark_options);

        return $view->fetch('logicboxes::extension_fields/au.php');
    }

    /**
     * Set Registration Params
     *
     * Sets the custom domain registration form fields into appropriate params
     * after it's been posted.
     *
     * @param  array $data The array of post data, which we'll then  use to pull out the fields we need to modify.
     * @return array Returns the array of modified form post data values.
     */
    public function setRegistrationParams($data, $domain_params)
    {
        $params = array();

        $params['attr-name1'] = 'id-type';
        $params['attr-value1'] = $data['ced_eligibility_type'];

        $params['attr-name2'] = 'id';
        $params['attr-value2'] = $data['ced_eligibility_id'];

        $params['attr-name3'] = 'policyReason';
        $params['attr-value3'] = $data['ced_eligibility_reason'];

        $params['attr-name4'] = 'isAUWarranty';
        $params['attr-value4'] = ($data['ced_warranty'] == '1' ? 'true' : 'false');

        if ($data['ced_eligibility_type'] == 'TM') {

            $params['attr-name5'] = 'eligibilityType';
            $params['attr-value5'] = $data['tm_type'];

            $params['attr-name6'] = 'eligibilityName';
            $params['attr-value6'] = $data['eligibility_name'];

            $params['attr-name7'] = 'registrantName';
            $params['attr-value7'] = $data['registrant_name'];

        } elseif ($data['ced_eligibility_type'] == 'Other') {

            $params['attr-name5'] = 'eligibilityType';
            $params['attr-value5'] = 'Other';

            $params['attr-name6'] = 'eligibilityName';
            $params['attr-value6'] = $data['eligibility_name'];

            $params['attr-name7'] = 'registrantName';
            $params['attr-value7'] = $data['registrant_name'];

        }
        return $params;
    }

    /**
     * Modify Domain Params
     *
     * Allows manipulation of the domain paramiters before submitting a registration
     * or renewal request. Generally this'll rarely need to be used for anything other
     * than appending custom data. However for example you could modify the nameservers
     * of a domain before it's registered here.
     *
     * @param  array $data The array containing the post data used to store the domain registration or transfer details.
     * @param  array $data The array containing only the logicboxes specific domain registration or transfer details.
     * @return array Returns the array of modified data values.
     */
    public function modifyDomainParams($data, $domain_params)
    {
        return $this->setRegistrationParams($data, $domain_params);
    }
}
