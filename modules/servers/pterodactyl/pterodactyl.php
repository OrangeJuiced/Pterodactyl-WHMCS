<?php
/**
  Pterodactyl WHMCS Module

    The MIT License (MIT)

    Copyright (c) 2016 Emmet Young

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
 **/

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

//Define the current version of this module
$MODULE_VERSION = "1.0";

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Makes a formatted API call to Pterodactyl utilizing CURL.
 *
 * Values returned here are the result of the API called to the
 * pterodactyl panel.
 *
 * @return array
 */
function pterodactyl_api_call($publickey, $privatekey, $url, $type, array $data = NULL )
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
    curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($curl, CURLOPT_USERAGENT, "Pterodactyl WHMCS Plugin/". $MODULE_VERSION);

    if(isset($data))
    {
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    $hmac = hash_hmac('sha256', $url . $data, $privatekey, true);

    $headers = array("Authorization: Bearer " . $publickey . '.' . base64_encode($hmac),
                     "Content-Type: application/json; charset=utf-8");

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $curldata = curl_exec($curl);

    if($curldata == false)
    {
        logModuleCall(
            'pterodactylWHMCS',
            'CURL ERROR',
            curl_error($curl),
            ''
        );
    }

    $responsedata = json_decode($curldata, true);
    $responsedata['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    logModuleCall(
        'pterodactylWHMCS',
        $url,
        isset($data) ? print_r($data, true) : '',
        print_r($responsedata, true)
    );

    return $responsedata;
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Provisioning_Module_Meta_Data_Parameters
 *
 * @return array
 */
function pterodactyl_MetaData()
{
    return array(
        'DisplayName' => 'Pterodactyl',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
    );
}

function getServices()
{
  //Until we find a different way we're going hardcoded
  $response = pterodactyl_api_call('GLHdlRp0J6WzPSvB', 'sTPPOWugbflg2v51.Fk8Dv9i.WpjW2K2', 'https://vulcan.kaashosting.be/api/admin/services', 'GET');

  //All available services
  $services = [];

  foreach ($response['data'] as $key => $value) {
    $services[$value['id']] = $value['attributes']['name'];
  }

  return $services;
}

function getLocations()
{
  //Until we find a different way we're going hardcoded
  $response = pterodactyl_api_call('GLHdlRp0J6WzPSvB', 'sTPPOWugbflg2v51.Fk8Dv9i.WpjW2K2', 'https://vulcan.kaashosting.be/api/admin/locations', 'GET');

  $locations = [];

  foreach ($response['data'] as $key => $value) {
    $locations[$value['id']] = $value['attributes']['long'];
  }

  return $locations;
}

/**
 * Define product configuration options.
 *
 * @return array
 */
function pterodactyl_ConfigOptions(array $params)
{
    $services = getServices();
    $locations = getLocations();

    $items = array(
        'memory' => array(
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1024',
            'Description' => 'Total memory (in MB) to assign to the server',
        ),
        'swap' => array(
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0',
            'Description' => 'Total swap (in MB) to assign to the server',
        ),
        'cpu' => array(
            'Type' => 'text',
            'Size' => '10',
            'Default' => '100',
            'Description' => 'Cpu limit, value is as a percentage of each core. One core being 100%.',
        ),
        'io' => array(
            'Type' => 'text',
            'Size' => '10',
            'Default' => '500',
            'Description' => 'Block IO adjustment number.',
        ),
        'disk' => array(
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1024',
            'Description' => 'Total disk space (in MB) to assign to the server.',
        ),
        'location' => array(
            "FriendlyName" => "Available locations",
            'Type' => 'dropdown',
            'Options' => $locations,
            'Default' => '1',
            'Description' => 'ID of location in which server should be created.',
        ),
        'services' => array(
            "FriendlyName" => "Available services",
            'Type' => 'dropdown',
            'Options' => $services,
            'Default' => '1',
            'Description' => 'ID of the service this server is using.',
        ),
        'option' => array(
            'Type' => 'text',
            'Size' => '10',
            'Default' => '1',
            'Description' => 'ID of the specific service option this server is using.',
        ),
        'startup' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'The startup parameters this server is using.',
        ),
        'auto_deploy' => array(
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Tick to enable auto deploy. You do not need to set the node and allocation if you do so.',
        ),
        'node' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'ID of the node to assign the server to (must be apart of the specified location id).',
        ),
        'allocation' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'The allocation ID to use for the server (only if not using auto_deploy, and not using ip and port).',
        ),
        'pack' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '0',
            'Description' => 'Pack ID number, leave as 0 if you dont want to utilize it.',
         ),
        'description' => array(
            'Type' => 'text',
            'Size' => '100',
            'Default' => '{{servicename}} server for user {{userid}}',
            'Description' => 'Description to be used for server creation.',
        )
    );

    return $items;
}

/**
 * Handle the creation of our user table.
 */
function create_user_table()
{
    if(!Capsule::schema()->hasTable('tbl_pterodactylproduct'))
    {
        Capsule::schema()->create(
            'tbl_pterodactylproduct',
            function ($table) {
                $table->increments('id');
                $table->integer('client_id');
                $table->integer('service_id');
                $table->integer('user_id');
                $table->integer('server_id');
            }
        );
    }
}

/**
 * Generate a random string.
 */
function generate_username()
{
    $returnable = false;
    while (!$returnable) {
        $generated = str_random(8);
        if (preg_match('/[A-Z]+[a-z]+[0-9]+/', $generated)) {
            $returnable = true;
        }
    }
    return $generated;
}

/**
 * Handle overiding of variables.
 */
function handle_overide(array $params, $overide_variable, $config_option, $data = NULL)
{
    if (isset($params['configoptions'][$overide_variable]) && $params['configoptions'][$overide_variable] != "")
        return $params['configoptions'][$overide_variable];
    else if (isset($params['customfields'][$overide_variable]) && $params['customfields'][$overide_variable] != "")
        return $params['customfields'][$overide_variable];
    else if (isset($params[$config_option]) && $params[$config_option] != "")
        return $params[$config_option];
    else
        return $data;
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function pterodactyl_CreateAccount(array $params)
{
    try {
        $newAccount = false;

        create_user_table();

        $searching = true;
	      $current_page = 1;
        while($searching)
        {
            $users = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/users?page=' . $current_page, 'GET');
            foreach($users['data'] as $user)
            {
                if ($user['attributes']['email'] !== $params['clientsdetails']['email'])
                    continue;

                $user_id = $user['id'];
                $searching = false;
                break;
            }

            if($current_page == $users['meta']['pagination']['total_pages'])
            {
                $searching = false;
            } else {
                $current_page++;
            }
        }

        if(!isset($user_id))
        {
            //Begin by creating the user on the panel side
            $data = array("email" => $params['clientsdetails']['email'],
                          "username" => $params['clientsdetails']['firstname'] . $params['clientsdetails']['lastname'] . "#" . generate_username(),
                          "name_first" => $params['clientsdetails']['firstname'],
                          "name_last" => $params['clientsdetails']['lastname'],
                          "root_admin" => false,
                          "password" => $params['password']
                         );

            $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/users', 'POST', $data);

            if($response['status_code'] != 200)
            {
                return "Error during create account: Response: ".$response['error'] + " Status Code: ".$response['status_code'];
            }

            $user_id = $response['data']['id'];
            $newAccount = true;
        }

        $server_name = generate_username();

        //Now get the panel to create a new server for our new user.
        $new_server = array("name" => $server_name."_".$params['serviceid'],
                            "user_id" => $user_id,
                            "auto_deploy" => $params['configoption10'] === 'on' ? true : false
                           );

        //Handle overiding of service ID, we need to handle this before grabbing the service
        $new_server['service_id'] = handle_overide($params, 'service_id', 'configoption7');

        $servicesresponse = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/services/'.$new_server['service_id'].'?include=options.variables', 'GET');

        //Get the startup command from the service option
        foreach ($servicesresponse['included'] as $key => $value) {
          if(($value['type'] == 'option') && ($value['id'] == $params['configoption8']))
          {
            $startup = $value['attributes']['startup'];
          }
        }

        $replaceableFields = array('{{servicename}}', '{{userid}}');
        $dataToReplaceWith = array($service['data']['attributes']['name'], $params['clientsdetails']['firstname']);

        $new_server['memory']      = handle_overide($params, 'memory',      'configoption1' );
        $new_server['swap']        = handle_overide($params, 'swap',        'configoption2' );
        $new_server['cpu']         = handle_overide($params, 'cpu',         'configoption3' );
        $new_server['io']          = handle_overide($params, 'io',          'configoption4' );
        $new_server['disk']        = handle_overide($params, 'disk',        'configoption5' );
        $new_server['pack_id']     = handle_overide($params, 'pack_id',     'configoption13');
        $new_server['location_id'] = handle_overide($params, 'location_id', 'configoption6' );
        $new_server['option_id']   = handle_overide($params, 'option_id',   'configoption8' );
        $new_server['startup']     = handle_overide($params, 'startup',     'configoption9', $startup);
        $new_server['description']  = str_replace($replaceableFields, $dataToReplaceWith, $params['configoption14']);

        //We need to loop through every option to handle environment variables for our specified option
        foreach($servicesresponse['included'] as $option)
        {

            if ($option['type'] !== 'variable')
				continue;

            if ($new_server['option_id'] == $option['attributes']['option_id'])
            {
                $new_server["env_".$option['attributes']['env_variable']] = handle_overide($params, $option['attributes']['env_variable'], NULL, $option['attributes']['default_value']);
            }
        }

        //If auto deploy is disabled, we need additional information
        if (!$new_server['auto_deploy'])
        {
            $new_server['node_id']       = handle_overide($params, 'node_id',       'configoption11');
            $new_server["allocation_id"] = handle_overide($params, 'allocation_id', 'configoption12');
        }

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers?include=allocations', 'POST', $new_server);

        if($response['status_code'] != 200)
        {
            return "Error during create server: Response Message: ".$response['error'] + " Status Code: ".$response['status_code'];
        }

        $server_id = $response['data']['id'];

        Capsule::table('tbl_pterodactylproduct')->insert(
                ['client_id' => $params['userid'],'service_id' => $params['serviceid'], 'user_id' => $user_id,'server_id' => $server_id]
        );

        //Grab the admin ID, makes it easier to make calls to the API
        $adminid = Capsule::table('tbladmins')->where('disabled',0)->where('roleid',1)->pluck('id');

        //Setup all the parameters we want to pass into the email
        $clientname = $params['clientsdetails']['fullname'];
        $panelurl = $params['serverhostname'];
        $clientemail = $params['clientsdetails']['email'];
        $clientpassword = $params['password'];

        $clientId['clientid'] = $params['userid'];
        $clientServiceId['serviceid'] = $params['serviceid'];

        //Call the WHMCS api to get client details, we need this to display the currency code
        $clientdetails = localAPI("getclientsdetails", $clientId, $adminid[0]);
        //Also call the WHMCS API to get the product details for the client
        $clientproducts = localAPI("getclientsproducts", $clientServiceId, $adminid[0]);

        $service_product_name = $clientproducts['products']['product'][0]['name'];
        $service_payment_method = $clientproducts['products']['product'][0]['paymentmethodname'];
        $service_billing_cycle = $clientproducts['products']['product'][0]['billingcycle'];
        $service_next_due_date  = $clientproducts['products']['product'][0]['nextduedate'] == "0000-00-00" ? "----" :  $clientproducts['products']['product'][0]['nextduedate'];
        $service_recurring_amount  = "$".$clientproducts['products']['product'][0]['recurringamount']." ".$clientdetails['currency_code'];

        //Format the email for sending
        $email["customtype"] = "product";
        $email["customsubject"] = "New Product Information";
        $email["custommessage"] = "<p>Dear $clientname,</p>
                                   <p>Your order for <b>$service_product_name</b> has now been activated. Please keep this message for your records.</p>
                                   <p><b>Product/Service:</b> $service_product_name <br />
                                   <b>Payment Method:</b> $service_payment_method <br />
                                   <b>Amount:</b> $service_recurring_amount <br />
                                   <b>Billing Cycle:</b> $service_billing_cycle <br />
                                   <b>Next Due Date:</b> $service_next_due_date <br /> <br />
                                   <b>Panel Login URL:</b> <a href='$panelurl'>$panelurl</a><br />
                                   <b>Panel Login Email:</b> $clientemail <br />";
        if ($newAccount)
        {
            $email["custommessage"] .= "<b>Panel Login Password:</b> $clientpassword <br />";
        }
        else
        {
            $email["custommessage"] .= "<b>Panel Login Password:</b> Use pre-existing password. <br /><br />";
        }

        foreach($response['included'] as $allocation)
        {
            $email["custommessage"] .= "<b>Server IP:</b> ".$allocation['attributes']['ip'].":".$allocation['attributes']['port']."<br />";
            if(isset($allocation['attributes']['ip_alias']))
            {
                $email["custommessage"] .= "<b>Server Alias:</b> ".$allocation['attributes']['ip_alias'].":".$allocation['attributes']['port']."<br />";
            }
        }

        $email["custommessage"] .= "</p>
                                     <p>Thank you for choosing us.</p>";

        $email["custommessage"] = preg_replace( "/\r|\n/", "", $email["custommessage"] );

        $email["id"] = $params['serviceid'];
        localAPI("sendemail", $email, $adminid[0]);

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Test connection with the given server parameters.
 *
 * @return array
 */
function pterodactyl_TestConnection(array $params)
{
    try {
        $success = true;
        $errorMsg = '';

        $nodes = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/nodes', 'GET', $data);

        if ($nodes['status_code'] != 200)
        {
            $success = false;
            $errorMsg = 'Failed to connect to server, ensure your API keys are correct and your panel is running on a valid SSL Certificate. Failed with HTTP Status Code: ' + $nodes['status_code'];
        }

        if ($nodes['meta']['pagination']['count'] <= 0)
        {
            $success = false;
            $errorMsg = 'No Nodes available, please setup a node before proceeding, otherwise this module will fail to work correctly.';
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        $success = false;
        $errorMsg = $e->getMessage();
    }
    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

function pterodactyl_get_client($serviceid)
{
   return Capsule::table('tbl_pterodactylproduct')
                                ->select('user_id', 'server_id')
                                ->where('service_id', '=', $serviceid)
                                ->first();
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function pterodactyl_SuspendAccount(array $params)
{
    try {
        $client = pterodactyl_get_client($params['serviceid']);

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$client->server_id.'/suspend?action=suspend', 'PATCH');

        if($response['status_code'] != 204)
        {
            return $response['error'] + " HTTP Status code: " + $response['status_code'] ;
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function pterodactyl_UnsuspendAccount(array $params)
{
    try {
        $client = pterodactyl_get_client($params['serviceid']);

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$client->server_id."/suspend?action=unsuspend", 'PATCH');

        if($response['status_code'] != 204)
        {
            return $response['error'] + " HTTP Status code: " + $response['status_code'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function pterodactyl_TerminateAccount(array $params)
{
    try {
        $client = pterodactyl_get_client($params['serviceid']);

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$client->server_id, 'DELETE');

        if($response['status_code'] != 204)
        {
            return $response['error'] + " HTTP Status code: " + $response['status_code'];
        }

        Capsule::table('tbl_pterodactylproduct')
                                ->where('service_id', '=', $client->service_id)
                                ->delete();

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Change the password for an instance of a product/service.
 *
 * Called when a password change is requested. This can occur either due to a
 * client requesting it via the client area or an admin requesting it from the
 * admin side.
 *
 * This option is only available to client end users when the product is in an
 * active status.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function pterodactyl_ChangePassword(array $params)
{
    try {
        $client = pterodactyl_get_client($params['serviceid']);

        $data = array("password" => $params['password'] );

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/users/'. $client->user_id, 'PUT', $data);

        if($response['status_code'] != 200)
        {
            return $response['error'] + " HTTP Status code: " + $response['status_code'];
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.
 *
 * This same function is called for upgrades and downgrades of both
 * products and configurable options.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */

function pterodactyl_ChangePackage(array $params)
{
    try {
        $client = pterodactyl_get_client($params['serviceid']);

        $data = array("memory" => $params['configoption1'],
                      "swap" => $params['configoption2'],
                      "cpu" => $params['configoption3'],
                      "io" => $params['configoption4'],
                      "disk" => $params['configoption5'],
                     );

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'. $client->server_id.'/build', 'PUT', $data);

        if($response['status_code'] != 200)
        {
            return $response['error'] + " HTTP Status code: " + $response['status_code'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.
 *
 * Supports an unlimited number of additional field labels and content of any
 * type to output.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 * @see pterodactyl_AdminServicesTabFieldsSave()
 *
 * @return array
 */
function pterodactyl_AdminServicesTabFields(array $params)
{
    try {
        $response = array();

        return array(
            'Memory' => $params['configoption1']."mb",
            'Swap'   => $params['configoption2']."mb",
            'CPU'    => $params['configoption3']."%",
            'IO'     => $params['configoption4'],
            'Disk'   => $params['configoption5']."mb",
            'Last Access Date' => date("Y-m-d H:i:s", $response['lastLoginTimestamp']),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, simply return no additional fields to display.
    }

    return array();
}


/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function pterodactyl_ClientArea(array $params)
{
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';

    $serviceAction = 'get_stats';
    $templateFile = 'templates/overview.tpl';

    try {
        $client = pterodactyl_get_client($params['serviceid']);

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'. $client->server_id . '?include=allocations', 'GET');

        $serverip['ip'] = '';
	      $serverip['ip_alias'] = '';

        foreach($response['included'] as $allocation)
        {

            $serverip['ip'][] .= $allocation['attributes']['ip'].":".$allocation['attributes']['port'] .", ";
            if(isset($allocation['attributes']['ip_alias']))
            {
                $serverip['ip_alias'] .= $allocation['attributes']['ip_alias'].":".$allocation['attributes']['port'] .", ";
            }
        }

        return array(
            'tabOverviewReplacementTemplate' => $templateFile,
            'templateVariables' => array(
                'panelhostname' => $params['serverhostname'],
                'memory' => $params['configoption1']."mb",
                'disk' => $params['configoption5']."mb",
                'email' => $params['clientsdetails']['email'],
                'server_ip' =>  $serverip['ip'],
                'server_alias' => $serverip['ip_alias'],
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'pterodactylWHMCS',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
    }
}
