<?php
error_reporting(E_ERROR | E_PARSE);
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Dashboard\ComputerRoom;
use Dashboard\funcs;
use Dashboard\Utils;

require_once($_SERVER["DOCUMENT_ROOT"] . "/api/basicAuth.php");

//require_once($_SERVER["DOCUMENT_ROOT"] . "/dashboard/src/ComputerRoom.php");
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/src/Leasing.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/src/MySQLConnection.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/vendor/autoload.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/src/Lenel.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/src/MSSQLConnection.php');

$config = [
    'settings' => [
        'displayErrorDetails' => $_SERVER["ENVIRONMENT"] != "prod"
    ]
];

$app = new \Slim\App($config);

// start security group, limited to XXX IT, XXX Security
$app->group('/security', function () use ($app) {
    $this->get('/activity', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $customer = $getParam['customer'] ?? '';
        $datacenter = $getParam['datacenter'] ?? '';
        $start_date = $getParam['startDate'] ?? '';
        $end_date = $getParam['endDate'] ?? '';
        $room = $getParam['room'] ?? '';
        if ($room == "ALL") {
            $room = "";
        }
        $activity = $lenel->getActivity($customer, $datacenter, $room, $start_date, $end_date);
        $retval =$response->withJson(['activity' => $activity]); 
        return $retval;
    });

    $this->get('/activityTest', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $customer = $getParam['customer'] ?? '';
        $datacenter = $getParam['datacenter'] ?? '';
        $start_date = $getParam['startDate'] ?? '';
        $end_date = $getParam['endDate'] ?? '';
        $room = $getParam['room'] ?? '';
        $activity = $lenel->getActivityTest($customer, $datacenter, $room, $start_date, $end_date);
        return $response->withJson(['activity' => $activity]);
    });

    $this->get('/accessList', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $customer = $getParam['customer'] ?? '';
        $datacenter = $getParam['datacenter'] ?? '';
        $room = $getParam['room'] ?? '';
        $accessList = $lenel->getAccessList($customer, $datacenter, $room);
        return $response->withJson(['accessList' => $accessList]);
    });

    $this->get('/accessListByReader', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $customer = $getParam['customer'] ?? '';
        $datacenter = $getParam['datacenter'] ?? '';
        $room = $getParam['room'] ?? '';
        $panelID = $getParam['panel_id'] ?? '';
        $readerID = $getParam['reader_id'] ?? '';
        $raw = $getParam['raw'] ?? '';
        $accessList = $lenel->getAccessListByReader($customer, $datacenter, $room, $panelID, $readerID, $raw);
        return $response->withJson(['accessList' => $accessList]);
    });

    $this->get('/accessListByEmployee', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $customer = $getParam['customer'] ?? '';
        $datacenter = $getParam['datacenter'] ?? '';
        $room = $getParam['room'] ?? '';
        $accessList = $lenel->getAccessListByEmployee($customer, $datacenter, $room);
        return $response->withJson(['accessList' => $accessList]);
    });

    $this->get('/accessListByDatacenter', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $customer = $getParam['customer'] ?? '';
        $datacenter = $getParam['datacenter'] ?? '';
        $accessList = $lenel->getAccessListByDatacenter($customer, $datacenter);
        return $response->withJson(['accessList' => $accessList]);
    });

    $this->get('/accessListByRoom', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $datacenter = $getParam['datacenter'] ?? '';
        $room = $getParam['room'] ?? '';
        $accessList = $lenel->getAccessListByRoom($datacenter, $room);
        return $response->withJson(['accessList' => $accessList]);
    });

    $this->get('/departments', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $departments = $lenel->fetchAll('DEPT');

        return $response->withJson(['departments' => $departments]);
    });

    $this->get('/departments/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $id = $args['id'] ?? '';
        $lenel = new Lenel();
        $department = $lenel->fetchRecord('DEPT', $id);

        return $response->withJson(['department' => $department]);
    });

    $this->get('/cardholders', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $cardholders = $lenel->fetchAll('EMP');

        return $response->withJson(['cardholders' => $cardholders]);
    });

    $this->get('/cardholders/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $id = $args['id'] ?? '';
        $lenel = new Lenel();
        $cardholder = $lenel->fetchRecord('EMP', $id);

        return $response->withJson(['cardholder' => $cardholder]);
    });

    $this->get('/readers', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $getParam = $request->getQueryParams();
        $params = [
            'PANELID' => $getParam['panelID'] ?? '',
            'READERID' => $getParam['readerID'] ?? ''
        ];

        // code style regarding inline ifs?
        // $readers = empty($getParam) ? $lenel->fetchAll('READER') : $lenel->fetchAll('READER', $params);

        if(empty($getParam))
            $readers = $lenel->fetchAll('READER');
        else
            $readers = $lenel->fetchAll('READER', $params);

        return $response->withJson(['readers' => $readers]);
    });

    $this->get('/accessLevels', function (Request $request, Response $response) {
        $lenel = new Lenel();
        $accessLevels = $lenel->fetchAll('ACCESSLVL');

        return $response->withJson(['access levels' => $accessLevels]);
    });

    $this->get('/accessLevels/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $id = $args['id'] ?? '';
        $lenel = new Lenel();
        $accessLevel = $lenel->fetchAccessLevel($id);

        return $response->withJson(['access level' => $accessLevel]);
    });

    $this->get('/accessLevels/{id:[0-9]+}/readers', function (Request $request, Response $response, $args) {
        $id = $args['id'] ?? '';
        $lenel = new Lenel();
        $readers = $lenel->fetchAccessLevelReaders($id);

        return $response->withJson(['readers' => $readers]);
    });

    $this->get('/accessLevels/{id:[0-9]+}/cardholders', function (Request $request, Response $response, $args) {
        $id = $args['id'] ?? '';
        $lenel = new Lenel();
        $cardholders = $lenel->getCardholderByAccessLevel($id);

        return $response->withJson(['cardholders' => $cardholders]);
    });

    $this->get('/accessLevels/empty', function (Request $request, Response $response, $args) {
        $lenel = new Lenel();
        $accessLevels = $lenel->getEmptyAccessLevel();

        return $response->withJson(['access levels' => $accessLevels]);
    });
})->add(function ($request, $response, $next) {
    $requestPath = "/" . $request->getUri()->getPath();
    $perm = hasPermission($requestPath);
    if (!$perm) {
        error_log("Permission denied API, missing " . getRequestAuthorizationCategory($requestPath));
        return $response->withStatus(401)->withJson( [ ] );
    }
    $response = $next($request, $response);
    return $response;
});
// end security group

// start leases group, not limited to XXX employees
$app->group('/leases', function () use ($app) {
    $app->group('/datacenters', function () use ($app) {
        $leasing = new Leasing();

        $this->get('', function (Request $request, Response $response) use ($leasing) {
            $datacenters = $leasing->fetchAll('datacenters'); //limit by Customer

            return $response->withJson(['datacenters' => $datacenters]);
        });
    });
});

// start leasing group, limited to XXX employees
$app->group('/leasing', function () use ($app) {
    $app->group('/datacenters', function () use ($app) {
        $leasing = new Leasing();

        $this->get('', function (Request $request, Response $response) use ($leasing) {
            $datacenters = $leasing->fetchAll('datacenters');

            return $response->withJson(['datacenters' => $datacenters]);
        });

        $this->get('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $datacenter = $leasing->fetchRecord('datacenters', $id);

            return $response->withJson(['datacenter' => $datacenter]);
        });

        $this->get('/{id:[0-9]+}/rooms', function (Request $request, Response $response, $args) use ($leasing) {
            $dc = $args['id'] ?? '';
            $rooms = $leasing->fetchRoomsByDatacenterID($dc);

            return $response->withJson(['rooms' => $rooms]);
        });

        $this->post('', function (Request $request, Response $response, $args) use ($leasing) {
            $getParam = $request->getQueryParams();
            $fields = [
                'commonName' => $getParam['commonName'] ?? '',
                'businessName' => $getParam['businessName'] ?? '',
                'street' => $getParam['street'] ?? '',
                'city' => $getParam['city'] ?? '',
                'state' => $getParam['state'] ?? '',
                'postalCode' => $getParam['postalCode'] ?? '',
                'country' => $getParam['country'] ?? ''
            ];

            $leasing->addRecord('datacenters', $fields);
            $datacenter = $leasing->fetchLatestRecord('datacenters');

            return $response->withJson(['datacenter' => $datacenter], 201);
        });

        $this->put('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $getParam = $request->getQueryParams();
            $id = $args['id'] ?? '';

            $fields = [
                'commonName' => $getParam['commonName'] ?? '',
                'businessName' => $getParam['businessName'] ?? '',
                'street' => $getParam['street'] ?? '',
                'city' => $getParam['city'] ?? '',
                'state' => $getParam['state'] ?? '',
                'postalCode' => $getParam['postalCode'] ?? '',
                'country' => $getParam['country'] ?? ''
            ];

            $leasing->updateRecord('datacenters', $id, $fields);
            $datacenter = $leasing->fetchRecord('datacenters', $id);

            return $response->withJson(['datacenter' => $datacenter]);
        });

        $this->delete('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $leasing->deleteRecord('datacenters', $id);

            return $response->withJson(['Datacenter deleted']);
        });
    });


    $app->get('/datacenters/{name}', function (Request $request, Response $response, $args) {
        $leasing = new Leasing();
        $name = $args['name'] ?? '';
        $datacenter = $leasing->fetchDatacenter($name);

        return $response->withJson(['datacenter' => $datacenter]);
    });

    $app->get('/datacenters/{name}/rooms', function (Request $request, Response $response, $args) {
        $leasing = new Leasing();
        $dc = $args['name'] ?? '';
        $rooms = $leasing->fetchRoomsByDatacenter($dc);

        return $response->withJson(['rooms' => $rooms]);
    });

    $app->group('/customers', function () {
        $leasing = new Leasing();

        $this->get('', function (Request $request, Response $response) use ($leasing) {
            $customers = $leasing->fetchCustomers();
            return $response->withJson(['customers' => $customers]);
        });

        $this->get('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $customer = $leasing->fetchRecord('customers', $id);

            return $response->withJson(['customer' => $customer]);
        });

        $this->get('/{id:[0-9]+}/datacenters', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $datacenters = $leasing->fetchDatacentersByCustomer($id);

            return $response->withJson(['datacenters' => $datacenters]);
        });

        $this->get('/{id:[0-9]+}/datacenters/{datacenter}/rooms', function (Request $request, Response $response, $args) use ($leasing) {
            $customerID = $args['id'] ?? '';
            $datacenterID = $args['datacenter'] ?? '';
            $datacenterRooms = $leasing->fetchRoomsByCustomerAndDatacenter($customerID, $datacenterID);

            return $response->withJson(['rooms' => $datacenterRooms]);
        });

    // Should customerID and/or customerName be another parameter in a common /readers route?
        $this->get('/{id:[0-9]+}/readers', function (Request $request, Response $response, $args) use ($leasing) {
            $customerID = $args['id'] ?? '';
            $getParam = $request->getQueryParams();
            $params = [
                'datacenterID' => $getParam['datacenterID'] ?? '',
                'datacenterName' => $getParam['datacenterName'] ?? '',
                'roomID' => $getParam['roomID'] ?? '',
                'roomName' => $getParam['roomName'] ?? '',
            ];
            $readers = $leasing->fetchReadersByCustomerID($customerID, $params);

            return $response->withJson(['Readers' => $readers]);
        });

        $this->get('/{id:[0-9]+}/datacenters/{datacenter}/rooms/{room}/readers', function (Request $request, Response $response, $args) use ($leasing) {
            $customerID = $args['id'];
            $datacenterID = $args['datacenter'];
            $roomID = $args['room'];
            $readers = $leasing->fetchReadersByCustomerDatacenterRoom($customerID, $datacenterID, $roomID);

            return $response->withJson(['Readers' => $readers]);
        });

        $this->get('/{id:[0-9]+}/rooms', function (Request $request, Response $response, $args) use ($leasing) {
            $customerID = $args['id'] ?? '';
            $getParam = $request->getQueryParams();
            $params = [
                'datacenterID' => $getParam['datacenterID'] ?? '',
                'datacenterName' => $getParam['datacenterName'] ?? '',
                'roomID' => $getParam['roomID'] ?? '',
                'roomName' => $getParam['roomName'] ?? '',
            ];
            $rooms = $leasing->fetchRoomsByCustomerID($customerID, $params);

            return $response->withJson(['rooms' => $rooms]);
        });

        $this->post('', function (Request $request, Response $response, $args) use ($leasing) {
            $getParam = $request->getQueryParams();
            $fields = [
                'name' => $getParam['name'] ?? '',
                'domain' => $getParam['domain'] ?? ''
            ];

            $leasing->addRecord('customers', $fields);

            $customer = $leasing->fetchLatestRecord('customers');

            return $response->withJson(['customer' => $customer], 201);
        });

        $this->put('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $getParam = $request->getQueryParams();
            $id = $args['id'] ?? '';
            $fields = [
                'name' => $getParam['name'] ?? '',
                'domain' => $getParam['domain'] ?? ''
            ];

            $leasing->updateRecord('customers', $id, $fields);
            $customer = $leasing->fetchRecord('customers', $id);

            return $response->withJson(['customer' => $customer]);
        });

        $this->delete('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $leasing->deleteRecord('customers', $id);

            return $response->withJson(['Customer deleted']);
        });
    });

    $app->get('/customers/{name}', function (Request $request, Response $response, $args) {
        $leasing = new Leasing();
        $name = $args['name'] ?? '';
        $customer = $leasing->fetchCustomer($name);

        return $response->withJson(['customer' => $customer]);
    });

    $app->group('/rooms', function () {
        $leasing = new Leasing();

        $this->get('', function (Request $request, Response $response) use ($leasing) {
            $getParam = $request->getQueryParams();
            $type = $getParam['type'] ?? '';
            $rooms = $leasing->fetchRooms($type);

            return $response->withJson(['rooms' => $rooms]);
        });

        $this->get('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $room = $leasing->fetchRoom($id);

            return $response->withJson(['room' => $room]);
        });

        $this->get('/{id:[0-9]+}/leases', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $leases = $leasing->fetchLeasesByRoom($id);

            return $response->withJson(['leases' => $leases])
                            ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $this->get('/{id:[0-9]+}/readers', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $readers = $leasing->fetchReadersByRoom($id);

            return $response->withJson(['readers' => $readers]);
        });

        $this->post('/{id:[0-9]+}/readers', function (Request $request, Response $response, $args) use ($leasing) {
            $getParam = $request->getQueryParams();
            $fields = [
                'room_id' => $args['id'] ?? '',
                'panel_id' => $getParam['panelID'] ?? '',
                'reader_id' => $getParam['readerID'] ?? ''
            ];
            $leasing->addRecord('rooms_readers', $fields);

            return $response->withJson(['added reader']);
        });

    //  Need to think how update is possible given there is no unique ID per record. Should we use sync instead to delete all and add?
        $this->put('/{id:[0-9]+}/readers', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $getParam = $request->getQueryParams();
            $panelID = $getParam['panelID'] ?? '';
            $readerID = $getParam['readerID'] ?? '';
            $updatedReader = $leasing->updateReaderInRoom($id, $panelID, $readerID);

            return $response->withJson([
                'updated reader' => $updatedReader
            ]);
        });

        $this->delete('/{id:[0-9]+}/readers', function (Request $request, Response $response, $args) use ($leasing) {
            $roomID = $args['id'] ?? '';
            $getParam = $request->getQueryParams();
            $panelID = $getParam['panelID'] ?? '';
            $readerID = $getParam['readerID'] ?? '';
            $leasing->deleteReaderFromRoom($roomID, $panelID, $readerID);

            return $response->withJson(['deleted reader']);
        });
    });

    $app->group('/leases', function () {
        $leasing = new Leasing();

        $this->get('', function (Request $request, Response $response) use ($leasing) {
            $getParam = $request->getQueryParams();
            $params = [
                'customerID' => $getParam['customerID'] ?? '',
                'customerName' => $getParam['customerName'] ?? '',
                'datacenterID' => $getParam['datacenterID'] ?? '',
                'datacenterName' => $getParam['datacenterName'] ?? '',
                'roomID' => $getParam['roomID'] ?? '',
                'roomName' => $getParam['roomName'] ?? ''
            ];
            $leases = $leasing->fetchLeases($params);

            return $response->withJson(['leases' => $leases])
                            ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $this->get('/{id:[0-9]+}', function (Request $request, Response $response, $args) use ($leasing) {
            $id = $args['id'] ?? '';
            $lease = $leasing->fetchRecord('leases', $id);

            return $response->withJson(['lease' => $lease]);
        });
    });

    // possible prototype for all future endpoints
    $app->get('/readers', function (Request $request, Response $response, $args) {
        $leasing = new Leasing();
        $getParam = $request->getQueryParams();
        $params = [
            'customerID' => $getParam['customerID'] ?? '',
            'customerName' => $getParam['customerName'] ?? '',
            'datacenterID' => $getParam['datacenterID'] ?? '',
            'datacenterName' => $getParam['datacenterName'] ?? '',
            'roomID' => $getParam['roomID'] ?? '',
            'roomName' => $getParam['roomName'] ?? ''
        ];
        $readers = $leasing->fetchReaders($params);

        return $response->withJson(['Readers' => $readers]);
    });
})->add(function ($request, $response, $next) {
    $perm = ($_SESSION["company"] == "XXXXXX" || $_SESSION["company"] == "XXXXXY");
    if (!$perm) {
        error_log("Permission denied API, company != XXXXXX");
        return $response->withStatus(401)->withJson( [ ] );
    }
    $response = $next($request, $response);
    return $response;
}); // end leasing group

$app->group('/dashboard/v1', function () use ($app) {
    $app->group('/realtime/flatline', function () {
        $this->get('/electrical', function (Request $request, Response $response, $arg){
            $leasing = new Leasing();
            $active_rooms = $leasing->getActiveDatacenters();

            $flatlines = [];
            foreach ($active_rooms as $active_room)
	        {
            	$datacenter = $active_room["datacenter"];
            	$room = $active_room["room"];
            	if(!empty($dc) && $datacenter == $dc) {
                    $computerRoom = new ComputerRoom($datacenter, substr($room, 3));
                    $flatlines[$datacenter][$room] = $computerRoom->getElectricalFlatlines();
            	}
            	elseif(empty($dc)) {
                    $computerRoom = new ComputerRoom($datacenter, substr($room, 3));
                    $flatlines[$datacenter][$room] = $computerRoom->getElectricalFlatlines();
            	}
	        }
	        return $response->withJson($flatlines);
        });

        $this->get('/mechanical', function (Request $request, Response $response, $arg){
            $leasing = new Leasing();
            $active_rooms = $leasing->getActiveDatacenters();

            $flatlines = [];
	        foreach ($active_rooms as $active_room)
            {
                $datacenter = $active_room["datacenter"];
                $room = $active_room["room"];
                if(!empty($dc) && $datacenter == $dc) {
                    $computerRoom = new ComputerRoom($datacenter, substr($room, 3));
                    $flatlines[$datacenter][$room] = $computerRoom->getMechanicalFlatlines();
                }
                elseif(empty($dc)) {
                    $computerRoom = new ComputerRoom($datacenter, substr($room, 3));
                    $flatlines[$datacenter][$room] = $computerRoom->getMechanicalFlatlines();
                }
            }
            return $response->withJson($flatlines);        
	});
    })->add(function ($request, $response, $next) {
        if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
            error_log("Permission denied API, not local user: " . $_SERVER['REMOTE_ADDR']);
            return $response->withStatus(401)->withJson( [ ] );
        }
        $response = $next($request, $response);
        return $response;
    });

    $this->get('/realtime/{datacenter}/{room}', function(Request $request, Response $response, $args) {
        $dc = $args['datacenter'] ?? "";
        $cr = $args['room'] ?? "";
        $computerRoom = new ComputerRoom($dc, $cr);
        $getParam = $request->getQueryParams();
        $type = $getParam['type'] ?? '';
        $format = !empty($getParam['format']) && $getParam['format'] == 'xml' ? 'xml' : 'json';
        $retval = array();
        if ($type == '' || $type == 'electrical') {
            $retval['electrical'] = $computerRoom->prepareElectricalData();
        }
        if ($type == '' || $type == 'mechanical') {
            $retval['mechanical'] = $computerRoom->prepareMechanicalData();
        }
        if ($format == 'xml') {
            $renderer = new RKA\ContentTypeRenderer\Renderer();
            $renderer->setXmlRootElementName("xml");
            $request = $request->withHeader('Accept', 'application/xml');
            return $renderer->render($request, $response, $retval);
        }
        return $response->withJson($retval);
    });

});

$app->run();
