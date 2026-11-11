<?php
/**
 * Automated Test Suite for Alumni Portal API & Router
 * Istanbul University MIS
 */

class ApiTester {
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void {
        echo "========================================================\n";
        echo "  Starting Istanbul University MIS API Endpoint Tests   \n";
        echo "========================================================\n\n";

        // Test 1: GET /auto
        $res = $this->simulateRequest('GET', '/auto');
        $this->assert("GET /auto returns 'ok'", $res['body'] === 'ok', "Expected 'ok', got: '{$res['body']}'");
        $this->assert("GET /auto status is 200", $res['status'] === 200);

        // Test 2: GET /auto/
        $res = $this->simulateRequest('GET', '/auto/');
        $this->assert("GET /auto/ returns 'ok'", $res['body'] === 'ok', "Expected 'ok', got: '{$res['body']}'");

        // Test 3: GET /auto/hello
        $res = $this->simulateRequest('GET', '/auto/hello');
        $this->assert("GET /auto/hello returns 'Hello, World!'", $res['body'] === 'Hello, World!', "Expected 'Hello, World!', got: '{$res['body']}'");

        // Test 4: GET /auto/hello/{name} (e.g. senol)
        $res = $this->simulateRequest('GET', '/auto/hello/senol');
        $this->assert("GET /auto/hello/senol returns 'Hello, senol!'", $res['body'] === 'Hello, senol!', "Expected 'Hello, senol!', got: '{$res['body']}'");

        // Test 5: GET /auto/hello/istanbul
        $res = $this->simulateRequest('GET', '/auto/hello/istanbul');
        $this->assert("GET /auto/hello/istanbul returns 'Hello, istanbul!'", $res['body'] === 'Hello, istanbul!');

        // Test 6: GET /auto/sum/{number1}/{number2} with integers (10, 5 -> 15)
        $res = $this->simulateRequest('GET', '/auto/sum/10/5');
        $json = json_decode($res['body'], true);
        $this->assert("GET /auto/sum/10/5 returns JSON with sum = 15", isset($json['sum']) && $json['sum'] === 15, "Expected sum: 15, got: " . json_encode($json));

        // Test 7: GET /auto/sum/{number1}/{number2} with floats (12.5, 2.5 -> 15)
        $res = $this->simulateRequest('GET', '/auto/sum/12.5/2.5');
        $json = json_decode($res['body'], true);
        $this->assert("GET /auto/sum/12.5/2.5 returns sum = 15", isset($json['sum']) && $json['sum'] == 15);

        // Test 8: GET /auto/sum/invalid/5 validation
        $res = $this->simulateRequest('GET', '/auto/sum/invalid/5');
        $json = json_decode($res['body'], true);
        $this->assert("GET /auto/sum/invalid/5 returns 400 validation error", $res['status'] === 400 && isset($json['error']));

        // Test 9: GET /auto/main renders main.html
        $res = $this->simulateRequest('GET', '/auto/main');
        $this->assert("GET /auto/main renders main HTML view", strpos($res['body'], 'Faculty of Economics') !== false);

        // Test 10: GET /auto/about renders about.html
        $res = $this->simulateRequest('GET', '/auto/about');
        $this->assert("GET /auto/about renders about HTML view", strpos($res['body'], 'About Our Department') !== false);

        // Test 11: GET / (root landing page)
        $res = $this->simulateRequest('GET', '/');
        $this->assert("GET / renders main HTML view", strpos($res['body'], 'Faculty of Economics') !== false);

        // Test 12: GET /about (root about page)
        $res = $this->simulateRequest('GET', '/about');
        $this->assert("GET /about renders about HTML view", strpos($res['body'], 'About Our Department') !== false);

        // Test 13: GET /alumni returns active status and data
        $res = $this->simulateRequest('GET', '/alumni');
        $json = json_decode($res['body'], true);
        $this->assert("GET /alumni returns valid JSON with status success", isset($json['status']) && $json['status'] === 'success');
        $this->assert("GET /alumni provides records array", isset($json['data']) && is_array($json['data']));

        // Test 14: POST /auto skeleton with JSON body
        $postData = json_encode(['action' => 'test_submission', 'user' => 'senol']);
        $res = $this->simulateRequest('POST', '/auto', $postData, ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /auto receives and responds with payload", isset($json['status']) && $json['status'] === 'success' && ($json['received_payload']['action'] ?? '') === 'test_submission');

        // Test 15: In-Memory User Model direct CRUD validation
        require_once __DIR__ . '/../models/User.php';
        User::reset();
        $initialCount = User::count();
        $this->assert("User Model initializes with seeded records", $initialCount === 5, "Expected 5, got: {$initialCount}");

        $created = User::create([
            'email' => 'unit.test@istanbul.edu.tr',
            'first_name' => 'Unit',
            'last_name' => 'Test',
            'role' => 'student',
            'is_verified' => 1
        ]);
        $this->assert("User Model creates record with auto-increment ID", $created['id'] === 6 && $created['email'] === 'unit.test@istanbul.edu.tr');

        $found = User::findById(6);
        $this->assert("User Model finds record by ID", $found !== null && $found['first_name'] === 'Unit');

        $updated = User::update(6, ['first_name' => 'UpdatedUnit']);
        $this->assert("User Model updates existing record", $updated !== null && $updated['first_name'] === 'UpdatedUnit');

        $deleted = User::delete(6);
        $this->assert("User Model deletes record", $deleted && User::findById(6) === null);
        User::reset();

        // Test 16: Web: GET /users renders UserController view
        $res = $this->simulateRequest('GET', '/users');
        $this->assert("GET /users renders HTML user directory view", strpos($res['body'], 'User Management') !== false && strpos($res['body'], 'Registered Users') !== false);

        // Test 17: Web: GET /users/create renders create form
        $res = $this->simulateRequest('GET', '/users/create');
        $this->assert("GET /users/create renders HTML create form modal", strpos($res['body'], 'Create New User') !== false);

        // Test 18: Web: POST /users creates user from form data
        $formBody = http_build_query([
            'first_name'  => 'WebCan',
            'last_name'   => 'Aydin',
            'email'       => 'web.can@example.com',
            'role'        => 'alumni',
            'is_verified' => '1'
        ]);
        $res = $this->simulateRequest('POST', '/users', $formBody, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $this->assert("POST /users creates user and renders success notice", strpos($res['body'], 'created successfully') !== false);

        // Test 19: Web: POST /users/{id}/delete removes user
        $res = $this->simulateRequest('POST', '/users/3/delete');
        $this->assert("POST /users/{id}/delete removes user with notice", strpos($res['body'], 'successfully deleted') !== false);

        // Test 20: API: GET /api/users returns JSON user list
        $res = $this->simulateRequest('GET', '/api/users');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/users returns JSON array from ApiUserController", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 21: API: GET /api/users/1 returns single user
        $res = $this->simulateRequest('GET', '/api/users/1');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/users/1 returns user object with ID 1", isset($json['data']['id']) && (int)$json['data']['id'] === 1);

        // Test 22: API: POST /api/users creates user via JSON
        $newApiUser = json_encode([
            'first_name'  => 'ApiHakan',
            'last_name'   => 'Yildiz',
            'email'       => 'hakan.yildiz@istanbul.edu.tr',
            'role'        => 'alumni',
            'is_verified' => 1
        ]);
        $res = $this->simulateRequest('POST', '/api/users', $newApiUser, ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/users creates user and returns 201 status", isset($json['status']) && $json['status'] === 'success' && ($json['data']['first_name'] ?? '') === 'ApiHakan');

        // Test 23: API: PUT /api/users/{id} updates user via JSON
        $updatePayload = json_encode(['first_name' => 'HakanUpdated']);
        $res = $this->simulateRequest('PUT', '/api/users/1', $updatePayload, ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("PUT /api/users/1 updates user via ApiUserController", isset($json['status']) && $json['status'] === 'success' && ($json['data']['first_name'] ?? '') === 'HakanUpdated');

        // Test 24: API: DELETE /api/users/{id} deletes user via JSON
        $res = $this->simulateRequest('DELETE', '/api/users/4');
        $json = json_decode($res['body'], true);
        $this->assert("DELETE /api/users/4 removes user with JSON success response", isset($json['status']) && $json['status'] === 'success');

        // Test 25: API: GET /api/users/999 returns 404 for non-existent user
        $res = $this->simulateRequest('GET', '/api/users/999');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/users/999 returns 404 error response", isset($json['status']) && $json['status'] === 'error');

        // Test 26: Web: GET /users/1 renders user profile view via UserController
        $res = $this->simulateRequest('GET', '/users/1');
        $this->assert("GET /users/1 renders user profile via UserController", strpos($res['body'], 'User Profile:') !== false || strpos($res['body'], 'Admin') !== false);

        // Test 27: Web: POST /users/1/update updates user via form submission
        $updateFormBody = http_build_query([
            'first_name'  => 'AdminModified',
            'last_name'   => 'MIS',
            'email'       => 'admin@istanbul.edu.tr',
            'role'        => 'super_admin',
            'is_verified' => '1'
        ]);
        $res = $this->simulateRequest('POST', '/users/1/update', $updateFormBody, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $this->assert("POST /users/1/update updates user via UserController", strpos($res['body'], 'updated successfully') !== false);

        // Test 28: Swagger UI: GET /swagger renders interactive UI
        $res = $this->simulateRequest('GET', '/swagger');
        $this->assert("GET /swagger renders Swagger UI documentation interface", strpos($res['body'], 'SwaggerUIBundle') !== false && strpos($res['body'], '/openapi.json') !== false);

        // Test 29: OpenAPI JSON: GET /openapi.json delivers valid OpenAPI 3.0.3 specification
        $res = $this->simulateRequest('GET', '/openapi.json');
        $spec = json_decode($res['body'], true);
        $this->assert("GET /openapi.json returns valid JSON specification", $spec !== null && ($spec['openapi'] ?? '') === '3.0.3');

        // Test 30: OpenAPI Verification: Check Web endpoints documented
        $hasWebList   = isset($spec['paths']['/users']['get']);
        $hasWebCreate = isset($spec['paths']['/users']['post']);
        $hasWebForm   = isset($spec['paths']['/users/create']['get']);
        $hasWebShow   = isset($spec['paths']['/users/{id}']['get']);
        $hasWebUpdate = isset($spec['paths']['/users/{id}/update']['post']);
        $hasWebDelete = isset($spec['paths']['/users/{id}/delete']['post']);
        $this->assert("OpenAPI spec documents all /users web endpoints", $hasWebList && $hasWebCreate && $hasWebForm && $hasWebShow && $hasWebUpdate && $hasWebDelete);

        // Test 31: OpenAPI Verification: Check REST API endpoints & schemas documented
        $hasApiList   = isset($spec['paths']['/api/users']['get']);
        $hasApiCreate = isset($spec['paths']['/api/users']['post']);
        $hasApiShow   = isset($spec['paths']['/api/users/{id}']['get']);
        $hasApiUpdate = isset($spec['paths']['/api/users/{id}']['put']);
        $hasApiDelete = isset($spec['paths']['/api/users/{id}']['delete']);
        $hasSchemas   = isset($spec['components']['schemas']['User']) && isset($spec['components']['schemas']['UserCreateInput']) && isset($spec['components']['schemas']['UserUpdateInput']);
        $this->assert("OpenAPI spec documents all /api/users CRUD endpoints & schemas", $hasApiList && $hasApiCreate && $hasApiShow && $hasApiUpdate && $hasApiDelete && $hasSchemas);

        // Test 32: OpenAPI YAML: GET /openapi.yaml returns YAML specification
        $res = $this->simulateRequest('GET', '/openapi.yaml');
        $this->assert("GET /openapi.yaml delivers OpenAPI YAML specification", strpos($res['body'], 'openapi: 3.0.3') !== false && strpos($res['body'], '/api/users:') !== false);

        // Test 33: Dedicated Routes Layer: Router class loads routes/web.php
        require_once __DIR__ . '/../routes/Router.php';
        $testRouter = new Router();
        $webLoader = require __DIR__ . '/../routes/web.php';
        $this->assert("routes/web.php returns callable route loader", is_callable($webLoader));
        $webLoader($testRouter);
        $routes = $testRouter->getRoutes();
        $this->assert("Router contains registered web routes", count($routes) >= 2);

        // Test 34: Dedicated Routes Layer: GET /users maps to UserController::index
        $matchGet = $testRouter->match('GET', '/users');
        $this->assert("Router matches 'GET /users' route", $matchGet !== null);
        $this->assert("Route 'GET /users' targets UserController::index", $matchGet !== null && $matchGet['handler'] === ['UserController', 'index']);

        // Test 35: Dedicated Routes Layer: POST /users maps to UserController::store
        $matchPost = $testRouter->match('POST', '/users');
        $this->assert("Router matches 'POST /users' route", $matchPost !== null);
        $this->assert("Route 'POST /users' targets UserController::store", $matchPost !== null && $matchPost['handler'] === ['UserController', 'store']);

        // Test 36: Dedicated Routes Layer: Front Controller executes GET /users via routes layer
        $res = $this->simulateRequest('GET', '/users');
        $this->assert("Front Controller dispatches GET /users via routes layer", strpos($res['body'], 'User Management') !== false && strpos($res['body'], 'stat-total') !== false);

        // Test 37: Dedicated Routes Layer: Front Controller executes POST /users via routes layer
        $postPayload = http_build_query([
            'first_name'  => 'RouterTest',
            'last_name'   => 'Graduate',
            'email'       => 'router.test@alumni.iu.edu.tr',
            'role'        => 'alumni',
            'is_verified' => '1'
        ]);
        $res = $this->simulateRequest('POST', '/users', $postPayload, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $this->assert("Front Controller dispatches POST /users via routes layer to create user", strpos($res['body'], 'created successfully') !== false && strpos($res['body'], 'RouterTest') !== false);

        // Test 38: Career Services: JobPosting Model CRUD & relationships
        require_once __DIR__ . '/../models/JobPosting.php';
        JobPosting::reset();
        $initialJobCount = JobPosting::count();
        $this->assert("JobPosting Model initializes with seeded opportunities", $initialJobCount >= 3);
        $newJob = JobPosting::create([
            'posted_by_user_id' => 1,
            'company_name'      => 'Test Corp',
            'company_id'        => 999,
            'title'             => 'Test Engineer',
            'description'       => 'Test description for unit testing.',
            'employment_type'   => 'full_time',
            'location'          => 'Istanbul, Turkey',
            'experience_level'  => 'entry'
        ]);
        $this->assert("JobPosting Model creates record with auto-increment ID", $newJob['id'] > 0 && $newJob['title'] === 'Test Engineer');
        $jobUser = JobPosting::user($newJob['id']);
        $this->assert("JobPosting belongsTo User relationship resolves correctly", $jobUser !== null && (int)$jobUser['id'] === 1);

        // Test 39: Application Tracking: Application Model CRUD & relationships
        require_once __DIR__ . '/../models/Application.php';
        Application::reset();
        $initialAppCount = Application::count();
        $this->assert("Application Model initializes with seeded dossiers", $initialAppCount >= 2);
        $newApp = Application::create([
            'job_posting_id'  => 1,
            'user_id'         => 3,
            'applicant_name'  => 'Test Candidate',
            'applicant_email' => 'test.candidate@ogr.iu.edu.tr',
            'status'          => 'submitted'
        ]);
        $this->assert("Application Model creates application dossier", $newApp['id'] > 0 && $newApp['applicant_name'] === 'Test Candidate');
        $appJob = Application::jobPosting($newApp['id']);
        $this->assert("Application belongsTo JobPosting relationship resolves correctly", $appJob !== null && (int)$appJob['id'] === 1);
        $appUser = Application::user($newApp['id']);
        $this->assert("Application belongsTo User relationship resolves correctly", $appUser !== null && (int)$appUser['id'] === 3);

        // Test 40: Web: GET /job-postings renders Career Hub view
        $res = $this->simulateRequest('GET', '/job-postings');
        $this->assert("GET /job-postings renders Career Hub view", strpos($res['body'], 'Career Hub & Job Postings') !== false && strpos($res['body'], 'stat-total') !== false);

        // Test 41: Web: GET /job-postings/create renders creation modal
        $res = $this->simulateRequest('GET', '/job-postings/create');
        $this->assert("GET /job-postings/create renders opportunity creation modal", strpos($res['body'], 'Post a New Opportunity') !== false);

        // Test 42: Web: POST /job-postings creates opportunity via form
        $jobForm = http_build_query([
            'company_name'    => 'IU Tech Park',
            'company_id'      => '555',
            'title'           => 'Web Developer Fellow',
            'employment_type' => 'full_time',
            'experience_level'=> 'entry',
            'location'        => 'Istanbul, Turkey',
            'description'     => 'Work on faculty systems.'
        ]);
        $res = $this->simulateRequest('POST', '/job-postings', $jobForm, ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $this->assert("POST /job-postings creates opportunity and renders success notice", strpos($res['body'], 'created successfully') !== false);

        // Test 43: Web: GET /job-postings/1 renders single job view
        $res = $this->simulateRequest('GET', '/job-postings/1');
        $this->assert("GET /job-postings/1 renders job details", strpos($res['body'], 'Trendyol Group') !== false || strpos($res['body'], 'Cloud Data Engineer') !== false);

        // Test 44: Web: GET /applications renders applications hub
        $res = $this->simulateRequest('GET', '/applications');
        $this->assert("GET /applications renders Applications Hub view", strpos($res['body'], 'Candidate Applications Hub') !== false);

        // Test 45: API: GET /api/job-postings returns JSON opportunities
        $res = $this->simulateRequest('GET', '/api/job-postings');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/job-postings returns JSON array", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 46: API: GET /api/job-postings/1 returns single job JSON
        $res = $this->simulateRequest('GET', '/api/job-postings/1');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/job-postings/1 returns job object", isset($json['data']['id']) && (int)$json['data']['id'] === 1);

        // Test 47: API: POST /api/job-postings creates job via JSON
        $newApiJob = json_encode([
            'posted_by_user_id' => 1,
            'company_name'      => 'Turkcell Technology',
            'company_id'        => 105,
            'title'             => 'BI Analyst Graduate',
            'description'       => 'Business intelligence data visualization and SQL analytics.',
            'employment_type'   => 'full_time',
            'location'          => 'Istanbul, Turkey',
            'experience_level'  => 'entry'
        ]);
        $res = $this->simulateRequest('POST', '/api/job-postings', $newApiJob, ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/job-postings creates job and returns 201 status", isset($json['status']) && $json['status'] === 'success' && ($json['data']['title'] ?? '') === 'BI Analyst Graduate');

        // Test 48: API: PUT /api/job-postings/1 updates job via JSON
        $updateJobPayload = json_encode(['title' => 'Senior Cloud Data Platform Engineer']);
        $res = $this->simulateRequest('PUT', '/api/job-postings/1', $updateJobPayload, ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("PUT /api/job-postings/1 updates job via API", isset($json['status']) && $json['status'] === 'success' && ($json['data']['title'] ?? '') === 'Senior Cloud Data Platform Engineer');

        // Test 49: Nested API: GET /api/companies/101/job-postings
        $res = $this->simulateRequest('GET', '/api/companies/101/job-postings');
        $json = json_decode($res['body'], true);
        $this->assert("Nested GET /api/companies/101/job-postings returns company listings", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 50: Nested API: GET /api/users/1/job-postings
        $res = $this->simulateRequest('GET', '/api/users/1/job-postings');
        $json = json_decode($res['body'], true);
        $this->assert("Nested GET /api/users/1/job-postings returns user posted jobs", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 51: API: GET /api/applications returns JSON applications
        $res = $this->simulateRequest('GET', '/api/applications');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/applications returns JSON array", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 52: API: GET /api/applications/1 returns single application
        $res = $this->simulateRequest('GET', '/api/applications/1');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/applications/1 returns application object", isset($json['data']['id']) && (int)$json['data']['id'] === 1);

        // Test 53: API: POST /api/applications submits application via JSON
        $newApiApp = json_encode([
            'job_posting_id'  => 2,
            'user_id'         => 3,
            'applicant_name'  => 'API Candidate',
            'applicant_email' => 'api.candidate@istanbul.edu.tr',
            'cover_letter'    => 'Strong experience in business systems analysis.'
        ]);
        $res = $this->simulateRequest('POST', '/api/applications', $newApiApp, ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/applications submits application and returns 201 status", isset($json['status']) && $json['status'] === 'success' && ($json['data']['applicant_name'] ?? '') === 'API Candidate');

        // Test 54: Nested API: GET /api/job-postings/1/applications
        $res = $this->simulateRequest('GET', '/api/job-postings/1/applications');
        $json = json_decode($res['body'], true);
        $this->assert("Nested GET /api/job-postings/1/applications returns applications for job", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 55: Nested API: GET /api/users/4/applications
        $res = $this->simulateRequest('GET', '/api/users/4/applications');
        $json = json_decode($res['body'], true);
        $this->assert("Nested GET /api/users/4/applications returns user application history", isset($json['status']) && $json['status'] === 'success' && is_array($json['data']));

        // Test 56: Error Handling: Undefined Web Route (GET /invalid-endpoint) returns 404 Not Found view
        $res = $this->simulateRequest('GET', '/invalid-endpoint');
        $this->assert("GET /invalid-endpoint returns 404 status", $res['status'] === 404, "Expected status 404, got: {$res['status']}");
        $this->assert("GET /invalid-endpoint returns 404 Not Found JSON error with path", strpos($res['body'], '404 Not Found') !== false && strpos($res['body'], '/invalid-endpoint') !== false);
        $this->assert("GET /invalid-endpoint does not fall back to home view", strpos($res['body'], 'Career Opportunities & Academic Directory') === false);

        // Test 57: Error Handling: Undefined API Route (GET /api/invalid-endpoint) returns 404 JSON error
        $res = $this->simulateRequest('GET', '/api/invalid-endpoint');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/invalid-endpoint returns 404 status", $res['status'] === 404);
        $this->assert("GET /api/invalid-endpoint returns JSON error response", isset($json['status']) && $json['status'] === 'error' && isset($json['error']) && $json['error'] === '404 Not Found');

        // Test 58: Error Handling: Undefined POST Route (POST /invalid-endpoint) returns 404 Not Found
        $res = $this->simulateRequest('POST', '/invalid-endpoint', json_encode(['foo' => 'bar']), ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /invalid-endpoint returns 404 status instead of 200 skeleton", $res['status'] === 404);
        $this->assert("POST /invalid-endpoint contains 404 error message", isset($json['status']) && $json['status'] === 'error');

        // Test 59: Route Matching Validation: Router methods hasRoute() and isDefinedRoute()
        $this->assert("Router::hasRoute('GET', '/users') validates existing route", $testRouter->hasRoute('GET', '/users'));
        $this->assert("Router::hasRoute('GET', '/invalid-endpoint') rejects invalid route", !$testRouter->hasRoute('GET', '/invalid-endpoint'));
        $this->assert("Router::isDefinedRoute('/invalid-endpoint') confirms path is undefined", !$testRouter->isDefinedRoute('/invalid-endpoint'));

        // Test 60: Static Asset Validation: Valid static file (GET /docs/openapi.json) returns 200 OK
        $res = $this->simulateRequest('GET', '/docs/openapi.json');
        $json = json_decode($res['body'], true);
        $this->assert("GET /docs/openapi.json returns 200 and valid JSON specification", $res['status'] === 200 && isset($json['openapi']));

        // Test 61: Static Asset Validation: Missing asset (GET /assets/missing.css) returns 404 Not Found
        $res = $this->simulateRequest('GET', '/assets/missing.css');
        $this->assert("GET /assets/missing.css returns 404 status and error view", $res['status'] === 404 && strpos($res['body'], '404 Not Found') !== false);

        // Test 62: Method Validation: POST to GET-only route (/users/create) returns 405 Method Not Allowed
        $res = $this->simulateRequest('POST', '/users/create');
        $this->assert("POST /users/create returns 405 Method Not Allowed", $res['status'] === 405 && strpos($res['body'], '405 Method Not Allowed') !== false);

        // Test 63: Request Validation: POST /api/login missing Content-Type returns 400 Bad Request
        $res = $this->simulateRequest('POST', '/api/login', json_encode(['email' => 'admin@istanbul.edu.tr', 'password' => 'AdminMis2026!']));
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login missing Content-Type returns 400 status", $res['status'] === 400);
        $this->assert("POST /api/login missing Content-Type schema matches 400 Bad Request", isset($json['status']) && $json['status'] === 'error' && ($json['error'] ?? '') === '400 Bad Request');

        // Test 64: Request Validation: POST /api/login with malformed JSON body returns 400 Bad Request
        $res = $this->simulateRequest('POST', '/api/login', '{malformed_json: missing_quotes}', ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login with malformed JSON returns 400 status", $res['status'] === 400 && ($json['error'] ?? '') === '400 Bad Request');

        // Test 65: Input Validation: POST /api/login with missing password returns 422 Unprocessable Entity
        $res = $this->simulateRequest('POST', '/api/login', json_encode(['email' => 'admin@istanbul.edu.tr']), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login with missing fields returns 422 status", $res['status'] === 422);
        $this->assert("POST /api/login with missing fields matches 422 Unprocessable Entity schema", isset($json['status']) && $json['status'] === 'error' && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 66: Input Validation: POST /api/login with invalid email syntax returns 422 Unprocessable Entity
        $res = $this->simulateRequest('POST', '/api/login', json_encode(['email' => 'not-an-email', 'password' => 'secret123']), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login with invalid email returns 422 status", $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 67: Input Validation: POST /api/login with short password (< 6 chars) returns 422 Unprocessable Entity
        $res = $this->simulateRequest('POST', '/api/login', json_encode(['email' => 'admin@istanbul.edu.tr', 'password' => '123']), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login with short password returns 422 status", $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 68: Authentication: POST /api/login with non-existent user returns 401 Unauthorized
        $res = $this->simulateRequest('POST', '/api/login', json_encode(['email' => 'unknown@example.com', 'password' => 'password123']), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login with invalid credentials returns 401 status", $res['status'] === 401 && ($json['error'] ?? '') === '401 Unauthorized');

        // Test 69: Authentication: POST /api/login with valid credentials returns 200 OK and Bearer token
        $res = $this->simulateRequest('POST', '/api/login', json_encode(['email' => 'admin@istanbul.edu.tr', 'password' => 'AdminMis2026!']), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/login succeeds with 200 status", $res['status'] === 200 && ($json['status'] ?? '') === 'success');
        $this->assert("POST /api/login issues Bearer token", isset($json['token']) && !empty($json['token']) && ($json['token_type'] ?? '') === 'Bearer');
        $loginToken = $json['token'] ?? '';

        // Test 70: Input Validation: POST /api/register missing required fields returns 422 Unprocessable Entity
        $res = $this->simulateRequest('POST', '/api/register', json_encode(['email' => 'new.user@alumni.iu.edu.tr']), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/register with missing fields returns 422 status", $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 71: Input Validation: POST /api/register with invalid email syntax returns 422 Unprocessable Entity
        $res = $this->simulateRequest('POST', '/api/register', json_encode([
            'email' => 'invalid-email-format',
            'password' => 'secure123',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/register with invalid email returns 422", $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 72: Input Validation: POST /api/register with short password returns 422 Unprocessable Entity
        $res = $this->simulateRequest('POST', '/api/register', json_encode([
            'email' => 'shortpass@example.com',
            'password' => '12',
            'first_name' => 'Short',
            'last_name' => 'Pass'
        ]), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/register with short password returns 422", $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 73: Registration: POST /api/register creates user and returns 201 Created with token
        $res = $this->simulateRequest('POST', '/api/register', json_encode([
            'email' => 'postman.tester@alumni.iu.edu.tr',
            'password' => 'Secret2026!',
            'first_name' => 'Postman',
            'last_name' => 'Tester',
            'role' => 'alumni'
        ]), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/register returns 201 Created", $res['status'] === 201 && ($json['status'] ?? '') === 'success');
        $this->assert("POST /api/register returns valid token", isset($json['token']) && !empty($json['token']));

        // Test 74: Registration: POST /api/register duplicate email returns 422 Unprocessable Entity
        // Uses a seeded email (always present in default storage) to avoid cross-process state dependency
        $res = $this->simulateRequest('POST', '/api/register', json_encode([
            'email' => 'admin@istanbul.edu.tr',
            'password' => 'AdminMis2026!',
            'first_name' => 'Admin',
            'last_name' => 'Duplicate'
        ]), ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert("POST /api/register duplicate email returns 422 Unprocessable Entity", $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity');

        // Test 75: Auth Middleware: GET /api/profile without token returns 401 Unauthorized JSON
        $res = $this->simulateRequest('GET', '/api/profile');
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/profile without token returns 401 status", $res['status'] === 401);
        $this->assert("GET /api/profile without token matches 401 Unauthorized schema", isset($json['status']) && $json['status'] === 'error' && ($json['error'] ?? '') === '401 Unauthorized');

        // Test 76: Auth Middleware: GET /api/profile with invalid token returns 401 Unauthorized JSON
        $res = $this->simulateRequest('GET', '/api/profile', '', ['HTTP_AUTHORIZATION' => 'Bearer completely_bogus_token']);
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/profile with invalid token returns 401", $res['status'] === 401 && ($json['error'] ?? '') === '401 Unauthorized');

        // Test 77: Auth Middleware: GET /api/profile with valid token returns 200 OK profile data
        $res = $this->simulateRequest('GET', '/api/profile', '', ['HTTP_AUTHORIZATION' => 'Bearer admin-token']);
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/profile with valid token returns 200 OK", $res['status'] === 200 && ($json['status'] ?? '') === 'success');
        $this->assert("GET /api/profile contains authenticated user profile", isset($json['data']['email']) && $json['data']['email'] === 'admin@istanbul.edu.tr');

        // Test 78: Authorization Middleware: GET /api/admin with non-admin token returns 403 Forbidden JSON
        $res = $this->simulateRequest('GET', '/api/admin', '', ['HTTP_AUTHORIZATION' => 'Bearer user-token']);
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/admin with student token returns 403 Forbidden status", $res['status'] === 403);
        $this->assert("GET /api/admin with student token matches 403 Forbidden schema", isset($json['status']) && $json['status'] === 'error' && ($json['error'] ?? '') === '403 Forbidden');

        // Test 79: Authorization Middleware: GET /api/admin with admin token returns 200 OK dashboard data
        $res = $this->simulateRequest('GET', '/api/admin', '', ['HTTP_AUTHORIZATION' => 'Bearer admin-token']);
        $json = json_decode($res['body'], true);
        $this->assert("GET /api/admin with admin token returns 200 OK status", $res['status'] === 200 && ($json['status'] ?? '') === 'success');
        $this->assert("GET /api/admin provides administrative dashboard data", isset($json['data']['users_count']) && ($json['data']['system_status'] ?? '') === 'operational');

        // --------------------------------------------------------
        // Tests 80–84: Input Validation & Controlled 500 Endpoint
        // --------------------------------------------------------

        // Test 80: Validation — PUT /api/job-postings/{id} with empty title returns 422
        $res = $this->simulateRequest('PUT', '/api/job-postings/1',
            json_encode(['title' => '', 'salary_range' => '₺50,000 / month']),
            ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert(
            "PUT /api/job-postings with empty title returns 422 Unprocessable Entity",
            $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity'
        );
        $this->assert(
            "PUT /api/job-postings with empty title includes 'title' in details",
            isset($json['details']['title'])
        );

        // Test 81: Validation — PUT /api/job-postings/{id} with invalid salary returns 422
        $res = $this->simulateRequest('PUT', '/api/job-postings/1',
            json_encode(['title' => 'Valid Title', 'salary' => -500]),
            ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert(
            "PUT /api/job-postings with negative salary returns 422 Unprocessable Entity",
            $res['status'] === 422 && ($json['error'] ?? '') === '422 Unprocessable Entity'
        );
        $this->assert(
            "PUT /api/job-postings with negative salary includes 'salary' in details",
            isset($json['details']['salary'])
        );

        // Test 82: Validation — PUT /api/job-postings/{id} with both invalid fields returns 422 with two detail entries
        $res = $this->simulateRequest('PUT', '/api/job-postings/1',
            json_encode(['title' => '', 'salary' => 0]),
            ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert(
            "PUT /api/job-postings with both invalid fields returns 422 and two detail entries",
            $res['status'] === 422
            && isset($json['details']['title'])
            && isset($json['details']['salary'])
        );

        // Test 83: Validation — PUT /api/job-postings/{id} with valid title and positive salary succeeds
        $res = $this->simulateRequest('PUT', '/api/job-postings/1',
            json_encode(['title' => 'Senior Data Engineer', 'salary' => 95000]),
            ['CONTENT_TYPE' => 'application/json']);
        $json = json_decode($res['body'], true);
        $this->assert(
            "PUT /api/job-postings with valid title and positive salary returns 200 OK",
            $res['status'] === 200 && ($json['status'] ?? '') === 'success'
        );

        // Test 84: Controlled 500 — GET /api/trigger-500 returns 500 JSON without stack trace
        $res = $this->simulateRequest('GET', '/api/trigger-500');
        $json = json_decode($res['body'], true);
        $this->assert(
            "GET /api/trigger-500 returns 500 Internal Server Error status",
            $res['status'] === 500
        );
        $this->assert(
            "GET /api/trigger-500 response matches standardized 500 JSON schema",
            is_array($json)
            && ($json['status']  ?? '') === 'error'
            && ($json['error']   ?? '') === '500 Internal Server Error'
            && isset($json['message'])
        );
        $this->assert(
            "GET /api/trigger-500 does not expose raw stack trace in response body",
            strpos($res['body'], '#0 ') === false
            && strpos($res['body'], 'Stack trace') === false
            && strpos($res['body'], 'Uncaught') === false
        );

        echo "\n--------------------------------------------------------\n";
        echo "Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(string $testName, bool $condition, string $detail = ''): void {
        if ($condition) {
            echo " [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$testName}" . ($detail ? " — {$detail}" : "") . "\n";
            $this->failed++;
        }
    }

    private function simulateRequest(string $method, string $uri, string $rawBody = '', array $headers = []): array {
        // Build CLI command executing index.php in a subshell with simulated server environment
        $indexScript = realpath(__DIR__ . '/../index.php');
        $escapedIndex = escapeshellarg($indexScript);

        $envSetup = [
            "register_shutdown_function(function() { echo PHP_EOL . '__HTTP_STATUS__:' . http_response_code(); });",
            "\$GLOBALS['_SERVER']['REQUEST_METHOD'] = " . var_export($method, true) . ";",
            "\$GLOBALS['_SERVER']['REQUEST_URI'] = " . var_export($uri, true) . ";",
            "\$GLOBALS['_SERVER']['SCRIPT_NAME'] = '/index.php';",
            "\$GLOBALS['_SERVER']['HTTP_HOST'] = 'localhost:8000';",
        ];

        foreach ($headers as $k => $v) {
            $envSetup[] = "\$GLOBALS['_SERVER'][" . var_export($k, true) . "] = " . var_export($v, true) . ";";
        }

        $bodyFile = '';
        if ($rawBody !== '') {
            $tempFile = tempnam(sys_get_temp_dir(), 'test_body_');
            file_put_contents($tempFile, $rawBody);
            $envSetup[] = "stream_wrapper_unregister('php');";
            $envSetup[] = "class TestPhpStream { public \$context; private \$handle; function stream_open(\$p, \$m, \$o, &\$opened_path) { \$this->handle = fopen(" . var_export($tempFile, true) . ", 'r'); return true; } function stream_read(\$c) { return fread(\$this->handle, \$c); } function stream_eof() { return feof(\$this->handle); } function stream_stat() { return fstat(\$this->handle); } }";
            $envSetup[] = "stream_wrapper_register('php', 'TestPhpStream');";
            $bodyFile = $tempFile;
        }

        $code = implode(' ', $envSetup) . " require " . var_export($indexScript, true) . ";";
        
        $descriptorSpec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open("php -r \"$code\"", $descriptorSpec, $pipes);
        
        $output = '';
        $stderr = '';
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $status = proc_close($process);
        } else {
            $status = -1;
        }

        if ($bodyFile !== '' && file_exists($bodyFile)) {
            unlink($bodyFile);
        }

        // Detect HTTP status code via shutdown function or output inspection
        $httpStatus = 200;
        if (strpos($output, '__HTTP_STATUS__:') !== false) {
            $parts = explode('__HTTP_STATUS__:', $output);
            $output = rtrim($parts[0]);
            $httpStatus = (int)trim($parts[1]);
        } elseif (strpos($output, 'Invalid parameters:') !== false || strpos($output, 'validation error') !== false) {
            $httpStatus = 400;
        } elseif (strpos($output, 'Route not found') !== false || strpos($output, '404 Not Found') !== false) {
            $httpStatus = 404;
        }

        return [
            'status' => $httpStatus,
            'body' => $output,
            'stderr' => $stderr
        ];
    }
}

$tester = new ApiTester();
$tester->run();
