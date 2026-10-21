<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\MenuItemRepository;
use App\Repositories\MenuRepository;
use App\Repositories\PageRepository;

final class MenuController extends Controller
{
    private const LOCATIONS = [
        'header'      => 'Header Navigation',
        'footer'      => 'Footer Links',
        'quick_links' => 'Quick Links',
    ];

    /** Built-in routes offered in the "Link to" picker — kept in sync with routes/web.php's public GET routes. */
    private const BUILT_IN_ROUTES = [
        '/'                => 'Home',
        '/about'           => 'About',
        '/leadership'      => 'Leadership',
        '/academics'       => 'Academics',
        '/departments'     => 'Departments',
        '/admissions'      => 'Admissions',
        '/admission-form'  => 'Admission Form',
        '/gallery'         => 'Gallery',
        '/news'            => 'News',
        '/blog'            => 'Blog',
        '/events'          => 'Events',
        '/faculty'         => 'Faculty',
        '/downloads'       => 'Downloads',
        '/contact'         => 'Contact',
    ];

    public function index(Request $request): Response
    {
        return $this->redirectRoute('admin.menus.location', ['location' => 'header']);
    }

    /** Structure list + inline Add/Edit form, both on one page (?edit={id} loads an item into the form). */
    public function show(Request $request): Response
    {
        $location = (string) $request->parameter('location');
        $menu = $this->resolveMenu($location);
        $itemRepo = new MenuItemRepository();

        $editId = (int) $request->query('edit', 0);
        $editingItem = $editId > 0 ? $itemRepo->find($editId) : null;
        if ($editingItem !== null && (int) $editingItem['menu_id'] !== (int) $menu['id']) {
            $editingItem = null;
        }

        return $this->view('admin.menus.index', [
            'title'         => 'Menus',
            'location'      => $location,
            'locations'     => self::LOCATIONS,
            'menu'          => $menu,
            'items'         => $itemRepo->tree((int) $menu['id']),
            'editingItem'   => $editingItem,
            'builtInRoutes' => self::BUILT_IN_ROUTES,
            'customPages'   => (new PageRepository())->publishedCustom(),
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        $location = (string) $request->parameter('location');
        $menu = $this->resolveMenu($location);

        return $this->save($request, $menu, null);
    }

    public function update(Request $request): Response
    {
        $location = (string) $request->parameter('location');
        $menu = $this->resolveMenu($location);
        $id = (int) $request->parameter('id');

        $repo = new MenuItemRepository();
        $item = $repo->find($id);
        if ($item === null || (int) $item['menu_id'] !== (int) $menu['id']) {
            throw new HttpException(404);
        }

        return $this->save($request, $menu, $id);
    }

    public function destroy(Request $request): Response
    {
        $location = (string) $request->parameter('location');
        $menu = $this->resolveMenu($location);
        $id = (int) $request->parameter('id');

        $repo = new MenuItemRepository();
        $item = $repo->find($id);
        if ($item === null || (int) $item['menu_id'] !== (int) $menu['id']) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('menu.item_deleted', 'menus', 'menu_items', $id, "Deleted menu item \"{$item['title']}\" from {$menu['name']}");
        Session::flash('status', 'Menu item deleted.');

        return $this->redirectRoute('admin.menus.location', ['location' => $location]);
    }

    /** Bulk save from the Structure list's drag/indent/outdent controls. */
    public function reorder(Request $request): Response
    {
        $location = (string) $request->parameter('location');
        $menu = $this->resolveMenu($location);

        $ids = (array) $request->input('order', []);
        $depths = (array) $request->input('depth', []);
        $rows = [];
        foreach ($ids as $i => $id) {
            $rows[] = ['id' => (int) $id, 'depth' => (int) ($depths[$i] ?? 0)];
        }

        (new MenuItemRepository())->reorder((int) $menu['id'], $rows);
        Audit::log('menu.structure_updated', 'menus', 'menus', (int) $menu['id'], "Reordered {$menu['name']}");
        Session::flash('status', 'Menu structure updated.');

        return $this->redirectRoute('admin.menus.location', ['location' => $location]);
    }

    private function resolveMenu(string $location): array
    {
        if (!array_key_exists($location, self::LOCATIONS)) {
            throw new HttpException(404);
        }

        $menu = (new MenuRepository())->findByLocation($location);
        if ($menu === null) {
            throw new HttpException(404);
        }

        return $menu;
    }

    private function save(Request $request, array $menu, ?int $id): Response
    {
        $location = $menu['location'];

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100',
        ]);

        $errors = $validator->errors();

        $target = (string) $request->input('target', '_self');
        if (!in_array($target, ['_self', '_blank'], true)) {
            $target = '_self';
        }

        // link_target is either a resolved path (from the Built-in/Your-pages
        // groups), "none" (a dropdown heading with no link of its own), or
        // "custom" (free-form URL in the custom_url field).
        $linkTarget = (string) $request->input('link_target', '');
        $url = match (true) {
            $linkTarget === 'custom' => trim((string) $request->input('custom_url', '')),
            $linkTarget === 'none'   => '',
            $linkTarget !== ''       => $linkTarget,
            default                  => trim((string) $request->input('url', '')),
        };

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());
            $back = $id === null ? '' : ('?edit=' . $id);

            return $this->redirect(route('admin.menus.location', ['location' => $location]) . $back);
        }

        $repo = new MenuItemRepository();

        // Parent/child is set exclusively via the Structure list's drag and
        // indent/outdent controls (reorder(), above) — the item form itself
        // has no "parent" field, so a new item always starts top-level, and
        // editing one leaves its current position in the tree untouched.
        $existing = $id !== null ? $repo->find($id) : null;
        $parentId = $existing !== null ? ((int) ($existing['parent_id'] ?? 0) ?: null) : null;

        // New items append to the end of the list rather than defaulting to
        // 0, which would otherwise jump them to the very top.
        $sortOrder = $existing !== null
            ? (int) $existing['sort_order']
            : 1 + array_reduce($repo->forMenu((int) $menu['id']), static fn (int $max, array $row) => max($max, (int) $row['sort_order']), -1);

        $data = [
            'menu_id'     => (int) $menu['id'],
            'parent_id'   => $parentId,
            'title'       => (string) $request->input('title'),
            'url'         => $url,
            'icon'        => (string) $request->input('icon', ''),
            'target'      => $target,
            'description' => (string) $request->input('description', ''),
            'sort_order'  => $sortOrder,
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('menu.item_created', 'menus', 'menu_items', $newId, "Added \"{$data['title']}\" to {$menu['name']}");
            Session::flash('status', 'Menu item added.');
        } else {
            $repo->update($id, $data);
            Audit::log('menu.item_updated', 'menus', 'menu_items', $id, "Updated \"{$data['title']}\" in {$menu['name']}");
            Session::flash('status', 'Menu item updated.');
        }

        return $this->redirectRoute('admin.menus.location', ['location' => $location]);
    }
}
