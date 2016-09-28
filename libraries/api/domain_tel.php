<?php
namespace Addon\Logicboxes\Libraries\Api;

class DomainTel extends LogicboxesApi {

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

        $whois_types = array(
            'Natural' => \App::get('translation')->get('logicboxes_individual'),
            'Legal' => \App::get('translation')->get('logicboxes_organization'),
        );
        $view->set('whois_types', $whois_types);

        return $view->fetch('logicboxes::extension_fields/tel.php');
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

        $params['attr-name1'] = 'whois-type';
        $params['attr-value1'] = $data['whois-type'];

        $params['attr-name2'] = 'publish';
        $params['attr-value2'] = ($data['publish_whois'] == '1' ? 'Y' : 'N');

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
