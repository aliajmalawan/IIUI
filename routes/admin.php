<?php

declare(strict_types=1);

use App\Controllers\Admin\AdmissionApplicationController;
use App\Controllers\Admin\AppearanceController;
use App\Controllers\Admin\AuditLogController;
use App\Controllers\Admin\BlogController;
use App\Controllers\Admin\ContactMessageController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\AnalyticsController;
use App\Controllers\Admin\DepartmentController;
use App\Controllers\Admin\DesignationController;
use App\Controllers\Admin\DownloadController;
use App\Controllers\Admin\EventController;
use App\Controllers\Admin\ExamTermController;
use App\Controllers\Admin\ExaminationController;
use App\Controllers\Admin\FeeStructureController;
use App\Controllers\Admin\HolidayController;
use App\Controllers\Admin\FacultyController;
use App\Controllers\Admin\GalleryController;
use App\Controllers\Admin\GalleryImageController;
use App\Controllers\Admin\HomepageContentController;
use App\Controllers\Admin\HomepageFacilityController;
use App\Controllers\Admin\HomepageFeatureController;
use App\Controllers\Admin\HomepageGalleryController;
use App\Controllers\Admin\HomepageProgramController;
use App\Controllers\Admin\HomepageStatController;
use App\Controllers\Admin\MediaLibraryController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\NotificationController;
use App\Controllers\Admin\PageController;
use App\Controllers\Admin\PostCategoryController;
use App\Controllers\Admin\PostController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SiteSettingsController;
use App\Controllers\Admin\SliderController;
use App\Controllers\Admin\StaffController;
use App\Controllers\Admin\StudentController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\TestimonialController;
use App\Core\Router;

Router::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin.access', 'csrf']], function (): void {

    Router::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Analytics
    Router::get('/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics')->middleware('permission:reports');

    Router::get('/profile', [ProfileController::class, 'show'])->name('admin.profile');
    Router::post('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Router::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

    Router::get('/appearance', [AppearanceController::class, 'show'])->name('admin.appearance');
    Router::post('/theme', [AppearanceController::class, 'updateTheme'])->name('admin.theme');

    Router::get('/settings', [SiteSettingsController::class, 'index'])->name('admin.settings')->middleware('permission:settings');
    Router::get('/settings/{group}', [SiteSettingsController::class, 'show'])->name('admin.settings.group')->middleware('permission:settings');
    Router::post('/settings/{group}', [SiteSettingsController::class, 'update'])->name('admin.settings.group.update')->middleware('permission:settings');

    Router::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs')->middleware('permission:audit_logs');

    // ---------------------------------------------------------------------
    // Contact Messages — inbox for the public Contact form. Step 6.11.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'contact-messages', 'middleware' => ['permission:messages']], function (): void {
        Router::get('/', [ContactMessageController::class, 'index'])->name('admin.contact-messages');
        Router::get('/{id}', [ContactMessageController::class, 'show'])->where('id', '\d+')->name('admin.contact-messages.show');
        Router::post('/{id}/reply', [ContactMessageController::class, 'reply'])->where('id', '\d+')->name('admin.contact-messages.reply');
        Router::post('/{id}/status', [ContactMessageController::class, 'updateStatus'])->where('id', '\d+')->name('admin.contact-messages.status');
        Router::post('/{id}/delete', [ContactMessageController::class, 'destroy'])->where('id', '\d+')->name('admin.contact-messages.delete');
    });

    // ---------------------------------------------------------------------
    // Admission Applications — inbox for the public "Apply Now" form, plus
    // the entrance-test/merit pipeline (test_scheduled/test_taken and their
    // date/roll-no/score/interview-remarks/merit-position columns).
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'admissions', 'middleware' => ['permission:admissions']], function (): void {
        Router::get('/', [AdmissionApplicationController::class, 'index'])->name('admin.admissions');
        Router::get('/{id}', [AdmissionApplicationController::class, 'show'])->where('id', '\d+')->name('admin.admissions.show');
        Router::post('/{id}/status', [AdmissionApplicationController::class, 'updateStatus'])->where('id', '\d+')->name('admin.admissions.status');
        Router::post('/{id}/remarks', [AdmissionApplicationController::class, 'saveRemarks'])->where('id', '\d+')->name('admin.admissions.remarks');
        Router::post('/{id}/test/schedule', [AdmissionApplicationController::class, 'scheduleTest'])->where('id', '\d+')->name('admin.admissions.test.schedule');
        Router::post('/{id}/test/score', [AdmissionApplicationController::class, 'recordScore'])->where('id', '\d+')->name('admin.admissions.test.score');
        Router::post('/{id}/merit', [AdmissionApplicationController::class, 'saveMerit'])->where('id', '\d+')->name('admin.admissions.merit');
        Router::post('/{id}/convert', [AdmissionApplicationController::class, 'convert'])->where('id', '\d+')->name('admin.admissions.convert');
        Router::post('/{id}/delete', [AdmissionApplicationController::class, 'destroy'])->where('id', '\d+')->name('admin.admissions.delete');
    });

    // ---------------------------------------------------------------------
    // Homepage Builder — hero slider, about, statistics, gallery,
    // testimonials, CTA. See docs' Step 6.2.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'homepage', 'middleware' => ['permission:cms_pages']], function (): void {

        Router::get('/hero', [SliderController::class, 'index'])->name('admin.homepage.hero');
        Router::get('/hero/create', [SliderController::class, 'create'])->name('admin.homepage.hero.create');
        Router::post('/hero', [SliderController::class, 'store'])->name('admin.homepage.hero.store');
        Router::get('/hero/{id}/edit', [SliderController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.hero.edit');
        Router::post('/hero/{id}', [SliderController::class, 'update'])->where('id', '\d+')->name('admin.homepage.hero.update');
        Router::post('/hero/{id}/delete', [SliderController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.hero.delete');

        Router::get('/sections', [HomepageContentController::class, 'showSections'])->name('admin.homepage.sections');
        Router::post('/sections', [HomepageContentController::class, 'updateSections'])->name('admin.homepage.sections.update');

        Router::get('/about', [HomepageContentController::class, 'showAbout'])->name('admin.homepage.about');
        Router::post('/about', [HomepageContentController::class, 'updateAbout'])->name('admin.homepage.about.update');

        Router::get('/welcome', [HomepageContentController::class, 'showWelcome'])->name('admin.homepage.welcome');
        Router::post('/welcome', [HomepageContentController::class, 'updateWelcome'])->name('admin.homepage.welcome.update');

        Router::get('/features', [HomepageFeatureController::class, 'index'])->name('admin.homepage.features');
        Router::get('/features/create', [HomepageFeatureController::class, 'create'])->name('admin.homepage.features.create');
        Router::post('/features', [HomepageFeatureController::class, 'store'])->name('admin.homepage.features.store');
        Router::get('/features/{id}/edit', [HomepageFeatureController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.features.edit');
        Router::post('/features/{id}', [HomepageFeatureController::class, 'update'])->where('id', '\d+')->name('admin.homepage.features.update');
        Router::post('/features/{id}/delete', [HomepageFeatureController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.features.delete');

        Router::get('/programs', [HomepageProgramController::class, 'index'])->name('admin.homepage.programs');
        Router::get('/programs/create', [HomepageProgramController::class, 'create'])->name('admin.homepage.programs.create');
        Router::post('/programs', [HomepageProgramController::class, 'store'])->name('admin.homepage.programs.store');
        Router::get('/programs/{id}/edit', [HomepageProgramController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.programs.edit');
        Router::post('/programs/{id}', [HomepageProgramController::class, 'update'])->where('id', '\d+')->name('admin.homepage.programs.update');
        Router::post('/programs/{id}/delete', [HomepageProgramController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.programs.delete');

        Router::get('/facilities', [HomepageFacilityController::class, 'index'])->name('admin.homepage.facilities');
        Router::get('/facilities/create', [HomepageFacilityController::class, 'create'])->name('admin.homepage.facilities.create');
        Router::post('/facilities', [HomepageFacilityController::class, 'store'])->name('admin.homepage.facilities.store');
        Router::get('/facilities/{id}/edit', [HomepageFacilityController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.facilities.edit');
        Router::post('/facilities/{id}', [HomepageFacilityController::class, 'update'])->where('id', '\d+')->name('admin.homepage.facilities.update');
        Router::post('/facilities/{id}/delete', [HomepageFacilityController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.facilities.delete');

        Router::get('/stats', [HomepageStatController::class, 'index'])->name('admin.homepage.stats');
        Router::get('/stats/create', [HomepageStatController::class, 'create'])->name('admin.homepage.stats.create');
        Router::post('/stats', [HomepageStatController::class, 'store'])->name('admin.homepage.stats.store');
        Router::get('/stats/{id}/edit', [HomepageStatController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.stats.edit');
        Router::post('/stats/{id}', [HomepageStatController::class, 'update'])->where('id', '\d+')->name('admin.homepage.stats.update');
        Router::post('/stats/{id}/delete', [HomepageStatController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.stats.delete');

        Router::get('/gallery', [HomepageGalleryController::class, 'index'])->name('admin.homepage.gallery');
        Router::get('/gallery/create', [HomepageGalleryController::class, 'create'])->name('admin.homepage.gallery.create');
        Router::post('/gallery', [HomepageGalleryController::class, 'store'])->name('admin.homepage.gallery.store');
        Router::get('/gallery/{id}/edit', [HomepageGalleryController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.gallery.edit');
        Router::post('/gallery/{id}', [HomepageGalleryController::class, 'update'])->where('id', '\d+')->name('admin.homepage.gallery.update');
        Router::post('/gallery/{id}/delete', [HomepageGalleryController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.gallery.delete');

        Router::get('/testimonials', [TestimonialController::class, 'index'])->name('admin.homepage.testimonials');
        Router::get('/testimonials/create', [TestimonialController::class, 'create'])->name('admin.homepage.testimonials.create');
        Router::post('/testimonials', [TestimonialController::class, 'store'])->name('admin.homepage.testimonials.store');
        Router::get('/testimonials/{id}/edit', [TestimonialController::class, 'edit'])->where('id', '\d+')->name('admin.homepage.testimonials.edit');
        Router::post('/testimonials/{id}', [TestimonialController::class, 'update'])->where('id', '\d+')->name('admin.homepage.testimonials.update');
        Router::post('/testimonials/{id}/delete', [TestimonialController::class, 'destroy'])->where('id', '\d+')->name('admin.homepage.testimonials.delete');

        Router::get('/cta', [HomepageContentController::class, 'showCta'])->name('admin.homepage.cta');
        Router::post('/cta', [HomepageContentController::class, 'updateCta'])->name('admin.homepage.cta.update');

        Router::get('/faculty', [HomepageContentController::class, 'showFaculty'])->name('admin.homepage.faculty');
        Router::post('/faculty', [HomepageContentController::class, 'updateFaculty'])->name('admin.homepage.faculty.update');

        Router::get('/campus', [HomepageContentController::class, 'showCampus'])->name('admin.homepage.campus');
        Router::post('/campus', [HomepageContentController::class, 'updateCampus'])->name('admin.homepage.campus.update');
    });

    // ---------------------------------------------------------------------
    // Menus — header nav, footer links, quick links. Step 6.4.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'menus', 'middleware' => ['permission:cms_menus']], function (): void {
        Router::get('/', [MenuController::class, 'index'])->name('admin.menus');
        Router::get('/{location}', [MenuController::class, 'show'])->name('admin.menus.location');
        Router::post('/{location}/items', [MenuController::class, 'store'])->name('admin.menus.store');
        Router::post('/{location}/items/{id}', [MenuController::class, 'update'])->where('id', '\d+')->name('admin.menus.update');
        Router::post('/{location}/items/{id}/delete', [MenuController::class, 'destroy'])->where('id', '\d+')->name('admin.menus.delete');
        Router::post('/{location}/structure', [MenuController::class, 'reorder'])->name('admin.menus.reorder');
    });

    // ---------------------------------------------------------------------
    // Gallery — full photo album module. Step 6.5.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'galleries', 'middleware' => ['permission:cms_media']], function (): void {
        Router::get('/', [GalleryController::class, 'index'])->name('admin.galleries');
        Router::get('/create', [GalleryController::class, 'create'])->name('admin.galleries.create');
        Router::post('/', [GalleryController::class, 'store'])->name('admin.galleries.store');
        Router::get('/{id}/edit', [GalleryController::class, 'edit'])->where('id', '\d+')->name('admin.galleries.edit');
        Router::post('/{id}', [GalleryController::class, 'update'])->where('id', '\d+')->name('admin.galleries.update');
        Router::post('/{id}/delete', [GalleryController::class, 'destroy'])->where('id', '\d+')->name('admin.galleries.delete');

        Router::get('/{id}/images', [GalleryImageController::class, 'index'])->where('id', '\d+')->name('admin.galleries.images');
        Router::post('/{id}/images', [GalleryImageController::class, 'store'])->where('id', '\d+')->name('admin.galleries.images.store');
        Router::post('/{id}/videos', [GalleryImageController::class, 'storeVideo'])->where('id', '\d+')->name('admin.galleries.videos.store');
        Router::get('/{id}/images/{imageId}/edit', [GalleryImageController::class, 'edit'])
            ->where('id', '\d+')->where('imageId', '\d+')->name('admin.galleries.images.edit');
        Router::post('/{id}/images/{imageId}', [GalleryImageController::class, 'update'])
            ->where('id', '\d+')->where('imageId', '\d+')->name('admin.galleries.images.update');
        Router::post('/{id}/images/{imageId}/delete', [GalleryImageController::class, 'destroy'])
            ->where('id', '\d+')->where('imageId', '\d+')->name('admin.galleries.images.delete');
        Router::post('/{id}/images/{imageId}/cover', [GalleryImageController::class, 'setCover'])
            ->where('id', '\d+')->where('imageId', '\d+')->name('admin.galleries.images.cover');
    });

    // ---------------------------------------------------------------------
    // News — articles + categories, TinyMCE rich text. Step 6.6.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'news', 'middleware' => ['permission:cms_posts']], function (): void {
        Router::get('/categories', [PostCategoryController::class, 'index'])->name('admin.news.categories');
        Router::get('/categories/create', [PostCategoryController::class, 'create'])->name('admin.news.categories.create');
        Router::post('/categories', [PostCategoryController::class, 'store'])->name('admin.news.categories.store');
        Router::get('/categories/{id}/edit', [PostCategoryController::class, 'edit'])->where('id', '\d+')->name('admin.news.categories.edit');
        Router::post('/categories/{id}', [PostCategoryController::class, 'update'])->where('id', '\d+')->name('admin.news.categories.update');
        Router::post('/categories/{id}/delete', [PostCategoryController::class, 'destroy'])->where('id', '\d+')->name('admin.news.categories.delete');

        Router::get('/', [PostController::class, 'index'])->name('admin.news');
        Router::get('/create', [PostController::class, 'create'])->name('admin.news.create');
        Router::post('/', [PostController::class, 'store'])->name('admin.news.store');
        Router::get('/{id}/edit', [PostController::class, 'edit'])->where('id', '\d+')->name('admin.news.edit');
        Router::post('/{id}', [PostController::class, 'update'])->where('id', '\d+')->name('admin.news.update');
        Router::post('/{id}/delete', [PostController::class, 'destroy'])->where('id', '\d+')->name('admin.news.delete');
    });

    // ---------------------------------------------------------------------
    // Blog — same posts table as News (post_type='blog'), no categories.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'blog', 'middleware' => ['permission:cms_posts']], function (): void {
        Router::get('/', [BlogController::class, 'index'])->name('admin.blog');
        Router::get('/create', [BlogController::class, 'create'])->name('admin.blog.create');
        Router::post('/', [BlogController::class, 'store'])->name('admin.blog.store');
        Router::get('/{id}/edit', [BlogController::class, 'edit'])->where('id', '\d+')->name('admin.blog.edit');
        Router::post('/{id}', [BlogController::class, 'update'])->where('id', '\d+')->name('admin.blog.update');
        Router::post('/{id}/delete', [BlogController::class, 'destroy'])->where('id', '\d+')->name('admin.blog.delete');
    });

    // ---------------------------------------------------------------------
    // Events — school events with scheduling, venue, registration. Step 6.7.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'events', 'middleware' => ['permission:cms_posts']], function (): void {
        Router::get('/', [EventController::class, 'index'])->name('admin.events');
        Router::get('/create', [EventController::class, 'create'])->name('admin.events.create');
        Router::post('/', [EventController::class, 'store'])->name('admin.events.store');
        Router::get('/{id}/edit', [EventController::class, 'edit'])->where('id', '\d+')->name('admin.events.edit');
        Router::post('/{id}', [EventController::class, 'update'])->where('id', '\d+')->name('admin.events.update');
        Router::post('/{id}/delete', [EventController::class, 'destroy'])->where('id', '\d+')->name('admin.events.delete');
    });

    // ---------------------------------------------------------------------
    // Faculty — public-profile subset of the `staff` table (photo, bio,
    // department/designation, public visibility toggle). Step 6.8.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'faculty', 'middleware' => ['permission:staff']], function (): void {
        Router::get('/departments', [DepartmentController::class, 'index'])->name('admin.faculty.departments');
        Router::get('/departments/create', [DepartmentController::class, 'create'])->name('admin.faculty.departments.create');
        Router::post('/departments', [DepartmentController::class, 'store'])->name('admin.faculty.departments.store');
        Router::get('/departments/{id}/edit', [DepartmentController::class, 'edit'])->where('id', '\d+')->name('admin.faculty.departments.edit');
        Router::post('/departments/{id}', [DepartmentController::class, 'update'])->where('id', '\d+')->name('admin.faculty.departments.update');
        Router::post('/departments/{id}/delete', [DepartmentController::class, 'destroy'])->where('id', '\d+')->name('admin.faculty.departments.delete');

        Router::get('/designations', [DesignationController::class, 'index'])->name('admin.faculty.designations');
        Router::get('/designations/create', [DesignationController::class, 'create'])->name('admin.faculty.designations.create');
        Router::post('/designations', [DesignationController::class, 'store'])->name('admin.faculty.designations.store');
        Router::get('/designations/{id}/edit', [DesignationController::class, 'edit'])->where('id', '\d+')->name('admin.faculty.designations.edit');
        Router::post('/designations/{id}', [DesignationController::class, 'update'])->where('id', '\d+')->name('admin.faculty.designations.update');
        Router::post('/designations/{id}/delete', [DesignationController::class, 'destroy'])->where('id', '\d+')->name('admin.faculty.designations.delete');

        Router::get('/', [FacultyController::class, 'index'])->name('admin.faculty');
        Router::get('/create', [FacultyController::class, 'create'])->name('admin.faculty.create');
        Router::post('/', [FacultyController::class, 'store'])->name('admin.faculty.store');
        Router::get('/{id}/edit', [FacultyController::class, 'edit'])->where('id', '\d+')->name('admin.faculty.edit');
        Router::post('/{id}', [FacultyController::class, 'update'])->where('id', '\d+')->name('admin.faculty.update');
        Router::post('/{id}/delete', [FacultyController::class, 'destroy'])->where('id', '\d+')->name('admin.faculty.delete');
    });

    // ---------------------------------------------------------------------
    // Students — core admission/personal/class-placement record on the
    // `students` table. Guardians, attendance, documents, fee discounts,
    // promotions, transfers and transport are separate future modules.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'students', 'middleware' => ['permission:students']], function (): void {
        Router::get('/', [StudentController::class, 'index'])->name('admin.students');
        Router::get('/create', [StudentController::class, 'create'])->name('admin.students.create');
        Router::post('/', [StudentController::class, 'store'])->name('admin.students.store');
        Router::get('/{id}/edit', [StudentController::class, 'edit'])->where('id', '\d+')->name('admin.students.edit');
        Router::post('/{id}', [StudentController::class, 'update'])->where('id', '\d+')->name('admin.students.update');
        Router::post('/{id}/delete', [StudentController::class, 'destroy'])->where('id', '\d+')->name('admin.students.delete');
    });

    // ---------------------------------------------------------------------
    // Examinations — subject-wise exam schedule (the `exams` table) within
    // an exam term. Attendance roll-call, marks entry, and results
    // publishing are separate, larger future modules (see ExamRepository).
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'examinations', 'middleware' => ['permission:exams']], function (): void {
        Router::get('/', [ExaminationController::class, 'index'])->name('admin.examinations');
        Router::get('/create', [ExaminationController::class, 'create'])->name('admin.examinations.create');
        Router::post('/', [ExaminationController::class, 'store'])->name('admin.examinations.store');
        Router::get('/{id}/edit', [ExaminationController::class, 'edit'])->where('id', '\d+')->name('admin.examinations.edit');
        Router::post('/{id}', [ExaminationController::class, 'update'])->where('id', '\d+')->name('admin.examinations.update');
        Router::post('/{id}/delete', [ExaminationController::class, 'destroy'])->where('id', '\d+')->name('admin.examinations.delete');
    });

    // ---------------------------------------------------------------------
    // Staff — full HR record (payroll, CNIC, employment status) on the same
    // `staff` table Faculty uses for its public-profile subset. Attendance,
    // documents, subjects, payroll runs, and leave stay separate future
    // modules (staff_attendance/staff_documents/staff_subjects/payrolls/
    // leave_applications).
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'staff', 'middleware' => ['permission:staff']], function (): void {
        Router::get('/', [StaffController::class, 'index'])->name('admin.staff');
        Router::get('/create', [StaffController::class, 'create'])->name('admin.staff.create');
        Router::post('/', [StaffController::class, 'store'])->name('admin.staff.store');
        Router::get('/{id}/edit', [StaffController::class, 'edit'])->where('id', '\d+')->name('admin.staff.edit');
        Router::post('/{id}', [StaffController::class, 'update'])->where('id', '\d+')->name('admin.staff.update');
        Router::post('/{id}/delete', [StaffController::class, 'destroy'])->where('id', '\d+')->name('admin.staff.delete');
    });

    // ---------------------------------------------------------------------
    // Pages — the four system pages (About/Academics/Admissions/Contact)
    // plus any custom pages, each with a hero and a stack of content
    // sections. See PageRepository / PageSectionRepository for scope.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'pages', 'middleware' => ['permission:cms_pages']], function (): void {
        Router::get('/', [PageController::class, 'index'])->name('admin.pages');
        Router::get('/create', [PageController::class, 'create'])->name('admin.pages.create');
        Router::post('/', [PageController::class, 'store'])->name('admin.pages.store');
        Router::get('/{id}/edit', [PageController::class, 'edit'])->where('id', '\d+')->name('admin.pages.edit');
        Router::post('/{id}', [PageController::class, 'update'])->where('id', '\d+')->name('admin.pages.update');
        Router::post('/{id}/delete', [PageController::class, 'destroy'])->where('id', '\d+')->name('admin.pages.delete');

        Router::get('/{id}/sections', [PageController::class, 'sections'])->where('id', '\d+')->name('admin.pages.sections');
        Router::post('/{id}/sections', [PageController::class, 'addSection'])->where('id', '\d+')->name('admin.pages.sections.add');
        Router::get('/{id}/sections/{sectionId}/edit', [PageController::class, 'editSection'])
            ->where('id', '\d+')->where('sectionId', '\d+')->name('admin.pages.sections.edit');
        Router::post('/{id}/sections/{sectionId}', [PageController::class, 'updateSection'])
            ->where('id', '\d+')->where('sectionId', '\d+')->name('admin.pages.sections.update');
        Router::post('/{id}/sections/{sectionId}/toggle', [PageController::class, 'toggleSection'])
            ->where('id', '\d+')->where('sectionId', '\d+')->name('admin.pages.sections.toggle');
        Router::post('/{id}/sections/{sectionId}/delete', [PageController::class, 'deleteSection'])
            ->where('id', '\d+')->where('sectionId', '\d+')->name('admin.pages.sections.delete');
    });

    // ---------------------------------------------------------------------
    // Academic Calendar — exam terms + holidays, shown on the public
    // Academic Calendar page. Session management itself stays out of scope
    // (see AcademicSessionRepository).
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'exam-terms', 'middleware' => ['permission:exams']], function (): void {
        Router::get('/', [ExamTermController::class, 'index'])->name('admin.exam-terms');
        Router::get('/create', [ExamTermController::class, 'create'])->name('admin.exam-terms.create');
        Router::post('/', [ExamTermController::class, 'store'])->name('admin.exam-terms.store');
        Router::get('/{id}/edit', [ExamTermController::class, 'edit'])->where('id', '\d+')->name('admin.exam-terms.edit');
        Router::post('/{id}', [ExamTermController::class, 'update'])->where('id', '\d+')->name('admin.exam-terms.update');
        Router::post('/{id}/publish', [ExamTermController::class, 'publish'])->where('id', '\d+')->name('admin.exam-terms.publish');
        Router::post('/{id}/unpublish', [ExamTermController::class, 'unpublish'])->where('id', '\d+')->name('admin.exam-terms.unpublish');
        Router::post('/{id}/delete', [ExamTermController::class, 'destroy'])->where('id', '\d+')->name('admin.exam-terms.delete');
    });

    Router::group(['prefix' => 'holidays', 'middleware' => ['permission:notices']], function (): void {
        Router::get('/', [HolidayController::class, 'index'])->name('admin.holidays');
        Router::get('/create', [HolidayController::class, 'create'])->name('admin.holidays.create');
        Router::post('/', [HolidayController::class, 'store'])->name('admin.holidays.store');
        Router::get('/{id}/edit', [HolidayController::class, 'edit'])->where('id', '\d+')->name('admin.holidays.edit');
        Router::post('/{id}', [HolidayController::class, 'update'])->where('id', '\d+')->name('admin.holidays.update');
        Router::post('/{id}/delete', [HolidayController::class, 'destroy'])->where('id', '\d+')->name('admin.holidays.delete');
    });

    // ---------------------------------------------------------------------
    // Fee Structure — per-class amounts shown on the public Fee Structure
    // page. Challans/payments/fines are the rest of the Finance module,
    // still "Soon" in the sidebar.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'fee-structures', 'middleware' => ['permission:fee_structures']], function (): void {
        Router::get('/', [FeeStructureController::class, 'index'])->name('admin.fee-structures');
        Router::get('/create', [FeeStructureController::class, 'create'])->name('admin.fee-structures.create');
        Router::post('/', [FeeStructureController::class, 'store'])->name('admin.fee-structures.store');
        Router::get('/{id}/edit', [FeeStructureController::class, 'edit'])->where('id', '\d+')->name('admin.fee-structures.edit');
        Router::post('/{id}', [FeeStructureController::class, 'update'])->where('id', '\d+')->name('admin.fee-structures.update');
        Router::post('/{id}/delete', [FeeStructureController::class, 'destroy'])->where('id', '\d+')->name('admin.fee-structures.delete');
    });

    // ---------------------------------------------------------------------
    // Downloads — circulars, forms, syllabi. Files are stored outside the
    // webroot and streamed through a controller (docs/05-security.md §5).
    // Step 6.10.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'downloads', 'middleware' => ['permission:cms_media']], function (): void {
        Router::get('/', [DownloadController::class, 'index'])->name('admin.downloads');
        Router::get('/create', [DownloadController::class, 'create'])->name('admin.downloads.create');
        Router::post('/', [DownloadController::class, 'store'])->name('admin.downloads.store');
        Router::get('/{id}/edit', [DownloadController::class, 'edit'])->where('id', '\d+')->name('admin.downloads.edit');
        Router::post('/{id}', [DownloadController::class, 'update'])->where('id', '\d+')->name('admin.downloads.update');
        Router::post('/{id}/delete', [DownloadController::class, 'destroy'])->where('id', '\d+')->name('admin.downloads.delete');
    });

    // ---------------------------------------------------------------------
    // Media Library — read-through browser over everything already saved
    // under public/uploads/ by the other modules' own upload forms (see
    // App\Core\MediaLibrary for why this isn't a separate DB-backed system).
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'media-library', 'middleware' => ['permission:cms_media']], function (): void {
        Router::get('/', [MediaLibraryController::class, 'index'])->name('admin.media-library');
        Router::post('/delete', [MediaLibraryController::class, 'destroy'])->name('admin.media-library.delete');
    });

    // ---------------------------------------------------------------------
    // Users & Roles — admin account management + role assignment. Role
    // definitions (role_permissions) stay read-only here, see the notice
    // above UserRepository's "Users & Roles admin module" section.
    // ---------------------------------------------------------------------
    Router::group(['prefix' => 'users', 'middleware' => ['permission:users']], function (): void {
        Router::get('/', [UserController::class, 'index'])->name('admin.users');
        Router::get('/create', [UserController::class, 'create'])->name('admin.users.create');
        Router::post('/', [UserController::class, 'store'])->name('admin.users.store');
        Router::get('/{id}/edit', [UserController::class, 'edit'])->where('id', '\d+')->name('admin.users.edit');
        Router::post('/{id}', [UserController::class, 'update'])->where('id', '\d+')->name('admin.users.update');
        Router::post('/{id}/delete', [UserController::class, 'destroy'])->where('id', '\d+')->name('admin.users.delete');
    });

    Router::group(['prefix' => 'roles', 'middleware' => ['permission:roles']], function (): void {
        Router::get('/', [RoleController::class, 'index'])->name('admin.roles');
        Router::get('/{id}', [RoleController::class, 'show'])->where('id', '\d+')->name('admin.roles.show');
    });

    Router::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('admin.notifications.read-all');
    Router::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->where('id', '\d+')
        ->name('admin.notifications.read');
});
