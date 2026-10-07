<?php
/**
 * Istanbul University MIS Alumni Portal
 * Web Route Definitions
 *
 * Complete CRUD routes for user management (HTML views).
 * NOTE: static routes (/users/create) must be registered BEFORE
 * dynamic ones (/users/{id}) so "create" is never captured as an ID.
 */

return function (Router $router): void {
    // READ (collection): list all users
    $router->get('/users', ['UserController', 'index']);

    // CREATE (form view): render create form / modal
    $router->get('/users/create', ['UserController', 'create']);

    // CREATE (action): process new user submission
    $router->post('/users', ['UserController', 'store']);

    // READ (single): user details view
    $router->get('/users/{id}', ['UserController', 'show']);

    // UPDATE (form view): render edit form / modal
    $router->get('/users/{id}/edit', ['UserController', 'edit']);

    // UPDATE (action): HTML forms use POST; API/fetch clients may use PUT
    $router->post('/users/{id}/update', ['UserController', 'update']);
    $router->put('/users/{id}', ['UserController', 'update']);

    // DELETE (action): HTML forms use POST; API/fetch clients may use DELETE
    $router->post('/users/{id}/delete', ['UserController', 'delete']);
    $router->delete('/users/{id}', ['UserController', 'delete']);
};