<?php

namespace Shopmade\Shop\View;

use App\Core\Front\FrontSite;
use Illuminate\View\View;
use Throwable;

/**
 * Gives the shop layout what front.layout needs: the public site's tokens, header and footer data from its home
 * page, or a bare stand-in when no front is mounted. Shop pages are never indexed.
 */
class ShopLayoutComposer
{
    public function __construct(private FrontSite $site) {}

    public function compose(View $view): void
    {
        $view->with([...$this->siteData(), 'description' => '', 'noindex' => true, 'layout' => 'shop', 'alternates' => [], 'jsonLd' => [], 'canonical' => request()->url()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function siteData(): array
    {
        try {
            $home = $this->site->exists() ? $this->site->page('home', $this->site->defaultLanguage()) : null;

            if ($home !== null) {
                return [...$this->site->viewData($home), 'navigation' => $this->site->navigation($home['lang'], '')];
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        $name = (string) config('app.org_name', config('app.name'));

        return [
            'lang' => 'en',
            'title' => $name,
            'description' => '',
            'tokens' => '',
            't' => ['skip_to_content' => 'Skip to content', 'menu' => 'Menu', 'languages' => 'Languages', 'call' => 'Call', 'service_area' => 'Service area', 'services' => 'Services', 'hours' => 'Hours', 'follow' => 'Follow'],
            'name' => $name,
            'legalName' => $name,
            'homeUrl' => url('/'),
            'navigation' => [],
            'languageLinks' => [],
            'phone' => null,
            'phoneHref' => null,
            'email' => null,
            'address' => null,
            'cities' => [],
            'services' => [],
            'hours' => [],
            'social' => [],
            'year' => now()->year,
        ];
    }
}
