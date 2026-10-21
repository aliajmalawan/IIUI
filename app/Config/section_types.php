<?php

declare(strict_types=1);

/**
 * Section-type registry for the Pages section builder (admin "Pages" list).
 * A page's hero (heading/subheading/banner) lives directly on `pages`
 * (0033-0034); everything below that is a stack of `page_sections` rows,
 * each an instance of one of the types below. This array is the single
 * source of truth for both the admin editor form (Admin\PageController +
 * app/Views/admin/pages/section_edit.php, driven by App\Core\SectionForm)
 * and the public renderer (App\Core\SectionRenderer, which looks up
 * Views/web/sections/{type}.php for each section).
 *
 * Deliberately NOT included: anything that's already a real, working,
 * database-backed feature elsewhere (the Admissions application form, the
 * Contact form + map, the Academic Programs grid) — turning those into
 * generic content would mean rebuilding real functionality as decoration.
 * These types cover free-form marketing/informational content only.
 *
 * Supported field types: text, textarea, richtext, media, select (needs
 * 'options'), repeater (needs 'fields' of its own, one level deep).
 */
return [
    'rich_text' => [
        'label' => 'Rich Text',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'content' => ['type' => 'richtext', 'label' => 'Content'],
        ],
    ],

    'image_text' => [
        'label' => 'Image + Text',
        'fields' => [
            'heading'        => ['type' => 'text', 'label' => 'Heading (optional)'],
            'text'           => ['type' => 'textarea', 'label' => 'Text'],
            'image'          => ['type' => 'media', 'label' => 'Image', 'recommended_size' => '1600×800px (2:1)'],
            'image_position' => ['type' => 'select', 'label' => 'Image Position', 'options' => ['left' => 'Left', 'right' => 'Right']],
        ],
    ],

    'icon_grid' => [
        'label' => 'Icon Grid (features / highlights)',
        'fields' => [
            'heading'    => ['type' => 'text', 'label' => 'Heading'],
            'subheading' => ['type' => 'textarea', 'label' => 'Subheading (optional)'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Items',
                'fields' => [
                    'icon'        => [
                        'type' => 'select', 'label' => 'Icon',
                        'options' => [
                            'award' => 'Award', 'backpack2' => 'Backpack', 'bank' => 'Bank',
                            'book-half' => 'Book', 'building' => 'Building', 'calendar-event' => 'Calendar',
                            'check-circle' => 'Check', 'clock' => 'Clock', 'easel' => 'Easel',
                            'envelope' => 'Envelope', 'flask' => 'Flask (Science)', 'geo-alt-fill' => 'Location',
                            'graph-up-arrow' => 'Growth', 'journal-bookmark' => 'Journal', 'lightning-charge' => 'Lightning',
                            'mortarboard' => 'Graduation Cap', 'people' => 'People', 'person-badge' => 'Person Badge',
                            'shield-check' => 'Shield', 'star' => 'Star', 'telephone' => 'Phone',
                            'trophy' => 'Trophy', 'gear' => 'Gear', 'receipt' => 'Receipt',
                        ],
                    ],
                    'title'       => ['type' => 'text', 'label' => 'Title'],
                    'description' => ['type' => 'textarea', 'label' => 'Description'],
                ],
            ],
        ],
    ],

    'cta' => [
        'label' => 'Call to Action Banner',
        'fields' => [
            'heading'     => ['type' => 'text', 'label' => 'Heading'],
            'text'        => ['type' => 'textarea', 'label' => 'Text (optional)'],
            'button_text' => ['type' => 'text', 'label' => 'Button Text'],
            'button_url'  => ['type' => 'text', 'label' => 'Button URL'],
        ],
    ],

    'stats' => [
        'label' => 'Stats Row',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Stats',
                'fields' => [
                    'value' => ['type' => 'text', 'label' => 'Value (e.g. "1,200+")'],
                    'label' => ['type' => 'text', 'label' => 'Label (e.g. "Students")'],
                ],
            ],
        ],
    ],

    'faq' => [
        'label' => 'FAQ',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Questions',
                'fields' => [
                    'question' => ['type' => 'text', 'label' => 'Question'],
                    'answer'   => ['type' => 'textarea', 'label' => 'Answer'],
                ],
            ],
        ],
    ],

    'gallery' => [
        'label' => 'Image Gallery',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'images' => [
                'type' => 'repeater',
                'label' => 'Images',
                'fields' => [
                    'image'   => ['type' => 'media', 'label' => 'Image', 'recommended_size' => '1200×900px'],
                    'caption' => ['type' => 'text', 'label' => 'Caption (optional)'],
                ],
            ],
        ],
    ],

    'testimonials' => [
        'label' => 'Testimonials',
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading (optional)'],
            'items' => [
                'type' => 'repeater',
                'label' => 'Quotes',
                'fields' => [
                    'quote' => ['type' => 'textarea', 'label' => 'Quote'],
                    'name'  => ['type' => 'text', 'label' => 'Name'],
                    'role'  => ['type' => 'text', 'label' => 'Role (optional)'],
                ],
            ],
        ],
    ],
];
