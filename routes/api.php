<?php
/**
 * Istanbul University MIS Alumni Portal
 * REST API Route Definitions
 *
 * Dedicated routes layer registering JSON endpoints mapping to ApiUserController.
 */

return function (Router $router): void {
    // -------------------------------------------------------------
    // REST API User CRUD Endpoints
    // -------------------------------------------------------------

    // GET /api/users -> List all users (JSON)
    $router->get('/api/users', ['ApiUserController', 'index']);

    // POST /api/users -> Create user (JSON)
    $router->post('/api/users', ['ApiUserController', 'store']);

    // GET /api/users/{id} -> Get single user by ID (JSON)
    $router->get('/api/users/{id}', ['ApiUserController', 'show']);

    // PUT /api/users/{id} -> Update user (JSON)
    $router->put('/api/users/{id}', ['ApiUserController', 'update']);

    // PATCH /api/users/{id} -> Partial update user (JSON)
    $router->patch('/api/users/{id}', ['ApiUserController', 'update']);

    // POST /api/users/{id} -> Fallback update user (JSON)
    $router->post('/api/users/{id}', ['ApiUserController', 'update']);

    // POST /api/users/{id}/update -> Explicit update user (JSON)
    $router->post('/api/users/{id}/update', ['ApiUserController', 'update']);

    // DELETE /api/users/{id} -> Delete user (JSON)
    $router->delete('/api/users/{id}', ['ApiUserController', 'destroy']);

    // POST /api/users/{id}/delete -> Explicit delete user (JSON)
    $router->post('/api/users/{id}/delete', ['ApiUserController', 'destroy']);
};
