<?php

namespace Addon\Logicboxes\Libraries\Api;

class Order extends LogicboxesApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Suspend Order
     *
     * Suspends an order. This can be any service type and is not restricted to
     * just domains or hosting.
     *
     * @param  array Params containing the order id data
     * @return object Returns the suspend object
     */
    public function suspendOrder(array $params)
    {
        return $this->post('/orders/suspend.json', $params);
    }

    /**
     * Unsuspend Order
     *
     * Unsuspends an order. This can be any service type and is not restricted to
     * just domains or hosting.
     *
     * @param  array Params containing the order id data
     * @return object Returns the unsuspend object
     */
    public function unsuspendOrder(array $params)
    {
        return $this->post('/orders/unsuspend.json', $params);
    }

}
