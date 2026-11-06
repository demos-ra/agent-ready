=== MTSV Agent Site – Your Whole Site as an AI Agent Database ===
Contributors: demosra
Tags: ai agents, llms.txt, sitemap, llm, seo
Requires at least: 5.7
Tested up to: 7.1
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lets AI agents read your whole public site as one database: every post, page, category and author, linked and listed in llms.txt. No setup.

== Description ==

MTSV Agent Site publishes your whole site for AI agents: every public post, page, category, tag and author, as one linked database. Agents get all of it in one place, already connected, so they can understand your site as a whole instead of piecing it together page by page.

Where other plugins give agents a list of links or your pages as text, MTSV Agent Site also gives them how everything connects.

= What agents get =

* One index of everything: `/agent-site/index.mtsv`.
* Your posts, pages, categories, tags and authors, linked to each other.
* Each post's text as visitors see it, paragraph by paragraph.

= How agents find it =

* `/llms.txt`, the file AI agents look for, points them to the index and tells them how to read it.
* Each post's page links to its own text.

= Why MTSV =

MTSV is TSV with sheets: plain text holding nothing but your content and the separators between it. With no markup, quotes or repeated labels, agents read more of your site in fewer tokens. Any text editor opens it. To convert it, use [mtsv](https://github.com/demos-ra/mtsv). To let an AI app read it, use the [text-spreadsheet](https://github.com/demos-ra/text-spreadsheet) MCP server.

= Safe and automatic =

* Publishes only what WordPress already shows the public, by its own sitemap rules.
* Always current: built when an agent asks, with nothing stored.
* No settings, and nothing sent anywhere. Activate it and it works.

== Frequently Asked Questions ==

= Is it really the whole site? =

It is all the public text and structure. It leaves out drafts, private posts, media files, comments, custom fields, and your theme's layout, menus and widgets.

= My site already has an llms.txt. =

Yours is served instead. The agent site's own is always at `/agent-site/llms.txt`, and agents still find each post's text from its page. To add the agent site to your llms.txt, link to `/agent-site/llms.txt`.

= My site is in a subfolder. =

Everything works, llms.txt included: the llms.txt standard allows it in a subfolder.

= What about password-protected posts? =

Their title and URL are listed, as in WordPress's sitemap. Their text is only what visitors see without the password.

= Are posts marked noindex left out? =

No. Like WordPress's own sitemap, it does not read SEO plugins' noindex setting. Filters that change WordPress's sitemap do apply.

= Does it work with page builders? =

It reads what WordPress outputs as each post's content, so a builder that stores its content elsewhere may give little text.

= What about my host or caching plugin? =

A host that blocks `.mtsv` files stops the database, and a caching plugin may serve an older copy until it refreshes.

= How do I turn it off for agents? =

Turn on "Discourage search engines" in Settings, Reading, or deactivate the plugin. Developers can use the `mtsv_agent_site_enabled` filter.

== Changelog ==

= 1.1.0 =
* Renamed: Your Whole Site as an AI Agent Database.
* The agent site moves under `/agent-site/`, with its own llms.txt at `/agent-site/llms.txt`.
* llms.txt works when WordPress is in a subfolder.
* llms.txt tells agents where to start and how to read the database.
* The index lists only files with rows.
* A latest-posts front page links to the index, and every file points back to llms.txt.

= 1.0.0 =
* First release.
