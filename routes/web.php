<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

if (app()->environment('local')) {
    Route::get('/__dev_login', function () {
        $user = app(\App\Auth\ApiUserProvider::class)->retrieveByCredentials([
            'email' => 'admin@verdantt.com',
            'password' => 'Admin@123',
        ]);

        \Illuminate\Support\Facades\Auth::guard('admin')->login($user);

        return redirect('/admin');
    });
}
