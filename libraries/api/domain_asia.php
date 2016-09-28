<?php
namespace Addon\Logicboxes\Libraries\Api;

class DomainAsia extends LogicboxesApi {

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
        $entity_types = array(
            'naturalPerson' => \App::get('translation')->get('natural_person'),
            'corporation' => \App::get('translation')->get('corporation'),
            'cooperative' => \App::get('translation')->get('cooperative'),
            'partnership' => \App::get('translation')->get('partnership'),
            'government' => \App::get('translation')->get('government'),
            'politicalParty' => \App::get('translation')->get('politicalParty'),
            'society' => \App::get('translation')->get('society'),
            'institute' => \App::get('translation')->get('institute')
        );

        $identity_types = array(
            'passport' => \App::get('translation')->get('passport_or_citizenship_id'),
            'certificate' => \App::get('translation')->get('certificate_of_incorporation_or_equivalent'),
            'legislation' => \App::get('translation')->get('entity_formation_act_decree_or_legislation'),
            'societyRegistration' => \App::get('translation')->get('society_registration_or_equivalent'),
            'politicalPartyRegistration' => \App::get('translation')->get('political_party_registry')
        );

        $fields = '';

        $fields .= $this->forms->select('ced_country', \App::get('translation')->get('country_within_asia'), array('options' => $this->_countryList()));
        $fields .= $this->forms->select('ced_entity_type', \App::get('translation')->get('entity_type'), array('options' => $entity_types));
        $fields .= $this->forms->select('ced_id_type', \App::get('translation')->get('identity_type'), array('options' => $identity_types));
        $fields .= $this->forms->input('ced_id', \App::get('translation')->get('identity_number'));

        return $fields;
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
        $params = array();

        $params['attr-name1'] = 'locality';
        $params['attr-value1'] = $data['ced_country'];

        $params['attr-name2'] = 'legalentitytype';
        $params['attr-value2'] = $data['ced_entity_type'];

        $params['attr-name3'] = 'identform';
        $params['attr-value3'] = $data['ced_id_type'];

        $params['attr-name4'] = 'identnumber';
        $params['attr-value4'] = $data['ced_id'];

        return $params;
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
        return null;
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
        $params['attr-name1'] = 'cedcontactid';
        $params['attr-value1'] = $domain_params['reg-contact-id'];

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

    /**
     * Return ASIA Country List
     *
     * Returns the country list needed for .ASIA domains.
     *
     * @return array Returns the array of Asia countries with their ISO codes.
     */
    private function _countryList()
    {
        // Whilst ideally this would be a dynamicly generated list, for now this
        // will suffice. There's no easy way of tracking countries by region
        // without having a much more complex system in place for managing them.
        $iso_codes = array(
            'AF' => 'Afghanistan',
            'AQ' => 'Antarctica',
            'AM' => 'Armenia',
            'AU' => 'Australia',
            'AZ' => 'Azerbaijan',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BT' => 'Bhutan',
            'BN' => 'Brunei',
            'KH' => 'Cambodia',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CK' => 'Cook Islands',
            'CY' => 'Cyprus',
            'FJ' => 'Fiji Islands',
            'GE' => 'Georgia',
            'HM' => 'Heard and McDonald Islands',
            'HK' => 'Hong Kong S.A.R.',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IL' => 'Israel',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KI' => 'Kiribati',
            'KR' => 'Korea',
            'KP' => 'Korea, North',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LB' => 'Lebanon',
            'MO' => 'Macau S.A.R.',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'MH' => 'Marshall Islands',
            'FM' => 'Micronesia',
            'MN' => 'Mongolia',
            'MM' => 'Myanmar',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NZ' => 'New Zealand',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory, Occupied',
            'PG' => 'Papua new Guinea',
            'PH' => 'Philippines',
            'QA' => 'Qatar',
            'WS' => 'Samoa',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'SB' => 'Solomon Islands',
            'LK' => 'Sri Lanka',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TV' => 'Tuvalu',
            'AE' => 'United Arab Emirates',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VN' => 'Vietnam',
            'YE' => 'Yemen'
        );
        return $iso_codes;
    }

}
