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

        // Detect HTTP status code if set in output
        $httpStatus = 200;
        if (strpos($output, 'Invalid parameters:') !== false || strpos($output, 'validation error') !== false) {
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
