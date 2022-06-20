<?php

namespace Wave\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use TCG\Voyager\Models\Role;
use Wave\Notifications\VerifyEmail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' =>
        [
            'complete'
        ]]);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        if (setting('auth.username_in_registration') && setting('auth.username_in_registration') == 'yes') {
            return Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'username' => 'required|string|max:20|unique:users',
                'password' => 'required|string|min:6|confirmed'
            ]);
        }

        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    public function create(array $data)
    {
        $role = Role::where('name', '=', config('voyager.user.default_role'))->first();

        if (isset($data['username']) && !empty($data['username'])) {
            $username = $data['username'];
        } elseif (isset($data['name']) && !empty($data['name'])) {
            $username = Str::slug($data['name']);
        } else {
            $username = $this->getUniqueUsernameFromEmail($data['email']);
        }

        $username_original = $username;
        $counter = 1;

        while (User::where('username', '=', $username)->first()) {
            $username = $username_original . (string)$counter;
            $counter += 1;
        }

        $trial_days = setting('billing.trial_days', 14);
        $trial_ends_at = null;
        // if trial days is not zero we will set trial_ends_at to ending date
        if (intval($trial_days) > 0) {
            $trial_ends_at = now()->addDays(setting('billing.trial_days', 14));
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $username,
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'trial_ends_at' => $trial_ends_at
        ]);

        if (setting('auth.verify_email', false)) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * Complete a new user registration after they have purchased
     *
     * @param  Request  $request
     * @return redirect
     */
    public function complete(Request $request)
    {
        if (setting('auth.username_in_registration') && setting('auth.username_in_registration') == 'yes') {
            $request->validate([
                'name' => 'required|string|min:3|max:255',
                'username' => 'required|string|max:20|unique:users,username,' . auth()->user()->id,
                'password' => 'required|string|min:6'
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|min:3|max:255',
                'password' => 'required|string|min:6'
            ]);
        }

        // Update the user info
        $user = auth()->user();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->save();

        $user->markEmailAsVerified();

        return redirect()->route('wave.dashboard')->with(['message' => 'Successfully updated your profile information.', 'message_type' => 'success']);
    }

    public function showRegistrationForm()
    {
        if (setting('billing.card_upfront')) {
            return redirect()->route('wave.pricing');
        }
        return view('theme::auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath())->with(['message' => 'Thanks for signing up!', 'message_type' => 'success']);
    }

    public function getUniqueUsernameFromEmail($email)
    {
        $username = strtolower(trim(Str::slug(explode('@', $email)[0])));

        $new_username = $username;

        $user_exists = \Wave\User::where('username', '=', $username)->first();
        $counter = 1;
        while (isset($user_exists->id)) {
            $new_username = $username . $counter;
            $counter += 1;
            $user_exists = \Wave\User::where('username', '=', $new_username)->first();
        }

        $username = $new_username;

        if (strlen($username) < 4) {
            $username = $username . uniqid();
        }

        return strtolower($username);
    }
}
