-- Homepage section titles, eyebrows and two bullet lists that used to be hard-coded in
-- app/Views/web/home.php. Defaults are the previous wording, so the public site is unchanged.
INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`) VALUES
    ('homepage', 'section_welcome_eyebrow',      'Welcome',                       'text', 'Welcome - small label',        'Small label above the About / Welcome heading.', 1, 100),
    ('homepage', 'section_features_eyebrow',     'Why Choose Us',                 'text', 'Why Choose Us - small label',  'Small label above the section heading.', 1, 101),
    ('homepage', 'section_features_heading',     'What Sets Our Campus Apart',    'text', 'Why Choose Us - heading',      NULL, 1, 102),
    ('homepage', 'section_programs_eyebrow',     'Academic Programs',             'text', 'Programs - small label',       NULL, 1, 103),
    ('homepage', 'section_programs_heading',     'A Program for Every Stage',     'text', 'Programs - heading',           NULL, 1, 104),
    ('homepage', 'section_faculty_eyebrow',      'Our Faculty',                   'text', 'Faculty - small label',        'The faculty heading itself is edited under the "Our Faculty" tab.', 1, 105),
    ('homepage', 'section_facilities_eyebrow',   'Facilities',                    'text', 'Facilities - small label',     NULL, 1, 106),
    ('homepage', 'section_facilities_heading',   'Everything Our Campus Offers',  'text', 'Facilities - heading',         NULL, 1, 107),
    ('homepage', 'section_campus_eyebrow',       'Campus Life',                   'text', 'Campus Life - small label',    'The Campus Life heading itself is edited under the "Vibrant Campus" tab.', 1, 108),
    ('homepage', 'section_testimonials_eyebrow', 'In Their Words',                'text', 'Testimonials - small label',   NULL, 1, 109),
    ('homepage', 'section_testimonials_heading', 'What Our Students Say',         'text', 'Testimonials - heading',       NULL, 1, 110),
    ('homepage', 'section_news_eyebrow',         'Latest News',                   'text', 'Latest News - small label',    NULL, 1, 111),
    ('homepage', 'section_news_heading',         'What''s Happening on Campus',   'text', 'Latest News - heading',        NULL, 1, 112),
    ('homepage', 'campus_button_text',           'Explore Student Life',          'text', 'Button text',                  'Text of the button under the highlights (links to the Gallery).', 1, 113),
    ('homepage', 'cta_badges', 'Merit-Based Admissions\nEasy Application Process\nAffordable Fee Structure\nScholarships Available', 'textarea', 'Highlight points', 'One point per line, shown as ticks under the heading.', 1, 114),
    ('homepage', 'campus_highlights', 'Annual Events\nSeminars & Workshops\nSports Activities\nEducational Tours', 'textarea', 'Highlights', 'One item per line (up to 6). Icons are assigned automatically.', 1, 115)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);