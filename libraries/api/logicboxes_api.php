<?php
namespace Addon\Logicboxes\Libraries\Api;
class LogicboxesApi
{

    public $cmd;

    private $live_api = 'https://httpapi.com/api';
    private $sandbox_api = 'https://test.httpapi.com/api';

    private $api_key;
    private $reseller_id;

    public $base_url;

    public $global_params = array();

    /**
     * Constructor
     *
     * Sets up the Logicboxes API
     */
    public function __construct()
    {
        $api_key = \App::get('configs')->get('settings.logicboxes.logicboxes_api_key');;
        $reseller_id = \App::get('configs')->get('settings.logicboxes.logicboxes_reseller_id');

        $sandbox = false;
        $sandbox_enabled = \App::get('configs')->get('settings.logicboxes.logicboxes_enable_sandbox');

        if ($sandbox_enabled == '1') {
            $sandbox = true;
            $this->base_url = $this->sandbox_api;
        } else {
            $this->base_url = $this->live_api;
        }

        $this->reseller_id = $reseller_id;
        $this->api_key = $api_key;
        $this->sandbox = $sandbox;

        $this->global_params = array(
            'api-key' => $this->api_key,
            'auth-userid' => $this->reseller_id
        );
    }

    /**
     * Get TLD
     *
     * Returns the TLD for a given domain name.
     *
     * @return string Returns the TLD
     */
    public function getTld($domain)
    {
        $domain_split = preg_split('/([\.])/', $domain, 2);
        $extension = '.'.$domain_split[1];

        return $extension;
    }

    /**
     * Get
     *
     * Performs a get request on Logicboxes and returns the data as an object.
     *
     * @param  string The URI path to query
     * @param  array Optional paramiters to add to the get request
     * @return object Returns the get request data
     */
    public function get($url, $params = array())
    {
        $params = $this->global_params + $params;
        return json_decode(\App::get('dispatcher')->load($this->base_url.$url, "GET", $params));
    }

    /**
     * Post
     *
     * Performs a post request on Logicboxes and returns the data as an object.
     *
     * @param  string The URI path to query
     * @param  array The post data to send
     * @return object Returns the post request data
     */
    public function post($url, $params = array())
    {
        $params = $this->global_params + $params;
        return json_decode(\App::get('dispatcher')->load($this->base_url.$url, "POST", $params));
    }

}
