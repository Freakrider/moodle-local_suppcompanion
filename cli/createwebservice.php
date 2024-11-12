<?php

define('CLI_SCRIPT', 1);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir  . '/clilib.php');
require_once($CFG->dirroot . '/lib/testing/generator/data_generator.php');
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

// Set the variables for the new webservice.
$wsname = 'Support Companion';
$wsshortname = 'support_companion';
// Format wsname to lowercase and remove spaces.
$wsname_formatted = strtolower(str_replace(' ', '', $wsname));

$additionalcapabilities = [
    "moodle/user:viewdetails",
    "moodle/user:viewhiddendetails",
    "moodle/course:useremail",
    "moodle/user:update",
    "moodle/course:create",
    "mod/quiz:addinstance",
    "mod/quiz:manage",
    // "webservice/restful:use",
];

// Set system context.
$systemcontext = context_system::instance();

// Set admin user.
$USER = get_admin();

// Enable web services and REST protocol.
set_config('enablewebservices', true);
$enabledprotocols = get_config('core', 'webserviceprotocols');
if (stripos($enabledprotocols, 'rest') === false) {
    set_config('webserviceprotocols', $enabledprotocols . ',restful');
}

// Create a web service user.
// Check if the web service user already exists.
$existinguser = $DB->get_record('user', ['username' => 'ws-' . $wsname_formatted . '-user']);

if (!$existinguser) {
    // Create a web service user if it does not exist.
    $datagenerator = new testing_data_generator();
    $webserviceuser = $datagenerator->create_user([
        'username' => 'ws-' . $wsname_formatted . '-user',
        'firstname' => 'Webservice',
        'lastname' => 'User (' . $wsname . ')',
        'policyagreed' => 1,
        'email' => 'ws-' . $wsname_formatted . '-user@example.com' // Ensure a unique email
    ]);
    cli_writeln("User 'ws-" . $wsname_formatted . "-user' created.");
} else {
    $webserviceuser = $existinguser;
    cli_writeln("User 'ws-" . $wsname_formatted . "-user' already exists, using existing user.");
}

// Check if the web service role already exists.
$existingrole = $DB->get_record('role', ['shortname' => 'ws-' . $wsname_formatted . '-role']);

if (!$existingrole) {
    // Create a web service role based on the manager archetype if it does not exist.
    $wsroleid = create_role('WS Role for ' . $wsname, 'ws-' . $wsname_formatted . '-role', '', 'manager');
    set_role_contextlevels($wsroleid, [CONTEXT_SYSTEM, CONTEXT_COURSE, CONTEXT_MODULE, CONTEXT_BLOCK, CONTEXT_USER]);

    // Assign the additional restful capability.
    assign_capability('webservice/restful:use', CAP_ALLOW, $wsroleid, $systemcontext->id, true);

    // Assign any other additional capabilities needed.
    foreach ($additionalcapabilities as $cap) {
        assign_capability($cap, CAP_ALLOW, $wsroleid, $systemcontext->id, true);
    }

    cli_writeln("Role 'WS Role for $wsname' created.");
} else {
    $wsroleid = $existingrole->id;
    cli_writeln("Role 'WS Role for $wsname' already exists, using existing role.");
}
// Assign the role to the user.
role_assign($wsroleid, $webserviceuser->id, $systemcontext->id);

// Initialize the webservice manager and activate the specified service.
$webservicemanager = new webservice();
$service = $webservicemanager->get_external_service_by_shortname($wsshortname);

// Check if service exists, if not, create it.
if (!$service) {
    $service = new stdClass();
    $service->name = $wsname;
    $service->enabled = 1;
    $service->restrictedusers = 1;
    $service->component = 'local_suppcompanion';
    $service->id = $webservicemanager->add_external_service($service);
    cli_writeln("Service $wsname created with ID $service->id.");

    // Authorize the user to use the service immediately after creation.
    $webservicemanager->add_ws_authorised_user((object) [
        'externalserviceid' => $service->id,
        'userid' => $webserviceuser->id
    ]);
} else {
    cli_writeln("Service $wsname exists with ID $service->id.");
        // Authorize the user to use the service immediately after creation.
        $webservicemanager->add_ws_authorised_user((object) [
            'externalserviceid' => $service->id,
            'userid' => $webserviceuser->id
        ]);
}

// Authorize the user to use the service.
$webservicemanager->add_ws_authorised_user((object) [
    'externalserviceid' => $service->id,
    'userid' => $webserviceuser->id
]);

// Generate a token for the user.
$token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service->id, $webserviceuser->id, $systemcontext);
cli_writeln("Token for $wsname created: $token.");

// Update the service to ensure it's active.
$service = $webservicemanager->get_external_service_by_id($service->id);
$webservicemanager->update_external_service($service);
