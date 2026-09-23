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
