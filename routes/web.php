<?php
/**
 * Istanbul University MIS Alumni Portal
 * Web Route Definitions
 *
 * Registers HTML view routes and web form actions for:
 * 1. Users CRUD
 * 2. Job Postings (direct and nested)
 * 3. Applications (direct and nested)
 */

return function (Router $router): void {
    // -------------------------------------------------------------
    // User Management Web Routes
    // -------------------------------------------------------------
    $router->get('/users',               ['UserController', 'index']);
    $router->get('/users/create',        ['UserController', 'create']);
    $router->post('/users',              ['UserController', 'store']);
    $router->get('/users/{id}',          ['UserController', 'show']);
    $router->get('/users/{id}/edit',     ['UserController', 'edit']);
    $router->post('/users/{id}/update',  ['UserController', 'update']);
    $router->put('/users/{id}',          ['UserController', 'update']);
    $router->post('/users/{id}/delete',  ['UserController', 'delete']);
    $router->delete('/users/{id}',       ['UserController', 'delete']);

    // -------------------------------------------------------------
    // Job Postings Web Routes (Direct)
    // NOTE: /job-postings/create must precede /job-postings/{id}
    // -------------------------------------------------------------
    $router->get('/job-postings',              ['JobPostingController', 'index']);
    $router->get('/job-postings/create',       ['JobPostingController', 'create']);
    $router->post('/job-postings',             ['JobPostingController', 'store']);
    $router->get('/job-postings/{id}',         ['JobPostingController', 'show']);
    $router->post('/job-postings/{id}/update', ['JobPostingController', 'update']);
    $router->put('/job-postings/{id}',         ['JobPostingController', 'update']);
    $router->post('/job-postings/{id}/delete', ['JobPostingController', 'delete']);
    $router->delete('/job-postings/{id}',      ['JobPostingController', 'delete']);

    // -------------------------------------------------------------
    // Job Postings Nested Web Routes
    // -------------------------------------------------------------
    $router->get('/companies/{id}/job-postings', ['JobPostingController', 'byCompany']);
    $router->get('/users/{id}/job-postings',     ['JobPostingController', 'byUser']);

    // -------------------------------------------------------------
    // Applications Web Routes (Direct)
    // -------------------------------------------------------------
    $router->get('/applications',               ['ApplicationController', 'index']);
    $router->get('/applications/{id}',          ['ApplicationController', 'show']);
    $router->post('/applications',              ['ApplicationController', 'store']);
    $router->post('/applications/{id}/status',  ['ApplicationController', 'updateStatus']);
    $router->post('/applications/{id}/delete',  ['ApplicationController', 'delete']);
    $router->delete('/applications/{id}',       ['ApplicationController', 'delete']);

    // -------------------------------------------------------------
    // Applications Nested Web Routes
    // -------------------------------------------------------------
    $router->get('/job-postings/{id}/applications',  ['ApplicationController', 'byJobPosting']);
    $router->post('/job-postings/{id}/applications', ['ApplicationController', 'apply']);
    $router->get('/users/{id}/applications',         ['ApplicationController', 'byUser']);
};