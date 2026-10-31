=== Agent Ready – llms.txt, Agent Sitemap & Plain-Text Pages for AI Agents ===
Contributors: demosra
Tags: llms.txt, sitemap, ai, ai agents, seo
Requires at least: 5.5
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make your WordPress site agent-ready. One install gives it an llms.txt, a plain-text index, and a clean text copy of every post.

== Description ==

Sitemaps made your site readable to search engines. Agent Ready makes it readable to AI agents.

Agents read websites, but HTML is built for people. Agent Ready publishes everything agents need to read your site, in one plugin. Plug and play. No settings. Nothing sent anywhere.

= What agents get =

* `/llms.txt` says what your site is and where to read more.
* `/agent-sitemap.mtsv` lists every public post, page, term and author as plain text.
* `/agent-sitemap-post-42.mtsv` holds the clean text of post 42.
* Each post's head links to its text copy.

= How it works =

Install it and activate it. Agent Ready publishes what WordPress's own sitemap treats as public, and keeps the agent sitemap up to date when your content changes.

= Reading MTSV =

The files are MTSV: tab-separated values with sheets. Any text editor opens them. To convert them, use [mtsv](https://github.com/demos-ra/mtsv). To let an AI agent read them, use the [text-spreadsheet](https://github.com/demos-ra/text-spreadsheet) MCP server.

== Frequently Asked Questions ==

= My site already has an llms.txt. =

Agent Ready offers to use its own and keeps yours as llms.txt.bak, restored when you deactivate the plugin. If an SEO plugin writes your llms.txt, turn that feature off in the SEO plugin first, or it will write the file back.

= What about password-protected posts? =

As in WordPress's own sitemap, they are listed with their title and URL. Their text copy holds only what WordPress shows a visitor without the password.

= Are posts marked noindex left out? =

No. Agent Ready follows WordPress's own sitemap, which does not read an SEO plugin's noindex setting.

= How do I turn it off for agents? =

Turning on "Discourage search engines" in Settings, Reading stops everything, and every URL answers 404. Deactivating the plugin also stops it.

== Changelog ==

= 1.0.0 =
* First release.
