<?php

use App\Http\Controllers\DeployController;
use App\Http\Middleware\ValidateDeployToken;
use Illuminate\Support\Facades\Route;

Route::post('/deploy', DeployController::class)
    ->middleware(ValidateDeployToken::class);
