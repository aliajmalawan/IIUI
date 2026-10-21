<?php

declare(strict_types=1);

use App\Controllers\Web\AcademicCalendarController;
use App\Controllers\Web\AdmissionController;
use App\Controllers\Web\BlogController;
use App\Controllers\Web\ContactController;
use App\Controllers\Web\DownloadController;
use App\Controllers\Web\EventController;
use App\Controllers\Web\FacultyController;
use App\Controllers\Web\FeeStructureController;
use App\Controllers\Web\HealthController;
use App\Controllers\Web\GalleryController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\NewsEventsController;
use App\Controllers\Web\PageController;
use App\Controllers\Web\PostController;
use App\Controllers\Web\SeoController;
use App\Core\Router;

// Crawler files stay outside the pageview group so bots never inflate traffic stats.
Router::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Router::get('/health', [HealthController::class, 'show'])->name('health');
Router::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Every public route here is wrapped in the pageview middleware so
// admin/analytics can report real traffic — never applied to routes/admin.php
// or routes/auth.php.
Router::group(['middleware' => ['pageview']], function (): void {
    Router::get('/', [HomeController::class, 'index'])->name('home');

    Router::get('/about', [PageController::class, 'about'])->name('about');
    Router::get('/leadership', [PageController::class, 'leadership'])->name('leadership');
    Router::get('/academics', [PageController::class, 'academics'])->name('academics');
    Router::get('/academic-calendar', [AcademicCalendarController::class, 'index'])->name('academic-calendar');
    Router::get('/departments', [PageController::class, 'departments'])->name('departments');
    Router::get('/fee-structure', [FeeStructureController::class, 'index'])->name('fee-structure');
    Router::get('/admissions', [PageController::class, 'admissions'])->name('admissions');
    Router::get('/admission-form', [AdmissionController::class, 'form'])->name('admission.form');
    Router::get('/contact', [PageController::class, 'contact'])->name('contact.show');

    // Custom pages created from the admin "Pages" list (routes/web.php's own
    // /about, /academics, /admissions, /contact above stay the canonical URL
    // for those four system pages — this only ever serves non-system ones).
    Router::get('/page/{slug}', [PageController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('page.show');

    Router::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Router::get('/gallery/{slug}', [GalleryController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('gallery.show');

    // The bare /news index is now the combined "News & Events" feed
    // (reference: darsons.edu.pk/news.php) — PostController still serves
    // individual articles and the legacy per-category URL below.
    Router::get('/news', [NewsEventsController::class, 'index'])->name('news.index');
    Router::get('/news/category/{slug}', [PostController::class, 'category'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('news.category');
    Router::get('/news/{slug}', [PostController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('news.show');

    Router::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Router::get('/blog/{slug}', [BlogController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('blog.show');

    Router::get('/events', [EventController::class, 'index'])->name('events.index');
    Router::get('/events/{slug}', [EventController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('events.show');

    Router::get('/faculty', [FacultyController::class, 'index'])->name('faculty.index');
    Router::get('/faculty/{slug}', [FacultyController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('faculty.show');

    Router::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Router::get('/downloads/{id}/file', [DownloadController::class, 'file'])
        ->where('id', '\d+')
        ->name('downloads.file');

    Router::post('/contact', [ContactController::class, 'store'])
        ->middleware(['csrf', 'throttle:contact'])
        ->name('contact.store');

    // /admissions/apply is only the form's POST target. A visitor (or an old link/email) opening it
    // in a browser gets sent to the real form instead of a 404.
    Router::get('/admissions/apply', [AdmissionController::class, 'redirectToForm']);
    Router::post('/admissions/apply', [AdmissionController::class, 'store'])
        ->middleware(['csrf', 'throttle:admission_apply'])
        ->name('admissions.apply');
});
