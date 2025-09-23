protected $routeMiddleware = [
    // ...
    'admin' => \App\Http\Middleware\EnsureAdmin::class,
];
