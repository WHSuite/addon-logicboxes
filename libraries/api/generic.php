<?php

namespace Addon\Logicboxes\Libraries\Api;

class Generic extends LogicboxesApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Country List
     *
     * Returns the country list object from Logicboxes
     *
     * @return string Returns the list object
     */
    public function countryList()
    {
        $countries = $this->get('/country/list.json');

        return $countries;
    }

}
