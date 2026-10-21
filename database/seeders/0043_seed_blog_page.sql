-- =============================================================================
-- 0043_seed_blog_page.sql · Companion to the new /blog public page and
-- Admin -> Blog CRUD (Controllers/Web/BlogController,
-- Controllers/Admin/BlogController) — both built on the existing `posts`
-- table via the post_type='blog' discriminator added in
-- 0042_add_post_type_for_blog.sql, not a new table.
--
-- Seeds 3 realistic STARTER/PLACEHOLDER posts (same convention as every
-- other seeded page this project) so the page isn't empty on first load,
-- and adds "Blog" to the header nav's "Media" dropdown (menu_items
-- id=33), alongside Gallery and News & Events.
-- =============================================================================
USE `iiui_mardan`;

INSERT INTO `posts`
  (`post_type`, `category_id`, `title`, `slug`, `excerpt`, `content`, `status`, `is_featured`, `is_pinned`, `published_at`, `author_id`)
VALUES
  (
    'blog', NULL,
    '5 Tips for a Smooth Start to the New Academic Year',
    '5-tips-smooth-start-new-academic-year',
    'A few simple habits that help students settle into a new class with confidence, from our own teaching staff.',
    '<p>Every September, the same question comes up in parent-teacher meetings: how can we help our children settle into a new class faster? Over the years, our teaching staff has noticed a handful of habits that consistently make the first few weeks easier.</p><h2>1. Set a fixed homework time</h2><p>Children do best when homework happens at roughly the same time each day, even if the workload is light. It removes the daily negotiation over "when."</p><h2>2. Pack the bag the night before</h2><p>A calm morning starts the night before. Five minutes of checking the timetable and packing books avoids a rushed, stressful start to the day.</p><h2>3. Read something outside the textbook</h2><p>Even fifteen minutes of reading for pleasure keeps vocabulary and focus sharp between terms.</p><h2>4. Talk about school, not just grades</h2><p>Asking "what was interesting today?" instead of "how did you do?" opens up a very different, more useful conversation.</p><h2>5. Keep communication with teachers open</h2><p>A short message to the class teacher early in the term, about anything from allergies to attention span, helps us support your child better from day one.</p><p>Wishing every student at IIUI Schools & College a confident, successful year ahead.</p>',
    'published', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), 1
  ),
  (
    'blog', NULL,
    'Why We Celebrate Science Fair Every Year',
    'why-we-celebrate-science-fair-every-year',
    'Behind every science fair display is a student who learned to ask a real question and test it. Here is why that matters.',
    '<p>Once a year, our courtyard fills with baking-soda volcanoes, hand-built circuits, and posters covered in careful handwriting. It is easy to see the Science Fair as just a fun day off the usual timetable, but it does something the regular syllabus rarely gets time for: it lets a student own a question from start to finish.</p><p>In a normal class, a student is usually solving a problem someone else defined. At the Science Fair, they pick the question themselves, often after several false starts, and follow it all the way through to a result they can explain in their own words.</p><p>That process, choosing a question, testing it, being wrong, adjusting, and presenting what was actually found, is closer to how real scientific and engineering work happens than any single textbook chapter can teach.</p><p>We will keep making room for it every year.</p>',
    'published', 0, 0, DATE_SUB(NOW(), INTERVAL 10 DAY), 1
  ),
  (
    'blog', NULL,
    'A Day in the Life of an IIUI Mardan Student',
    'day-in-the-life-iiui-mardan-student',
    'From the morning assembly to the last bell, a look at what a typical school day actually looks like on our campus.',
    '<p>Prospective parents often ask what a normal day actually looks like here, beyond the timetable on the admissions page. So here is a simple walk-through, wing by wing.</p><h2>Morning Assembly</h2><p>The day begins with a short assembly, recitation, and a few announcements, a small ritual that sets a calm tone before the first period.</p><h2>Classes and Breaks</h2><p>Periods run through the morning with a short break for younger students to eat and play, and a longer lunch break for Middle School and above.</p><h2>Afternoon Wind-Down</h2><p>The last periods of the day tend to be lighter, art, physical education, or library time, before the final bell.</p><p>It is, most days, a fairly ordinary school day, and that consistency is exactly the point.</p>',
    'published', 0, 0, DATE_SUB(NOW(), INTERVAL 18 DAY), 1
  );

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `type`, `url`, `target`, `sort_order`, `is_active`)
VALUES
  (1, 33, 'Blog', 'custom', '/blog', '_self', 21, 1);

INSERT INTO `migrations` (`filename`, `batch`) VALUES ('0043_seed_blog_page.sql', 1)
ON DUPLICATE KEY UPDATE `applied_at` = `applied_at`;
