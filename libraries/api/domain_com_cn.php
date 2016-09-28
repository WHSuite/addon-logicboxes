<?php
namespace Addon\Logicboxes\Libraries\Api;

class DomainCom_Cn extends LogicboxesApi {

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

        return $view->fetch('logicboxes::extension_fields/cn.php');
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

        $params['attr-name1'] = 'cnhosting';
        $params['attr-value1'] = ($data['cnhosting'] == '1' ? 'true' : 'false');

        $params['attr-name2'] = 'cnhostingclause';
        $params['attr-value2'] = ($data['cnhostingclause'] == '1' ? 'yes' : 'no');

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
