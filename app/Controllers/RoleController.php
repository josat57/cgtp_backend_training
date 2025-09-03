<?php

namespace App\Controllers;

use App\Models\Role;
use App\Utils\Response;

class RoleController
{
    private $roleModel;

    public function __construct()
    {
        $this->roleModel = new Role();
    }

    public function index($request, $response)
    {
        $roles = $this->roleModel->all();
        return Response::json($response, $roles);
    }

    public function store($request, $response)
    {
        $data = $request->getParsedBody();
        $role = $this->roleModel->create($data);
        return Response::json($response, $role, 201);
    }

    public function show($request, $response, $args)
    {
        $role = $this->roleModel->find($args['id']);
        if (!$role) {
            return Response::json($response, ['error' => 'Role not found'], 404);
        }
        return Response::json($response, $role);
    }

    public function update($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $updated = $this->roleModel->update($args['id'], $data);
        if (!$updated) {
            return Response::json($response, ['error' => 'Role not found'], 404);
        }
        return Response::json($response, ['message' => 'Role updated successfully']);
    }

    public function delete($request, $response, $args)
    {
        $deleted = $this->roleModel->delete($args['id']);
        if (!$deleted) {
            return Response::json($response, ['error' => 'Role not found'], 404);
        }
        return Response::json($response, ['message' => 'Role deleted successfully']);
    }
}
