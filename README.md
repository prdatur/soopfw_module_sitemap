# Soopfw module: Sitemap

Sitemap is a [SoopFw](http://soopfw.org) extension.
It will generate a sitemap for you which you an use for example within google.
It generates the sitemap based up on the standard of [http://www.sitemaps.org/protocol.html](http://www.sitemaps.org/protocol.html).

Modules which want to provide sitemap entries need to implement two hooks.

The first is "**sitemap_section**" which returns all sections for this module.
The next one is "**sitemap_get_entries**" which returns all entries based up on the section.
