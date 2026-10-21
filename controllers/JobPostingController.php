<?php
/**
 * Istanbul University MIS Alumni Portal
 * Web Job Posting Controller
 *
 * Handles web requests for job postings: listing, creating, viewing,
 * editing, and deleting opportunities, rendered through views/job_postings.html.
 */

require_once __DIR__ . '/../models/JobPosting.php';
require_once __DIR__ . '/../models/User.php';

class JobPostingController {
    /**
     * Render the HTML view with data injection.
     */
    private function render(array $jobs, array $context = []): void {
        $viewPath = __DIR__ . '/../views/job_postings.html';
        if (!file_exists($viewPath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "View not found: job_postings.html";
            exit;
        }

        $html = file_get_contents($viewPath);

        // Inject dynamic JSON payload for frontend JavaScript reactivity
        $initialState = [
            'jobs'    => $jobs,
            'context' => $context,
            'stats'   => [
                'total'     => count($jobs),
                'active'    => count(array_filter($jobs, fn($j) => $j['status'] === 'active')),
                'full_time' => count(array_filter($jobs, fn($j) => $j['employment_type'] === 'full_time')),
                'intern'    => count(array_filter($jobs, fn($j) => $j['employment_type'] === 'internship')),
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
     * Web: GET /job-postings
     */
    public function index(): void {
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }
        if (!empty($_GET['employment_type'])) {
            $filters['employment_type'] = trim($_GET['employment_type']);
        }
        $this->render(JobPosting::all($filters));
    }

    /**
     * Web: GET /job-postings/create
     */
    public function create(): void {
        $this->render(JobPosting::all(), [
            'openCreate' => true,
            'title'      => 'Post New Opportunity'
        ]);
    }

    /**
     * Web: GET /job-postings/{id}
     */
    public function show(int $id): void {
        $job = JobPosting::findById($id);
        if ($job === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Job posting not found.";
            exit;
        }

        $this->render(JobPosting::all(), [
            'selectedJob' => $job,
            'title'       => $job['title'] . ' at ' . $job['company_name']
        ]);
    }

    /**
     * Web: POST /job-postings
     */
    public function store(array $postData = []): void {
        $data = !empty($postData) ? $postData : $_POST;

        try {
            $created = JobPosting::create($data);
            $this->render(JobPosting::all(), [
                'notice' => [
                    'type'    => 'success',
                    'message' => "Job posting '{$created['title']}' created successfully!"
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(JobPosting::all(), [
                'notice' => [
                    'type'    => 'error',
                    'message' => $e->getMessage()
                ],
                'openCreate' => true
            ]);
        }
    }

    /**
     * Web: POST /job-postings/{id}/update
     */
    public function update(int $id, array $postData = []): void {
        $data = !empty($postData) ? $postData : $_POST;

        try {
            $updated = JobPosting::update($id, $data);
            if ($updated === null) {
                http_response_code(404);
                echo "Job posting not found.";
                exit;
            }
            $this->render(JobPosting::all(), [
                'notice' => [
                    'type'    => 'success',
                    'message' => "Job posting '{$updated['title']}' updated successfully!"
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(JobPosting::all(), [
                'notice' => [
                    'type'    => 'error',
                    'message' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Web: POST /job-postings/{id}/delete
     */
    public function delete(int $id): void {
        $deleted = JobPosting::delete($id);
        $this->render(JobPosting::all(), [
            'notice' => [
                'type'    => $deleted ? 'success' : 'error',
                'message' => $deleted ? "Job posting #{$id} successfully deleted." : "Job posting not found."
            ]
        ]);
    }

    /**
     * Web: GET /companies/{id}/job-postings
     */
    public function byCompany(int $id): void {
        $jobs = JobPosting::findByCompany($id);
        $this->render($jobs, [
            'companyFilter' => $id,
            'title'         => "Opportunities for Company #{$id}"
        ]);
    }

    /**
     * Web: GET /users/{id}/job-postings
     */
    public function byUser(int $id): void {
        $jobs = JobPosting::findByUser($id);
        $this->render($jobs, [
            'userFilter' => $id,
            'title'      => "Opportunities Posted by User #{$id}"
        ]);
    }
}
