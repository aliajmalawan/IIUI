<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\GalleryImageRepository;
use App\Repositories\HomepageFacilityRepository;
use App\Repositories\HomepageFeatureRepository;
use App\Repositories\HomepageGalleryRepository;
use App\Repositories\HomepageProgramRepository;
use App\Repositories\HomepageStatRepository;
use App\Repositories\PostRepository;
use App\Repositories\SettingRepository;
use App\Repositories\SliderRepository;
use App\Repositories\StaffRepository;
use App\Repositories\TestimonialRepository;

final class HomeController extends Controller
{
    private const LATEST_NEWS_COUNT = 3;
    private const FACULTY_PREVIEW_COUNT = 4;

    /** The "Vibrant Campus" section falls back to this gallery album's first photo when no campus_image setting is uploaded. */
    private const CAMPUS_GALLERY_CATEGORY = 'Campus Life';

    public function index(Request $request): Response
    {
        $settings = new SettingRepository();
        $campusImagePath = $settings->get('campus_image', '');
        $campusImage = $campusImagePath !== ''
            ? ['image_path' => $campusImagePath, 'caption' => null]
            : ((new GalleryImageRepository())->publishedFlat(self::CAMPUS_GALLERY_CATEGORY)[0] ?? null);

        return $this->view('web.home', [
            'title'          => $settings->get('site_name', (string) config('app.name')),
            'slides'         => (new SliderRepository())->activeForDisplay(),
            'testimonials'   => (new TestimonialRepository())->published(),
            'content'        => $settings->allPublic(),
            'features'       => (new HomepageFeatureRepository())->activeList(),
            'programs'       => (new HomepageProgramRepository())->activeList(),
            'facilities'     => (new HomepageFacilityRepository())->activeList(),
            'stats'          => (new HomepageStatRepository())->activeList(),
            'galleryImages'  => (new HomepageGalleryRepository())->activeList(),
            'latestNews'     => (new PostRepository())->published(null, self::LATEST_NEWS_COUNT),
            'facultyPreview' => array_slice((new StaffRepository())->publicList(), 0, self::FACULTY_PREVIEW_COUNT),
            'campusImage'    => $campusImage,
        ], 'web');
    }
}
