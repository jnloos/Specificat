<?php

use App\Models\Project;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    $projectId = Cookie::get('curr_project');
    if ($projectId && Project::find($projectId)) {
        return redirect()->route('project.show', ['project' => $projectId]);
    }
    return redirect()->route('project.new');
})->name('dashboard');

Route::prefix('projects')
    ->name('project.')
    ->group(function () {
        Route::get('new', function () {
            return view('index');
        })->name('new');

        Route::get('{project}', function (Project $project) {
            Cookie::queue('curr_project', str($project->id), minutes: 60 * 24 * 31);
            return view('project', compact('project'));
        })->name('show');
    });

Route::get('experts', function () {
    return view('experts');
})->name('experts');

Route::get('public/{path}', function (string $path) {
    abort_unless(Storage::disk('local')->exists('public/' . $path), 404);
    return response()->file(Storage::disk('local')->path('public/' . $path));
})->name('public')->where('path', '.+');
