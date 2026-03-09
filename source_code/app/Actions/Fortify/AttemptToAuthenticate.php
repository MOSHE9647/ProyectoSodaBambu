<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AttemptToAuthenticate
{
    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * Create a new action instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        // Get the username field defined in Fortify configuration (e.g., 'email')
        $username = Fortify::username();

        // Verify if the user exists before attempting authentication
        $user = User::where($username, $request->input($username))->first();

        if (!$user) {
            // If the user does not exist, throw a specific email error
            throw ValidationException::withMessages([
                $username => ['El correo electrónico proporcionado no coincide con nuestros registros.'],
            ]);
        }

        // Attempt to authenticate using the Laravel guard
        if ($this->guard->attempt(
            $request->only($username, 'password'),
            $request->boolean('remember')
        )) {
            // Successfully authenticated, proceed to the next action
            return $next($request);
        }

        // If we reach here, it means the password was incorrect, so we throw a specific password error
        throw ValidationException::withMessages([
            'password' => ['La contraseña proporcionada es incorrecta.'],
        ]);
    }
}