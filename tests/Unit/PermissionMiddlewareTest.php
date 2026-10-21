<?php
declare(strict_types=1);

define('ROOT_PATH', 'c:/xampp/htdocs/IIUI');
define('APP_PATH', ROOT_PATH . '/app');
require APP_PATH . '/Core/Autoloader.php';
App\Core\Autoloader::register();
App\Core\Autoloader::addNamespace('App', APP_PATH);

use App\Middleware\PermissionMiddleware;

// [label, module, method, path, export, expected any-of slugs]
$cases = [
    ['list page needs view',                 'cms_posts', 'GET',  '/admin/news',                 '',    ['cms_posts.view']],
    ['HEAD counts as a read',                'cms_posts', 'HEAD', '/admin/news',                 '',    ['cms_posts.view']],
    ['create form needs create',             'cms_posts', 'GET',  '/admin/news/create',          '',    ['cms_posts.create']],
    ['edit form needs edit',                 'cms_posts', 'GET',  '/admin/news/12/edit',         '',    ['cms_posts.edit']],
    ['csv export needs export, not view',    'messages',  'GET',  '/admin/contact-messages',     'csv', ['messages.export']],
    ['delete needs delete',                  'cms_posts', 'POST', '/admin/news/12/delete',       '',    ['cms_posts.delete']],
    ['store or update needs create or edit', 'cms_posts', 'POST', '/admin/news/12',              '',    ['cms_posts.create', 'cms_posts.edit']],
    ['status change is an edit',             'messages',  'POST', '/admin/contact-messages/3/status', '', ['messages.create', 'messages.edit']],
    ['admission convert has its own grant',  'admissions', 'POST', '/admin/admissions/4/convert', '',  ['admissions.convert', 'admissions.edit']],
    ['trailing slash is ignored',            'users',     'POST', '/admin/users/9/delete/',      '',    ['users.delete']],
    ['a non-csv export value is just a view', 'users',    'GET',  '/admin/users',                'xml', ['users.view']],
];

$pass = 0;
$fail = 0;
foreach ($cases as [$label, $module, $method, $path, $export, $expected]) {
    $got = PermissionMiddleware::requiredFor($module, $method, $path, $export);
    if ($got === $expected) {
        echo "PASS: {$label}\n";
        ++$pass;
    } else {
        echo "FAIL: {$label}\n   expected " . implode(',', $expected) . "\n   got      " . implode(',', $got) . "\n";
        ++$fail;
    }
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
