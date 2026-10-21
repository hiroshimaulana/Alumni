<?php
/**
 * Istanbul University MIS Alumni Portal
 * Web Application Controller
 *
 * Handles web requests for job applications: viewing candidate submissions,
 * applying for opportunities, and managing review statuses through views/applications.html.
 */

require_once __DIR__ . '/../models/Application.php';
require_once __DIR__ . '/../models/JobPosting.php';
require_once __DIR__ . '/../models/User.php';

class ApplicationController {
    /**
     * Render the HTML view with data injection.
     */
    private function render(array $applications, array $context = []): void {
        $viewPath = __DIR__ . '/../views/applications.html';
        if (!file_exists($viewPath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "View not found: applications.html";
            exit;
        }

        $html = file_get_contents($viewPath);

        // Inject initial state
        $initialState = [
            'applications' => $applications,
            'context'      => $context,
            'stats'        => [
                'total'       => count($applications),
                'submitted'   => count(array_filter($applications, fn($a) => $a['status'] === 'submitted')),
                'review'      => count(array_filter($applications, fn($a) => $a['status'] === 'under_review')),
                'shortlisted' => count(array_filter($applications, fn($a) => $a['status'] === 'shortlisted')),
                'accepted'    => count(array_filter($applications, fn($a) => $a['status'] === 'accepted')),
            ]
        ];

        $jsonState = json_encode($initialState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $scriptTag = "<script id=\"app-initial-state\" type=\"application/json\">{$jsonState}</script>";

        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', "{$scriptTag}\n</head>", $html);
        } else {
            $html .= $scriptTag;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /**
     * Web: GET /applications
     */
    public function index(): void {
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }
        $this->render(Application::all($filters));
    }

    /**
     * Web: GET /applications/{id}
     */
    public function show(int $id): void {
        $app = Application::findById($id);
        if ($app === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Application not found.";
            exit;
        }

        $job = JobPosting::findById((int)$app['job_posting_id']);
        $this->render(Application::all(), [
            'selectedApp' => $app,
            'relatedJob'  => $job,
            'title'       => 'Application from ' . $app['applicant_name']
        ]);
    }

    /**
     * Web: POST /applications
     */
    public function store(array $postData = []): void {
        $data = !empty($postData) ? $postData : $_POST;

        try {
            $created = Application::create($data);
            $this->render(Application::all(), [
                'notice' => [
                    'type'    => 'success',
                    'message' => "Application submitted successfully for candidate '{$created['applicant_name']}'!"
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(Application::all(), [
                'notice' => [
                    'type'    => 'error',
                    'message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Web: POST /job-postings/{id}/applications (Apply directly to a job)
     */
    public function apply(int $id, array $postData = []): void {
        $data = !empty($postData) ? $postData : $_POST;
        $data['job_posting_id'] = $id;

        try {
            $created = Application::create($data);
            $this->render(Application::all(), [
                'notice' => [
                    'type'    => 'success',
                    'message' => "Your application for job #{$id} has been received!"
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(Application::all(), [
                'notice' => [
                    'type'    => 'error',
                    'message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Web: GET /job-postings/{id}/applications
     */
    public function byJobPosting(int $id): void {
        $job = JobPosting::findById($id);
        $apps = Application::findByJobPosting($id);

        $this->render($apps, [
            'jobFilter' => $id,
            'jobTitle'  => $job ? $job['title'] : "Job #{$id}",
            'title'     => 'Applications for ' . ($job ? $job['title'] : "Job #{$id}")
        ]);
    }

    /**
     * Web: GET /users/{id}/applications
     */
    public function byUser(int $id): void {
        $apps = Application::findByUser($id);
        $this->render($apps, [
            'userFilter' => $id,
            'title'      => "Applications submitted by User #{$id}"
        ]);
    }

    /**
     * Web: POST /applications/{id}/status
     */
    public function updateStatus(int $id, array $postData = []): void {
        $data = !empty($postData) ? $postData : $_POST;
        $status = $data['status'] ?? 'under_review';
        $feedback = $data['feedback'] ?? null;

        try {
            $updated = Application::updateStatus($id, $status, $feedback);
            if ($updated === null) {
                http_response_code(404);
                echo "Application not found.";
                exit;
            }
            $this->render(Application::all(), [
                'notice' => [
                    'type'    => 'success',
                    'message' => "Application #{$id} status transitioned to '{$status}'."
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(Application::all(), [
                'notice' => [
                    'type'    => 'error',
                    'message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Web: POST /applications/{id}/delete
     */
    public function delete(int $id): void {
        $deleted = Application::delete($id);
        $this->render(Application::all(), [
            'notice' => [
                'type'    => $deleted ? 'success' : 'error',
                'message' => $deleted ? "Application #{$id} withdrawn successfully." : "Application not found."
            ]
        ]);
    }
}
