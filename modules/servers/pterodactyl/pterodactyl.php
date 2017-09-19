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

// function getServices()
// {
//   //Until we find a different way we're going hardcoded
//   $response = pterodactyl_api_call('GLHdlRp0J6WzPSvB', 'sTPPOWugbflg2v51.Fk8Dv9i.WpjW2K2', 'https://vulcan.kaashosting.be/api/admin/services', 'GET');
//
//   //All available services
//   $services = [];
//
//   foreach ($response['data'] as $key => $value) {
//     $services[$value['id']] = $value['attributes']['name'];
//   }
//
//   return $services;
// }

// function getLocations()
// {
//   //Until we find a different way we're going hardcoded
//   $response = pterodactyl_api_call('GLHdlRp0J6WzPSvB', 'sTPPOWugbflg2v51.Fk8Dv9i.WpjW2K2', 'https://vulcan.kaashosting.be/api/admin/locations', 'GET');
//
//   $locations = [];
//
//   foreach ($response['data'] as $key => $value) {
//     $locations[$value['id']] = $value['attributes']['long'];
//   }
//
//   return $locations;
// }

/**
 * Define product configuration options.
 *
 * @return array
 */
function pterodactyl_ConfigOptions(array $params)
{
    // $services = getServices();
    // $locations = getLocations();

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
          'Default' => '256',
          'Description' => 'Total swap (in MB) to assign to the server',
      ),
      'cpu' => array(
          'Type' => 'text',
          'Size' => '10',
          'Default' => '50',
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
          'Type' => 'text',
          'Size' => '10',
          'Default' => '1',
          'Description' => 'ID of location in which server should be created.',
      ),
      'service' => array(
          'Type' => 'text',
          'Size' => '10',
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
          'Size' => '75',
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

        //Begin by creating the user on the panel side
        $data = array("email" => $params['clientsdetails']['email'],
                      "username" => str_replace(' ', '', ($params['clientsdetails']['firstname'] . $params['clientsdetails']['lastname'] . generate_username())),
                      "name_first" => $params['clientsdetails']['firstname'],
                      "name_last" => $params['clientsdetails']['lastname'],
                      "root_admin" => false,
                      "password" => $params['password'],
                      // TODO: Send the WHMCS id to the panel & build support for this into the panel
                      "custom_id" => $params['clientsdetails']['id']
                     );

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/users', 'POST', $data);

        if($response['status_code'] != 200)
        {
            $newAccount = false;

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

            //return "Error during create account: Response: ".$response['error'] + " Status Code: ".$response['status_code'];
        } else {
            $user_id = $response['data']['id'];
            $newAccount = true;
        }

        $server_name = generate_username();

        //Gather data for server creation
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
            if ($value['attributes']['startup'] == NULL)
            {
              $startup = $servicesresponse['data']['attributes']['startup'];
            } else {
              $startup = $value['attributes']['startup'];
            }
          }
        }

        $replaceableFields = array('{{servicename}}', '{{userid}}');
        $dataToReplaceWith = array($serviceresponse['data']['attributes']['name'], $params['clientsdetails']['firstname']);

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

        //Get the server ID from the response
        $server_id = $response['data']['id'];

        sleep(10);

        if ($new_server['service_id'] == 2) {
            $newalloc = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$server_id.'/allocation', 'POST');

            $data['startup'] = $new_server['startup'];
            $data['service_id'] = $new_server['service_id'];
            $data['option_id'] = $new_server['option_id'];
            $data['pack_id'] = $new_server['pack_id'];
            $data['env_46'] = $newalloc['port'];

            $responsealloc = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$server_id.'/startup', 'PUT', $data);
        }

        //Grab the IP from the response
        //If the IP has an alias we use that
        foreach($response['included'] as $allocation)
        {
            if(($new_server['service_id'] == 2) && isset($allocation['attributes']['ip_alias']))
            {
                $ip = $allocation['attributes']['ip_alias'] . ":" . $newalloc['port'];
            } elseif (isset($allocation['attributes']['ip_alias'])) {
                $ip = $allocation['attributes']['ip_alias'] . ":" . $allocation['attributes']['port'];
            } else {
                $ip = $allocation['attributes']['ip'] . ":" . $allocation['attributes']['port'];
            }
        }

        //Grab the admin ID, makes it easier to make calls to the API
        $adminid = Capsule::table('tbladmins')->where('disabled',0)->where('roleid',1)->pluck('id');

        //Update client product
        $postData = array(
            'serviceid' => $params['serviceid'],
            'domain' => $server_id,
            'dedicatedip' => $ip,
            'serviceusername' => $params['clientsdetails']['email']
        );

        $response = localAPI("UpdateClientProduct", $postData, $adminid[0]);

        //Vars for the email template to use
        $email = [];
        $email['panelurl'] = $params['serverhostname'];
        $email['loginemail'] = $params['clientsdetails']['email'];
        $email['id'] = $params['serviceid'];
        $email['ip'] = $ip;

        if ($newAccount)
        {
            $email['password'] = $params['password'];
        }
        else
        {
            $email['password'] = "Existing";
        }

        //Send the email
        $postData = array(
            'messagename' => 'Vulcan',
            'id' => $params['serviceid'],
            'customvars' => $email
        );

        $response = localAPI("sendemail", $postData, $adminid[0]);

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
        $server = $params['domain'];

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$server.'/suspend?action=suspend', 'PATCH');

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
        $server = $params['domain'];

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$server."/suspend?action=unsuspend", 'PATCH');

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
        $server = $params['domain'];

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'.$server, 'DELETE');

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
        $server = $params['domain'];

        $data = array("password" => $params['password'] );

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/users/'. $server, 'PUT', $data);

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
        $server = $params['domain'];

        $data = array("memory" => $params['configoption1'],
                      "swap" => $params['configoption2'],
                      "cpu" => $params['configoption3'],
                      "io" => $params['configoption4'],
                      "disk" => $params['configoption5'],
                     );

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'. $server.'/build', 'PUT', $data);

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
        $responsedata = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'. $params['domain'], 'GET');
        $response = array();

        return array(
            'Memory' => $params['configoption1']."mb",
            'Swap'   => $params['configoption2']."mb",
            'CPU'    => $params['configoption3']."%",
            'IO'     => $params['configoption4'],
            'Disk'   => $params['configoption5']."mb",
            "Server page" => "<a href='https://vulcan.kaashosting.nl/server/" . $responsedata['data']['attributes']['uuidShort'] . "' target='_blank'><button type='button' class='btn btn-default'>View</button></a>",
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
        $server = $params['domain'];

        $response = pterodactyl_api_call($params['serverusername'], $params['serverpassword'], $params['serverhostname'].'/api/admin/servers/'. $server . '?include=allocations', 'GET');

        $serverip['ip'] = '';
	      $serverip['ip_alias'] = '';

        return array(
            'tabOverviewReplacementTemplate' => $templateFile,
            'templateVariables' => array(
                'panelhostname' => $params['serverhostname'],
                'email' => $params['clientsdetails']['email'],
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
