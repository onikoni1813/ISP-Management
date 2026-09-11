<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CmsBanner;
use App\Models\CmsFaq;
use App\Models\CmsNotice;
use App\Models\CmsPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CmsController extends Controller
{
    /**
     * Display CMS Overview Hub & Quick Status
     */
    public function index(): Response
    {
        Gate::authorize('website.manage');

        return Inertia::render('Admin/Cms/Index', [
            'pagesCount' => CmsPage::count(),
            'bannersCount' => CmsBanner::count(),
            'faqsCount' => CmsFaq::count(),
            'noticesCount' => CmsNotice::count(),
            'publishedNotices' => CmsNotice::where('is_published', true)->count(),
        ]);
    }

    /**
     * CMS Pages Management
     */
    public function pages(): Response
    {
        Gate::authorize('website.manage');

        $pages = CmsPage::orderBy('order')->orderBy('title')->get();

        return Inertia::render('Admin/Cms/Pages', [
            'pages' => $pages,
        ]);
    }

    public function storePage(Request $request): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'slug' => 'required|string|max:100|unique:cms_pages,slug',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'order' => 'integer',
        ]);

        $page = CmsPage::create($validated);

        AuditLog::log('cms_page_created', 'website', $page, null, $page->toArray());

        return back()->with('success', "Page '{$page->title}' created successfully.");
    }

    public function updatePage(Request $request, CmsPage $page): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'slug' => "required|string|max:100|unique:cms_pages,slug,{$page->id}",
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'order' => 'integer',
        ]);

        $oldValues = $page->toArray();
        $page->update($validated);

        AuditLog::log('cms_page_updated', 'website', $page, $oldValues, $page->toArray());

        return back()->with('success', "Page '{$page->title}' updated successfully.");
    }

    public function togglePagePublish(CmsPage $page): RedirectResponse
    {
        Gate::authorize('website.manage');

        $page->update(['is_published' => !$page->is_published]);

        AuditLog::log('cms_page_toggled', 'website', $page, null, ['is_published' => $page->is_published]);

        return back()->with('success', "Page status changed to " . ($page->is_published ? 'Published' : 'Draft') . ".");
    }

    public function destroyPage(CmsPage $page): RedirectResponse
    {
        Gate::authorize('website.manage');

        $title = $page->title;
        AuditLog::log('cms_page_deleted', 'website', $page, $page->toArray(), null);
        $page->delete();

        return back()->with('success', "Page '{$title}' deleted successfully.");
    }

    /**
     * CMS Banners Management
     */
    public function banners(): Response
    {
        Gate::authorize('website.manage');

        $banners = CmsBanner::orderBy('order')->latest()->get();

        return Inertia::render('Admin/Cms/Banners', [
            'banners' => $banners,
        ]);
    }

    public function storeBanner(Request $request): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'badge_text' => 'nullable|string|max:100',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'background_gradient' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        $banner = CmsBanner::create($validated);

        AuditLog::log('cms_banner_created', 'website', $banner, null, $banner->toArray());

        return back()->with('success', 'Banner created successfully.');
    }

    public function updateBanner(Request $request, CmsBanner $banner): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'badge_text' => 'nullable|string|max:100',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'background_gradient' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        $oldValues = $banner->toArray();
        $banner->update($validated);

        AuditLog::log('cms_banner_updated', 'website', $banner, $oldValues, $banner->toArray());

        return back()->with('success', 'Banner updated successfully.');
    }

    public function toggleBannerActive(CmsBanner $banner): RedirectResponse
    {
        Gate::authorize('website.manage');

        $banner->update(['is_active' => !$banner->is_active]);

        AuditLog::log('cms_banner_toggled', 'website', $banner, null, ['is_active' => $banner->is_active]);

        return back()->with('success', "Banner is now " . ($banner->is_active ? 'Active' : 'Inactive') . ".");
    }

    public function destroyBanner(CmsBanner $banner): RedirectResponse
    {
        Gate::authorize('website.manage');

        AuditLog::log('cms_banner_deleted', 'website', $banner, $banner->toArray(), null);
        $banner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }

    /**
     * CMS FAQ Management
     */
    public function faqs(): Response
    {
        Gate::authorize('website.manage');

        $faqs = CmsFaq::orderBy('order')->latest()->get();

        return Inertia::render('Admin/Cms/Faqs', [
            'faqs' => $faqs,
        ]);
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'order' => 'integer',
            'is_published' => 'boolean',
        ]);

        if (empty($validated['category'])) {
            $validated['category'] = 'General';
        }

        $faq = CmsFaq::create($validated);

        AuditLog::log('cms_faq_created', 'website', $faq, null, $faq->toArray());

        return back()->with('success', 'FAQ question added successfully.');
    }

    public function updateFaq(Request $request, CmsFaq $faq): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'order' => 'integer',
            'is_published' => 'boolean',
        ]);

        $oldValues = $faq->toArray();
        $faq->update($validated);

        AuditLog::log('cms_faq_updated', 'website', $faq, $oldValues, $faq->toArray());

        return back()->with('success', 'FAQ question updated successfully.');
    }

    public function toggleFaqPublish(CmsFaq $faq): RedirectResponse
    {
        Gate::authorize('website.manage');

        $faq->update(['is_published' => !$faq->is_published]);

        AuditLog::log('cms_faq_toggled', 'website', $faq, null, ['is_published' => $faq->is_published]);

        return back()->with('success', "FAQ status changed to " . ($faq->is_published ? 'Published' : 'Draft') . ".");
    }

    public function destroyFaq(CmsFaq $faq): RedirectResponse
    {
        Gate::authorize('website.manage');

        AuditLog::log('cms_faq_deleted', 'website', $faq, $faq->toArray(), null);
        $faq->delete();

        return back()->with('success', 'FAQ deleted successfully.');
    }

    /**
     * CMS Notices Management
     */
    public function notices(): Response
    {
        Gate::authorize('website.manage');

        $notices = CmsNotice::latest()->get();

        return Inertia::render('Admin/Cms/Notices', [
            'notices' => $notices,
        ]);
    }

    public function storeNotice(Request $request): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'is_published' => 'boolean',
        ]);

        if (empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $notice = CmsNotice::create($validated);

        AuditLog::log('cms_notice_created', 'website', $notice, null, $notice->toArray());

        return back()->with('success', 'Notice published successfully.');
    }

    public function updateNotice(Request $request, CmsNotice $notice): RedirectResponse
    {
        Gate::authorize('website.manage');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'is_published' => 'boolean',
        ]);

        $oldValues = $notice->toArray();
        $notice->update($validated);

        AuditLog::log('cms_notice_updated', 'website', $notice, $oldValues, $notice->toArray());

        return back()->with('success', 'Notice updated successfully.');
    }

    public function toggleNoticePublish(CmsNotice $notice): RedirectResponse
    {
        Gate::authorize('website.manage');

        $notice->update(['is_published' => !$notice->is_published]);

        AuditLog::log('cms_notice_toggled', 'website', $notice, null, ['is_published' => $notice->is_published]);

        return back()->with('success', "Notice status changed to " . ($notice->is_published ? 'Published' : 'Draft') . ".");
    }

    public function destroyNotice(CmsNotice $notice): RedirectResponse
    {
        Gate::authorize('website.manage');

        AuditLog::log('cms_notice_deleted', 'website', $notice, $notice->toArray(), null);
        $notice->delete();

        return back()->with('success', 'Notice deleted successfully.');
    }
}
