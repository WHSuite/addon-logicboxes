<?php
namespace Addon\Logicboxes\Libraries\Api;

class Domain extends LogicboxesApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Domain Info
     *
     * Returns all the details about the domain - the raw object direct from
     * Logicboxes
     *
     * @param  string $domain The domain name we want to get the details for
     * @return object Returns the domain details object
     */
    public function domainInfo($domain)
    {
        $params = array(
            'domain-name' => $domain,
            'options' => 'All'
        );

        return $this->get('/domains/details-by-name.json', $params);
    }

    /**
     * Check Domain Availablity
     *
     * Runs an availablity check on a domain.
     *
     * @param  string $domain The domain name we want to check
     * @return object Returns the availablity object
     */
    public function domainAvailability($request)
    {
        $params = array(
            'domain-name' => $request['SLD'],
            'tlds' => $request['TLD']
        );
        return $this->get('/domains/available.json', $params);

    }

    /**
     * Register Domain
     *
     * Attemps to register a domain name using provided domain and contact params.
     *
     * @param  array $params The params required to perform the domain registration
     * @param  arrat $contact_params The contact details to use for the registration
     * @return object Returns the registration object.
     */
    public function registerDomain(array $params, array $contact_params = array())
    {
        // Load the domain record based on the provided data.
        $domain_name = $params['SLD']. '.' . $params['TLD'];
        $params['domain'] = $domain_name;

        $domain = \Domain::where('domain', '=', $domain_name)->first();

        if(empty($domain)) {
            return false;
        }
        $purchase = $domain->ProductPurchase()->first();
        $client = $purchase->Client()->first();

        $params['client'] = $client;

        $customerApi = new Customer;

        try {
            $customer = $customerApi->getCustomerByEmail(array('username' => $client->email));
        } catch(\Exception $e) {
            $customer = null;
        }

        if (isset($customer->customerid) && $customer->customerid > 0) {

            $customer_id = $customer->customerid;
        } else {

            // Customer does not exist, create them based on the client details
            $customer_id = $customerApi->createCustomer($params);
            if(!is_int($customer_id)) {
                \App::get('session')->setFlash('error', $customer_id->message);
                return false;
            }
        }

        // Now we need to sort out contact details. Certain domains require that
        // the contact details have special details. First lets determin if this
        // domain falls into that category, as if it doesn't we can just attempt
        // to get the default contact details (and if they dont exist, we'll
        // create them)

        $tld = $this->domainTld($domain_name);
        $contact_type = $customerApi->contactType($tld);

        // Default contact data:
        $default_contact = $customerApi->getDefaultContact($customer_id, $domain_name);
        if ((isset($default_contact->status) && $default_contact->status == 'ERROR') || (isset($default_contact->type) && $defaut_contact->type != $contact_type) || (!empty($contact_params)) || !isset($default_contact->Contact)) {
            // What we're doing here is 3 checks. The first checks to see if the
            // default contact returned an error. If it did, there's a good chance
            // it was because no contact details exist, so we'll have to create them
            // based on the client's information.
            //
            // The second check is to see if the returned contact information was
            // the actual contact details we need. Some domains have special contact
            // details and are different types (e.g 'CoopContact' instead of 'Contact')
            // If the type doesn't match the domain, we need to create a new contact
            // that supports thre type of domain we're about to register. That'll
            // then be usable for any future registrations of this type of domain
            // as well.
            //
            // Finally the third check is to see of the contact params is filled
            // out - if it is, it means that whilst this domain might not have a
            // custom contact type, it does need some additional details. This
            // is a bit of a strange way of doing it (it would seemingly have
            // made more sense for Logicboxes to just have custom contact types
            // instead of random additional bits of data) but is needed by some
            // extensions, such as .asia. Because tracking all these extra details
            // would be quite complex, we just create a new contact record for each
            // applicable domain as there wont be any need to manage them and we can
            // always replace them if a customer wants to modify the contact details.
            $contact_params['client_id'] = $client->id;
            $contact_params['customer-id'] = $customer_id;
            $contact_params['domain'] = $params['domain'];

            $contact_id = $customerApi->createContact($params);
        } else {
            $contact_id = $default_contact->Contact->registrant;
        }

        if (isset($contact_id) && strlen($contact_id) > 8) {
            \App::get('session')->setFlash('error', \App::get('translation')->get('domain_registration_error_contact_details'));
            return false;
        }

        // With Logicboxes some of the contacts must be set to -1 if the domain
        // extension is a certain type. To do this we're just going to create
        // variables for each contact type, then override the values for those
        // extensions that dont need them.
        if (strpos($tld, '.')) {
            $tld_part = explode('.', $tld);
            $tld = end($tld_part);
        }

        $admin_contact_blacklist = array('eu', 'nz', 'ru', 'uk');
        if (in_array($tld, $admin_contact_blacklist)) {
            $admin_contact_id = -1;
        } else {
            $admin_contact_id = $contact_id;
        }

        $tech_contact_blacklist = array('eu', 'nz', 'ru', 'uk');
        if (in_array($tld, $tech_contact_blacklist)) {
            $tech_contact_id = -1;
        } else {
            $tech_contact_id = $contact_id;
        }

        $bill_contact_blacklist = array('at', 'berlin', 'ca', 'eu', 'nl', 'nz', 'ru', 'uk');
        if (in_array($tld, $bill_contact_blacklist)) {
            $bill_contact_id = -1;
        } else {
            $bill_contact_id = $contact_id;
        }

        $nameservers = array();

        foreach ($params as $key => $value) {
            if (substr($key, 0, 2) === 'NS') {
                $nameservers[] = $value;
            }
        }

        $domain_params = array(
            'domain-name' => $params['domain'],
            'years' => $params['NumYears'],
            'ns' => $nameservers,
            'customer-id' => $customer_id,
            'reg-contact-id' => $contact_id,
            'admin-contact-id' => $admin_contact_id,
            'tech-contact-id' => $tech_contact_id,
            'billing-contact-id' => $bill_contact_id,
            'invoice-option' => 'NoInvoice',
            'protect-privacy' => 'false'
        );


        $result = $this->get('/domains/register.json', $domain_params);

        if($result->status == 'Success') {
            $this->syncDomain($params['domain']);
        }

        return $result;
    }

    /**
     * Renew Domain
     *
     * Attempts to renew a domain that already exists in WHSuite.
     *
     * @param  array $params The domain params needed for the renewal
     * @return object Returns the renewal object
     */
    public function renewDomain(array $params)
    {
        $renew_params = array(
            'order-id' => $params['orderid'],
            'years' => $params['years'],
            'exp-date' => $params['exp-date'],
            'invoice-option' => 'NoInvoice'
        );

        return $this->post('/domains/renew.json', $renew_params);
    }

    /**
     * Transfer Domain
     *
     * Attempts to transfer in a domain that already exists in WHSuite.
     *
     * @param  array $params The domain params needed for the transfer
     * @param  array $contact_params The contact params to use for the new domain record
     * @return object Returns the transfer object
     */
    public function transferDomain(array $params, array $contact_params = array())
    {
        $customerApi = new Customer;

        // Now we need to sort out contact details. Certain domains require that
        // the contact details have special details. First lets determin if this
        // domain falls into that category, as if it doesn't we can just attempt
        // to get the default contact details (and if they dont exist, we'll
        // create them)

        $tld = $this->domainTld($params['domain-name']);
        $contact_type = $customerApi->contactType($tld);

        $domain_params = array(
            'domain-name' => $params['domain-name'],
            'customer-id' => $params['customer-id'],
            'reg-contact-id' => $params['reg-contact-id'],
            'admin-contact-id' => $params['admin-contact-id'],
            'tech-contact-id' => $params['tech-contact-id'],
            'billing-contact-id' => $params['billing-contact-id'],
            'invoice-option' => 'NoInvoice',
            'protect-privacy' => 'false'
        );

        // If we need to, we can add to / modify the above domain params depending
        // on the domain extention. One example is .asia which needs to add a
        // 'cedcontactid' field, which should just match the existing contact fields.
        $tld_ending = ucfirst(ltrim($this->domainTld($params['domain-name']), "."));
        if(class_exists('\Addon\Logicboxes\Libraries\Api\Domain'.$tld_ending)) {
            // The class exists, load it into a var for simplicity.
            $class_name = '\Addon\Logicboxes\Libraries\Api\Domain'.$tld_ending;
            $tld_class = new $class_name();

            $domain_params = $domain_params + $tld_class->modifyDomainParams($domain_params);
        }

        return $this->get('/domains/transfer.json', $domain_params);
    }

    /**
     * Set Domain Contacts
     *
     * Updates the domain's contact
     *
     * @param  array $params The domain details and nameservers to use
     * @return object Returns the modify-ns object
     */
    public function setDomainContacts(array $params)
    {
        return $this->get('/domains/modify-contact.json', $params);
    }



    /**
     * Modify Nameservers
     *
     * Updates the domain's nameserver records.
     *
     * @param  array $params The domain details and nameservers to use
     * @return object Returns the modify-ns object
     */
    public function modifyNameservers(array $params)
    {
        return $this->get('/domains/modify-ns.json', $params);
    }

    /**
     * Add Child Nameserver
     *
     * Adds a child nameserver to a domain already in WHSuite.
     *
     * @param  array $params The domain params needed for the child nameserver
     * @return object Returns the add-cns object
     */
    public function addChildNameserver(array $params)
    {
        return $this->get('/domains/add-cns.json', $params);
    }

    /**
     * Add Child Nameserver Hostname
     *
     * Adds a child nameserver hostname to a domain already in WHSuite.
     *
     * @param  array $params The domain params needed for the child nameserver hostname
     * @return object Returns the add-cns-name object
     */
    public function modifyChildNameserverHostName(array $params)
    {
        return $this->get('/domains/modify-cns-name.json', $params);
    }

    /**
     * Modify Child Nameserver IP Address
     *
     * Updates a child nameserver on a domain already in WHSuite.
     *
     * @param  array $params The domain params needed for the child nameserver
     * @return object Returns the modify-cns-ip object
     */
    public function modifyChildNameserverIpAddress(array $params)
    {
        return $this->get('/domains/modify-cns-ip.json', $params);
    }

    /**
     * Delete Child Nameserver
     *
     * Deletes a child nameserver record (based on its IP) on a domain already in WHSuite.
     *
     * @param  array $params The domain params needed for the child nameserver
     * @return object Returns the delete-cns-ip object
     */
    public function deleteChildNameserver(array $params)
    {
        return $this->get('/domains/delete-cns-ip.json', $params);
    }

    /**
     * Modify Domain Locks
     *
     * Used for performing different locking actions on the domain name such as
     * suspend/unsuspend.
     *
     * @param  array $params The domain params needed for the domain lock
     * @return object Returns the locks object
     */
    public function domainLocks(array $params)
    {
        return $this->get('/domains/locks.json', $params);
    }

    /**
     * Enable Theft Protection
     *
     * Enables the domain lock (not to be confused with the domainLocks method)
     * that prevents the domain being transferred out. Logicboxes calls this
     * 'Domain Theft Protection'
     *
     * @param  array $params The domain params needed for the theft protection
     * @return object Returns the enable-theft-protection object
     */
    public function enableTheftProtection(array $params)
    {
        return $this->get('/domains/enable-theft-protection.json', $params);
    }

    /**
     * Disable Theft Protection
     *
     * Disables the domain lock (not to be confused with the domainLocks method)
     * that prevents the domain being transferred out. Logicboxes calls this
     * 'Domain Theft Protection'
     *
     * @param  array $params The domain params needed for the theft protection
     * @return object Returns the disable-theft-protection object
     */
    public function disableTheftProtection(array $params)
    {
        return $this->get('/domains/disable-theft-protection.json', $params);
    }

    /**
     * Delete Domain
     *
     * Tells the registry to delete the domain, making it available to be
     * registered by anyone.
     *
     * @param  array $params The domain params needed for the delete action
     * @return object Returns the delete object
     */
    public function deleteDomain(array $params)
    {
        return $this->get('/domains/delete.json', $params);
    }

    /**
     * Resend RAA Email
     *
     * Resends the ICANN RAA Email verification to the registrant of the domain.
     *
     * @param  array $params The domain params needed to resend the email
     * @return object Returns the resend-verification object
     */
    public function resendRaaEmail(array $params)
    {
        return $this->get('/domains/raa/resend-verification.json', $params);
    }

    /**
     * Sync Domain
     *
     * Resyncs the local data we have about the domain such as its expiry date,
     * nameservers, etc. This is run when any major action is performed on a domain
     * to ensure we've got the up to date data on record.
     *
     * @param  string $domain The domain name to sync
     * @param  object $domain_info Optional - you can pass the domain info from the domainInfo method, however it'll do this itself if its not provided
     * @return object Returns the enable-theft-protection object
     */
    public function syncDomain($domain, $domain_info = null)
    {
        $domain_record = \Domain::where('domain', '=', $domain)->first();
        $purchase = \ProductPurchase::find($domain_record->product_purchase_id);
        if ($domain_record->enable_sync == '1') {

            if ($domain_info == null) {
                $domain_info = $this->domainInfo($domain);
            }

            if (!isset($domain_info) || !isset($domain_info->noOfNameServers)) {
                return null;
            }

            $nameservers = '';
            for($i=1;$i<=$domain_info->noOfNameServers;$i++) {
                $nsfield = 'ns'.$i;
                $nameservers .= $domain_info->$nsfield.', ';
            }
            $nameservers = rtrim($nameservers, ', ');
            $registrar_lock = '0';
            if (isset($domain_info->orderstatus))
            {
                foreach ($domain_info->orderstatus as $id => $orderstatus) {
                    if ($orderstatus == 'transferlock' || $orderstatus == 'customerlock') {
                        $registrar_lock = '1';
                    }
                }
            }

            // Since some domains dont have certain contact types, we'll double
            // check to make sure they are set in the domain_info var and if not
            // add them to it.
            if (!isset($domain_info->admincontact)) {
                $domain_info->admincontact = new \stdClass();
                $domain_info->admincontact->contactid = '0';
            }

            if (!isset($domain_info->billingcontact)) {
                $domain_info->billingcontact = new \stdClass();
                $domain_info->billingcontact->contactid = '0';
            }

            if (!isset($domain_info->techcontact)) {
                $domain_info->techcontact = new \stdClass();
                $domain_info->techcontact->contactid = '0';
            }

            $Carbon = \Carbon\Carbon::createFromTimestamp(
                $domain_info->creationtime,
                \App::get('configs')->get('settings.localization.timezone')
            );
            $domain_record->date_registered = $Carbon->toDateString();

            $Carbon = \Carbon\Carbon::createFromTimestamp(
                $domain_info->endtime,
                \App::get('configs')->get('settings.localization.timezone')
            );
            $domain_record->date_expires = $Carbon->toDateString();
            $domain_record->nameservers = $nameservers;
            $domain_record->registrar_lock = $registrar_lock;
            $domain_record->registrar_data = 'orderid: '.$domain_info->orderid.'. admincontactid: '.$domain_info->admincontact->contactid.'. billingcontactid: '.$domain_info->billingcontact->contactid.'. techcontactid: '.$domain_info->techcontact->contactid.'. registrantcontact: '.$domain_info->registrantcontact->contactid;
            $domain_record->save();

            // Recalculate the next invoice date.
            $invoice_days = \App::get('configs')->get('settings.billing.domain_invoice_days');

            $expiry_date = new \DateTime();
            $expiry_date->setTimestamp($domain_info->endtime);
            $expiry_date->sub(new \DateInterval('P'.$invoice_days.'D'));
            $next_invoice_date = $expiry_date->format('Y-m-d');

            $Carbon = \Carbon\Carbon::createFromTimestamp(
                $domain_info->endtime,
                \App::get('configs')->get('settings.localization.timezone')
            );
            $purchase->next_renewal = $Carbon->toDateString();
            $purchase->next_invoice = $next_invoice_date;
            $purchase->save();
        }
    }

    /**
     * Domain TLD
     *
     * Strips out the domain to return just the TLD.
     *
     * @param  string $domain The domain to get the TLD of.
     * @return string Returns the tld
     */
    public function domainTld($domain)
    {
        $domain = strtolower($domain);

        $tld_part_exploded = explode(".", $domain, 2);
        $tld_part = end($tld_part_exploded);
        return $tld_part;
    }
}
