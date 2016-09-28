<?php
namespace Addon\Logicboxes\Libraries;

class Logicboxes implements \App\Libraries\Interfaces\Registrar\RegistrarLibrary
{

    /**
     * Get Domain Info
     *
     * Fetches the basic domain data from the registry to provide an overview
     * of the domain, it's registration period, lock status, etc.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function getDomainInfo($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        $response = new \stdClass();

        // Check the domain availability
        $domain_info = $domain_api->domainInfo($request->domain->domain);


        if (isset($domain_info->status) && $domain_info->status == 'ERROR') {
            $response->status = 0;

            return $response;
        }

        $lock = false;

        if (isset($domain_info->orderstatus) && count($domain_info->orderstatus) > 0) {
            foreach ($domain_info->orderstatus as $status_item) {
                if ($status_item == 'transferlock') {
                    $lock = true;
                }
            }
        }

        $nameservers = array();

        for ($i=1;$i <= $domain_info->noOfNameServers;$i++) {
            $nameservers[] = $domain_info->{'ns'.$i};
        }

        $response->status = '1'; // Set the response status
        $response->domain_name = $domain_info->domainname;

        $Carbon = \Carbon\Carbon::createFromTimestamp(
            $domain_info->endtime,
            \App::get('configs')->get('settings.localization.timezone')
        );
        $response->date_expires = $Carbon->toDateString();
        $response->lock_status = $lock;
        $response->nameservers = $nameservers;
        return $response;

    }

    /**
     * Register Domain
     *
     * Tells the registry to register the domain name for a given period of time
     * and sets the contacts, nameservers, etc.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function registerDomain($request)
    {

        // Load the domain api
        $domain_api = new Api\Domain();

        // Split the domain at the extension
        $domain_parts = \App::get('domainhelper')->splitDomain($request->domain->domain);

        $domain_params = array(
            'SLD' => $domain_parts[0],
            'TLD' => $domain_parts[1],
            'NumYears' => $request->years,
            'IgnoreNSFail' => 'Yes',
        );

        // Add the nameservers to the $domain_params array
        $i = 1;
        foreach ($request->nameservers as $nameserver) {

            // Enom allows up to 12 nameservers to be set, so ensure no more than
            // that are submitted.
            if ($i <= 12) {
                $domain_params['NS'.$i] = $nameserver;
                $i++;
            }
        }

        // Build contact data
        $registrant = \App::get('domainhelper')->getContact($request->contacts['registrant']);
        $administrative = \App::get('domainhelper')->getContact($request->contacts['administrative']);
        $technical = \App::get('domainhelper')->getContact($request->contacts['technical']);
        $billing = \App::get('domainhelper')->getContact($request->contacts['billing']);


        // Registrant contact. Not all extensions require this, however we'll set
        // it anyway as it can still be used and reduces the need to do additional
        // unnecessery checks.
        $registrant_contact = array(
            'RegistrantFirstName' => $registrant->first_name,
            'RegistrantLastName' => $registrant->last_name,
            'RegistrantAddress1' => $registrant->address1,
            'RegistrantAddress2' => $registrant->address2,
            'RegistrantCity' => $registrant->city,
            'RegistrantStateProvince' => $registrant->state,
            'RegistrantPostalCode' => $registrant->postcode,
            'RegistrantCountry' => \App::get('domainhelper')->getIsoCode($registrant->country),
            'RegistrantEmailAddress' => $registrant->email,
            'RegistrantPhone' => '+' . $registrant->phone_cc . '.' . $registrant->phone,
        );

        if ($registrant->company !='') {
            $registrant_contact['RegistrantOrganization'] = $registrant->company;

            if ($registrant->job_title == '') {
                $registrant->job_title = 'N/A';
            }

            $registrant_contact['RegistrantJobTitle'] = $registrant->job_title;

            if ($registrant->fax_cc == '' || $registrant->fax == '') {
                $registrant_contact['RegistrantFax'] = '+' . $registrant->phone_cc . '.' . $registrant->phone;
            } else {
                $registrant_contact['RegistrantFax'] = '+' . $registrant->fax_cc . '.' . $registrant->fax;
            }
        }

        // Administrative contact
        $administrative_contact = array(
            'AdminFirstName' => $administrative->first_name,
            'AdminLastName' => $administrative->last_name,
            'AdminAddress1' => $administrative->address1,
            'AdminAddress2' => $administrative->address2,
            'AdminCity' => $administrative->city,
            'AdminStateProvince' => $administrative->state,
            'AdminPostalCode' => $administrative->postcode,
            'AdminCountry' => \App::get('domainhelper')->getIsoCode($administrative->country),
            'AdminEmailAddress' => $administrative->email,
            'AdminPhone' => '+' . $administrative->phone_cc . '.' . $administrative->phone
        );

        if ($administrative->company !='') {
            $administrative_contact['AdminOrganization'] = $administrative->company;

            if ($administrative->job_title == '') {
                $administrative->job_title = 'N/A';
            }

            $administrative_contact['AdminJobTitle'] = $administrative->job_title;
        }

        // Technical contact
        $technical_contact = array(
            'TechFirstName' => $technical->first_name,
            'TechLastName' => $technical->last_name,
            'TechAddress1' => $technical->address1,
            'TechAddress2' => $technical->address2,
            'TechCity' => $technical->city,
            'TechStateProvince' => $technical->state,
            'TechPostalCode' => $technical->postcode,
            'TechCountry' => \App::get('domainhelper')->getIsoCode($technical->country),
            'TechEmailAddress' => $technical->email,
            'TechPhone' => '+' . $technical->phone_cc . '.' . $technical->phone
        );

        if ($technical->company !='') {
            $technical_contact['TechOrganization'] = $technical->company;

            if ($technical->job_title == '') {
                $technical->job_title = 'N/A';
            }

            $technical_contact['TechJobTitle'] = $technical->job_title;
        }

        // Billing contact
        $billing_contact = array(
            'AuxBillingFirstName' => $billing->first_name,
            'AuxBillingLastName' => $billing->last_name,
            'AuxBillingAddress1' => $billing->address1,
            'AuxBillingAddress2' => $billing->address2,
            'AuxBillingCity' => $billing->city,
            'AuxBillingStateProvince' => $billing->state,
            'AuxBillingPostalCode' => $billing->postcode,
            'AuxBillingCountry' => \App::get('domainhelper')->getIsoCode($billing->country),
            'AuxBillingEmailAddress' => $billing->email,
            'AuxBillingPhone' => '+' . $billing->phone_cc . '.' . $billing->phone
        );

        if ($billing->company !='') {
            $billing_contact['AuxBillingOrganization'] = $billing->company;

            if ($billing->job_title == '') {
                $billing->job_title = 'N/A';
            }

            $billing_contact['AuxBillingJobTitle'] = $billing->job_title;
        }

        // Merge contacts into the $domain_params array
        $domain_params = array_merge($domain_params, $registrant_contact, $administrative_contact, $technical_contact, $billing_contact);

        if (is_array($request->custom_data)) {
            $domain_params = array_merge($domain_params, $request->custom_data);
        }

        $custom_registration_handler = false;

        $extension_class = \App::get('domainhelper')->loadExtensionClass($request->domain);

        if ($extension_class && $extension_class->registration_handler) {
            // Some domains may use a custom registration handler if they use a different
            // api call. These will be handled directly in the extension handler class
            // if one exists.
            // This domain extension uses a custom registration handler.
            $result = $extension_class->registerDomain($domain_params);
        } else {
            $result = $domain_api->registerDomain($domain_params);
        }

        if (isset($result->actionstatus) && $result->actionstatus == 'Success') {
            $return = new \stdClass();
            $return->status = '1';

            return $return;
        }

        $return = new \stdClass();
        $return->status = '0';

        if(isset($result->actionstatusdesc)) {
            $return->response = new \stdClass();
            $return->response->type = 'error';
            $return->response->message = $result->actionstatusdesc;
        }

        return $return;
    }

    /**
     * Renew Domain
     *
     * Renews the domain name for a given period of time.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function renewDomain($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        // Split the domain at the extension
        $domain_data = $domain_api->domainInfo($request->domain->domain);

        $domain_params = array(
            'orderid' => $domain_data->orderid,
            'years' => $request->years,
            'exp-date' => $domain_data->endtime
        );

        $custom_renewal_handler = false;

        $extension_class = \App::get('domainhelper')->loadExtensionClass($request->domain);

        if ($extension_class && $extension_class->renewal_handler) {
            // Some domains may use a custom renewal handler if they use a different
            // api call. These will be handled directly in the extension handler class
            // if one exists.
            // This domain extension uses a custom renewal handler.
            $result = $extension_class->renewDomain($domain_params);
        } else {
            $result = $domain_api->renewDomain($domain_params);
        }


        if (isset($result->status) && $result->status == 'Success') {
            $return = new \stdClass();
            $return->status = '1';

            return $return;
        }

        $return = new \stdClass();
        $return->status = '0';
        return $return;
    }

   /**
     * Transfer Domain
     *
     * Tells the registry to request a transfer of the domain name and sets the
     * contacts.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function transferDomain($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        // Load the customer api
        $customer_api = new Api\Customer();

        // Split the domain at the extension
        $domain_parts = \App::get('domainhelper')->splitDomain($request->domain->domain);

        // Build contact data
        $registrant = \App::get('domainhelper')->getContact($request->contacts['registrant']);
        $administrative = \App::get('domainhelper')->getContact($request->contacts['administrative']);
        $technical = \App::get('domainhelper')->getContact($request->contacts['technical']);
        $billing = \App::get('domainhelper')->getContact($request->contacts['billing']);


        // Registrant contact. Not all extensions require this, however we'll set
        // it anyway as it can still be used and reduces the need to do additional
        // unnecessery checks.
        $registrant_contact = array(
            'FirstName' => $registrant->first_name,
            'LastName' => $registrant->last_name,
            'Address1' => $registrant->address1,
            'Address2' => $registrant->address2,
            'City' => $registrant->city,
            'StateProvince' => $registrant->state,
            'PostalCode' => $registrant->postcode,
            'Country' => \App::get('domainhelper')->getIsoCode($registrant->country),
            'EmailAddress' => $registrant->email,
            'Phone' => '+' . $registrant->phone_cc . '.' . $registrant->phone,
        );

        if ($registrant->company !='') {
            $registrant_contact['Company'] = $registrant->company;
        }

        $customerid = false;

        $product_purchase = $request->domain->ProductPurchase()->first();
        $client = $product_purchase->Client()->first();

        // Check if customer exists
        $customer = $customer_api->getCustomerByEmail(array('username' => $client->email));

        if (isset($customer->customerid) && $customer->customerid > 0) {
            // Customer exists. Store their id
            $customerid = $customer->customerid;
        } else {
            // Create customer
            $customer = $customer_api->createCustomer(array('client' => $client));
        }

        $customerid = $customer->customerid;

        // Now we have a customer id we can create a contact id.

        $customer_params = array(
            'client' => $client,
            'domain_name' => $request->domain->domain
        );

        $customer_params = array_merge($customer_params, $registrant_contact);

        $customer_params['customer_id'] = $customerid;

        $contactid = $customer_api->createContact($customer_params);

        // At this point we've got a customer id and a contact id.
        // Time to so the transfer request.

        if (is_array($request->custom_data)) {
            $domain_params = array_merge($domain_params, $request->custom_data);
        }
        $custom_transfer_handler = false;

        $extension_class = \App::get('domainhelper')->loadExtensionClass($request->domain);


        $domain_params = array(
            'domain-name' => $request->domain->domain,
            'auth-code' => $request->auth_code,
            'customer-id' => $customerid,
            'reg-contact-id' => $contactid,
            'admin-contact-id' => $contactid,
            'tech-contact-id' => $contactid,
            'billing-contact-id' => $contactid,

        );


        if ($extension_class && $extension_class->transfer_handler) {
            // Some domains may use a custom transfer handler if they use a different
            // api call. These will be handled directly in the extension handler class
            // if one exists.
            // This domain extension uses a custom transfer handler.
            $result = $extension_class->transferDomain($domain_params);
        } else {
            $result = $domain_api->transferDomain($domain_params);
        }

        if (isset($result->status) && $result->status == 'AdminApproved') {
            $return = new \stdClass();
            $return->status = '1';

            return $return;
        }

        $return = new \stdClass();
        $return->status = '0';
        return $return;
    }

    /**
     * Set Domain Lock
     *
     * Sets the domain to either locked or unlocked depending on the request.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function setDomainLock($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        $domain_info = $domain_api->domainInfo($request->domain->domain);

        $domain_params = array(
            'order-id' =>$domain_info->orderid
        );

        if($request->unlocked) {
            $result = $domain_api->disableTheftProtection($domain_params);
        } else {
            $result = $domain_api->enableTheftProtection($domain_params);
        }

        if (isset($result->actionstatus) && $result->actionstatus == 'Success') {
            $result->status = '1';
            return $result;
        }

        $result->status = '0';
        return $result;
    }

    /**
     * Get Domain Auth Code
     *
     * Tells Enom to email the auth code to the domain owner, as they do not
     * currently support getting the auth code directly. Because of this we'll
     * return a message based response to the domain helper.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function getDomainAuthCode($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        $domain_info = $domain_api->domainInfo($request->domain->domain);

        $result = new \stdClass();

        if (isset($domain_info->domsecret) && $domain_info->domsecret != '') {
            $result->auth_code = $domain_info->domsecret;
            $result->status = '1';
            return $result;
        }

        $result->status = '0';
        return $result;
    }

    /**
     * Get Domain Nameservers
     *
     * Retrieves the current nameservers for the domain.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function getDomainNameservers($request)
    {

        // Load the domain api
        $domain_api = new Api\Domain();

        $result = $this->getDomainInfo($request);

        if (isset($result->status) && $result->status == '1') {

            //$result->nameservers = $result->nameservers;
            $result->status = '1';
            return $result;
        }

        $result->status = '0';
        return $result;
    }

    /**
     * Set Domain Nameservers
     *
     * Sets the nameservers for the domain.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function setDomainNameservers($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        // Split the domain at the extension
        $domain_parts = \App::get('domainhelper')->splitDomain($request->domain->domain);

        $domain_info = $domain_api->domainInfo($request->domain->domain);

        $domain_params = array(
            'order-id' => $domain_info->orderid,
            'ns' => $request->nameservers
        );

        // Because we need to move the 'dns' value over to the 'nameservers' value
        // it's easier to just convert the simplexmlelement to a standard object,
        // hence the json encode/decode below.
        $result = $domain_api->modifyNameservers($domain_params);

        if (isset($result->status) && $result->status == 'Success') {
            $result->status = '1';
            return $result;
        } elseif (isset($result->status) && $result->status == 'Failed') {

            if(isset($result->actionstatusdesc) && $result->actionstatusdesc != '') {

                $result->response = new \stdClass();
                $result->response->type = 'error';
                $result->response->message = $result->actionstatusdesc;
            }

        }

        $result->status = '0';
        return $result;
    }


    /**
     * Set Domain Contacts
     *
     * Tells the registry to update the contact details for the domain whois.
     *
     * @param  Object $request An object containing the domain data.
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function setDomainContacts($request)
    {

        // Load the domain api
        $domain_api = new Api\Domain();

        // Load the customer api
        $customer_api = new Api\Customer();

        // Split the domain at the extension
        $domain_info = $this->getDomainInfo($request);

        $product_purchase = $request->domain->ProductPurchase()->first();
        $client = $product_purchase->Client()->first();

        // Build contact data
        $registrant = \App::get('domainhelper')->getContact($request->contacts['registrant']);


        // Registrant contact. Not all extensions require this, however we'll set
        // it anyway as it can still be used and reduces the need to do additional
        // unnecessery checks.
        $registrant_contact = array(
            'FirstName' => $registrant->first_name,
            'LastName' => $registrant->last_name,
            'Address1' => $registrant->address1 . ' '. $registrant->address2,
            'City' => $registrant->city,
            'PostalCode' => $registrant->postcode,
            'Country' => \App::get('domainhelper')->getIsoCode($registrant->country),
            'EmailAddress' => $registrant->email,
            'phone-cc' => $registrant->phone_cc,
            'phone' => $registrant->phone,
        );

        if ($registrant->company !='') {
            $registrant_contact['company'] = $registrant->company;
        } else {
            $registrant_contact['company'] = 'N/A';
        }

        if ($registrant->fax != '' && $registrant->fax_cc !='') {
            $registrant_contact['fax-cc'] = $registrant->fax_cc;
            $registrant_contact['fax'] = $registrant->fax;
        }

        $registrant_contact['client'] = $client;
        $registrant_contact = array_merge($registrant_contact, (array)$domain_info);

        // Logicboxes provides no method of setting billing, tech or admin contacts.
        // The above code is only using the registrant for now, and in an update
        // the intention will be to bring in this functionality ourselves by
        // defining four individual contacts, and storing their logicboxes contact
        // id's so we can manually assign them as registrant, tech, admin and
        // billing.
        $domain_data = $domain_api->domainInfo($request->domain->domain);

        // Create the new contact record so we can get a new contact id.
        $new_contact = $customer_api->createContact($registrant_contact);

        $contact_params = array(
            'order-id' => $domain_data->orderid,
            'reg-contact-id' => $new_contact,
            'admin-contact-id' => $new_contact,
            'billing-contact-id' => $new_contact,
            'tech-contact-id' => $new_contact
        );

        $result = $domain_api->setDomainContacts($contact_params);

        if (isset($result->status) && $result->status == 'Success') {
            $return = new \stdClass();
            $return->status = '1';

            return $return;
        }

        $return = new \stdClass();
        $return->status = '0';
        return $return;
    }


    /**
     * Product Fields
     *
     * Returns form fields specific to domains registered through this registrar
     * on the product management page.
     *
     * @param  int $extension_id The id of the domain extension
     * @param  int $service_id The id of the purchased service (aka purchase id)
     * @return string Returns the HTML form that gets injected into the product management page.
     */
    public function productFields($extension_id)
    {
        return null;
    }

    /**
     * Terminate Service
     *
     * With domains we do not want to terminate them. The only option a termination
     * would provide is completely deleting the domain, and making it available
     * to register by anyone. Generally this isn't the standard practice when
     * terminating someones domain service. For now until we review all options,
     * the terminate function does nothing.
     *
     * This will likely however eventually provide a number of options such as
     * changing the domain owner and nameservers to a parking page, or simply
     * suspending the domain.
     *
     * For now we return true to allow WHSuite to continue and remove the service
     * as being active for the client.
     *
     * @param  int $domain_id The id of the domain to terminate
     * @return bool Returns the status of the termination
     */
    public function terminateService($domain_id)
    {
        return true;
    }

   /**
     * Suspend Service
     *
     * Suspends the domain with a generic suspention notice.
     *
     * @param  int $domain_id The id of the domain name
     * @return string Returns true if the action was successful.
     */
    public function suspendService($domain_id)
    {
        $domain = \Domain::find($domain_id);

        $domain_api = new Api\Domain();
        $domain_info = $domain_api->domainInfo($domain->domain);

        $params = array(
            'order-id' => $domain_info->orderid,
            'reason' => 'Suspended by system.'
        );

        $result = $orderApi->suspendOrder($params);

        if ($result->status == 'Success') {
            return true;
        }
        return false;
    }

    /**
     * Unsuspend Service
     *
     * Unsuspends the domain.
     *
     * @param  int $domain_id The id of the domain name
     * @return string Returns true if the action was successful.
     */
    public function unsuspendService($domain_id)
    {
        $domain = \Domain::find($domain_id);

        $domain_api = new \Addon\Logicboxes\Libraries\Api\Domain($this->reseller_id, $this->api_key, $this->sandbox);
        $domain_info = $domain_api->domainInfo($domain->domain);

        $params = array(
            'order-id' => $domain_info->orderid
        );

        $result = $orderApi->unsuspendOrder($params);

        if ($result->status == 'Success') {
            return true;
        }
        return false;
    }


    /**
     * Check Availability
     *
     * Checks the availability of a domain name.
     *
     * @param  Object $request The request object
     * @return Object Returns a result object containing either error responses or the requested data.
     */
    public function domainAvailability($request)
    {
        // Load the domain api
        $domain_api = new Api\Domain();

        // Split the domain at the extension
        $domain_parts = \App::get('domainhelper')->splitDomain($request->domain);

        $domain_params = array(
            'SLD' => $domain_parts[0],
            'TLD' => $domain_parts[1],
        );

        $result = $domain_api->domainAvailability($domain_params);

        if (!isset($result->{$request->domain})) {
            return false;
        }

        $result = $result->{$request->domain};

        $response = new \stdClass();

        if (isset($result) && !empty($result)) {
            $response->status = '1';

            if ($result->status == 'available') {

                $response->availability = 'available';
            } elseif ($result->status == 'regthroughothers' || $result->status == 'regthroughus') {

                $response->availability = 'registered';
            } else {
                $response->availability = 'unknown';
            }

            return $response;
        }

        $response->status = '0';
        return $response;
    }

    /**
     * Update Remote
     *
     * The update remote method is primarily used for hosting, and allows WHSuite
     * to request that a hosting account is updated. With hosting accounts the
     * param parsed is the hosting account id. However for domains and other services
     * we parse the purchase id.
     *
     * In most cases domains have no need to perform a remote update as you cant
     * exactly go and tell a domain registrar to modify the expiry date, or anything like
     * that. So for now, we dont actually do anything at all, and just return true.
     *
     * We've left this method here purely for any future developments or special
     * cases that do require you to update a registrar.
     *
     * @param  int $id The id of the client who owns the service
     * @param  int $service_id The id of the purchased service (aka purchase id)
     * @return Redirect Residrects back to the domain management page
     */
    public function updateRemote($purchase_id)
    {
        return true;
    }


    /**
     * Check Availability
     *
     * Checks the availability of a domain name.
     *
     * @param  string $domain The domain name to check the availability of
     * @return string Returns the registration state of the domain ('available', 'registered' or 'unknown')
     */
    public function checkAvailability($domain)
    {
        $this->init();

        $domain_api = new \Addon\Logicboxes\Libraries\Api\Domain($this->reseller_id, $this->api_key, $this->sandbox);

        $result = $domain_api->domainAvailability($domain);

        foreach ($result as $item) {
            $status = $item->status;
        }

        if ($status == 'available') {
            return 'available';
        } elseif ($status == 'regthroughus') {
            return 'registered';
        } elseif ($status == 'regthroughothers') {
            return 'registered';
        }
        return 'unknown';
    }

    /**
     * Register Domain
     *
     * Performs the registration action on a domain stored in the whsuite database.
     *
     * @param  string $domain_id The domain id to register
     * @return array Returns the registration results
     */
    public function oldregisterDomain($domain_id)
    {
        $domain = \Domain::find($domain_id);
        $purchase = $domain->ProductPurchase()->first();
        $client = $purchase->Client()->first();
        $product = $purchase->Product()->first();

        $domain_api = new \Addon\Logicboxes\Libraries\Api\Domain();

        $registrar_data = json_decode($domain->registrar_data);

        $registrar_data->years = $domain->registration_period;
        $registrar_data->nameservers = array_filter($registrar_data->nameservers);

        $domain_params = array(
            'client_id' => $client->id,
            'nameservers' => array_filter($registrar_data->nameservers),
            'domain' => $domain->domain,
            'years' => $domain->registration_period,
            'post_data' => (array)$registrar_data
        );

        // Check for any special params based on certain domain extension
        // being used.
        $tld_ending = str_replace('.', '_', ucfirst(ltrim($domain_api->getTld($domain->domain), ".")));

        // Check if a class exists for the tld ending
        if(class_exists('\Addon\Logicboxes\Libraries\Api\Domain'.$tld_ending)) {
            // The class exists, load it into a var for simplicity.
            $class_name = '\Addon\Logicboxes\Libraries\Api\Domain'.$tld_ending;
            $tld_class = new $class_name();
        }

        if(isset($tld_class)) {
            // We want to send those custom contact params through to the
            // register domain method, as inside there we'll quietly create
            // a contact record for the client. This saves having to fill out
            // extra sets of contact details, or maintain a list of contact
            // detaild and keep then synced to the registrar.
            $result = $domain_api->registerDomain($domain_params, array('domain_contact_params' => $tld_class->setContactParams($registrar_data)));
        } else {
            $result = $domain_api->registerDomain($domain_params);
        }

        return $result;
    }

    /**
     * Transfer Domain
     *
     * Performs the transfer action on a domain stored in the whsuite database.
     *
     * @param  string $domain_id The domain id to transfer
     * @return array Returns the transfer results
     */
    public function oldtransferDomain($domain_id)
    {
        $domain = \Domain::find($domain_id);
        $purchase = $domain->ProductPurchase()->first();

        $domain_data = json_decode($domain->registrar_data, true);

        $this->init();

        $domain_api = new \Addon\Logicboxes\Libraries\Api\Domain();

        $params = array(
            'client_id' => $purchase->client_id,
            'domain' => $domain->domain,
            'nameservers' => explode(", ", $domain->nameservers)
        );

        $domain_params = array();

        $tld_ending = ucfirst(ltrim($domain_api->getTld($domain->domain), "."));

        // Check if a class exists for the tld ending
        if(class_exists('\Addon\Logicboxes\Libraries\Api\Domain'.$tld_ending)) {
            // The class exists, load it into a var for simplicity.
            $class_name = '\Addon\Logicboxes\Libraries\Api\Domain'.$tld_ending;
            $tld_class = new $class_name();

            $domain_params = $domain_params + $tld_class->setRegistrationParams($domain_data);
        }

        return $domain_api->transferDomain($params, $domain_params);
    }
}