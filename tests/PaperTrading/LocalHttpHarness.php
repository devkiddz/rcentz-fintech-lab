<?php
declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;

/** Real HTTP kernel, with CSRF enabled and an in-memory cookie-backed session. */
final class LocalTradingHttpHarness
{
    private string $token;
    private string $compiled;

    public function __construct(private $app)
    {
        if (!$app->environment('local')) {
            throw new RuntimeException('HTTP acceptance requires APP_ENV=local; CSRF must not be bypassed by testing mode.');
        }
        $this->token = bin2hex(random_bytes(32));
        $this->compiled = sys_get_temp_dir().'/fintech-http-'.bin2hex(random_bytes(8));
        if (!mkdir($this->compiled, 0700)) { throw new RuntimeException('Cannot isolate compiled HTTP views.'); }
        config(['session.driver'=>'array', 'cache.default'=>'array', 'view.compiled'=>$this->compiled]);
        $app['session']->forgetDrivers();
        $app->forgetInstance('blade.compiler');
        // No outbound calls, emails, queue jobs or notifications from acceptance fixtures.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Queue::fake();
    }

    public function request(?User $user, string $method, string $uri, array $data = [],
        bool $csrf = true, bool $json = true): \Symfony\Component\HttpFoundation\Response
    {
        $this->app['auth']->forgetGuards();
        if ($user) {
            $fresh = $user->fresh();
            if (!$fresh) { throw new RuntimeException('HTTP fixture user is missing.'); }
            $this->app['auth']->guard('web')->setUser($fresh);
            $this->app['auth']->shouldUse('web');
        }
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('_token', $this->token);
        $session->save();
        $cookieName = config('session.cookie');
        $prefix = CookieValuePrefix::create($cookieName, $this->app['encrypter']->getKey());
        $cookie = $this->app['encrypter']->encrypt($prefix.$session->getId(), false);
        if ($csrf && !in_array($method, ['GET','HEAD'], true)) { $data['_token'] = $this->token; }
        $request = Request::create($uri, $method, $data, [$cookieName=>$cookie], [], [
            'HTTP_HOST'=>'localhost', 'SERVER_NAME'=>'localhost', 'SERVER_PORT'=>80,
            'REMOTE_ADDR'=>'127.0.0.1', 'HTTP_ACCEPT'=>$json ? 'application/json' : 'text/html',
            'HTTP_X_REQUESTED_WITH'=>$json ? 'XMLHttpRequest' : '',
        ]);
        // handle() traverses global middleware, web middleware, route binding and controllers.
        // No worker/scheduler or terminable callback processing is started by this harness.
        return $this->app->make(Kernel::class)->handle($request);
    }

    public function status(string $name, $response, int $expected): void
    {
        verify($name.' (HTTP '.$response->getStatusCode().')', $response->getStatusCode() === $expected);
    }

    public function json(string $name, $response): array
    {
        verify($name.' returns JSON', str_contains((string)$response->headers->get('Content-Type'), 'application/json'));
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        verify($name.' has a JSON object', is_array($data));
        return $data;
    }

    public function cleanup(): void
    {
        foreach (glob($this->compiled.'/*') ?: [] as $file) {
            if (is_file($file)) { unlink($file); }
        }
        if (is_dir($this->compiled)) { rmdir($this->compiled); }
    }
}
