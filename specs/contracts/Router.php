<?php

namespace MaterNatura\Contracts;

interface Router {
    public function dispatch(string $method, string $uri): void;
    public function get(string $path, callable $handler): void;
    public function post(string $path, callable $handler): void;
    public function addSlugRoute(callable $slugHandler): void;
}
