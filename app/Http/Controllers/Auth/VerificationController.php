<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles email verification for newly registered users,
    | as well as resending verification emails for both authenticated and guest users.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Enforce auth only on the in-app notice and authenticated resend
        $this->middleware('auth')->only(['show', 'resend']);
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only(['verify', 'resend', 'resendGuest']);
    }

    /**
     * Show the public email verification resend form.
     *
     * @return View
     */
    public function showResendForm(Request $request)
    {
        return view('auth.verify-resend', [
            'email' => $request->query('email', old('email')),
        ]);
    }

    /**
     * Resend the email verification notification for a guest user.
     *
     * @return RedirectResponse
     */
    public function resendGuest(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Generic response to prevent account enumeration
        return back()->with(
            'status',
            'If an unverified account exists for that email address, a new verification link has been sent.'
        );
    }

    /**
     * Mark the authenticated or guest user's email address as verified.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function verify(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Your email address is already verified. You may sign in.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('login')->with('status', 'Your email has been verified! You may now sign in.');
    }
}
