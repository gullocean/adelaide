<?php

if (defined('PATH_THIRD')) {
    require PATH_THIRD.'store/autoload.php';
}

use Store\Model\Order;
use Store\Model\OrderShippingMethod;

class Store_aus_post_ext
{
    const VERSION = '1.0.2';

    public $name = 'Store Australia Post Shipping';
    public $version = self::VERSION;
    public $description = 'Provides Australia Post shipping calculations for Expresso Store';
    public $settings_exist = 'y';
    public $docs_url = 'https://exp-resso.com/docs';
    public $settings = array();
    public $endpoint = 'http://drc.edeliver.com.au/ratecalc.asp';

    // dimensions (mm)
    const MIN_DIMENSION = 50;
    const MAX_LENGTH = 1050;
    const MAX_GIRTH = 1400; // 2 * width + 2 * height

    // weight (g)
    const MIN_WEIGHT = 100;
    const MAX_WEIGHT = 20000;

    public function __construct($settings = array())
    {
        $defaults = array();
        foreach (array_keys($this->settings()) as $key) {
            $defaults[$key] = null;
        }
        $this->settings = array_merge($defaults, $settings);
    }

    public function activate_extension()
    {
        $data = array(
            'class'     => __CLASS__,
            'method'    => 'shipping_methods',
            'hook'      => 'store_order_shipping_methods',
            'priority'  => 10,
            'settings'  => serialize($this->settings),
            'version'   => $this->version,
            'enabled'   => 'y'
        );

        ee()->db->insert('extensions', $data);
    }

    public function update_extension($current = '')
    {
        if ($current == '' || $current == $this->version) {
            return false;
        }

        ee()->db->where('class', __CLASS__)
            ->update('extensions', array('version' => $this->version));
    }

    public function settings()
    {
        $settings = array();
        $settings['source_postcode'] = '';
        $settings['service'] = array('s', array(
            'Standard' => 'Regular',
            'Express' => 'Express',
            'Exp_Plt' => 'Express Platinum',
            'ECI_D' => 'Express Courier International Document',
            'ECI_M' => 'Express Courier International Merchandise',
            'Air' => 'International Air',
            'Sea' => 'International Sea',
        ));

        return $settings;
    }

    public function shipping_methods(Order $order, array $methods)
    {
        if (ee()->extensions->last_call !== false) {
            $methods = ee()->extensions->last_call;
        }

        $option = new OrderShippingMethod;
        $option->id = __CLASS__;
        $option->name = 'Australia Post';
        $option->amount = 0.0;
        $option->class = __CLASS__;

        if ($order->order_shipping_qty > 0) {
            $request = $this->build_request($order);
            $response = $this->send($request);
            $response_array = $this->decode_response($response->getBody());

            if ($response_array['err_msg'] != 'OK') {
                return $methods;
            }

            $option->amount = $response_array['charge'];
        }

        $methods[$option->id] = $option;

        return $methods;
    }

    public function build_request(Order $order)
    {
        // prep aus post query
        $request = array();
        $request['Length'] = max(static::MIN_DIMENSION, round($order->order_shipping_length_cm * 10));
        $request['Width'] = max(static::MIN_DIMENSION, round($order->order_shipping_width_cm * 10));
        $request['Height'] = max(static::MIN_DIMENSION, round($order->order_shipping_height_cm * 10));
        $request['Weight'] = max(static::MIN_WEIGHT, round($order->order_shipping_weight_kg * 1000));
        $request['Pickup_Postcode'] = $this->settings['source_postcode'];
        $request['Destination_Postcode'] = $order->shipping_postcode;
        $request['Country'] = $order->shipping_country;
        $request['Service_Type'] = $this->settings['service'];

        // default country to australia
        if (empty($request['Country'])) {
            $request['Country'] = 'au';
        }
        // default postcode to sending locally
        if (empty($request['Destination_Postcode'])) {
            $request['Destination_Postcode'] = $request['Pickup_Postcode'];
        }

        // protect against extreme girth
        $max_height = static::MAX_GIRTH / 4;
        if ($request['Height'] > $max_height) $request['Height'] = $max_height;

        $max_width = (static::MAX_GIRTH / 2) - $request['Height'];
        if ($request['Width'] > $max_width) $request['Width'] = $max_width;

        // if we have exceeded length or weight restriction, need to split into multiple packages
        $request['Quantity'] = ceil(max($request['Weight'] / static::MAX_WEIGHT, $request['Length'] / static::MAX_LENGTH));
        if ($request['Quantity'] > 1)
        {
            // we still must ensure parcels don't go below minimums
            $request['Length'] = max(static::MIN_DIMENSION, round($request['Length'] / $request['Quantity']));
            $request['Weight'] = max(static::MIN_WEIGHT, round($request['Weight'] / $request['Quantity']));
        }

        return $request;
    }

    public function send($data)
    {
        $url = $this->endpoint.'?'.http_build_query($data);

        return ee()->store->cached_http->get($url)->send();
    }

    /**
     * Convert the response from Australia Post into an associative array
     */
    public function decode_response($response)
    {
        $lines = explode("\n", $response);
        $out = array();

        foreach ($lines as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) == 2) {
                $out[trim($parts[0])] = trim($parts[1]);
            }
        }

        foreach (array('charge', 'days', 'err_msg') as $key) {
            if ( ! isset($out[$key])) $out[$key] = null;
        }

        return $out;
    }
}
