<?php
/**
 * Istanbul University MIS Alumni Portal
 * REST API Route Definitions
 *
 * Registers JSON API endpoints mapping to API controllers for:
 * 1. Users CRUD (/api/users)
 * 2. Job Postings CRUD & Nested Endpoints (/api/job-postings, /api/companies/{id}/job-postings, /api/users/{id}/job-postings)
 * 3. Applications CRUD & Nested Endpoints (/api/applications, /api/job-postings/{id}/applications, /api/users/{id}/applications)
 */

return function (Router $router): void {
    // -------------------------------------------------------------
    // REST API: Authentication & Protected Core Endpoints
    // -------------------------------------------------------------
    $router->post('/api/login',    ['ApiAuthController', 'login']);
    $router->post('/api/register', ['ApiAuthController', 'register']);
    $router->get('/api/profile',   ['ApiAuthController', 'profile']);
    $router->get('/api/admin',     ['ApiAuthController', 'admin']);

    // -------------------------------------------------------------
    // REST API: Development / Testing Utilities
    // Intentionally triggers an unhandled exception so the global
    // exception handler can be exercised in automated test suites.
    // Remove or gate behind an env-flag before deploying to production.
    // -------------------------------------------------------------
    $router->get('/api/trigger-500', function(): void {
        throw new \Exception('Database connection failed');
    });

    // -------------------------------------------------------------
    // REST API: Users CRUD
    // -------------------------------------------------------------
    $router->get('/api/users',                 ['ApiUserController', 'index']);
    $router->post('/api/users',                ['ApiUserController', 'store']);
    $router->get('/api/users/{id}',            ['ApiUserController', 'show']);
    $router->put('/api/users/{id}',            ['ApiUserController', 'update']);
    $router->patch('/api/users/{id}',          ['ApiUserController', 'update']);
    $router->post('/api/users/{id}',           ['ApiUserController', 'update']);
    $router->post('/api/users/{id}/update',    ['ApiUserController', 'update']);
    $router->delete('/api/users/{id}',         ['ApiUserController', 'destroy']);
    $router->post('/api/users/{id}/delete',    ['ApiUserController', 'destroy']);

    // -------------------------------------------------------------
    // REST API: Job Postings (Direct CRUD)
    // -------------------------------------------------------------
    $router->get('/api/job-postings',              ['ApiJobPostingController', 'index']);
    $router->post('/api/job-postings',             ['ApiJobPostingController', 'store']);
    $router->get('/api/job-postings/{id}',         ['ApiJobPostingController', 'show']);
    $router->put('/api/job-postings/{id}',         ['ApiJobPostingController', 'update']);
    $router->patch('/api/job-postings/{id}',       ['ApiJobPostingController', 'update']);
    $router->delete('/api/job-postings/{id}',      ['ApiJobPostingController', 'destroy']);
    $router->post('/api/job-postings/{id}/delete', ['ApiJobPostingController', 'destroy']);

    // -------------------------------------------------------------
    // REST API: Job Postings (Nested Routes)
    // -------------------------------------------------------------
    $router->get('/api/companies/{id}/job-postings', ['ApiJobPostingController', 'byCompany']);
    $router->get('/api/users/{id}/job-postings',     ['ApiJobPostingController', 'byUser']);

    // -------------------------------------------------------------
    // REST API: Applications (Direct CRUD)
    // -------------------------------------------------------------
    $router->get('/api/applications',              ['ApiApplicationController', 'index']);
    $router->post('/api/applications',             ['ApiApplicationController', 'store']);
    $router->get('/api/applications/{id}',         ['ApiApplicationController', 'show']);
    $router->put('/api/applications/{id}',         ['ApiApplicationController', 'update']);
    $router->patch('/api/applications/{id}',       ['ApiApplicationController', 'update']);
    $router->delete('/api/applications/{id}',      ['ApiApplicationController', 'destroy']);
    $router->post('/api/applications/{id}/delete', ['ApiApplicationController', 'destroy']);

    // -------------------------------------------------------------
    // REST API: Applications (Nested Routes)
    // -------------------------------------------------------------
    $router->get('/api/job-postings/{id}/applications',  ['ApiApplicationController', 'byJobPosting']);
    $router->post('/api/job-postings/{id}/applications', ['ApiApplicationController', 'apply']);
    $router->get('/api/users/{id}/applications',         ['ApiApplicationController', 'byUser']);
};
