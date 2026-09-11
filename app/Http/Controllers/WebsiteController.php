<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\CmsBanner;
use App\Models\CmsFaq;
use App\Models\CmsNotice;
use App\Models\CmsPage;
use App\Models\Package;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Public High-Converting Homepage (/).
     */
    public function index(): Response
    {
        $packages = Package::with('currentPrice')
            ->where('status', 'active')
            ->orderBy('speed_mbps')
            ->get();

        $coverageAreas = Area::with('children')
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        $banners = CmsBanner::where('is_active', true)
            ->orderBy('order')
            ->get();

        $latestNotices = CmsNotice::where('is_published', true)
            ->latest('published_at')
            ->take(3)
            ->get();

        return Inertia::render('Website/Home', [
            'packages' => $packages,
            'coverageAreas' => $coverageAreas,
            'banners' => $banners,
            'latestNotices' => $latestNotices,
        ]);
    }

    /**
     * About Page (/about).
     */
    public function about(): Response
    {
        $page = CmsPage::where('slug', 'about')->where('is_published', true)->first();

        return Inertia::render('Website/About', [
            'cmsPage' => $page,
        ]);
    }

    /**
     * Dedicated Packages Listing (/packages).
     */
    public function packages(): Response
    {
        $packages = Package::with('currentPrice')
            ->where('status', 'active')
            ->orderBy('speed_mbps')
            ->get();

        return Inertia::render('Website/Packages', [
            'packages' => $packages,
        ]);
    }

    /**
     * Coverage Areas Explorer (/coverage).
     */
    public function coverage(): Response
    {
        $coverageAreas = Area::with('children')
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return Inertia::render('Website/Coverage', [
            'coverageAreas' => $coverageAreas,
        ]);
    }

    /**
     * Frequently Asked Questions (/faq).
     */
    public function faq(): Response
    {
        $faqs = CmsFaq::where('is_published', true)
            ->orderBy('order')
            ->get();

        return Inertia::render('Website/Faq', [
            'faqs' => $faqs,
        ]);
    }

    /**
     * Public ISP Notices & Announcements (/notices).
     */
    public function notices(): Response
    {
        $notices = CmsNotice::where('is_published', true)
            ->orderByDesc('published_at')
            ->get();

        return Inertia::render('Website/Notices', [
            'notices' => $notices,
        ]);
    }

    /**
     * Contact Us & Support Hotline (/contact).
     */
    public function contact(): Response
    {
        $areas = Area::where('status', 'active')->get(['id', 'name', 'code']);

        return Inertia::render('Website/Contact', [
            'areas' => $areas,
        ]);
    }

    /**
     * Custom Dynamic CMS Page (/p/{slug}).
     */
    public function customPage(string $slug): Response
    {
        $page = CmsPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return Inertia::render('Website/CustomPage', [
            'page' => $page,
        ]);
    }

    /**
     * Submit Online New Connection Application from Public Website.
     */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'area_id' => 'required|exists:areas,id',
            'address' => 'required|string|max:500',
            'package_id' => 'required|exists:packages,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['billing_day'] = 1;

        // Auto-create customer from public website application
        $customer = $this->customerService->createCustomer($validated, null);

        return back()->with('success', "Thank you, {$customer->name}! Your connection application has been received (Ref: {$customer->customer_code}). Our field team will contact you within 24 hours.");
    }
}
